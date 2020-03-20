<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\UserInfoProvider;

use PHPUnit\Framework\TestCase;
use MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Realname;
use HashConfig;

class RealnameTest extends TestCase {

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Realname::__construct
	 */
	public function testConstructor() {
		$provider = new Realname( new HashConfig( [] ) );

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Realname',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Realname::factory
	 */
	public function testFactory() {
		$provider = Realname::factory( new HashConfig( [] ) );

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Realname',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Realname::getValue
	 * @expectedException Exception
	 */
	public function testBadConfigException() {
		$provider = Realname::factory( new HashConfig( [] ) );
		$provider->getValue( [
			'realname' => [
				'John Doe'
			]
		] );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Realname::getValue
	 * @expectedException Exception
	 */
	public function testMissingAttributeException() {
		$provider = Realname::factory( new HashConfig( [
			'RealNameAttribute' => 'realname'
		] ) );
		$provider->getValue( [] );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Realname::getValue
	 */
	public function testGetValue() {
		$provider = Realname::factory( new HashConfig( [
			'RealNameAttribute' => 'realname'
		] ) );
		$realname = $provider->getValue( [
			'realname' => [
				'John Doe'
			]
		] );

		$this->assertEquals( 'John Doe', $realname );
	}
}
