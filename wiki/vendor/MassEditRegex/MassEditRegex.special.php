<?php

/**
 * Allow users in the Bot group to edit many articles in one go by applying
 * regular expressions to a list of pages.
 *
 * @file
 * @ingroup SpecialPage
 *
 * @link https://www.mediawiki.org/wiki/Extension:MassEditRegex Documentation
 *
 * @author Adam Nielsen <malvineous@shikadi.net>
 * @copyright Copyright © 2009,2013 Adam Nielsen
 * @license GPL-2.0-or-later
 */

use MediaWiki\MediaWikiServices;

/// Maximum number of pages/diffs to display when previewing the changes
define( 'MER_MAX_PREVIEW_DIFFS', 20 );

/// Maximum number of pages to edit.
define( 'MER_MAX_EXECUTE_PAGES', 1000 );

/** Main class that define a new special page */
class MassEditRegexSpecialPage extends SpecialPage {
	/**
	 * @var string[]|null User-supplied page titles
	 */
	private $aPageList;

	/**
	 * @var string|null Type of titles (categories, backlinks, etc.)
	 */
	private $strPageListType;

	/**
	 * @var string|null Match regex from form
	 */
	private $strMatch;

	/**
	 * @var string|null Substitution regex from form
	 */
	private $strReplace;

	/**
	 * @var bool|null Is the client-side checkbox ticked?
	 */
	private $isClientSide;

	/**
	 * @var Skin|null
	 */
	private $sk;

	/**
	 * @var \MassEditRegex|null
	 */
	private $massEditRegex;

	function __construct() {
		parent::__construct( 'MassEditRegex', 'masseditregex' );
	}

	public function doesWrites() {
		return true;
	}

	/// Perform the regex process.

	/**
	 * @param bool $isPreview
	 *   true to generate diffs, false to perform page edits.
	 */
	public function perform( $isPreview ) {
		$out = $this->getOutput();
		$getuser = $this->getUser();
		$pageCountLimit = $isPreview ? MER_MAX_PREVIEW_DIFFS : MER_MAX_EXECUTE_PAGES;
		$errors = [];

		$htmlDiff = '';

		if ( $isPreview ) {
			$this->massEditRegex->getDiffEngine()->showDiffStyle();
		} else {
			$out->addHTML( '<ul>' );
		}

		$iArticleCount = 0;
		try {
			foreach ( $this->aPageList as $pageTitle ) {
				$titleArray = [];
				switch ( $this->strPageListType ) {
					case 'pagenames': // Can do this in one hit
						$t = Title::newFromText( $pageTitle );
						if ( !$t || !$this->editPage( $t, $isPreview, $htmlDiff ) ) {
							$errors[] = $this->msg( 'masseditregex-page-not-exists',
								$pageTitle )->escaped();
						}
						$iArticleCount++;
						break;

					case 'pagename-prefixes':
						$prefixSearch = new StringPrefixSearch;
						$titles = $prefixSearch->search( $pageTitle,
							$pageCountLimit - $iArticleCount );
						if ( empty( $titles ) ) {
							$errors[] = $this->msg( 'masseditregex-exprnomatch',
								$pageTitle )->escaped();
							$iArticleCount++;
							break;
						}

						foreach ( $titles as $title ) {
							$t = Title::newFromText( $title );
							if ( !$t ) {
								$errors[] = $this->msg( 'masseditregex-page-not-exists', $title )->escaped();
							} else {
								$titleArray[] = $t;
							}
						}
						break;

					case 'categories':
						$cat = Category::newFromName( $pageTitle );
						if ( $cat === false ) {
							$errors[] = $this->msg( 'masseditregex-page-not-exists',
								$pageTitle )->escaped();
							break;
						}
						$titleArray = $cat->getMembers( $pageCountLimit - $iArticleCount );
						break;

					case 'backlinks':
						$t = Title::newFromText( $pageTitle );
						if ( !$t ) {
							if ( $isPreview ) {
								$errors[] = $this->msg( 'masseditregex-page-not-exists',
									$pageTitle )->escaped();
							}
							break;
						}
						$blc = $t->getBacklinkCache();
						if ( $t->getNamespace() == NS_TEMPLATE ) {
							// Backlinks for Template pages are in a different table
							$table = 'templatelinks';
						} else {
							$table = 'pagelinks';
						}
						$titleArray = $blc->getLinks( $table, false, false,
							$pageCountLimit - $iArticleCount );
						break;
				}

				// If the above switch produced an array of pages, run through them now
				foreach ( $titleArray as $target ) {
					if ( !$this->editPage( $target, $isPreview, $htmlDiff ) ) {
						$errors[] = $this->msg( 'masseditregex-page-not-exists',
							$target->getPrefixedText() )->escaped();
					}
					$iArticleCount++;
					if ( $iArticleCount >= $pageCountLimit ) {
						$htmlDiff .= Xml::element( 'p', null,
							$this->msg( 'masseditregex-max-preview-diffs' )
								->numParams( $pageCountLimit )
								->text()
						);
						break;
					}
				}

			}
		} catch ( MWException $e ) {
			$errors[] = htmlspecialchars( $e );

			// Force a preview if there was a bad regex
			if ( !$isPreview ) {
				$out->addHTML( '</ul>' );
			}
			$isPreview = true;
		}

		if ( !$isPreview ) {
			$out->addHTML( '</ul>' );
		}

		if ( ( $iArticleCount == 0 ) && empty( $errors ) ) {
			$errors[] = $this->msg( 'masseditregex-err-nopages' )->escaped();
			// Force a preview if there was nothing to do
			$isPreview = true;
		}

		if ( !empty( $errors ) ) {
			$out->addHTML( '<div class="errorbox">' );
			$out->addHTML( $this->msg( 'masseditregex-editfailed' )->escaped() );

			$out->addHTML( '<ul><li>' );
			$out->addHTML( implode( '</li><li> ', $errors ) );
			$out->addHTML( '</li></ul></div>' );
		}

		if ( $isPreview ) {
			// Show the form again ready for further editing if we're just previewing
			$this->showForm();

			// Show the diffs now (after any errors)
			$out->addHTML( '<hr style="margin: 1em;"/>' );
			$out->addHTML( $htmlDiff );
		} else {
			$out->addWikiMsg( 'masseditregex-num-articles-changed', $iArticleCount );
			$out->addHTML(
				Linker::linkKnown(
					SpecialPage::getSafeTitleFor( 'Contributions', $getuser->getName() ),
					$this->msg( 'masseditregex-view-full-summary' )->escaped()
				)
			);
		}
	}

