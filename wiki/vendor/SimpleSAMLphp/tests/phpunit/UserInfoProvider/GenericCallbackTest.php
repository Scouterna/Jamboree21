<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\UserInfoProvider;

use PHPUnit\Framework\TestCase;
use MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\GenericCallback;

class GenericCallbackTest extends TestCase {

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\GenericCallback::__construct
	 */
	public function testConstructor() {
		$provider = new GenericCallback( function () {
  } );

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\GenericCallback',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\GenericCallback::getValue
	 */
	public function testGetValue() {
		$provider = new GenericCallback( function ( $attributes ) {
			if ( !isset( $attributes['mail'] ) ) {
				throw new Exception( 'missing email address' );
			}
			$parts = explode( '@', $attributes['mail'][0] );
			return strtolower( $parts[0] );
		} );
		$value = $provider->getValue( [
			'mail' => [
				'John.Doe@example.com'
			]
		] );

		$this->assertEquals( 'john.doe', $value );
	}
}
