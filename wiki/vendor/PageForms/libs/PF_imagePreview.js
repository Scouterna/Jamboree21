/**
 * JavaScript for the Page Forms MediaWiki extension.
 *
 * @license GNU GPL v3+
 * @author Jeroen De Dauw <jeroendedauw at gmail dot com>
 */

( function ( $, mw ) {
	var _this = this;

	this.getPreviewImage = function( args, callback ) {
		$.getJSON(
			mw.config.get( 'wgScriptPath' ) + '/api.php',
			{
				'action': 'query',
				'format': 'json',
				'prop': 'imageinfo',
				'iiprop': 'url',
				'titles': 'File:' + args.title,
				'iiurlwidth': args.width
			},
			function( data ) {
				if ( data.query && data.query.pages ) {
					var pages = data.query.pages;

					for ( var p in pages ) { // object, not an array
						var info = pages[p].imageinfo;
						if ( info.length > 0 ) {
							callback( info[0].thumburl );
							return;
						}
					}
				}
				callback( false );
			}
		);
	};

	$( document ).ready( function() {
		$( '.pfImagePreview' ).each( function( index, domElement ) {
			var $uploadLink = $( domElement );
			var inputId = $uploadLink.attr( 'data-input-id' );
			var $input = $( '#' + inputId );
			var $previewDiv = $( '#' + inputId + '_imagepreview' );

			var showPreview = function() {
				_this.getPreviewImage(
					{
						'title': $input.val(),
						'width': 200
					},
					function( url ) {
						if ( url === false ) {
							$previewDiv.html( '' );
						} else {
							$previewDiv.html( $( '<img />' ).attr( { 'src': url } ) );
						}
					}
				);
			};

			$input.change( showPreview );
		} );
	} );
}( jQuery, mediaWiki ) );
