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

namespace MediaWiki\Minerva\Menu\Main;

use FatalError;
use Hooks;
use MWException;
use User;
use MediaWiki\Minerva\Menu\Definitions;
use MediaWiki\Minerva\Menu\Group;

/**
 * Used to build default (available for everyone by default) main menu
 */
final class DefaultMainMenuBuilder implements IMainMenuBuilder {

	/**
	 * @var bool
	 */
	private $showMobileOptions;

	/**
	 * Currently logged in user
	 * @var User
	 */
	private $user;

	/**
	 * @var Definitions
	 */
	private $definitions;

	/**
	 * Initialize the Default Main Menu builder
	 *
	 * @param bool $showMobileOptions Show MobileOptions instead of Preferences
	 * @param User $user The current user
	 * @param Definitions $definitions A menu items definitions set
	 */
	public function __construct( $showMobileOptions, User $user, Definitions $definitions ) {
		$this->showMobileOptions = $showMobileOptions;
		$this->user = $user;
		$this->definitions = $definitions;
	}

	/**
	 * @inheritDoc
	 * @throws FatalError
	 * @throws MWException
	 */
	public function getGroups(): array {
		return [
			BuilderUtil::getDiscoveryTools( $this->definitions ),
			$this->getPersonalTools(),
			BuilderUtil::getConfigurationTools( $this->definitions, $this->showMobileOptions ),
		];
	}

	/**
	 * @inheritDoc
	 * @throws MWException
	 */
	public function getSiteLinks(): Group {
		return BuilderUtil::getSiteLinks( $this->definitions );
	}

	/**
	 * Builds the personal tools menu item group.
	 *
	 * ... by adding the Watchlist, Settings, and Log{in,out} menu items in the given order.
	 *
	 * @return Group
	 * @throws FatalError
	 * @throws MWException
	 */
	private function getPersonalTools(): Group {
		$group = new Group();

		$this->definitions->insertAuthMenuItem( $group );

		if ( $this->user->isLoggedIn() ) {
			$this->definitions->insertWatchlistMenuItem( $group );
			$this->definitions->insertContributionsMenuItem( $group );
		}

		// Allow other extensions to add or override tools
		Hooks::run( 'MobileMenu', [ 'personal', &$group ] );
		return $group;
	}
}
