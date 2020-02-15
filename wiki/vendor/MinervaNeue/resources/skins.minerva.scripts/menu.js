var MainMenu = require( './menu/MainMenu.js' ),
	mainMenu = createMainMenu();

/**
 * Creates an instance of the `MainMenu`, using the `wgMinervaMenuData` for configuration.
 *
 * N.B. that the activator - the UI element that the user must click in order to open the main
 * menu - is always `.header .main-menu-button`.
 *
 * @return {MainMenu}
 *
 * @ignore
 */
function createMainMenu() {
	var options = mw.config.get( 'wgMinervaMenuData', {} );

	options.activator = '.header .main-menu-button';

	return new MainMenu( options );
}

$( function () {
	// eslint-disable-next-line no-jquery/no-global-selector
	if ( !$( '#mw-mf-page-left' ).find( '.menu' ).length ) {
		// Now we have a main menu button register it.
		mainMenu.registerClickEvents();
		mainMenu.appendTo( '#mw-mf-page-left' );
	}
} );

module.exports = mainMenu;
