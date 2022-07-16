<?php
/**
 * MediaWiki extension to add Google Translator in a portlet in the sidebar.
 * Installation instructions can be found on
 * http://www.mediawiki.org/wiki/Extension:Google_Translator
 *
 * This extension will not add the Google Translator portlet to *any* skin
 * that is used with MediaWiki. Because of inconsistencies in the skin
 * implementation, it will not be add to the following skins:
 * cologneblue, standard, nostalgia
 *
 * @addtogroup Extensions
 * @author Joachim De Schrijver
 * @license LGPL
 *
 * Loosely based on the Google AdSense extension by Siebrand Mazeland
 */

/**
 * Exit if called outside of MediaWiki
 */
if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

/**
 * SETTINGS
 * --------
 * The description of the portlet can be changed in [[MediaWiki:Googletranslator]].
 *
 * The following variables may need to be reset in your LocalSettings.php.
  */
$wgGoogleTranslatorOriginal  = $wgLanguageCode; // Original languages of the page that needs translation
$wgGoogleTranslatorLanguages  = 'fr,de';        // Languages included in the translating box

$wgExtensionCredits['other'][] = array(
	'name'           => 'Google Translator',
	'version'        => '0.3',
	'author'         => 'Joachim De Schrijver',
	'description'    => 'Adds [https://translate.google.com Google Translator] to the sidebar',
	'descriptionmsg' => 'googletranslator-desc',
	'url'            => 'https://www.mediawiki.org/wiki/Extension:Google_Translator',
);

// Register class and localisations
$dir = dirname(__FILE__) . '/';
$wgAutoloadClasses['GoogleTranslator'] = $dir . 'GoogleTranslator.class.php';
$wgExtensionMessagesFiles['GoogleTranslator'] = $dir . 'GoogleTranslator.i18n.php';

// Hook to modify the sidebar
$wgHooks['SkinBuildSidebar'][] = 'GoogleTranslator::GoogleTranslatorInSidebar';



//js footer
$wgHooks['SkinAfterContent'][] = function(&$data, $skin) {
	$data .= 
			"
	<script>
			function googleTranslateElementInit() {
			  new google.translate.TranslateElement({
			  }, 'google_translate_element');
			}
	</script>
	<script src=\"//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit\"></script>".
			'';
};