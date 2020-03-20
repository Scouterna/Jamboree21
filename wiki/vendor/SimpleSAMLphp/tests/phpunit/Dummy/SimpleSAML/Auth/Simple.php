<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\Dummy\SimpleSAML\Auth;

class Simple {

	/**
	 *
	 */
	public function requireAuth() {
		// Do nothing
	}

	/**
	 *
	 * @return array
	 */
	public function getAttributes() {
		return [];
	}

	public function logout() {
		// Do nothing
	}
}
