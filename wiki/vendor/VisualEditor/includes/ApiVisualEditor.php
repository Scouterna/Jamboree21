<?php
/**
 * Parsoid/RESTBase+MediaWiki API wrapper.
 *
 * @file
 * @ingroup Extensions
 * @copyright 2011-2019 VisualEditor Team and others; see AUTHORS.txt
 * @license MIT
 */

use MediaWiki\Block\DatabaseBlock;
use MediaWiki\MediaWikiServices;
use MediaWiki\Logger\LoggerFactory;

class ApiVisualEditor extends ApiBase {

	use ApiBlockInfoTrait;

	/**
	 * @var Config
	 */
	protected $veConfig;

	/**
	 * @var VirtualRESTServiceClient
	 */
	protected $serviceClient;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * @inheritDoc
	 */
	public function __construct( ApiMain $main, $name, Config $config ) {
		parent::__construct( $main, $name );
		$this->veConfig = $config;
		$this->serviceClient = new VirtualRESTServiceClient( new MultiHttpClient( [] ) );
		$this->logger = LoggerFactory::getInstance( 'VisualEditor' );
	}

	/**
	 * Creates the virtual REST service object to be used in VE's API calls. The
	 * method determines whether to instantiate a ParsoidVirtualRESTService or a
	 * RestbaseVirtualRESTService object based on configuration directives: if
	 * $wgVirtualRestConfig['modules']['restbase'] is defined, RESTBase is chosen,
	 * otherwise Parsoid is used (either by using the MW Core config, or the
	 * VE-local one).
	 *
	 * @return VirtualRESTService the VirtualRESTService object to use
	 */
	protected function getVRSObject() {
		// the params array to create the service object with
		$params = [];
		// the VRS class to use, defaults to Parsoid
		$class = ParsoidVirtualRESTService::class;
		// The global virtual rest service config object, if any
		$vrs = $this->getConfig()->get( 'VirtualRestConfig' );
		if ( isset( $vrs['modules'] ) && isset( $vrs['modules']['restbase'] ) ) {
			// if restbase is available, use it
			$params = $vrs['modules']['restbase'];
			// backward compatibility
			$params['parsoidCompat'] = false;
			$class = RestbaseVirtualRESTService::class;
		} elseif ( isset( $vrs['modules'] ) && isset( $vrs['modules']['parsoid'] ) ) {
			// there's a global parsoid config, use it next
			$params = $vrs['modules']['parsoid'];
			$params['restbaseCompat'] = true;
		} else {
			// No global modules defined, so no way to contact the document server.
			$this->dieWithError( 'apierror-visualeditor-docserver-unconfigured', 'no_vrs' );
		}
		// merge the global and service-specific params
		if ( isset( $vrs['global'] ) ) {
			$params = array_merge( $vrs['global'], $params );
		}
		// set up cookie forwarding
		if ( $params['forwardCookies'] ) {
			$params['forwardCookies'] = $this->getRequest()->getHeader( 'Cookie' );
		} else {
			$params['forwardCookies'] = false;
		}
		// create the VRS object and return it
		return new $class( $params );
	}

