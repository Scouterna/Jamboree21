function executeMassEdit() {

	function getDataFromPath( data, path  ) {
		path = path.split( '.' );
		for ( var x = 0; x < path.length; x++ ) {
			var dataKeys = [];
			for ( var k in data ) {
				dataKeys.push( k );
			}
			if ( $.inArray( path[x], dataKeys ) == -1 ) {
				return false;
			}
			data = data[path[x]];
		}
		return data;
	}

	function makeAPICall( data, callback, c ) {
		var waitForCallback = false;

		if ( c !== undefined ) {
			data.params[data.continueKey] = c;
		}

		$.ajax( {
			url: mw.util.wikiScript( 'api' ),
			data: data.params,
			dataType: 'json',
			type: 'POST',
			success: function( response ) {
				var result = getDataFromPath( response, data.resultPath );
				if ( result ) {
					if ( data.continuePath ) {
						var continueData = getDataFromPath( response, data.continuePath );
						if ( continueData ) {
							waitForCallback = true;
							makeAPICall( data, function( r ) {
								waitForCallback = false;
								result = result.concat( r );
							}, continueData );
						}
						var timerId = setInterval( function() {
							if ( !waitForCallback ) {
								callback( result );
								clearInterval( timerId );
							}
						}, 10 );
					} else {
						callback( result );
					}
				} else if ( response && response.error ) {
					alert( mw.message( 'masseditregex-js-mwapi-api-error',
						response.error.code, response.error.info ).text() );
				} else {
					alert( mw.message( 'masseditregex-js-mwapi-general-error' ).text() );
				}
			},
			error: function( xhr ) {
				alert( mw.message( 'masseditregex-js-mwapi-unknown-error' ).text() );
			}
		} );
	}

	function getCategoryPages( page, callback ) {
		var data = {
			resultPath: 'query.categorymembers',
			continuePath: 'query-continue.categorymembers.cmcontinue',
			continueKey: 'cmcontinue',
			params:{
				format: 'json',
				action: 'query',
				list: 'categorymembers',
				cmtitle: 'Category:' + page,
				cmlimit: 100,
				rawcontinue: 1
			}
		};
		makeAPICall( data, callback );
	}

	function getBackLinkPages( page, callback ) {
		var data = {
			resultPath: 'query.backlinks',
			continuePath: 'query-continue.backlinks.blcontinue',
			continueKey: 'cmcontinue',
			params:{
				format: 'json',
				action: 'query',
				list: 'backlinks',
				bltitle: page,
				bllimit: 100,
				rawcontinue: 1
			}
		};
		makeAPICall( data, callback );
	}

	function getAllPrefixPages( page, callback ) {
		var data = {
			resultPath: 'query.namespaces',
			params:{
				format: 'json',
				action: 'query',
				meta: 'siteinfo',
				siprop: 'namespaces'
			}
		};
		makeAPICall( data, function( namespaces ) {
			var data;
			var nsId;
			var result = {
				pages: []
			};
			var size = 0;
			var count = 0;
			var key;

			// Count number of elements
			for ( key in namespaces ) {
				if ( namespaces[key].id > 0 ) {
					size++;
				}
			}

			// Iterate through the namespaces
			for ( key in namespaces ) {
				nsId = namespaces[key].id;
				if ( nsId >= 0 ) {
					data = {
						resultPath: 'query.allpages',
						continuePath: 'query-continue.allpages.apcontinue',
						continueKey: 'apcontinue',
						params:{
							format: 'json',
							action: 'query',
							list: 'allpages',
							apnamespace: nsId,
							apprefix: page,
							aplimit: 100,
							rawcontinue: 1
						}
					};

					( function ( data, result, callback ) {
						makeAPICall(data, function (pages) {
							result.pages = result.pages.concat( pages );
							if ( callback !== null ) {
								callback( result.pages );
							}
						});
					}( data, result, count++ === size ? callback : null ) );

				}
			}
		} );
	}

	function getPages( pages, callback ) {
		var data = {
			resultPath: 'query.pages',
			params: {
				format: 'json',
				action: 'query',
				titles: pages.join( '|' ),
				rawcontinue: 1
			}
		};
		makeAPICall( data, function ( data ) {
			// Convert from object to array
			pages = [];
			for ( var key in data ) {
				pages.push( data[key] );
			}
			callback( pages );
		} );
	}

	function editPages( pages, search, replace, summary, cb ) {
		var rObj = { remaining: pages.length };
		if ( pages.length === 0 ) {
			cb( { error: 'No pages found!' } );
			return;
		}

		for ( var x = 0; x < pages.length; x++ ) {
			( function ( page, search, replace, cb, rObj ) {
				var pageId = page.pageid;

				if ( pageId === undefined ) {
					cb( { error: mw.message( 'masseditregex-js-pagenotexist', page.title ).text() } );
				} else {
					$.ajax({
						url: mw.util.wikiScript(),
						data: {
							action: 'ajax',
							rs: 'MassEditRegexAPI::edit',
							rsargs: [pageId, search, replace, summary],
						},
						dataType: 'json',
						type: 'POST'
					}).done( function ( response ) {
							rObj.remaining--;
							cb( page, response, rObj.remaining );
					});
				}
			}( pages[x], search, replace, cb, rObj ) );
		}
	}

	function doEdit( pages ) {
		var search = $( '#wpMatch' ).val();
		var replace = $( '#wpReplace' ).val();
		var summary = $( '#wpSummary' ).val();
		var content = $( '<div></div>' );
		var heading = $( '<h1></h1>' );
		content.append( heading );
		heading.text( mw.message( 'masseditregex-js-working', '?' ).text() );
		var list = $( '<ul></ul>' );
		content.append( list );

		content.dialog( {
			height: $(window).height() * 0.8,
			width: $(window).width() * 0.8,
			modal: true
		} );

		editPages( pages, search, replace, summary,
			function( page, response, remaining ) {
				var li = $('<li></li>');

				if ( page.error || response.error ) {
					li.text(page.title + ': ' + page.error ? page.error : response.error);
				} else {
					li.text( mw.message( 'masseditregex-num-changes', page.title,
						response.changes ).text() );
					heading.text( mw.message( 'masseditregex-js-working', remaining ).text() );
				}

				list.prepend(li);

				if ( remaining === 0 ) {
					li = $('<li></li>');
					li.text( mw.message( 'masseditregex-js-jobdone' ).text() );
					list.prepend(li);
					heading.text( mw.message( 'masseditregex-js-jobdone' ).text() );
				}
			}
		);
	}

	var pages = $( '#wpPageList' ).val().split( '\n' );
	var type = $( 'input[name="wpPageListType"]:checked' ).val();

	var x;
	switch ( type ) {
		case 'pagenames':
			getPages( pages, doEdit );
			break;
		case 'pagename-prefixes':
			for ( x = 0; x < pages.length; x++ ) {
				getAllPrefixPages( pages[x], doEdit );
			}
			break;
		case 'backlinks':
			for ( x = 0; x < pages.length; x++ ) {
				getBackLinkPages( pages[x], doEdit );
			}
			break;
		case 'categories':
			for ( x = 0; x < pages.length; x++ ) {
				getCategoryPages( pages[x], doEdit );
			}
			break;
	}
}

$( document ).ready( function () {
	$( '#wpSave' ).click( function () {
		if ( $( '#wpClientSide' ).is( ':checked' ) ) {
			executeMassEdit();
			return false;
		}
		return true;
	} );
} );
