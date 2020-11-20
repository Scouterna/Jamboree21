<?php
class MassEditRegexAPI {
	public static function edit( $pageid, $search, $replace, $summary ) {
		$massEditRegex = new MassEditRegex( $search, $replace, $summary );

		$user = User::newFromSession();

		// Check permissions
		if ( !$user->isAllowed( 'masseditregex' ) ) {
			return json_encode( [
				'error' => mfMessage( 'masseditregex-noaccess' )->text()
			] );
		}

		// Show a message if the database is in read-only mode
		if ( wfReadOnly() ) {
			return json_encode( [
				'error' => mfMessage( 'masseditregex-readonlydb' )->text()
			] );
		}

		// If user is blocked, s/he doesn't need to access this page
		if ( $user->isBlocked() ) {
			return json_encode( [
				'error' => mfMessage( 'masseditregex-blocked' )->text()
			] );
		}

		return json_encode( [
			'changes' => $massEditRegex->editPage( Title::newFromID( $pageid ) )
		] );
	}
}
