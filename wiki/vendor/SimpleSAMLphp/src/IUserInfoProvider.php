<?php

namespace MediaWiki\Extension\SimpleSAMLphp;

interface IUserInfoProvider {

	/**
	 *
	 * @param array $samlattributes
	 * @return string
	 */
	public function getValue( $samlattributes );
}