	/**
	 * Display the special page, and run the regexes if a form is being submitted
	 * @param string|null $par
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$getuser = $this->getUser();
		$out->addModules( 'MassEditRegex' );

		$this->setHeaders();

		// Check permissions
		if ( !$getuser->isAllowed( 'masseditregex' ) ) {
			$this->displayRestrictionError();
			return;
		}

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// If user is blocked, s/he doesn't need to access this page
		if ( $getuser->isBlocked() ) {
			throw new UserBlockedError( $getuser->getBlock() );
		}

		$this->outputHeader();

		$request = $this->getRequest();
		$strPageList = $request->getText( 'wpPageList', 'Sandbox' );
		$this->aPageList = explode( "\n", trim( $strPageList ) );
		$this->strPageListType = $request->getText( 'wpPageListType', 'pagenames' );

		$this->sk = $this->getSkin();

		$this->strMatch = $request->getText( 'wpMatch', '/hello (.*)\n/' );

		$this->strReplace = $request->getText( 'wpReplace', 'goodbye $1' );

		$summary = $request->getText( 'wpSummary', '' );
		$this->isClientSide = $request->getVal( 'wpClientSide', false ) == 1;

		$this->massEditRegex = new MassEditRegex(
			$this->strMatch, $this->strReplace, $summary, $getuser
		);

		if ( $request->wasPosted() ) {
			$this->perform( !$request->getCheck( 'wpSave' ) );
		} else {
			$this->showForm();
			$this->showHints();
		}
	}

	/**
	 * Display the form requesting the regexes from the user.
	 */
	function showForm() {
		$out = $this->getOutput();
		$getuser = $this->getUser();

		$out->addWikiMsg( 'masseditregextext' );
		$titleObj = SpecialPage::getTitleFor( 'MassEditRegex' );

		$out->addHTML(
			Xml::openElement( 'form', [
				'id' => 'masseditregex',
				'method' => 'post',
				'action' => $titleObj->getLocalURL( 'action=submit' )
			] ) .
			Xml::element( 'p',
				null, $this->msg( 'masseditregex-pagelisttxt' )->text()
			) .
			Xml::textarea(
				'wpPageList',
				implode( "\n", $this->aPageList )
			) .
			Xml::element( 'span',
				null, $this->msg( 'masseditregex-listtype-intro' )->text()
			) .
			Xml::openElement( 'ul', [
				'style' => 'list-style: none' // don't want any bullets for radio btns
			] )
		);

		// Generate HTML for the radio buttons (one for each list type)
		foreach ( [ 'pagenames', 'pagename-prefixes', 'categories', 'backlinks' ]
			as $strValue ) {

			// Have to use openElement because putting an Xml::xxx return value
			// inside an Xml::element causes the HTML code to be escaped and appear
			// on the page.
			$out->addHTML(
				Xml::openElement( 'li' ) .
				// Give grep a chance to find the usages:
				// masseditregex-listtype-pagenames, masseditregex-listtype-pagename-prefixes,
				// masseditregex-listtype-categories, masseditregex-listtype-backlinks
				Xml::radioLabel(
					$this->msg( 'masseditregex-listtype-' . $strValue )->text(),
					'wpPageListType',
					$strValue,
					'masseditregex-radio-' . $strValue,
					$strValue == $this->strPageListType
				) .
				Xml::closeElement( 'li' )
			);
		}
		$out->addHTML(
			Xml::closeElement( 'ul' ) .

			// Display the textareas for the regex and replacement to go into

			// Can't use Xml::buildTable because we need to put code into the table
			Xml::openElement( 'table', [
				'style' => 'width: 100%'
			] ) .
				Xml::openElement( 'tr' ) .
					Xml::openElement( 'td' ) .
						Xml::element( 'p', null, $this->msg( 'masseditregex-matchtxt' )->text() ) .
						Xml::textarea(
							'wpMatch',
							$this->strMatch  // use original value
						) .
						Xml::closeElement( 'textarea' ) .
					Xml::closeElement( 'td' ) .
					Xml::openElement( 'td' ) .
						Xml::element( 'p', null, $this->msg( 'masseditregex-replacetxt' )->text() ) .
						Xml::textarea(
							'wpReplace',
							$this->strReplace  // use original value
						) .
						Xml::closeElement( 'textarea' ) .
					Xml::closeElement( 'td' ) .
					Xml::closeElement( 'tr' ) .
			Xml::closeElement( 'table' ) .

			Xml::openElement( 'div', [
				'class' => 'editOptions',
				'style' => 'margin: 1ex;'
			] ) .

			// Display the edit summary and preview

			Xml::tags( 'span',
				[
					'class' => 'mw-summary',
					'id' => 'wpSummaryLabel'
				],
				Xml::tags( 'label', [
					'for' => 'wpSummary'
				], $this->msg( 'summary' )->escaped() )
			) . ' ' .

			Xml::input( 'wpSummary',
				60,
				$this->massEditRegex->getSummary(),
				[
					'id' => 'wpSummary',
					'maxlength' => '200',
					'tabindex' => '1'
				]
			) .

			Xml::tags( 'div',
				[ 'class' => 'mw-summary-preview' ],
				$this->msg( 'summary-preview' )->parse() .
					Linker::commentBlock( $this->massEditRegex->getSummary() )
			) .
			Xml::closeElement( 'div' ) . // class=editOptions

			// Display the preview + execute buttons
			Xml::element( 'input', [
				'id' => 'wpSave',
				'name' => 'wpSave',
				'type' => 'submit',
				'value' => $this->msg( 'masseditregex-executebtn' )->text(),
				'accesskey' => $this->msg( 'accesskey-save' )->text(),
				'title' => $this->msg( 'masseditregex-tooltip-execute' )->text() .
					' [' . $this->msg( 'accesskey-save' )->text() . ']',
			] ) .

			Xml::element( 'input', [
				'id' => 'wpPreview',
				'name' => 'wpPreview',
				'type' => 'submit',
				'value' => $this->msg( 'showpreview' )->text(),
				'accesskey' => $this->msg( 'accesskey-preview' )->text(),
				'title' => $this->msg( 'tooltip-preview' )->text() .
					' [' . $this->msg( 'accesskey-preview' )->text() . ']',
			] ) .

			Xml::tags( 'span',
				[
					'style' => 'margin-left: 1em;'
				],
				Xml::checkLabel(
					$this->msg( 'masseditregex-js-clientside' )->text(),
					'wpClientSide',
					'wpClientSide',
					$this->isClientSide,
					[
						'title' => $this->msg( 'masseditregex-js-execution' )->text(),
					]
				)
			)
		);

		$out->addHTML( Xml::closeElement( 'form' ) );
		$out->addModules( 'MassEditRegex' );
	}

