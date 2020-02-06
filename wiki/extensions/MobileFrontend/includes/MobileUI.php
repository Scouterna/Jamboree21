<?php

// FIXME: Use OOUI PHP when available.
/**
 * Helper methods for generating parts of the UI.
 */
class MobileUI {

	/**
	 * Get CSS classes for icons
	 * @param string $iconName Icon name
	 * @param string $iconType element or before
	 * @param string $additionalClassNames additional class names you want to associate
	 *  with the iconed element
	 * @return string class name for use with HTML element
	 */
	public static function iconClass( $iconName, $iconType = 'element', $additionalClassNames = '' ) {
		$base = 'mw-ui-icon';
		$modifiers = 'mw-ui-icon-' . $iconType;
		if ( $iconName ) {
			$modifiers .= ' mw-ui-icon-mf-' . $iconName;
		}
		return $base . ' ' . $modifiers . ' ' . $additionalClassNames;
	}

	/**
	 * Get CSS classes for a mediawiki ui semantic element
	 * @param string $base The base class
	 * @param string $modifier Type of anchor (progressive, destructive)
	 * @param string $additionalClassNames additional class names you want to associate
	 *  with the iconed element
	 * @return string class name for use with HTML element
	 */
	private static function semanticClass( $base, $modifier, $additionalClassNames = '' ) {
		$modifier = empty( $modifier ) ? '' : 'mw-ui-' . $modifier;
		return $base . ' ' . $modifier . ' ' . $additionalClassNames;
	}

	/**
	 * Get CSS classes for buttons
	 * @param string $modifier Type of button (progressive, destructive)
	 * @param string $additionalClassNames additional class names you want to associate
	 *  with the button element
	 * @return string class name for use with HTML element
	 */
	public static function buttonClass( $modifier = '', $additionalClassNames = '' ) {
		return self::semanticClass( 'mw-ui-button', $modifier, $additionalClassNames );
	}

	/**
	 * Get CSS classes for anchors
	 * @param string $modifier Type of anchor (progressive, destructive)
	 * @param string $additionalClassNames additional class names you want to associate
	 *  with the anchor element
	 * @return string class name for use with HTML element
	 */
	public static function anchorClass( $modifier = '', $additionalClassNames = '' ) {
		return self::semanticClass( 'mw-ui-anchor', $modifier, $additionalClassNames );
	}

	/**
	 * Mark some html as being content
	 * @param string $html HTML content
	 * @param string $className additional class names
	 * @return string of html
	 */
	public static function contentElement( $html, $className = '' ) {
		$templateParser = new TemplateParser( __DIR__ . '/templates' );
		return $templateParser->processTemplate( 'ContentBox', [
			'className' => $className,
			'html' => $html,
		] );
	}
}
