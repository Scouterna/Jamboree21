<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\UserInfoProvider;

use PHPUnit\Framework\TestCase;
use MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Email;
use HashConfig;

class EmailTest extends TestCase {

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Email::__construct
	 */
	public function testConstructor() {
		$provider = new Email( new HashConfig( [] ) );

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Email',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Email::factory
	 */
	public function testFactory() {
		$provider = Email::factory( new HashConfig( [] ) );

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Email',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Email::getValue
	 * @expectedException Exception
	 */
	public function testBadConfigException() {
		$provider = Email::factory( new HashConfig( [] ) );
		$provider->getValue( [
			'mail' => [
				'someone@somewhere.com'
			]
		] );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Email::getValue
	 * @expectedException Exception
	 */
	public function testMissingAttributeException() {
		$provider = Email::factory( new HashConfig( [
			'EmailAttribute' => 'mail'
		] ) );
		$provider->getValue( [] );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Email::getValue
	 */
	public function testGetValue() {
		$provider = Email::factory( new HashConfig( [
			'EmailAttribute' => 'mail'
		] ) );
		$mail = $provider->getValue( [
			'mail' => [
				'someone@somewhere.com'
			]
		] );

		$this->assertEquals( 'someone@somewhere.com', $mail );
	}
}
