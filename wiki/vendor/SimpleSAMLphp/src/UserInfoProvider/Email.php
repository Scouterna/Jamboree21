<?php

namespace MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider;

use \Exception;

class Email extends Base {

	/**
	 *
	 * @param array $samlattributes
	 * @return string
	 */
	public function getValue( $samlattributes ) {
		$emailAttr = $this->config->get( 'EmailAttribute' );
		$email = '';

		if ( is_null( $emailAttr ) ) {
			throw new Exception( '$wgSimpleSAMLphp_EmailAttribute is not set' );
		}
		if ( !isset( $samlattributes[$emailAttr] ) ) {
			throw new Exception( 'Could not find email attribute: ' . $emailAttr );
		}
		$email = $samlattributes[$emailAttr][0];

		return $email;
	}

}
