<?php

namespace MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider;

use MediaWiki\Extension\SimpleSAMLphp\IUserInfoProvider;

class GenericCallback implements IUserInfoProvider {

	/**
	 *
	 * @var \Callable
	 */
	private $callback = null;

	/**
	 *
	 * @param \Callable $callback
	 */
	public function __construct( $callback ) {
		$this->callback = $callback;
	}

	/**
	 *
	 * @param string $samlattributes
	 * @return string
	 */
	public function getValue( $samlattributes ) {
		return call_user_func_array( $this->callback, [ $samlattributes ] );
	}
}
