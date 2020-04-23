/**
 * @author Niklas Laxström
 * @license MIT
 */

( function ( mw, $ ) {

	// Compute form data for search suggestions functionality.
	function computeResultRenderCache( context ) {
		var $form, baseHref, linkParams;

		// Compute common parameters for links' hrefs
		$form = context.config.$region.closest( 'form' );

		baseHref = $form.attr( 'action' );
		baseHref += baseHref.indexOf( '?' ) > -1 ? '&' : '?';

		linkParams = {};
		$.each( $form.serializeArray(), function ( idx, obj ) {
			linkParams[ obj.name ] = obj.value;
		} );

		return {
			textParam: context.data.$textbox.attr( 'name' ),
			linkParams: linkParams,
			baseHref: baseHref
		};
	}

	// The function used to render the suggestions.
	function customRenderFunction( text, context ) {
		var page, namespace,
			title = mw.Title.newFromText( text ),
			info = computeResultRenderCache( context );

		info.linkParams[ info.textParam ] = text;

		page = title.getMainText();
		namespace = $( '<span>' )
			.text( mw.config.get( 'wgFormattedNamespaces' )[ title.namespace ] )
			.addClass( 'mw-mnss-srcc' );

		// 'this' is the container <div>, jQueryfied
		this
			.append( page, namespace )
			.wrap(
				$( '<a>' )
					.attr( 'href', info.baseHref + $.param( info.linkParams ) )
					.addClass( 'mw-searchSuggest-link' )
			);
	}

	$( document ).ready( function () {
		var $searchInput = $( '#searchInput' );

		$searchInput.suggestions( {
			fetch: function ( query ) {
				var $el;

				if ( query.length !== 0 ) {
					$el = $( this );
					$el.data( 'request', ( new mw.Api() ).get( {
						action: 'opensearch',
						search: query,
						namespace: mw.config.get( 'wgContentNamespaces' ).join( '|' ),
						suggest: ''
					} ).done( function ( data ) {
						$el.suggestions( 'suggestions', data[1] );
					} ) );
				}
			},
			result: {
				render: customRenderFunction
			}
		} );
	} );

}( mediaWiki, jQuery ) );
