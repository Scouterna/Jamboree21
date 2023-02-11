<?php
class PdfBookHooks {

	/**
	 * Register our pdfbook action and logging type
	 */
	public static function onRegistration() {
		global $wgActions, $wgLogTypes, $wgLogNames, $wgLogHeaders, $wgLogActions;
		$wgActions['pdfbook']     = 'PdfBookAction';
		$wgLogTypes[]             = 'pdf';
		$wgLogNames  ['pdf']      = 'pdflogpage';
		$wgLogHeaders['pdf']      = 'pdflogpagetext';
		$wgLogActions['pdf/book'] = 'pdflogentry';
	}

	/**
	 * Add PDF to actions tabs in skins
	 */
	public static function onSkinTemplateNavigation( $skin, &$actions ) {
		global $wgPdfBookTab;
		if ( $wgPdfBookTab && $skin->getUser()->isLoggedIn() ) {
			$actions['views']['pdfbook'] = [
				'class' => false,
				'text' => wfMessage( 'pdfbook-action' )->text(),
				'href' => self::actionLink( $skin )
			];
		}
		return true;
	}

	/**
	 * Get the URL for the action link
	 */
	public static function actionLink( $skin ) {
		$qs = 'action=pdfbook&format=single';
		foreach ( $_REQUEST as $k => $v ) {
			if ( $k != 'title' ) {
				$qs .= "&$k=$v";
			}
		}
		return $skin->getTitle()->getLocalURL( $qs );
	}
}
