<?php

namespace CirrusSearch\Api;

use ApiBase;
use ApiResult;
use CirrusSearch\Profile\SearchProfileService;
use CirrusSearch\SearchConfig;
use CirrusSearch\UserTestingEngine;
use MediaWiki\MediaWikiServices;

/**
 * Dumps CirrusSearch configuration for easy viewing.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */
class ConfigDump extends ApiBase {
	use ApiTrait;

	public static $PUBLICLY_SHAREABLE_CONFIG_VARS = [
		'CirrusSearchDisableUpdate',
		'CirrusSearchServers',
		'CirrusSearchConnectionAttempts',
		'CirrusSearchSlowSearch',
		'CirrusSearchUseExperimentalHighlighter',
		'CirrusSearchOptimizeIndexForExperimentalHighlighter',
		'CirrusSearchNamespaceMappings',
		'CirrusSearchExtraIndexes',
		'CirrusSearchExtraIndexClusters',
		'CirrusSearchFetchConfigFromApi',
		'CirrusSearchUpdateShardTimeout',
		'CirrusSearchClientSideUpdateTimeout',
		'CirrusSearchSearchShardTimeout',
		'CirrusSearchClientSizeSearchTimeout',
		'CirrusSearchMaintenanceTimeout',
		'CirrusSearchPrefixSearchStartsWithAnyWord',
		'CirrusSearchPhraseSlop',
		'CirrusSearchPhraseRescoreBoost',
		'CirrusSearchPhraseRescoreWindowSize',
		'CirrusSearchFunctionRescoreWindowSize',
		'CirrusSearchMoreAccurateScoringMode',
		'CirrusSearchPhraseSuggestUseText',
		'CirrusSearchPhraseSuggestUseOpeningText',
		'CirrusSearchIndexedRedirects',
		'CirrusSearchLinkedArticlesToUpdate',
		'CirrusSearchUnlikedArticlesToUpdate',
		'CirrusSearchWeights',
		'CirrusSearchAllFields',
		'CirrusSearchBoostOpening',
		'CirrusSearchNearMatchWeight',
		'CirrusSearchStemmedWeight',
		'CirrusSearchNamespaceWeights',
		'CirrusSearchDefaultNamespaceWeight',
		'CirrusSearchTalkNamespaceWeight',
		'CirrusSearchLanguageWeight',
		'CirrusSearchPreferRecentDefaultDecayPortion',
		'CirrusSearchPreferRecentUnspecifiedDecayPortion',
		'CirrusSearchPreferRecentDefaultHalfLife',
		'CirrusSearchMoreLikeThisConfig',
		'CirrusSearchInterwikiSources',
		'CirrusSearchRefreshInterval',
		'CirrusSearchFragmentSize',
		'CirrusSearchIndexAllocation',
		'CirrusSearchFullTextQueryBuilderProfile',
		'CirrusSearchRescoreProfile',
		'CirrusSearchPrefixSearchRescoreProfile',
		'CirrusSearchSimilarityProfile',
		'CirrusSearchCrossProjectProfiles',
		'CirrusSearchCrossProjectOrder',
		'CirrusSearchCrossProjectSearchBlockList',
		'CirrusSearchExtraIndexBoostTemplates',
		'CirrusSearchEnableCrossProjectSearch',
		'CirrusSearchEnableAltLanguage',
		'CirrusSearchEnableArchive',
		'CirrusSearchUseIcuFolding',
		'CirrusSearchUseIcuTokenizer',
		'CirrusSearchPhraseSuggestProfiles',
		'CirrusSearchCrossProjectBlockScorerProfiles',
		'CirrusSearchSimilarityProfiles',
		'CirrusSearchRescoreFunctionChains',
		'CirrusSearchCompletionProfiles',
		'CirrusSearchCompletionSettings',
		// All the config below was added when moving this data
		// from CirrusSearch config to a static array in this class
		'CirrusSearchDevelOptions',
		'CirrusSearchPrefixIds',
		'CirrusSearchMoreLikeThisFields',
		'CirrusSearchMoreLikeThisTTL',
		'CirrusSearchFiletypeAliases',
		'CirrusSearchDefaultCluster',
		'CirrusSearchClientSideConnectTimeout',
		'CirrusSearchClusters',
		'CirrusSearchReplicaGroup',
		'CirrusSearchExtraBackendLatency',
		'CirrusSearchAllowLeadingWildcard',
		'CirrusSearchClientSideSearchTimeout',
		'CirrusSearchStripQuestionMarks',
		'CirrusSearchFullTextQueryBuilderProfiles',
		'CirrusSearchEnableRegex',
		'CirrusSearchWikimediaExtraPlugin',
		'CirrusSearchRegexMaxDeterminizedStates',
		'CirrusSearchMaxIncategoryOptions',
		'CirrusSearchEnablePhraseSuggest',
		'CirrusSearchClusterOverrides',
		'CirrusSearchRescoreProfiles',
		'CirrusSearchRescoreFunctionScoreChains',
		'CirrusSearchNumCrossProjectSearchResults',
		'CirrusSearchLanguageToWikiMap',
		'CirrusSearchWikiToNameMap',
		'CirrusSearchIncLinksAloneW',
		'CirrusSearchIncLinksAloneK',
		'CirrusSearchIncLinksAloneA',
		'CirrusSearchNewCrossProjectPage',
		'CirrusSearchQueryStringMaxDeterminizedStates',
		'CirrusSearchElasticQuirks',
		'CirrusSearchPhraseSuggestMaxErrors',
		'CirrusSearchPhraseSuggestReverseField',
		'CirrusSearchBoostTemplates',
		'CirrusSearchIgnoreOnWikiBoostTemplates',
		'CirrusSearchAllFieldsForRescore',
		'CirrusSearchIndexBaseName',
		'CirrusSearchInterleaveConfig',
		'CirrusSearchMaxPhraseTokens',
		'LanguageCode',
		'ContentNamespaces',
		'NamespacesToBeSearchedDefault',
		'CirrusSearchCategoryDepth',
		'CirrusSearchCategoryMax',
		'CirrusSearchCategoryEndpoint',
		'CirrusSearchFallbackProfile',
		'CirrusSearchFallbackProfiles',
	];

