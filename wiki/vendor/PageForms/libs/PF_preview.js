/**
 * Handles dynamic Page Preview for Page Forms.
 *
 * @author Stephan Gambke
 */

/*global validateAll */

( function ( $, mw ) {

	'use strict';

	var $form;
	var $previewpane;
	var previewHeight;

	/**
	 * Called when the content is loaded into the preview pane
	 *
	 * @return {Mixed}
	 */
	var loadFrameHandler = function handleLoadFrame() {

		var $iframe = $( this );
		var $iframecontents = $iframe.contents();

		// find div containing the preview
		var $content = $iframecontents.find( '#wikiPreview' );

		var $iframebody = $content.closest( 'body' );
		var $iframedoc = $iframebody.parent();
		$iframedoc.height( 'auto' );

		// this is not a normal MW page (or it uses an unknown skin)
		if ( $content.length === 0 ) {
			$content = $iframebody;
		}

		$content.parentsUntil( 'html' ).addBack()
		.css( {
			margin: 0,
			padding: 0,
			width: '100%',
			height: 'auto',
			minWidth: '0px',
			minHeight: '0px',
			'float': 'none', // Cavendish skin uses floating -> unfloat content
			border: 'none',
			background: 'transparent'
		} )
		.siblings()
		.hide(); // FIXME: Some JS scripts don't like working on hidden elements

		// and attach event handler to adjust frame size every time the window
		// size changes
		$( window ).resize( function () {
			$iframe.height( $iframedoc.height() );
		} );

		$previewpane.show();

		var newPreviewHeight = $iframedoc.height();

		$iframe.height( newPreviewHeight );

		$( 'html, body' )
		.scrollTop( $( 'html, body' ).scrollTop() + newPreviewHeight - previewHeight )
		.animate( {
			scrollTop: $previewpane.offset().top
		}, 1000 );

		previewHeight = newPreviewHeight;

		$( function() {
			window.dispatchEvent( new Event( 'resize' ) ); // It fixes form preview
		} );

		return false;
	};

	/**
	 * Called when the server has sent the preview
	 *
	 * @param {Mixed} result
	 */
	var resultReceivedHandler = function handleResultReceived( result ) {

		var htm = result.result;

		var $iframe = $previewpane.children();

		if ( $iframe.length === 0 ) {

			// set initial height of preview area
			previewHeight = 0;

			$iframe = $( '<iframe>' )
			.css( { //FIXME: Should this go in a style file?
				'width': '100%',
				'height': previewHeight,
				'border': 'none',
				'overflow': 'hidden'
			} )
			.load( loadFrameHandler )
			.appendTo( $previewpane );

		}

		var ifr = $iframe[0];
		var doc = ifr.contentDocument || ifr.contentWindow.document || ifr.Document;

		doc.open();
		doc.write( htm );
		doc.close();

	};

	/**
	 * Called when the preview button was clicked
	 */
	var previewButtonClickedHandler = function handlePreviewButtonClicked() {

		if ( !validateAll() ) {
			return;
		}

		// data array to be sent to the server
		var data = {
			action: 'pfautoedit',
			format: 'json'
		};

		// do we have a URL like .../index.php?title=pagename&action=formedit ?
		if ( mw.config.get( 'wgAction' ) === 'formedit' ) {

			// set the title, server has to find a suitable form
			data.target = mw.config.get( 'wgPageName' );

			// do we have a URL like .../Special:FormEdit/formname/pagename ?
		} else if ( mw.config.get( 'wgCanonicalNamespace' ) === 'Special' && mw.config.get( 'wgCanonicalSpecialPageName' ) === 'FormEdit' ) {

			// get the pagename and split it into parts
			var pageName = mw.config.get( 'wgPageName' );
			var parts = pageName.split( '/' );

			if ( mw.util.getParamValue( 'form' ) ) {
				data.form = mw.util.getParamValue( 'form' );
			} else if ( parts.length > 1 ) { // found a formname
				data.form = parts[1];
			}

			if ( mw.util.getParamValue( 'target' ) ) {
				data.target = mw.util.getParamValue( 'target' );
			} else if ( parts.length > 2 ) { // found a pagename
				// Put the name back together, if it contains slashes.
				data.target = parts.slice(2).join( '/' );
			}
		}

		// add form values to the data
		data.query = $form.serialize();

		if ( data.query.length > 0 ) {
			data.query += '&';
		}

		data.query += 'wpPreview=' + encodeURIComponent( $( this ).attr( 'value' ) );

		$.ajax( {

			type: 'POST', // request type ( GET or POST )
			url: mw.util.wikiScript( 'api' ), // URL to which the request is sent
			data: data, // data to be sent to the server
			dataType: 'json', // type of data expected back from the server
			success: resultReceivedHandler // function to be called if the request succeeds
		} );
	};

	/**
	 * Register plugin
	 *
	 *  @return {Mixed}
	 */
	$.fn.pfAjaxPreview = function () {

		$form = this.closest( 'form' );
		$previewpane = $( '#wikiPreview' );

		// do some sanity checks
		if ( $previewpane.length === 0 || // no ajax preview without preview area
			$previewpane.contents().length > 0 || // preview only on an empty previewpane
			$form.length === 0 ) { // no ajax preview without form

			return this;
		}

		// register event handler
		this.click( previewButtonClickedHandler );

		return this;
	};

	$( document ).ready( function () {
		if ( mw.config.get( 'wgAction' ) === 'formedit' ||
			mw.config.get( 'wgCanonicalSpecialPageName' ) === 'FormEdit' ) {
			$( '#wpPreview' ).pfAjaxPreview();
			$( document ).on( 'VEForAllLoaded', function() {
				if ( $('.visualeditor').length > 0 ) {
					$( '#wpPreview' ).off('click', previewButtonClickedHandler).on('click', function( event ) {
						mw.pageFormsActualizeVisualEditorFields( function() {
							previewButtonClickedHandler( event );
						});
					});
				}
			});
		}
	} );

}( jQuery, mediaWiki ) );