	/**
	 * Show a short table of regex examples.
	 */
	function showHints() {
		$out = $this->getOutput();
		$getuser = $this->getUser();

		$out->addHTML(
			Xml::element( 'p', null, $this->msg( 'masseditregex-hint-intro' )->text() )
		);
		$out->addHTML( Xml::buildTable(

			// Table rows (the hints)
			[
				[
					'/$/',
					'abc',
					$this->msg( 'masseditregex-hint-toappend' )->text()
				],
				[
					'/$/',
					'\\n[[Category:New]]',
					// Since we can't pass "rowspan=2" to the hint text above, we'll
					// have to display it again
					$this->msg( 'masseditregex-hint-toappend' )->text()
				],
				[
					'/{{OldTemplate}}/',
					'',
					$this->msg( 'masseditregex-hint-remove' )->text()
				],
				[
					'/\\[\\[Category:[^]]+\]\]/',
					'',
					$this->msg( 'masseditregex-hint-removecat' )->text()
				],
				[
					'/(\\[\\[[^]]*\\|[^]]*)AAA(.*\\]\\])/',
					'$1BBB$2',
					$this->msg( 'masseditregex-hint-renamelink' )->text()
				],
			],

			// Table attributes
			[
				'class' => 'wikitable'
			],

			// Table headings
			[
				$this->msg( 'masseditregex-hint-headmatch' )->text(), // really needs width 12em
				$this->msg( 'masseditregex-hint-headreplace' )->text(), // really needs width 12em
				$this->msg( 'masseditregex-hint-headeffect' )->text()
			]

		) ); // Xml::buildTable
	}

