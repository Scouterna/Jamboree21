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

// Ensure that the script cannot be executed outside of MediaWiki
if ( !defined( 'MEDIAWIKI' ) ) {
   die( 'This is an extension to MediaWiki and cannot be run standalone.' );
}

// Register extension with MediaWiki
$wgExtensionCredits['specialpage'][] = [
	'path' => __FILE__,
	'name' => 'MassEditRegex',
	'namemsg' => 'masseditregex-extensionname',
	'version' => '8.3.0',
	'author' => [
		'Adam Nielsen',
		'...'
		],
	'url' => 'https://www.mediawiki.org/wiki/Extension:MassEditRegex',
	'descriptionmsg' => 'masseditregex-desc',
	'license-name' => 'GPL-2.0-or-later'
];

// Register extension messages and other localisation
$wgMessagesDirs['MassEditRegex'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['MassEditRegexAlias'] = __DIR__ . '/MassEditRegex.alias.php';

// Register extension classes
$wgAutoloadClasses['MassEditRegex'] = __DIR__ . '/MassEditRegex.class.php';
$wgAutoloadClasses['MassEditRegexSpecialPage'] = __DIR__ . '/MassEditRegex.special.php';
$wgAutoloadClasses['MassEditRegexAPI'] = __DIR__ . '/MassEditRegex.api.php';

// Register special page into MediaWiki
$wgSpecialPages['MassEditRegex'] = 'MassEditRegexSpecialPage';

// Create new right to use Special:MassEditRegex
$wgAvailableRights[] = 'masseditregex';

// Register hooks
$wgHooks['SkinTemplateNavigation::Universal'][] = 'MassEditRegexSpecialPage::efSkinTemplateNavigationUniversal';
$wgHooks['BaseTemplateToolbox'][] = 'MassEditRegexSpecialPage::efBaseTemplateToolbox';

// Register ResourcesLoaderModules
$wgResourceModules['MassEditRegex'] = [
	'position' => 'top',
	'scripts' => [
		'MassEditRegex.js'
	],
	'dependencies' => [
		'mediawiki.jqueryMsg',
		'jquery.ui.dialog'
	],
	'group' => 'MassEditRegex',
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'MassEditRegex',
	'messages' => [
		'masseditregex-js-execution',
		'masseditregex-js-jobdone',
		'masseditregex-num-changes',
		'masseditregex-js-working',
		'masseditregex-js-pagenotexist',
		'masseditregex-js-mwapi-api-error',
		'masseditregex-js-mwapi-general-error',
		'masseditregex-js-mwapi-unknown-error',
	]
];

// AJAX
$wgAjaxExportList[] = 'MassEditRegexAPI::edit';
