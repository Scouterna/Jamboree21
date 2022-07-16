<?php
if (!defined('MEDIAWIKI')) die();
/**
 * Class file for the GoogleTranslator extension
 *
 * @addtogroup Extensions
 * @author Joachim De Schrijver
 * @license LGPL
 */
class GoogleTranslator {
	static function GoogleTranslatorInSidebar( $skin, &$bar ) {
		global $wgGoogleTranslatorOriginal,$wgGoogleTranslatorLanguages;

		        $bar[ 'googletranslator' ] =  array(
				array(
					'text'   => 'TestPage',
					'href'   => '#',
					'id'     => 'google_translate_element',
					'active' => ''
				),
			);
		

		return true;
	}
}
