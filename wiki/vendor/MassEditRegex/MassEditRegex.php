<?php
/**
 * Allow users in the Bot group to edit many articles in one go by applying
 * regular expressions to a list of pages.
 *
 * @file
 * @ingroup Extensions
 *
 * @link https://www.mediawiki.org/wiki/Extension:MassEditRegex Documentation
 * @link https://www.mediawiki.org/wiki/Extension_talk:MassEditRegex Support
 * @link https://phabricator.wikimedia.org/diffusion/EMER/ Source Code
 *
 * @author Adam Nielsen <malvineous@shikadi.net>
 * @author Kim Eik <kim@heldig.org>
 *
 * @copyright Copyright © 2009-2016 Adam Nielsen
 *
 * @license GPL-2.0-or-later
 */

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'MassEditRegex' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['MassEditRegex'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['MassEditRegexAlias'] = __DIR__ . '/MassEditRegex.alias.php';
	wfWarn(
		'Deprecated PHP entry point used for the MassEditRegex extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the MassEditRegex extension requires MediaWiki 1.25+' );
}