	public function execute() {
		$result = $this->getResult();
		$props = array_flip( $this->extractRequestParams()[ 'prop' ] );
		if ( isset( $props['globals'] ) ) {
			$this->addGlobals( $result );
		}
		if ( isset( $props['namespacemap'] ) ) {
			$this->addConcreteNamespaceMap( $result );
		}
		if ( isset( $props['profiles'] ) ) {
			$this->addProfiles( $result );
		}
		if ( isset( $props['usertesting'] ) ) {
			$this->addUserTesting( $result );
		}
	}

	protected function addGlobals( ApiResult $result ) {
		$config = $this->getConfig();
		foreach ( self::$PUBLICLY_SHAREABLE_CONFIG_VARS as $key ) {
			if ( $config->has( $key ) ) {
				$result->addValue( null, $key, $config->get( $key ) );
			}
		}
	}

	/**
	 * Include a complete mapping from namespace id to index containing pages.
	 *
	 * Intended for external services/users that need to interact
	 * with elasticsearch or cirrussearch dumps directly.
	 *
	 * @param ApiResult $result Impl to write results to
	 */
	private function addConcreteNamespaceMap( ApiResult $result ) {
		$nsInfo = MediaWikiServices::getInstance()->getNamespaceInfo();
		$conn = $this->getCirrusConnection();
		$indexBaseName = $conn->getConfig()->get( SearchConfig::INDEX_BASE_NAME );
		foreach ( $nsInfo->getValidNamespaces() as $ns ) {
			$indexType = $conn->getIndexSuffixForNamespace( $ns );
			$indexName = $conn->getIndexName( $indexBaseName, $indexType );
			$result->addValue( 'CirrusSearchConcreteNamespaceMap', $ns, $indexName );
		}
	}

	/**
	 * Profile names and types
	 * @var string[]
	 */
	private static $PROFILES = [
		'CirrusSearchPhraseSuggestProfiles' => SearchProfileService::PHRASE_SUGGESTER,
		'CirrusSearchCrossProjectBlockScorerProfiles' => SearchProfileService::CROSS_PROJECT_BLOCK_SCORER,
		'CirrusSearchSimilarityProfiles' => SearchProfileService::SIMILARITY,
		'CirrusSearchRescoreFunctionChains' => SearchProfileService::RESCORE_FUNCTION_CHAINS,
		'CirrusSearchCompletionProfiles' => SearchProfileService::COMPLETION,
		'CirrusSearchFullTextQueryBuilderProfiles' => SearchProfileService::FT_QUERY_BUILDER,
		'CirrusSearchRescoreProfiles' => SearchProfileService::RESCORE,
	];

	/**
	 * Add data from profiles
	 * @param ApiResult $result
	 */
	private function addProfiles( ApiResult $result ) {
		$config = new SearchConfig();
		$profileService = $config->getProfileService();
		foreach ( self::$PROFILES as $var => $profileType ) {
			$data = $profileService->listExposedProfiles( $profileType );
			$this->getResult()->addValue( null, $var, $data, ApiResult::OVERRIDE );
		}
	}

	protected function addUserTesting( ApiResult $result ) {
		// UserTesting only automatically assigns test buckets during web requests.
		// This api call is different from a typical search request though, this is
		// used from non-search pages to find out what bucket to provide to a new
		// autocomplete session.
		$engine = UserTestingEngine::fromConfig( $this->getConfig() );
		$status = $engine->decideTestByAutoenroll();
		$result->addValue( null, 'CirrusSearchActiveUserTest',
			$status->isActive() ? $status->getTrigger() : '' );
	}

	public function getAllowedParams() {
		return [
			'prop' => [
				ApiBase::PARAM_DFLT => 'globals|namespacemap|profiles',
				ApiBase::PARAM_TYPE => [
					'globals',
					'namespacemap',
					'profiles',
					'usertesting',
				],
				ApiBase::PARAM_ISMULTI => true,
			],
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 * @return array
	 */
	protected function getExamplesMessages() {
		return [
			'action=cirrus-config-dump' =>
				'apihelp-cirrus-config-dump-example'
		];
	}

}
