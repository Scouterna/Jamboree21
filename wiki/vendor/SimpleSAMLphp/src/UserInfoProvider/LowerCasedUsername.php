<?php

namespace MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider;

class LowerCasedUsername extends Username {

	/**
	 *
	 * @param string $samlProvidedUsername
	 * @return string
	 */
	protected function normalizeUsername( $samlProvidedUsername ) {
		return strtolower( $samlProvidedUsername );
	}
}