	/**
	 * @param SkinTemplate $sktemplate
	 * @param array[][] &$links
	 *
	 * @return true
	 */
	public static function efSkinTemplateNavigationUniversal( $sktemplate, &$links ) {
		$title = $sktemplate->getTitle();
		$ns = $title->getNamespace();

		if ( !$sktemplate->getUser()->isAllowed( 'masseditregex' ) ) {
			return true;
		}

		if ( $ns == NS_CATEGORY ) {
			$url = SpecialPage::getTitleFor( 'MassEditRegex' )->getLocalURL(
				[
					'wpPageList' => $title->getText(),
					'wpPageListType' => 'categories',
				]
			);
		} elseif (
			( $ns == NS_SPECIAL )
			&& ( $title->isSpecial( 'Whatlinkshere' ) )
		) {
			if ( class_exists( 'MediaWiki\Special\SpecialPageFactory' ) ) {
				// MW 1.32+
				$titleParts = MediaWikiServices::getInstance()
					->getSpecialPageFactory()
					->resolveAlias( $title->getText() );
			} else {
				$titleParts = SpecialPageFactory::resolveAlias( $title->getText() );
			}

			$url = SpecialPage::getTitleFor( 'MassEditRegex' )->getLocalURL(
				[
					'wpPageList' => $titleParts[1],
					'wpPageListType' => 'backlinks',
				]
			);
		} else {
			// No tab
			return true;
		}

		$links['views']['masseditregex'] = [
			'class' => false,
			'text' => wfMessage( 'masseditregex-editall' )->text(),
			'href' => $url,
			'context' => 'main',
		];
		return true;
	}

	/**
	 * @param BaseTemplate $tpl
	 * @param array &$toolbox
	 *
	 * @return true
	 */
	public static function efBaseTemplateToolbox( $tpl, &$toolbox ) {
		global $wgTitle;
		if ( !$wgTitle->isSpecial( 'MassEditRegex' ) ) {
			return true;
		}

		// Hide the 'printable version' link as the shortcut key conflicts with
		// the preview button.
		unset( $toolbox['print'] );
		return true;
	}

	/**
	 * Call MassEditRegex::editPage() or MassEditRegex::previewPage()
	 * @param $title
	 * @param $isPreview
	 * @param $htmlDiff
	 * @deprecated this is just a wrapper function for legacy code, do not use.
	 *   Instead use editPage or previewPage in MassEditRegex
	 * @return bool
	 */
	private function editPage( $title, $isPreview, &$htmlDiff ) {
		$out = $this->getOutput();
		$getuser = $this->getUser();
		try {
			if ( $isPreview ) {
				$htmlDiff .= $this->massEditRegex->previewPage( $title );
			} else {
				$changeCount = $this->massEditRegex->editPage( $title );
				$out->addHTML( '<li>' . $this->msg( 'masseditregex-num-changes',
					$title->getPrefixedText(), $changeCount )->escaped() . '</li>' );
			}
			return true;
		} catch ( Exception $e ) {
			wfDebug( $e->getMessage() );
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'pagetools';
	}
}
