<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */
namespace MediaWiki\Minerva\Permissions;

use Config;
use ConfigException;
use ContentHandler;
use MediaWiki\Minerva\LanguagesHelper;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Minerva\SkinOptions;
use Title;
use User;

/**
 * A wrapper for all available Minerva permissions.
 */
final class MinervaPagePermissions implements IMinervaPagePermissions {

	/**
	 * @var Title Current page title
	 */
	private $title;
	/**
	 * @var Config Extension config
	 */
	private $config;

	/**
	 * @var User user object
	 */
	private $user;

	/**
	 * @var ContentHandler
	 */
	private $contentHandler;

	/**
	 * @var SkinOptions Minerva skin options
	 */
	private $skinOptions;

	/**
	 * @var LanguagesHelper
	 */
	private $languagesHelper;

  /**
   * @var PermissionManager MediaWiki Core PermissionManager
   */
  private $permissionManager;

	/**
	 * Initialize internal Minerva Permissions system
	 * @param Title $title Current page title
	 * @param Config $config Minerva config
	 * @param User $user Currently logged in user
	 * @param SkinOptions $skinOptions Skin options`
	 * @param ContentHandler $contentHandler
	 * @param LanguagesHelper $languagesHelper
	 * @param PermissionManager $permissionManager
	 */
	public function __construct(
		Title $title,
		Config $config,
		User $user,
		SkinOptions $skinOptions,
		ContentHandler $contentHandler,
		LanguagesHelper $languagesHelper,
		PermissionManager $permissionManager
	) {
		$this->title = $title;
		$this->config = $config;
		$this->user = $user;
		$this->skinOptions = $skinOptions;
		$this->contentHandler = $contentHandler;
		$this->languagesHelper = $languagesHelper;
		$this->permissionManager = $permissionManager;
	}

	/**
	 * Gets whether or not the action is allowed.
	 *
	 * Actions isn't allowed when:
	 * <ul>
	 *   <li>
	 *     the action is disabled (by removing it from the <code>MinervaPageActions</code>
	 *     configuration variable; or
	 *   </li>
	 *   <li>the user is on the main page</li>
	 * </ul>
	 *
	 * The "edit" action is not allowed if editing is not possible on the page
	 * @see method isCurrentPageContentModelEditable
	 *
	 * The "switch-language" is allowed if there are interlanguage links on the page,
	 * or <code>$wgMinervaAlwaysShowLanguageButton</code> is truthy.
	 *
	 * @inheritDoc
	 * @throws ConfigException
	 */
	public function isAllowed( $action ) {
		global $wgHideInterlanguageLinks;

		// T206406: Enable "Talk" or "Discussion" button on Main page, also, not forgetting
		// the "switch-language" button. But disable "edit" and "watch" actions.
		if ( $this->title->isMainPage() ) {
			if ( !in_array( $action, $this->config->get( 'MinervaPageActions' ) ) ) {
				return false;
			}
			if ( $action === self::SWITCH_LANGUAGE ) {
				return !$wgHideInterlanguageLinks;
			}
			return $action === self::TALK;
		}

		if ( $action === self::HISTORY && $this->title->exists() ) {
			return $this->skinOptions->get( SkinOptions::HISTORY_IN_PAGE_ACTIONS );
		}

		if ( $action === SkinOptions::TOOLBAR_SUBMENU ) {
			return $this->skinOptions->get( SkinOptions::TOOLBAR_SUBMENU );
		}

		if ( $action === self::EDIT_OR_CREATE ) {
			return $this->canEditOrCreate();
		}

		if ( !in_array( $action, $this->config->get( 'MinervaPageActions' ) ) ) {
			return false;
		}

		if ( $action === self::CONTENT_EDIT ) {
			return $this->isCurrentPageContentModelEditable();
		}

		if ( $action === self::WATCH ) {
			return $this->title->isWatchable()
				? $this->user->isAllowedAll( 'viewmywatchlist', 'editmywatchlist' )
				: false;
		}

		if ( $action === self::SWITCH_LANGUAGE ) {
			if ( $wgHideInterlanguageLinks ) {
				return false;
			}
			return $this->languagesHelper->doesTitleHasLanguagesOrVariants( $this->title ) ||
				$this->config->get( 'MinervaAlwaysShowLanguageButton' );
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function isTalkAllowed() {
		return $this->isAllowed( self::TALK ) &&
			   ( $this->title->isTalkPage() || $this->title->canHaveTalkPage() ) &&
			   $this->user->isLoggedIn();
	}

	/**
	 * Checks whether the editor can handle the existing content handler type.
	 *
	 * @return bool
	 */
	protected function isCurrentPageContentModelEditable() {
		return $this->contentHandler->supportsDirectEditing()
			&& $this->contentHandler->supportsDirectApiEditing();
	}

	/**
	 * Returns true if $title page exists and is editable or is creatable by $user as determined by
	 * quick checks.
	 * @return bool
	 */
	private function canEditOrCreate() {
		$userQuickEditCheck =
			$this->permissionManager->userCan(
				'edit', $this->user, $this->title, PermissionManager::RIGOR_QUICK
			) && (
				$this->title->exists() ||
				$this->permissionManager->userCan(
					'create', $this->user, $this->title, PermissionManager::RIGOR_QUICK
				)
			);
		$blocked = $this->user->isAnon() ? false : $this->permissionManager->isBlockedFrom(
			$this->user, $this->title, true
		);
		return $this->isCurrentPageContentModelEditable() && $userQuickEditCheck && !$blocked;
	}
}
