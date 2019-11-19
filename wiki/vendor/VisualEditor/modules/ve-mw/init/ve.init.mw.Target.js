/*!
 * VisualEditor MediaWiki Initialization Target class.
 *
 * @copyright 2011-2019 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Initialization MediaWiki target.
 *
 * @class
 * @extends ve.init.Target
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.init.mw.Target = function VeInitMwTarget( config ) {
	// Parent constructor
	ve.init.mw.Target.super.call( this, config );

	this.active = false;
	this.pageName = mw.config.get( 'wgRelevantPageName' );
	this.editToken = mw.user.tokens.get( 'editToken' );

	// Initialization
	this.$element.addClass( 've-init-mw-target' );
};

/* Inheritance */

OO.inheritClass( ve.init.mw.Target, ve.init.Target );

/* Events */

/**
 * @event surfaceReady
 */

/* Static Properties */

/**
 * Symbolic name for this target class.
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.init.mw.Target.static.name = null;

ve.init.mw.Target.static.toolbarGroups = [
	{
		name: 'history',
		include: [ 'undo', 'redo' ]
	},
	{
		name: 'format',
		type: 'menu',
		title: OO.ui.deferMsg( 'visualeditor-toolbar-format-tooltip' ),
		include: [ { group: 'format' } ],
		promote: [ 'paragraph' ],
		demote: [ 'preformatted', 'blockquote', 'heading1' ]
	},
	{
		name: 'style',
		type: 'list',
		icon: 'textStyle',
		title: OO.ui.deferMsg( 'visualeditor-toolbar-style-tooltip' ),
		include: [ { group: 'textStyle' }, 'language', 'clear' ],
		forceExpand: [ 'bold', 'italic', 'clear' ],
		promote: [ 'bold', 'italic' ],
		demote: [ 'strikethrough', 'code', 'underline', 'language', 'big', 'small', 'clear' ]
	},
	{
		name: 'link',
		include: [ 'link' ]
	},
	// Placeholder for reference tools (e.g. Cite and/or Citoid)
	{
		name: 'reference'
	},
	{
		name: 'structure',
		type: 'list',
		icon: 'listBullet',
		title: OO.ui.deferMsg( 'visualeditor-toolbar-structure' ),
		include: [ { group: 'structure' } ],
		demote: [ 'outdent', 'indent' ]
	},
	{
		name: 'insert',
		label: OO.ui.deferMsg( 'visualeditor-toolbar-insert' ),
		title: OO.ui.deferMsg( 'visualeditor-toolbar-insert' ),
		include: '*',
		forceExpand: [ 'media', 'transclusion', 'insertTable' ],
		promote: [ 'media', 'transclusion', 'insertTable' ]
	},
	{
		name: 'specialCharacter',
		include: [ 'specialCharacter' ]
	}
];

ve.init.mw.Target.static.importRules = {
	external: {
		blacklist: [
			// Annotations
			'link/mwExternal', 'textStyle/span', 'textStyle/font', 'textStyle/underline', 'meta/language', 'textStyle/datetime',
			// Nodes
			'article', 'section', 'div', 'alienInline', 'alienBlock', 'comment'
		],
		htmlBlacklist: {
			// Remove reference numbers copied from MW read mode (T150418)
			remove: [ 'sup.reference:not( [typeof] )' ],
			unwrap: [ 'fieldset', 'legend' ]
		},
		removeOriginalDomElements: true,
		nodeSanitization: true
	},
	all: null
};

/**
 * Type of integration. Used by ve.init.mw.trackSubscriber.js for event tracking.
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.init.mw.Target.static.integrationType = null;

/**
 * Type of platform. Used by ve.init.mw.trackSubscriber.js for event tracking.
 *
 * @static
 * @property {string}
 * @inheritable
 */
ve.init.mw.Target.static.platformType = null;

/* Static Methods */

/**
 * Fix the base URL from Parsoid if necessary.
 *
 * Absolutizes the base URL if it's relative, and sets a base URL based on wgArticlePath
 * if there was no base URL at all.
 *
 * @param {HTMLDocument} doc Parsoid document
 */
ve.init.mw.Target.static.fixBase = function ( doc ) {
	ve.fixBase( doc, document, ve.resolveUrl(
		// Don't replace $1 with the page name, because that'll break if
		// the page name contains a slash
		mw.config.get( 'wgArticlePath' ).replace( '$1', '' ),
		document
	) );
};

/**
 * @inheritdoc
 */
ve.init.mw.Target.static.createModelFromDom = function ( doc, mode, options ) {
	var conf = mw.config.get( 'wgVisualEditor' );

	options = ve.extendObject( {
		lang: conf.pageLanguageCode,
		dir: conf.pageLanguageDir
	}, options );

	// Parent method
	return ve.init.mw.Target.super.static.createModelFromDom.call( this, doc, mode, options );
};