	/**
	 * Accessor function for all RESTbase requests
	 *
	 * @param Title $title The title of the page to use as the parsing context
	 * @param string $method The HTTP method, either 'GET' or 'POST'
	 * @param string $path The RESTbase api path
	 * @param array $params Request parameters
	 * @param array $reqheaders Request headers
	 * @return array The RESTbase server's response, 'code', 'reason', 'headers' and 'body'
	 */
	protected function requestRestbase( Title $title, $method, $path, $params, $reqheaders = [] ) {
		global $wgVersion;
		$request = [
			'method' => $method,
			'url' => '/restbase/local/v1/' . $path
		];
		if ( $method === 'GET' ) {
			$request['query'] = $params;
		} else {
			$request['body'] = $params;
		}
		// Should be synchronised with modules/ve-mw/init/ve.init.mw.ArticleTargetLoader.js
		$reqheaders['Accept'] = 'text/html; charset=utf-8;' .
			' profile="https://www.mediawiki.org/wiki/Specs/HTML/2.0.0"';
		$reqheaders['Accept-Language'] = self::getPageLanguage( $title )->getCode();
		$reqheaders['User-Agent'] = 'VisualEditor-MediaWiki/' . $wgVersion;
		$reqheaders['Api-User-Agent'] = 'VisualEditor-MediaWiki/' . $wgVersion;
		$request['headers'] = $reqheaders;
		$response = $this->serviceClient->run( $request );
		if ( $response['code'] === 200 && $response['error'] === "" ) {
			// If response was served directly from Varnish, use the response
			// (RP) header to declare the cache hit and pass the data to the client.
			$headers = $response['headers'];
			$rp = null;
			if ( isset( $headers['x-cache'] ) && strpos( $headers['x-cache'], 'hit' ) !== false ) {
				$rp = 'cached-response=true';
			}
			if ( $rp !== null ) {
				$resp = $this->getRequest()->response();
				$resp->header( 'X-Cache: ' . $rp );
			}
		} elseif ( $response['error'] !== '' ) {
			$this->dieWithError(
				[ 'apierror-visualeditor-docserver-http-error', wfEscapeWikiText( $response['error'] ) ],
				'apierror-visualeditor-docserver-http-error'
			);
		} else {
			// error null, code not 200
			$this->logger->warning(
				__METHOD__ . ": Received HTTP {code} from RESTBase",
				[
					'code' => $response['code'],
					'trace' => ( new Exception )->getTraceAsString(),
					'response' => $response['body'],
					'requestPath' => $path,
					'requestIfMatch' => $reqheaders['If-Match'] ?? '',
				]
			);
			$this->dieWithError(
				[ 'apierror-visualeditor-docserver-http', $response['code'] ],
				'apierror-visualeditor-docserver-http'
			);
		}
		return $response;
	}

	/**
	 * Run wikitext through the parser's Pre-Save-Transform
	 *
	 * @param Title $title The title of the page to use as the parsing context
	 * @param string $wikitext The wikitext to transform
	 * @return string The transformed wikitext
	 */
	protected function pstWikitext( Title $title, $wikitext ) {
		return ContentHandler::makeContent( $wikitext, $title, CONTENT_MODEL_WIKITEXT )
			->preSaveTransform(
				$title,
				$this->getUser(),
				WikiPage::factory( $title )->makeParserOptions( $this->getContext() )
			)
			->serialize( 'text/x-wiki' );
	}

	/**
	 * Provide the RESTbase-parsed HTML of a given fragment of wikitext
	 *
	 * @param Title $title The title of the page to use as the parsing context
	 * @param string $wikitext The wikitext fragment to parse
	 * @param bool $bodyOnly Whether to provide only the contents of the `<body>` tag
	 * @param int|null $oldid What oldid revision, if any, to base the request from (default: `null`)
	 * @param bool $stash Whether to stash the result in the server-side cache (default: `false`)
	 * @return array The RESTbase server's response, 'code', 'reason', 'headers' and 'body'
	 */
	protected function parseWikitextFragment(
		Title $title, $wikitext, $bodyOnly, $oldid = null, $stash = false
	) {
		return $this->requestRestbase(
			$title,
			'POST',
			'transform/wikitext/to/html/' . urlencode( $title->getPrefixedDBkey() ) .
				( $oldid === null ? '' : '/' . $oldid ),
			[
				'wikitext' => $wikitext,
				'body_only' => $bodyOnly ? 1 : 0,
				'stash' => $stash ? 1 : 0
			]
		);
	}

