<?php

namespace MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor;

class SyncAllGroups extends Base {

	/**
	 * Reads out the attribute that holds the user groups and applies them to the local user object
	 */
	public function run() {
		$groupsAttributeName = $this->config->get( 'SyncAllGroups_GroupAttributeName' );
		$locallyManagedGroups = $this->config->get( 'SyncAllGroups_LocallyManaged' );
		$groupModificationCallback = $this->config->get( 'SyncAllGroups_GroupNameModificationCallback' );
		$delimiter = $this->config->get( 'GroupAttributeDelimiter' );

		$samlGroups = $this->attributes[$groupsAttributeName];
		if ( $delimiter !== null ) {
			$samlGroups = explode( $delimiter, $samlGroups[0] );
		}

		$samlGroups = array_map( 'trim', $samlGroups );

		if ( is_callable( $groupModificationCallback ) ) {
			$samlGroups = array_map( $groupModificationCallback, $samlGroups );
		}

		$locallyManagedGroups = array_map( 'trim', $locallyManagedGroups );

		$currentGroups = $this->user->getGroups();
		$groupsToAdd = array_diff( $samlGroups, $currentGroups );
		foreach ( $groupsToAdd as $groupToAdd ) {
			if ( in_array( $groupToAdd, $locallyManagedGroups ) ) {
				continue;
			}
			$this->user->addGroup( $groupToAdd );
		}

		$groupsToRemove = array_diff( $currentGroups, $samlGroups );
		foreach ( $groupsToRemove as $groupToRemove ) {
			if ( in_array( $groupToRemove, $locallyManagedGroups ) ) {
				continue;
			}
			$this->user->removeGroup( $groupToRemove );
		}
	}

}