// Deprecated alias
ve.init.mw.Target.prototype.createModelFromDom = function () {
	return this.constructor.static.createModelFromDom.apply( this.constructor.static, arguments );
};

/**
 * @inheritdoc
 * @param {number|string|null} section Section. Use null to unwrap all sections.
 * @param {boolean} [onlySection] Only return the requested section, otherwise returns the
 *  whole document with just the requested section still wrapped (visual mode only).
 */
ve.init.mw.Target.static.parseDocument = function ( documentString, mode, section, onlySection ) {
	var doc, sectionNode;
	if ( mode === 'source' ) {
		// Parent method
		doc = ve.init.mw.Target.super.static.parseDocument.call( this, documentString, mode );
	} else {
		// Parsoid documents are XHTML so we can use parseXhtml which fixed some IE issues.
		doc = ve.parseXhtml( documentString );
		if ( section !== undefined ) {
			if ( onlySection ) {
				sectionNode = doc.body.querySelector( '[data-mw-section-id="' + section + '"]' );
				doc.body.innerHTML = '';
				if ( sectionNode ) {
					doc.body.appendChild( sectionNode );
				}
			} else {
				// Strip Parsoid sections
				ve.unwrapParsoidSections( doc.body, section );
			}
		}
		// Strip legacy IDs, for example in section headings
		ve.stripParsoidFallbackIds( doc.body );
		// Fix relative or missing base URL if needed
		this.fixBase( doc );
	}

	return doc;
};

/* Methods */

/**
 * Handle both DOM and modules being loaded and ready.
 *
 * @param {HTMLDocument|string} doc HTML document or source text
 */
ve.init.mw.Target.prototype.documentReady = function ( doc ) {
	this.setupSurface( doc );
};

/**
 * Once surface is ready, initialize the UI
 *
 * @method
 * @fires surfaceReady
 */
ve.init.mw.Target.prototype.surfaceReady = function () {
	this.emit( 'surfaceReady' );
};

/**
 * Get HTML to send to Parsoid. This takes a document generated by the converter and
 * transplants the head tag from the old document into it, as well as the attributes on the
 * html and body tags.
 *
 * @param {HTMLDocument} newDoc Document generated by ve.dm.Converter. Will be modified.
 * @param {HTMLDocument} [oldDoc] Old document to copy attributes from.
 * @return {string} Full HTML document
 */
ve.init.mw.Target.prototype.getHtml = function ( newDoc, oldDoc ) {
	var i, len;

	function copyAttributes( from, to ) {
		var i, len;
		for ( i = 0, len = from.attributes.length; i < len; i++ ) {
			to.setAttribute( from.attributes[ i ].name, from.attributes[ i ].value );
		}
	}

	if ( oldDoc ) {
		// Copy the head from the old document
		for ( i = 0, len = oldDoc.head.childNodes.length; i < len; i++ ) {
			newDoc.head.appendChild( oldDoc.head.childNodes[ i ].cloneNode( true ) );
		}
		// Copy attributes from the old document for the html, head and body
		copyAttributes( oldDoc.documentElement, newDoc.documentElement );
		copyAttributes( oldDoc.head, newDoc.head );
		copyAttributes( oldDoc.body, newDoc.body );
	}

	// Filter out junk that may have been added by browser plugins
	$( newDoc )
		.find( [
			'script', // T54884, T65229, T96533, T103430
			'noscript', // T144891
			'object', // T65229
			'style:not( [ data-mw ] )', // T55252, but allow <style data-mw/> e.g. TemplateStyles T188143
			'embed', // T53521, T54791, T65121
			'a[href^="javascript:"]', // T200971
			'img[src^="data:"]', // T192392
			'div[id="myEventWatcherDiv"]', // T53423
			'div[id="sendToInstapaperResults"]', // T63776
			'div[id="kloutify"]', // T69006
			'div[id^="mittoHidden"]', // T70900
			'div.hon.certificateLink', // HON (T209619)
			'div.donut-container' // Web of Trust (T189148)
		].join( ',' ) )
		.remove();

	// data-mw-section-id is copied to headings by ve.unwrapParsoidSections
	// Remove these to avoid triggering selser.
	$( newDoc ).find( '[data-mw-section-id]:not( section )' ).removeAttr( 'data-mw-section-id' );

	// Add doctype manually
	return '<!doctype html>' + ve.serializeXhtml( newDoc );
};

/**
 * Track an event
 *
 * @param {string} name Event name
 */
ve.init.mw.Target.prototype.track = function () {};

/**
 * @inheritdoc
 */