	/**
	 * Provide the preload content for a page being created from another page
	 *
	 * @param string $preload The title of the page to use as the preload content
	 * @param string[] $params The preloadTransform parameters to pass in, if any
	 * @param Title $contextTitle The contextual page title against which to parse the preload
	 * @param bool $parse Whether to parse the preload content
	 * @return string The parsed content
	 */
	protected function getPreloadContent( $preload, $params, Title $contextTitle, $parse = false ) {
		$content = '';
		$preloadTitle = Title::newFromText( $preload );
		// Check for existence to avoid getting MediaWiki:Noarticletext
		if ( $preloadTitle instanceof Title &&
			 $preloadTitle->exists() &&
			 $preloadTitle->userCan( 'read' )
		) {
			$preloadPage = WikiPage::factory( $preloadTitle );
			if ( $preloadPage->isRedirect() ) {
				$preloadTitle = $preloadPage->getRedirectTarget();
				$preloadPage = WikiPage::factory( $preloadTitle );
			}

			$content = $preloadPage->getContent( Revision::RAW );
			$parserOptions = ParserOptions::newFromUser( $this->getUser() );

			$content = $content->preloadTransform(
				$preloadTitle,
				$parserOptions,
				(array)$params
			)->serialize();

			if ( $parse ) {
				// We need to turn this transformed wikitext into parsoid html
				$content = $this->parseWikitextFragment( $contextTitle, $content, true )['body'];
			}
		}
		return $content;
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$this->serviceClient->mount( '/restbase/', $this->getVRSObject() );

		$user = $this->getUser();
		$params = $this->extractRequestParams();

		$title = Title::newFromText( $params['page'] );
		if ( $title && $title->isSpecial( 'CollabPad' ) ) {
			// Convert Special:CollabPad/MyPage to MyPage so we can parsefragment properly
			$title = SpecialCollabPad::getSubPage( $title );
		}
		if ( !$title ) {
			$this->dieWithError( [ 'apierror-invalidtitle', wfEscapeWikiText( $params['page'] ) ] );
		}

		$parserParams = [];
		if ( isset( $params['oldid'] ) ) {
			$parserParams['oldid'] = $params['oldid'];
		}

		wfDebugLog( 'visualeditor', "called on '$title' with paction: '{$params['paction']}'" );
		switch ( $params['paction'] ) {
			case 'parse':
			case 'wikitext':
			case 'metadata':
				// Dirty hack to provide the correct context for edit notices
				// FIXME Don't write to globals! Eww.
				global $wgTitle;
				$wgTitle = $title;
				RequestContext::getMain()->setTitle( $title );

				$preloaded = false;
				$restbaseHeaders = null;

				// Get information about current revision
				if ( $title->exists() ) {
					$latestRevision = Revision::newFromTitle( $title );
					if ( $latestRevision === null ) {
						$this->dieWithError( 'apierror-visualeditor-latestnotfound', 'latestnotfound' );
					}
					$revision = null;
					if ( !isset( $parserParams['oldid'] ) || $parserParams['oldid'] === 0 ) {
						$parserParams['oldid'] = $latestRevision->getId();
						$revision = $latestRevision;
					} else {
						$revision = Revision::newFromId( $parserParams['oldid'] );
						if ( $revision === null ) {
							$this->dieWithError( [ 'apierror-nosuchrevid', $parserParams['oldid'] ], 'oldidnotfound' );
						}
					}

					$restoring = $revision && !$revision->isCurrent();
					$baseTimestamp = $latestRevision->getTimestamp();
					$oldid = intval( $parserParams['oldid'] );

					// If requested, request HTML from Parsoid/RESTBase
					if ( $params['paction'] === 'parse' ) {
						$wikitext = $params['wikitext'] ?? null;
						if ( $wikitext !== null ) {
							$stash = $params['stash'];
							$section = $section = $params['section'] ?? null;
							if ( $params['pst'] ) {
								$wikitext = $this->pstWikitext( $title, $wikitext );
							}
							if ( $section !== null ) {
								$sectionContent = new WikitextContent( $wikitext );
								$page = WikiPage::factory( $title );
								$newSectionContent = $page->replaceSectionAtRev(
									$section, $sectionContent, '', $oldid
								);
								'@phan-var WikitextContent $newSectionContent';
								$wikitext = $newSectionContent->getText();
							}
							$response = $this->parseWikitextFragment(
								$title, $wikitext, false, $oldid, $stash
							);
							$content = $response['body'];
							$restbaseHeaders = $response['headers'];
						} else {
							$content = $this->requestRestbase(
								$title,
								'GET',
								'page/html/' . urlencode( $title->getPrefixedDBkey() ) . '/' . $oldid .
									'?redirect=false&stash=true',
								[]
							)['body'];
						}
						if ( $content === false ) {
							$this->dieWithError( 'apierror-visualeditor-docserver', 'docserver' );
						}
					} elseif ( $params['paction'] === 'wikitext' ) {
						$apiParams = [
							'action' => 'query',
							'revids' => $oldid,
							'prop' => 'revisions',
							'rvprop' => 'content|ids'
						];

						$section = $params['section'] ?? null;

						if ( $section === 'new' ) {
							$content = '';
							if ( !empty( $params['preload'] ) ) {
								$content = $this->getPreloadContent(
									$params['preload'], $params['preloadparams'], $title,
									$params['paction'] !== 'wikitext'
								);
								$preloaded = true;
							}
						} else {
							$apiParams['rvsection'] = $section;

							$api = new ApiMain(
								new DerivativeRequest(
									$this->getRequest(),
									$apiParams,
									/* was posted? */ false
								),
								/* enable write? */ true
							);
							$api->execute();
							$result = $api->getResult()->getResultData();
							$pid = $title->getArticleID();
							$content = false;
							if ( isset( $result['query']['pages'][$pid]['revisions'] ) ) {
								foreach ( $result['query']['pages'][$pid]['revisions'] as $revArr ) {
									// Check 'revisions' is an array (T193718)
									if ( is_array( $revArr ) && $revArr['revid'] === $oldid ) {
										$content = $revArr['content'];
									}
								}
							}
							if ( $content === false ) {
								$this->dieWithError( 'apierror-visualeditor-docserver', 'docserver' );
							}
						}
					}

				} else {
					$content = '';
					Hooks::run( 'EditFormPreloadText', [ &$content, &$title ] );
					if ( $content !== '' && $params['paction'] !== 'wikitext' ) {
						$content = $this->parseWikitextFragment( $title, $content, true )['body'];
					}
					if ( $content === '' && !empty( $params['preload'] ) ) {
						$content = $this->getPreloadContent(
							$params['preload'], $params['preloadparams'], $title,
							$params['paction'] !== 'wikitext'
						);
						$preloaded = true;
					}
					$baseTimestamp = wfTimestampNow();
					$oldid = 0;
					$restoring = false;
				}

				// Get edit notices
				$notices = $title->getEditNotices();

				// Anonymous user notice
				if ( $user->isAnon() ) {
					$notices[] = $this->msg(
						'anoneditwarning',
						// Log-in link
						'{{fullurl:Special:UserLogin|returnto={{FULLPAGENAMEE}}}}',
						// Sign-up link
						'{{fullurl:Special:UserLogin/signup|returnto={{FULLPAGENAMEE}}}}'
					)->parseAsBlock();
				}

				// From EditPage#showCustomIntro
				if ( $params['editintro'] ) {
					$eiTitle = Title::newFromText( $params['editintro'] );
					if ( $eiTitle instanceof Title && $eiTitle->exists() && $eiTitle->userCan( 'read' ) ) {
						global $wgParser;
						$notices[] = $wgParser->parse(
							'<div class="mw-editintro">{{:' . $eiTitle->getFullText() . '}}</div>',
							$title,
							new ParserOptions()
						)->getText();
					}
				}

				// Old revision notice
				if ( $restoring ) {
					$notices[] = $this->msg( 'editingold' )->parseAsBlock();
				}

				if ( wfReadOnly() ) {
					$notices[] = $this->msg( 'readonlywarning', wfReadOnlyReason() );
				}

				// New page notices
				if ( !$title->exists() ) {
					$notices[] = $this->msg(
						$user->isLoggedIn() ? 'newarticletext' : 'newarticletextanon',
						wfExpandUrl( Skin::makeInternalOrExternalUrl(
							$this->msg( 'helppage' )->inContentLanguage()->text()
						) )
					)->parseAsBlock();
					// Page protected from creation
					if ( $title->getRestrictions( 'create' ) ) {
						$notices[] = $this->msg( 'titleprotectedwarning' )->parseAsBlock();
					}
					// From EditPage#showIntro, checking if the page has previously been deleted:
					$dbr = wfGetDB( DB_REPLICA );
					LogEventsList::showLogExtract( $out, [ 'delete', 'move' ], $title,
						'',
						[
							'lim' => 10,
							'conds' => [ 'log_action != ' . $dbr->addQuotes( 'revision' ) ],
							'showIfEmpty' => false,
							'msgKey' => [ 'recreate-moveddeleted-warn' ]
						]
					);
					if ( $out ) {
						$notices[] = $out;
					}
				}

				// Look at protection status to set up notices + surface class(es)
				$protectedClasses = [];
				if ( MediaWikiServices::getInstance()->getPermissionManager()
						->getNamespaceRestrictionLevels( $title->getNamespace() ) !== [ '' ]
				) {
					// Page protected from editing
					if ( $title->isProtected( 'edit' ) ) {
						// Is the title semi-protected?
						if ( $title->isSemiProtected() ) {
							$protectedClasses[] = 'mw-textarea-sprotected';

							$noticeMsg = 'semiprotectedpagewarning';
						} else {
							$protectedClasses[] = 'mw-textarea-protected';

							// Then it must be protected based on static groups (regular)
							$noticeMsg = 'protectedpagewarning';
						}
						$notices[] = $this->msg( $noticeMsg )->parseAsBlock() .
						$this->getLastLogEntry( $title, 'protect' );
					}

					// Deal with cascading edit protection
					list( $sources, $restrictions ) = $title->getCascadeProtectionSources();
					if ( isset( $restrictions['edit'] ) ) {
						$protectedClasses[] = ' mw-textarea-cprotected';

						$notice = $this->msg( 'cascadeprotectedwarning' )->parseAsBlock() . '<ul>';
						// Unfortunately there's no nice way to get only the pages which cause
						// editing to be restricted
						foreach ( $sources as $source ) {
							$notice .= "<li>" .
								MediaWikiServices::getInstance()->getLinkRenderer()->makeLink( $source ) .
								"</li>";
						}
						$notice .= '</ul>';
						$notices[] = $notice;
					}
				}

				// Permission notice
				$permErrors = $title->getUserPermissionsErrors( 'create', $user, 'quick' );
				if ( $permErrors && !$title->exists() ) {
					$notices[] = $this->msg(
						'permissionserrorstext-withaction', 1, $this->msg( 'action-createpage' )
					) . "<br>" . call_user_func_array( [ $this, 'msg' ], $permErrors[0] )->parse();
				}

				// Show notice when editing user / user talk page of a user that doesn't exist
				// or who is blocked
				// HACK of course this code is partly duplicated from EditPage.php :(
				if ( $title->getNamespace() == NS_USER || $title->getNamespace() == NS_USER_TALK ) {
					$parts = explode( '/', $title->getText(), 2 );
					$targetUsername = $parts[0];
					$targetUser = User::newFromName(
						$targetUsername,
						/* allow IP users*/ false
					);
					$block = $targetUser->getBlock();

					if (
						!( $targetUser && $targetUser->isLoggedIn() ) &&
						!User::isIP( $targetUsername )
					) {
						// User does not exist
						$notices[] = "<div class=\"mw-userpage-userdoesnotexist error\">\n" .
							$this->msg( 'userpage-userdoesnotexist', wfEscapeWikiText( $targetUsername ) ) .
							"\n</div>";
					} elseif (
						!is_null( $block ) &&
						$block->getType() != DatabaseBlock::TYPE_AUTO &&
						( $block->isSitewide() || $targetUser->isBlockedFrom( $title ) )
					) {
						// Show log extract if the user is sitewide blocked or is partially
						// blocked and not allowed to edit their user page or user talk page
						$notices[] = $this->msg(
							'blocked-notice-logextract',
							// Support GENDER in notice
							$targetUser->getName()
						)->parseAsBlock() . $this->getLastLogEntry( $targetUser->getUserPage(), 'block' );
					}
				}

				$block = null;
				$blockinfo = null;
				$permissionManager = MediaWikiServices::getInstance()->getPermissionManager();
				// Blocked user notice
				if ( $user->isBlockedGlobally() ) {
					$block = $user->getGlobalBlock();
				} elseif ( $permissionManager->isBlockedFrom( $user, $title, true ) ) {
					$block = $user->getBlock();
				}
				if ( $block ) {
					$notices[] = [
						'type' => 'block',
						'message' => call_user_func_array(
							[ $this, 'msg' ],
							$block->getPermissionsError( $this->getContext() )
						)->parseAsBlock(),
					];

					$blockinfo = $this->getBlockDetails( $block );
				}

				// Simplified EditPage::getEditPermissionErrors()
				// Will be false e.g. if user is blocked or page is protected
				$canEdit = !(
					$title->getUserPermissionsErrors( 'edit', $user, 'full' ) ||
					( !$title->exists() && $title->getUserPermissionsErrors( 'create', $user, 'full' ) )
				);

				// HACK: Build a fake EditPage so we can get checkboxes from it
				// Deliberately omitting ,0 so oldid comes from request
				$article = new Article( $title );
				$editPage = new EditPage( $article );
				$req = $this->getRequest();
				$req->setVal( 'format', $editPage->contentFormat );
				// By reference for some reason (T54466)
				$editPage->importFormData( $req );
				$states = [
					'minor' => $user->getOption( 'minordefault' ) && $title->exists(),
					'watch' => $user->getOption( 'watchdefault' ) ||
						( $user->getOption( 'watchcreations' ) && !$title->exists() ) ||
						$user->isWatched( $title ),
				];
				$checkboxesDef = $editPage->getCheckboxesDefinition( $states );
				$checkboxesMessagesList = [];
				foreach ( $checkboxesDef as $name => &$options ) {
					if ( isset( $options['tooltip'] ) ) {
						$checkboxesMessagesList[] = "accesskey-{$options['tooltip']}";
						$checkboxesMessagesList[] = "tooltip-{$options['tooltip']}";
					}
					if ( isset( $options['title-message'] ) ) {
						$checkboxesMessagesList[] = $options['title-message'];
						if ( !is_string( $options['title-message'] ) ) {
							// Extract only the key. Any parameters are included in the fake message definition
							// passed via $checkboxesMessages. (This changes $checkboxesDef by reference.)
							$options['title-message'] = $this->msg( $options['title-message'] )->getKey();
						}
					}
					$checkboxesMessagesList[] = $options['label-message'];
					if ( !is_string( $options['label-message'] ) ) {
						// Extract only the key. Any parameters are included in the fake message definition
						// passed via $checkboxesMessages. (This changes $checkboxesDef by reference.)
						$options['label-message'] = $this->msg( $options['label-message'] )->getKey();
					}
				}
				$checkboxesMessages = [];
				foreach ( $checkboxesMessagesList as $messageSpecifier ) {
					// $messageSpecifier may be a string or a Message object
					$message = $this->msg( $messageSpecifier );
					$checkboxesMessages[ $message->getKey() ] = $message->plain();
				}

				foreach ( $checkboxesDef as &$value ) {
					// Don't convert the boolean to empty string with formatversion=1
					$value[ApiResult::META_BC_BOOLS] = [ 'default' ];
				}
				$result = [
					'result' => 'success',
					'notices' => $notices,
					'checkboxesDef' => $checkboxesDef,
					'checkboxesMessages' => $checkboxesMessages,
					'protectedClasses' => implode( ' ', $protectedClasses ),
					'basetimestamp' => $baseTimestamp,
					'starttimestamp' => wfTimestampNow(),
					'oldid' => $oldid,
					'blockinfo' => $blockinfo,
					'canEdit' => $canEdit,
				];
				if ( $restbaseHeaders ) {
					$result['etag'] = $restbaseHeaders['etag'];
				}
				if ( $params['paction'] === 'parse' ||
					 $params['paction'] === 'wikitext' ||
					 ( !empty( $params['preload'] ) && isset( $content ) )
				) {
					$result['content'] = $content;
					if ( $preloaded ) {
						// If the preload param was actually used, pass it
						// back so the caller knows. (It's not obvious to the
						// caller, because in some situations it'll depend on
						// whether the page has been created. They can work it
						// out from some of the other returns, but this is
						// simpler.)
						$result['preloaded'] = $params['preload'];
					}
				}
				break;

			case 'templatesused':
				// HACK: Build a fake EditPage so we can get checkboxes from it
				// Deliberately omitting ,0 so oldid comes from request
				$article = new Article( $title );
				$editPage = new EditPage( $article );
				$result = $editPage->makeTemplatesOnThisPageList( $editPage->getTemplates() );
				break;

			case 'parsedoc':
			case 'parsefragment':
				$wikitext = $params['wikitext'];
				$bodyOnly = ( $params['paction'] === 'parsefragment' );
				if ( $params['pst'] ) {
					$wikitext = $this->pstWikitext( $title, $wikitext );
				}
				$content = $this->parseWikitextFragment(
					$title, $wikitext, $bodyOnly
				)['body'];
				if ( $content === false ) {
					$this->dieWithError( 'apierror-visualeditor-docserver', 'docserver' );
				} else {
					$result = [
						'result' => 'success',
						'content' => $content
					];
				}
				break;
		}

		$this->getResult()->addValue( null, $this->getModuleName(), $result );
	}

