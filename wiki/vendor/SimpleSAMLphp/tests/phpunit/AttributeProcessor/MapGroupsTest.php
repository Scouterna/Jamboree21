<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\AttributeProcessor;

use MediaWikiTestCase;
use MediaWiki\Extension\SimpleSAMLphp\IAttributeProcessor;
use HashConfig;
use TestUserRegistry;

class MapGroupsTest extends MediaWikiTestCase {

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor\MapGroups::factory
	 */
	public function testFactory() {
		$factoryMethod =
			'MediaWiki\\Extension\\SimpleSAMLphp\\AttributeProcessor\\MapGroups::factory';

		$user = $this->getMock( '\User' );
		$attributes = [];
		$config = new HashConfig( [] );
		$saml = $this->getMock( '\SimpleSAML\Auth\Simple' );

		$processor = $factoryMethod( $user, $attributes, $config, $saml );

		$this->assertInstanceOf(
			IAttributeProcessor::class,
			$processor,
			"Method $factoryMethod did not return an IAttributeProcessor!"
		);
	}

	/**
	 *
	 * @param array $attributes
	 * @param array $configArray
	 * @param array $initialGroups
	 * @param array $expectedGroups
	 * @dataProvider provideRunData
	 * @covers MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor\MapGroups::run
	 */
	public function testRun( $attributes, $configArray, $initialGroups, $expectedGroups ) {
		$factoryMethod =
			'MediaWiki\\Extension\\SimpleSAMLphp\\AttributeProcessor\\MapGroups::factory';

		$testUser = TestUserRegistry::getMutableTestUser( 'MapGroupsTestUser', $initialGroups );
		$user = $testUser->getUser();
		$config = new HashConfig( $configArray );
		$saml = $this->getMock( '\SimpleSAML\Auth\Simple' );

		$processor = $factoryMethod( $user, $attributes, $config, $saml );
		$processor->run();
		$actualGroups = $user->getGroups();

		$this->assertArrayEquals(
			$expectedGroups,
			$actualGroups,
			"Groups have not been set properly!"
		);
	}

	/**
	 *
	 * @return array
	 */
	public function provideRunData() {
		return [
			'default-example' => [
				[ 'groups' => [ 'administrator', 'dontsync' ] ],
				[
					'GroupMap' => [ 'sysop' => [ 'groups' => [ 'administrator' ] ] ],
					'GroupAttributeDelimiter' => null
				],
				[ 'abc' ],
				[ 'abc', 'sysop' ]
			],
			'delimiter-example' => [
				[ 'groups' => [ 'administrator,dontsync' ] ],
				[
					'GroupMap' => [ 'sysop' => [ 'groups' => [ 'administrator' ] ] ],
					'GroupAttributeDelimiter' => ','
				],
				[ 'abc' ],
				[ 'abc', 'sysop' ]
			]
		];
	}
}