ve.init.mw.Target.prototype.createTargetWidget = function ( config ) {
	if ( this.getSurface().getMode() === 'source' ) {
		// Reset to visual mode for target widgets
		return new ve.ui.MWTargetWidget( ve.extendObject( {
			commandRegistry: ve.ui.commandRegistry,
			sequenceRegistry: ve.ui.sequenceRegistry,
			dataTransferHandlerFactory: ve.ui.dataTransferHandlerFactory
		}, config ) );
	} else {
		return new ve.ui.MWTargetWidget( config );
	}
};

/**
 * @inheritdoc
 */
ve.init.mw.Target.prototype.createSurface = function ( dmDoc, config ) {
	var importRules;

	if ( config && config.mode === 'source' ) {
		importRules = ve.copy( this.constructor.static.importRules );
		importRules.all = importRules.all || {};
		// Preserve empty linebreaks on paste in source editor
		importRules.all.keepEmptyContentBranches = true;
		config = this.getSurfaceConfig( ve.extendObject( {}, config, {
			importRules: importRules
		} ) );
		return new ve.ui.MWWikitextSurface( dmDoc, config );
	}

	return new ve.ui.MWSurface( dmDoc, this.getSurfaceConfig( config ) );
};

/**
 * @inheritdoc
 */
ve.init.mw.Target.prototype.getSurfaceConfig = function ( config ) {
	// If we're not asking for a specific mode's config, use the default mode.
	config = ve.extendObject( { mode: this.defaultMode }, config );
	return ve.init.mw.Target.super.prototype.getSurfaceConfig.call( this, ve.extendObject( {
		// Provide the wikitext versions of the registries, if we're using source mode
		commandRegistry: config.mode === 'source' ? ve.ui.wikitextCommandRegistry : ve.ui.commandRegistry,
		sequenceRegistry: config.mode === 'source' ? ve.ui.wikitextSequenceRegistry : ve.ui.sequenceRegistry,
		dataTransferHandlerFactory: config.mode === 'source' ? ve.ui.wikitextDataTransferHandlerFactory : ve.ui.dataTransferHandlerFactory
	}, config ) );
};

/**
 * Switch to editing mode.
 *
 * @method
 * @param {HTMLDocument|string} doc HTML document or source text
 */
ve.init.mw.Target.prototype.setupSurface = function ( doc ) {
	var target = this;
	setTimeout( function () {
		// Build model
		var dmDoc;

		target.track( 'trace.convertModelFromDom.enter' );
		dmDoc = target.constructor.static.createModelFromDom( doc, target.getDefaultMode() );
		target.track( 'trace.convertModelFromDom.exit' );

		// Build DM tree now (otherwise it gets lazily built when building the CE tree)
		target.track( 'trace.buildModelTree.enter' );
		dmDoc.buildNodeTree();
		target.track( 'trace.buildModelTree.exit' );

		setTimeout( function () {
			target.addSurface( dmDoc );
		} );
	} );
};

/**
 * @inheritdoc
 */
ve.init.mw.Target.prototype.addSurface = function () {
	var surface,
		target = this;

	// Clear dummy surfaces
	// TODO: Move to DesktopArticleTarget
	this.clearSurfaces();

	// Create ui.Surface (also creates ce.Surface and dm.Surface and builds CE tree)
	this.track( 'trace.createSurface.enter' );
	// Parent method
	surface = ve.init.mw.Target.super.prototype.addSurface.apply( this, arguments );
	// Add classes specific to surfaces attached directly to the target,
	// as opposed to TargetWidget surfaces
	surface.$element.addClass( 've-init-mw-target-surface' );
	this.track( 'trace.createSurface.exit' );

	this.setSurface( surface );

	setTimeout( function () {
		// Initialize surface
		target.track( 'trace.initializeSurface.enter' );

		target.active = true;
		// Now that the surface is attached to the document and ready,
		// let it initialize itself
		surface.initialize();

		target.track( 'trace.initializeSurface.exit' );
		target.surfaceReady();
	} );

	return surface;
};

/**
 * @inheritdoc
 */
ve.init.mw.Target.prototype.setSurface = function ( surface ) {
	if ( !surface.$element.parent().length ) {
		this.$element.append( surface.$element );
	}

	// Parent method
	ve.init.mw.Target.super.prototype.setSurface.apply( this, arguments );
};

/**
 * Refresh our stored edit/csrf token
 *
 * This should be called in response to a badtoken error, to resolve whether the
 * token was expired / the user changed. If the user did change, this updates
 * the current user.
 *
 * @param {ve.dm.Document} [doc] Document to associate with the API request
 * @return {jQuery.Promise} Promise resolved with whether we switched users
 */
