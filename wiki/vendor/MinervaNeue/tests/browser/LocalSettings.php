<?php
$wgRightsText = 'Creative Commons Attribution 3.0';
$wgRightsUrl = "http://creativecommons.org/licenses/by-sa/3.0/";
// Allow users to edit privacy link.
$wgGroupPermissions['user']['editinterface'] = true;

$wgHooks['InterwikiLoadPrefix'][] = function ( $prefix, &$iwdata ) {
	if ( $prefix === 'es' ) {
		// return our hardcoded interwiki info
		$iwdata = [
			'iw_url' => 'http://wikifoo.org/es/index.php/$1',
			'iw_local' => 0,
			'iw_trans' => 0,
		];
		return false;
	}
	// nothing to do, continue lookup
	return true;
};
$wgInterwikiCache = false;

$wgMinervaPageIssuesNewTreatment = [
	"base" => true,
	"beta" => true,
];

$wgMFEnableBeta = true;

// Set the desktop skin to MinervaNeue. Otherwise, it will try to guess the skin name using the
// class name MinervaNeue which resolves to `minervaneue`.
$wgDefaultSkin = 'minerva';

// needed for testing whether the language button is displayed and disabled
$wgMinervaAlwaysShowLanguageButton = true;

// For those who have wikibase installed.
$wgMFUseWikibase = true;

$wgMFDisplayWikibaseDescriptions = [
	'search' => true,
	'nearby' => true,
	'watchlist' => true,
	'tagline' => true,
];

$wgMinervaShowCategoriesButton['base'] = true;
