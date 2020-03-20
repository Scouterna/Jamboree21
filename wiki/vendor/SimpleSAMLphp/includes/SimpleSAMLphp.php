<?php

use MediaWiki\Extension\SimpleSAMLphp\IAttributeProcessor;
use MediaWiki\Extension\SimpleSAMLphp\IUserInfoProvider;

/*
 * Copyright (c) 2014 The MITRE Corporation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * @author Cindy Cicalese <cindom@gmail.com>
 * @author Mark A. Hershberger <mah@nichework.com>
 */
class SimpleSAMLphp extends PluggableAuth {

	/**
	 *
	 * @var array
	 */
	private $attributes = [];

	/**
	 *
	 * @var int
	 */
	private $userId = 0;

	/**
	 *
	 * @var string
	 */
	private $username = '';

	/**
	 *
	 * @var string
	 */
	private $realname = '';

	/**
	 *
	 * @var string
	 */
	private $email = '';

	/**
	 * Get the user's username and the id.
	 * @throws Exception
	 */
	private function initUsernameAndId() {
		$usernameProvider = $this->makeUserInfoProvider( 'username' );
		$this->username = $usernameProvider->getValue( $this->attributes );
		$this->userId = User::idFromName( $this->username );
	}

	/**
	 * Get the user's real name.
	 * @throws Exception
	 */
	private function initRealname() {
		$realnameProvider = $this->makeUserInfoProvider( 'realname' );
		$this->realname = $realnameProvider->getValue( $this->attributes );
	}

	/**
	 * Get the user's email address.
	 * @throws Exception
	 */
	private function initEmail() {
		$emailProvider = $this->makeUserInfoProvider( 'email' );
		$this->email = $emailProvider->getValue( $this->attributes );
	}

	/**
	 * @since 1.0
	 *
	 * @param int &$userId id of user
	 * @param string &$username username
	 * @param string &$realname real name of user
	 * @param string &$email email
	 * @param string|null &$errorMessage any error encountered
	 * @return bool true if the user is authenticated
	 * @see https://www.mediawiki.org/wiki/Extension:PluggableAuth
	 */
	public function authenticate(
		&$userId, &$username, &$realname, &$email, &$errorMessage = null
	 ) {
		$saml = self::getSAMLClient();
		try {
			$saml->requireAuth();
		} catch ( Exception $e ) {
			$errorMessage = $e->getMessage();
			wfDebugLog( 'SimpleSAMLphp', $errorMessage );
			return false;
		}
		$this->attributes = $saml->getAttributes();

		try {
			$this->initUsernameAndId();
			$this->initRealname();
			$this->initEmail();

			$userId = $this->userId;
			$username = $this->username;
			$realname = $this->realname;
			$email = $this->email;
		}
		catch ( Exception $ex ) {
			$errorMessage = $ex->getMessage();
			wfDebugLog( 'SimpleSAMLphp', $errorMessage );
			return false;
		}

		return true;
	}

	/**
	 * @since 1.0
	 *
	 * @param User &$user to deauthenticate
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function deauthenticate( User &$user ) {
		$saml = self::getSAMLClient();
		$returnto = null;
		if ( isset( $_REQUEST['returnto'] ) ) {
			$title = Title::newFromText( $_REQUEST['returnto'] );
			if ( !is_null( $title ) ) {
				$returnto = $title->getFullURL();
			}
		}
		if ( is_null( $returnto ) ) {
			$returnto = Title::newMainPage()->getFullURL();
		}
		$saml->logout( $returnto );
	}

	/**
	 * @since 1.0
	 *
	 * @param int $userId id of user
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function saveExtraAttributes( $userId ) {
		// intentionally left blank
	}

	/**
	 * @since 4.1
	 * Update MediaWiki group membership of the authenticated user.
	 * Implement PluggableAuthPopulateGroups hook from PluggableAuth
	 * to use groups from SAML attributes.
	 *
	 * @param User $user to get groups from SAML
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public static function populateGroups( User $user ) {
		$saml = self::getSAMLClient();
		$attributes = $saml->getAttributes();

		$config = new GlobalVarConfig( 'wgSimpleSAMLphp_' );
		$attributeProcessorFactories = $config->get( 'AttributeProcessorFactories' );
		foreach ( $attributeProcessorFactories as $attributeProcessorFactory ) {
			$attributeProcessor = $attributeProcessorFactory( $user, $attributes, $config, $saml );
			if ( $attributeProcessor instanceof IAttributeProcessor === false ) {
				throw new MWException(
					"Factory '$attributeProcessorFactory' returned an invalid AttributeProcessor!"
				);
			}
			$attributeProcessor->run();
		}
	}

	/**
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	private static function getSAMLClient() {
		// Make MW core `SpecialPageFatalTest` pass
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return new MediaWiki\Extension\SimpleSAMLphp\Tests\Dummy\SimpleSAML\Auth\Simple();
		}
		require_once rtrim( $GLOBALS['wgSimpleSAMLphp_InstallDir'], '/' )
			. '/lib/_autoload.php';
		$class = 'SimpleSAML_Auth_Simple';
		if ( class_exists( 'SimpleSAML\Auth\Simple' ) ) {
			$class = 'SimpleSAML\\Auth\\Simple';
		}
		return new $class( $GLOBALS['wgSimpleSAMLphp_AuthSourceId'] );
	}

	/**
	 *
	 * @param string $infoName
	 * @return IUserInfoProvider
	 * @throws Exception
	 */
	private function makeUserInfoProvider( $infoName ) {
		$config = new GlobalVarConfig( 'wgSimpleSAMLphp_' );
		$userInfoProviderFactories = $config->get( 'MandatoryUserInfoProviderFactories' );
		if ( !isset( $userInfoProviderFactories[$infoName] ) ) {
			throw new Exception( "No factory callback set for '$infoName'!" );
		}
		$factoryCallback = $userInfoProviderFactories[$infoName];
		if ( !is_callable( $factoryCallback ) ) {
			throw new Exception( "Invalid factory callback set for '$infoName'!" );
		}

		$provider = call_user_func_array( $factoryCallback, [ $config ] );
		if ( $provider instanceof IUserInfoProvider === false ) {
			throw new Exception( "Provider for '$infoName' does not implement IUserInfoProvider!" );
		}

		return $provider;
	}

}
