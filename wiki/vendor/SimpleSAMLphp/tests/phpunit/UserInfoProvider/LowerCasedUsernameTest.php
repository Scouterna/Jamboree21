<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\UserInfoProvider;

use MediaWikiTestCase;
use MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername;
use HashConfig;

class LowerCasedUsernameTest extends MediaWikiTestCase {

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::__construct
	 */
	public function testConstructor() {
		$provider = new LowerCasedUsername( new HashConfig( [] ) );

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::factory
	 */
	public function testFactory() {
		$provider = LowerCasedUsername::factory( new HashConfig( [] ) );

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::getValue
	 * @expectedException Exception
	 */
	public function testBadConfigException() {
		$provider = LowerCasedUsername::factory( new HashConfig( [] ) );
		$provider->getValue( [
			'username' => [
				'John Doe'
			]
		] );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::getValue
	 * @expectedException Exception
	 */
	public function testMissingAttributeException() {
		$provider = LowerCasedUsername::factory( new HashConfig( [
			'UsernameAttribute' => 'username'
		] ) );
		$provider->getValue( [] );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::getValue
	 * @expectedException Exception
	 */
	public function testInvalidLowerCasedUsernameException() {
		$provider = LowerCasedUsername::factory( new HashConfig( [
			'UsernameAttribute' => 'username'
		] ) );
		$provider->getValue( [
			'username' => [
				'John Doe|invalid'
			]
		] );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::getValue
	 */
	public function testGetValue() {
		$provider = LowerCasedUsername::factory( new HashConfig( [
			'UsernameAttribute' => 'username'
		] ) );
		$username = $provider->getValue( [
			'username' => [
				'John Doe'
			]
		] );

		$this->assertEquals( 'John doe', $username );
	}
}
