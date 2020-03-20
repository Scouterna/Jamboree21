<?php

namespace MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider;

use MediaWiki\Extension\SimpleSAMLphp\IUserInfoProvider;

abstract class Base implements IUserInfoProvider {

	/**
	 *
	 * @var \Config
	 */
	protected $config = null;

	/**
	 *
	 * @param \Config $config
	 */
	public function __construct( $config ) {
		$this->config = $config;
	}

	/**
	 *
	 * @param \Config $config
	 * @return IUserInfoProvider
	 */
	public static function factory( $config ) {
		return new static( $config );
	}
}