	/**
	 * Check if the configured allowed namespaces include the specified namespace
	 *
	 * @param Config $config Configuration object
	 * @param int $namespaceId Namespace ID
	 * @return bool
	 */
	public static function isAllowedNamespace( Config $config, $namespaceId ) {
		$availableNamespaces = self::getAvailableNamespaceIds( $config );
		return in_array( $namespaceId, $availableNamespaces );
	}

	/**
	 * Get a list of allowed namespace IDs
	 *
	 * @param Config $config Configuration object
	 * @return array
	 */
	public static function getAvailableNamespaceIds( Config $config ) {
		$availableNamespaces =
			// Note: existing numeric keys might exist, and so array_merge cannot be used
			(array)$config->get( 'VisualEditorAvailableNamespaces' ) +
			(array)ExtensionRegistry::getInstance()->getAttribute( 'VisualEditorAvailableNamespaces' );
		return array_values( array_unique( array_map( function ( $namespace ) {
			// Convert canonical namespace names to IDs
			$nsInfo = MediaWikiServices::getInstance()->getNamespaceInfo();
			$idFromName = $nsInfo->getCanonicalIndex( strtolower( $namespace ) );
			if ( $idFromName !== null ) {
				return $idFromName;
			}
			// Allow namespaces to be specified by ID as well
			return $nsInfo->exists( $namespace ) ? $namespace : null;
		}, array_keys( array_filter( $availableNamespaces ) ) ) ) );
	}

