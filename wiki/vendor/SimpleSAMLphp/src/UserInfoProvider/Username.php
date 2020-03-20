<?php

namespace MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider;

use \Exception;
use \Title;

class Username extends Base {

	/**
	 *
	 * @param array $samlattributes
	 * @return string
	 */
	public function getValue( $samlattributes ) {
		$usernameAttr = $this->config->get( 'UsernameAttribute' );
		$username = '';

		if ( is_null( $usernameAttr ) ) {
			throw new Exception( '$wgSimpleSAMLphp_UsernameAttribute is not set' );
		}

		if ( !isset( $samlattributes[$usernameAttr] ) ) {
			throw new Exception( 'Could not find username attribute: ' . $usernameAttr );
		}

		$username = $this->normalizeUsername( $samlattributes[$usernameAttr][0] );
		$newTitle = Title::makeTitleSafe( NS_USER, $username );
		if ( is_null( $newTitle ) ) {
			throw new Exception( 'Invalid username: ' . $username );
		}

		$username = $newTitle->getText();

		return $username;
	}

	/**
	 *
	 * @param string $samlProvidedUsername
	 * @return string
	 */
	protected function normalizeUsername( $samlProvidedUsername ) {
		return $samlProvidedUsername;
	}
}
