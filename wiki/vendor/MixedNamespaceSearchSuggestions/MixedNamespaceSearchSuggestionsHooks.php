<?php
/**
 * @author Niklas Laxström
 * @license MIT
 */

class MixedNamespaceSearchSuggestionsHooks {
	/**
	 * This is a hook function.
	 * @param OutputPage $out The OutputPage, which wasn't yet obvious to the linter.
	 */
	public static function onBeforePageDisplay( OutputPage $out ) {
		$out->addModules( 'ext.mnss.search' );
	}
}