ve.init.mw.Target.prototype.refreshEditToken = function ( doc ) {
	var api = this.getContentApi( doc ),
		deferred = $.Deferred(),
		target = this;
	api.get( {
		action: 'query',
		meta: 'tokens|userinfo',
		type: 'csrf'
	} )
		.done( function ( data ) {
			var
				userInfo = data.query && data.query.userinfo,
				editToken = data.query && data.query.tokens && data.query.tokens.csrftoken,
				isAnon = mw.user.isAnon();

			if ( userInfo && editToken ) {
				target.editToken = editToken;

				if (
					( isAnon && userInfo.anon !== undefined ) ||
						// Comparing id instead of name to protect against possible
						// normalisation and against case where the user got renamed.
						mw.config.get( 'wgUserId' ) === userInfo.id
				) {
					// New session is the same user still
					deferred.resolve( false );
				} else {
					// The now current session is a different user
					if ( userInfo.anon !== undefined ) {
						// New session is an anonymous user
						mw.config.set( {
							// wgUserId is unset for anonymous users, not set to null
							wgUserId: undefined,
							// wgUserName is explicitly set to null for anonymous users,
							// functions like mw.user.isAnon rely on this.
							wgUserName: null
						} );
					} else {
						// New session is a different user
						mw.config.set( { wgUserId: userInfo.id, wgUserName: userInfo.name } );
					}
					deferred.resolve( true );
				}
			} else {
				deferred.reject();
			}
		} )
		.fail( function () {
			deferred.reject();
		} );
	return deferred.promise();
};

/**
 * Get a wikitext fragment from a document
 *
 * @param {ve.dm.Document} doc Document
 * @param {boolean} [useRevision=true] Whether to use the revision ID + ETag
 * @param {boolean} [isRetry=false] Whether this call is retrying a prior call
 * @return {jQuery.Promise} Abortable promise which resolves with a wikitext string
 */
ve.init.mw.Target.prototype.getWikitextFragment = function ( doc, useRevision, isRetry ) {
	var promise, xhr,
		target = this,
		params = {
			action: 'visualeditoredit',
			token: this.editToken,
			paction: 'serialize',
			html: ve.dm.converter.getDomFromModel( doc ).body.innerHTML,
			page: this.getPageName()
		};

	// Optimise as a no-op
	if ( params.html === '' ) {
		return $.Deferred().resolve( '' );
	}

	if ( useRevision === undefined || useRevision ) {
		params.oldid = this.revid;
		params.etag = this.etag;
	}

	xhr = this.getContentApi( doc ).post(
		params,
		{ contentType: 'multipart/form-data' }
	);

	promise = xhr.then( function ( response ) {
		if ( response.visualeditoredit ) {
			return response.visualeditoredit.content;
		}
		return $.Deferred().reject();
	}, function ( error ) {
		if ( error === 'badtoken' && !isRetry ) {
			return target.refreshEditToken( doc ).then( function () {
				return target.getWikitextFragment( doc, useRevision, true );
			} );
		}
	} );

	promise.abort = function () {
		xhr.abort();
	};

	return promise;
};

/**
 * Parse a fragment of wikitext into HTML
 *
 * @param {string} wikitext Wikitext
 * @param {boolean} pst Perform pre-save transform
 * @param {ve.dm.Document} [doc] Parse for a specific document, defaults to current surface's
 * @return {jQuery.Promise} Abortable promise
 */
ve.init.mw.Target.prototype.parseWikitextFragment = function ( wikitext, pst, doc ) {
	return this.getContentApi( doc ).post( {
		action: 'visualeditor',
		paction: 'parsefragment',
		page: this.getPageName( doc ),
		wikitext: wikitext,
		pst: pst
	} );
};

/**
 * Get the page name associated with a specific document
 *
 * @param {ve.dm.Document} [doc] Document, defaults to current surface's
 * @return {string} Page name
 */
ve.init.mw.Target.prototype.getPageName = function () {
	return this.pageName;
};

/**
 * Get an API object associated with the wiki where the document
 * content is hosted.
 *
 * This would be overridden if editing content on another wiki.
 *
 * @param {ve.dm.Document} [doc] API for a specific document, should default to document of current surface.
 * @param {Object} [options] API options
 * @return {mw.Api} API object
 */
ve.init.mw.Target.prototype.getContentApi = function ( doc, options ) {
	return new mw.Api( options );
};

/**
 * Get an API object associated with the local wiki.
 *
 * For example you would always use getLocalApi for actions
 * associated with the current user.
 *
 * @param {Object} [options] API options
 * @return {mw.Api} API object
 */
ve.init.mw.Target.prototype.getLocalApi = function ( options ) {
	return new mw.Api( options );
};