	/**
	 * Check if the configured allowed content models include the specified content model
	 *
	 * @param Config $config Configuration object
	 * @param string $contentModel Content model ID
	 * @return bool
	 */
	public static function isAllowedContentType( Config $config, $contentModel ) {
		$availableContentModels = array_merge(
			ExtensionRegistry::getInstance()->getAttribute( 'VisualEditorAvailableContentModels' ),
			$config->get( 'VisualEditorAvailableContentModels' )
		);
		return isset( $availableContentModels[$contentModel] ) &&
			$availableContentModels[$contentModel];
	}

	/**
	 * Get the page language from a title, using the content language as fallback on special pages
	 * @param Title $title Title
	 * @return Language Content language
	 */
	public static function getPageLanguage( Title $title ) {
		if ( $title->isSpecial( 'CollabPad' ) ) {
			// Use the site language for CollabPad, as getPageLanguage just
			// returns the interface language for special pages.
			// TODO: Let the user change the document language on multi-lingual sites.
			return MediaWikiServices::getInstance()->getContentLanguage();
		} else {
			return $title->getPageLanguage();
		}
	}

	/**
	 * Gets the relevant HTML for the latest log entry on a given title, including a full log link.
	 *
	 * @param Title $title Title
	 * @param $types array|string
	 * @return string
	 */
	private function getLastLogEntry( Title $title, $types = '' ) {
		$outString = '';
		LogEventsList::showLogExtract( $outString, $types, $title, '',
			[ 'lim' => 1 ] );
		return $outString;
	}

	/**
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		return [
			'page' => [
				ApiBase::PARAM_REQUIRED => true,
			],
			'format' => [
				ApiBase::PARAM_DFLT => 'jsonfm',
				ApiBase::PARAM_TYPE => [ 'json', 'jsonfm' ],
			],
			'paction' => [
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => [
					'parse',
					'metadata',
					'templatesused',
					'wikitext',
					'parsefragment',
					'parsedoc',
				],
			],
			'wikitext' => null,
			'section' => null,
			'stash' => null,
			'oldid' => null,
			'editintro' => null,
			'pst' => false,
			'preload' => null,
			'preloadparams' => [
				ApiBase::PARAM_ISMULTI => true,
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function needsToken() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function mustBePosted() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function isInternal() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function isWriteMode() {
		return false;
	}
}
