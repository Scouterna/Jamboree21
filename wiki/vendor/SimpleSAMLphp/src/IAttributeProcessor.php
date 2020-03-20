<?php

namespace MediaWiki\Extension\SimpleSAMLphp;

interface IAttributeProcessor {

	/**
	 * Processes injected SAML attributes
	 */
	public function run();
}
