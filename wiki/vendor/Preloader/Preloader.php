<?php

/**
 * Extension allows preloading of custom content into all edit forms
 * when creating an article. <includeonly> and <noinclude> are respected
 * and stripped during new article population.
 *
 * @file
 * @ingroup Extensions
 * @author Troy Engel <troyengel@gmail.com>
 * @author Rob Church <robchur@gmail.com>
 */
 
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Preloader' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['Preloader'] = __DIR__ . '/i18n';
	wfWarn(
		'Deprecated PHP entry point used for Preloader extension. Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the Preloader extension requires MediaWiki 1.25+' );
}