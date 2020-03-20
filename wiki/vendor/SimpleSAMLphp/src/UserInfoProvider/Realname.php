<?php

namespace MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider;

use \Exception;

class Realname extends Base {

	/**
	 *
	 * @param array $samlattributes
	 * @return string
	 */
	public function getValue( $samlattributes ) {
		$realNameAttr = $this->config->get( 'RealNameAttribute' );
		$realname = '';

		if ( is_null( $realNameAttr ) ) {
			throw new Exception( '$wgSimpleSAMLphp_RealNameAttribute is not set' );
		}

		if ( is_array( $realNameAttr ) ) {
			foreach ( $realNameAttr as $attribute ) {
				if ( isset( $samlattributes[$attribute] ) ) {
					if ( $realname != '' ) {
						$realname .= ' ';
					}
					$realname .= $samlattributes[$attribute][0];
				} else {
					throw new Exception( 'Could not find real name attribute ' . $attribute );
				}
			}
		} elseif ( isset( $samlattributes[$realNameAttr] ) ) {
			$realname = $samlattributes[$realNameAttr][0];
		} else {
			throw new Exception( 'Could not find real name attribute: ' . $realNameAttr );
		}

		return $realname;
	}

}
