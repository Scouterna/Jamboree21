<?php

namespace MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor;

use MediaWiki\Extension\SimpleSAMLphp\IAttributeProcessor;

abstract class Base implements IAttributeProcessor {

	/**
	 *
	 * @var \User
	 */
	protected $user = null;

	/**
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 *
	 * @var \Config
	 */
	protected $config = null;

	/**
	 *
	 * @var SimpleSAML\Auth\Simple
	 */
	protected $saml = null;

	/**
	 *
	 * @param \User $user
	 * @param array $attributes
	 * @param \Config $config
	 * @param SimpleSAML\Auth\Simple $saml The SAML authentication interface
	 */
	public function __construct( $user, $attributes, $config, $saml ) {
		$this->user = $user;
		$this->attributes = $attributes;
		$this->config = $config;
		$this->saml = $saml;
	}

	/**
	 *
	 * @param \User $user
	 * @param array $attributes
	 * @param \Config $config
	 * @param SimpleSAML\Auth\Simple $saml The SAML authentication interface
	 * @return IAttributeProcessor
	 */
	public static function factory( $user, $attributes, $config, $saml ) {
		return new static( $user, $attributes, $config, $saml );
	}
}
