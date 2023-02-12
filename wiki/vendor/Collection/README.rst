====================================
*Collection* Extension for MediaWiki
====================================

About the *Collection* Extension
================================

The *Collection* extension for MediaWiki_ allows users to collect articles and
generate downloadable version in different formats (PDF, OpenDocument Text etc.)
for article collections and single articles.

The extension has been developed for and tested with MediaWiki_
version 1.33 and later, due to the use of the OOjs UI widget
library and some MW API changes.

The extension is being developed under the GNU General Public License by
`PediaPress GmbH`_ in close collaboration with `Wikimedia Foundation`_
and the `Commonwealth of Learning`_.

Copyright (C) 2008-2018, PediaPress GmbH, Siebrand Mazeland, Marcin Cieślak,
the Wikimedia Foundation, and other contributors

Prerequisites
=============

Install PHP with cURL support
-----------------------------

Currently Collection extension needs PHP with cURL support,
see http://php.net/curl

Installation and Configuration of the Collection Extension
==========================================================

* For *MediaWiki* versions up to and including **1.24**:
  Download the Collection extension matching your *MediaWiki* version from
  https://www.mediawiki.org/wiki/Special:ExtensionDistributor/Collection
  and unpack it into your mediawiki extensions directory::

    cd /srv/http/wiki/extensions
    tar -xzf Collection-MW1.18-113990.tar.gz -C /var/www/mediawiki/extensions

* For *MediaWiki* versions **1.25wmf19 and newer**:
  You can checkout the newest code of the *Collection* extension
  from the Git repository into the ``extensions`` directory of your
  *MediaWiki* installation::

    cd extensions/
    git clone https://gerrit.wikimedia.org/r/mediawiki/extensions/Collection

* Put this line in your ``LocalSettings.php``::

    require_once("$IP/extensions/Collection/Collection.php");

If you intend to use the public render server, you're now ready to go.


Install and Setup a Render Server
---------------------------------

Rendering and ZIP file generation is done by a server, which can run
separately from the MediaWiki installation and can be shared by
different MediaWikis.

If you have a low-traffic MediaWiki you can use the public render
server running at http://tools.pediapress.com/mw-serve/. In this case,
just keep the configuration variable $wgCollectionMWServeURL (see
below) at its default value.

Your MediaWiki must be accessible from the render server, i.e. if your
MediaWiki is behind a firewall you cannot use the public render
server.

If you can't use the public render server, you have two choices:

1) To install an OCG_ server, follow the instructions at
https://www.mediawiki.org/wiki/Offline_content_generator

2) To install a mwlib_ server, you'll have to
:ref:`install mwlib <mwlib-install>` and
:ref:`run your own render server <mwlib-renderserver>`.
See http://mwlib.readthedocs.org/ for more information.

Finally you'll have to set ``$wgCollectionMWServeURL`` in your ``LocalSetting.php``:

``$wgCollectionMWServeURL`` (string)

  Set this to the URL of either an mwlib_ or an OCG_ render server (see above).

  The default is ``http://tools.pediapress.com/mw-serve/``, the
  public mwlib_ render server hosted by PediaPress.

  If you must use a proxy in order to reach the render server, you can
  prefix the URL with the proxy URL and separate them with ``|``, for
  example:

       http://my.proxy.host:8888|https://tools.pediapress.com/mw-serve/

  If you need to use a ``|`` character in the URL of your render
  server, use a blank proxy: ``|http://my.render.server/path-with-|/``.


Password protected wikis
-------------------------------
Password protected wikis require some more information. You'll have to
set the ``$wgCollectionMWServeCredentials`` variable.

``$wgCollectionMWServeCredentials`` (string)

  Set this to a string of the form "USERNAME:PASSWORD" (or
  "USERNAME:PASSWORD:DOMAIN" if you're using LDAP), if the MediaWiki
  requires to be logged in to view articles.
  The render server will then login with these credentials using MediaWiki API
  before doing other requests.

  SECURITY NOTICE: If the MediaWiki and the render server communicate over an
  insecure channel (for example on an unencrypted channel over the internet), please
  DO NOT USE THIS SETTING, as the credentials will be exposed to eavesdropping!

Advanced Settings
-------------------------
The following variables can be set in ``LocalSetting.php``. Most
people do not have to change them:

``$wgCollectionMWServeCert`` (string)
   Filename of a SSL certificate in PEM format for the mw-serve render server.
   This needs to be used for self-signed certificates, otherwise cURL will
   throw an error. The default is null, i.e. no certificate.

``$wgCollectionFormats``
   An array mapping names of writers on the server to the name of the
   produced format.  The default value is::

       [
	   'rl' => 'PDF',
       ]

   i.e. only PDF enabled. If you want to add OpenDocument Text in addition to
   PDF you can set $wgCollectionFormats to something like this::

       $wgCollectionFormats = [
	   'rl' => 'PDF',
	   'odf' => 'ODT',
       ];

   On the public mwlib_ render server tools.pediapress.com, currently
   the following writers are available:

   * docbook: DocBook XML
   * odf: OpenDocument Text
   * rl: PDF
   * xhtml: XHTML 1.0 Transitional

   If you're using your own mwlib_ render server, the list of
   available writers can be listed with the following mwlib_ command::

     $ mw-render --list-writers

   On OCG_ render servers, currently the following writers are
   available:

   * rdf2latex: PDF
   * rdf2text: Plain text

``$wgCollectionFormatToServeURL`` (array)
   An array matching writer names with the server which should be used
   for them.  For example, if you wanted to configure an OCG_ server
   for PDF only, you might have::

       $wgCollectionFormatToServeURL = [
	   'rdf2latex' => 'http://my-ocg-server.com:8000',
       ];

   You can specify proxies in the same way as for ``$wgCollectionMWServeURL``.

``$wgCollectionCommandToServeURL`` (array)
   An array matching server commands with the server which should be
   used for them.  For example, to use the public pediapress POD
   server, you might have::

       $wgCollectionCommandToServeURL = [
	   'zip_post' => 'http://tools.pediapress.com/mw-serve/',
       ];

   Again, proxies can be prefixed to the URL, separated by ``|``, in
   the same way as for ``$wgCollectionMWServeURL``.

``$wgCollectionContentTypeToFilename`` (array)
   An array matching content types to filenames for downloaded documents. The
   default is::

	  $wgCollectionContentTypeToFilename = [
		  'application/pdf' => 'collection.pdf',
		  'application/vnd.oasis.opendocument.text' => 'collection.odt',
		  'text/plain' => 'collection.txt',
	  ];

``$wgCollectionPortletFormats`` (array)
   An array containing formats (keys in $wgCollectionFormats) that shall be
   displayed as "Download as XYZ" links in the "Print/export" portlet.
   The default value is::

       [ 'rl' ];

   i.e. there's one link "Download as PDF".

``$wgCollectionHierarchyDelimiter`` (string or null)
   FEATURE REMOVED 2015-03.
   Use $wgNamespacesWithSubpages instead. The only accepted delimiter is now
   the standard slash, "/".

``$wgCollectionArticleNamespaces`` (array)
   List of namespace numbers for pages which can be added to a collection.
   Category pages (NS_CATEGORY) are always an exception (all articles in a
   category are added, not the category page itself). Default is::

    [
      NS_MAIN,
      NS_TALK,
      NS_USER,
      NS_USER_TALK,
      NS_PROJECT,
      NS_PROJECT_TALK,
      NS_MEDIAWIKI,
      NS_MEDIAWIKI_TALK,
      100,
      101,
      102,
      103,
      104,
      105,
      106,
      107,
      108,
      109,
      110,
      111,
    ];

``$wgCommunityCollectionNamespace`` (integer)
   Namespace for "community collections", i.e. the namespace where non-personal
   article collection pages are saved.

	 Note: This configuration setting is only used if the system message
	 Coll-community_book_prefix has not been set (see below).

   Default is ``NS_PROJECT``.

``$wgCollectionMaxArticles`` (integer)
   Maximum number of articles allowed in a collection.

   Default is 500.

``$wgCollectionLicenseName`` (string or null)
   License name for articles in this MediaWiki.
   If set to ``null`` the localized version of the word "License" is used.

   Default is null.

``$wgCollectionLicenseURL`` (string or null)
   HTTP URL of an article containing the full license text in wikitext format
   for articles in this MediaWiki. E.g.

   ::

       $wgCollectionLicenseURL = 'http://en.wikipedia.org/w/index.php?title=Wikipedia:Text_of_the_GNU_Free_Documentation_License&action=raw';

   for the GFDL.
   If set to null, the standard MediaWiki variables $wgRightsPage,
   $wgRightsUrl and $wgRightsText are used for license information.

   If your MediaWiki contains articles with different licenses, make sure
   that each article contains the name of the license and set $wgCollectionLicenseURL
   to an article that contains all needed licenses.

``$wgCollectionPODPartners`` (array or false)
  Array of parameters needed to define print on demand providers:

  ::

        $wgCollectionPODPartners = [
                'pediapress' => [
                        'name' => 'PediaPress',
                        'url' => 'http://pediapress.com/',
                        'posturl' => 'http://pediapress.com/api/collections/',
                        'infopagetitle' => 'coll-order_info_article',
                ],
        ];

  (This is the default.)

  ``name``, ``url`` and ``posturl`` are mandatory parameters to display
  information on the list of available providers.

  If ``infopagetitle`` is present, it will be interpreted as the MediaWiki
  message that contains the name of the short information on particular
  provider. For example, it can be ``coll-order_info_mypress`` and
  if the message contains ``Help:Books/MyPress order information``, a contents
  of this page will be used. The message itself can be localized for
  different languages.

  Setting ``$wgCollectionPODPartners`` to false disables ordering interface
  altogether.


  There are two MediaWiki rights that are checked, before users are allowed
  to save collections: To be able to save collection pages under the User
  namespace, users must have the right 'collectionsaveasuserpage'; to be able
  to save collection pages under the community namespace
  (see $wgCommunityCollectionNamespace), users must have the right
  'collectionsaveascommunitypage'. For example, if all logged-in users shall
  be allowed to save collection pages under the User namespace, but only
  autoconfirmed users, shall be allowed to save collection pages under the
  community namespace, add this to your LocalSettings.php::

    $wgGroupPermissions['user']['collectionsaveasuserpage'] = true;
    $wgGroupPermissions['autoconfirmed']['collectionsaveascommunitypage'] = true;

You may also want to configure some of the following:

* As the current collection of articles is stored in the session, the session
  timeout should be set to some sensible value (at least a few hours, maybe
  one day). Adjust session.cookie_lifetime and session.gc_maxlifetime in your
  ``php.ini`` accordingly.

* Add a help page (for example ``Help:Books`` for wikis in English language).

  A repository of help pages in different languages can be found on
  `Meta-Wiki`_.

  The name of the help page is stored in the system message Coll-helppage and
  can be adjusted by editing the wiki page [[MediaWiki:Coll-helppage]].

* Add a template [[Template:saved_book]] which is transcluded on top of saved
  collection pages. An example for such a template can be found on the English
  Wikipedia: http://en.wikipedia.org/wiki/Template:Saved_book

  The name of the template can be adjusted via the system message
  Coll-savedbook_template, i.e. by editing [[MediaWiki:Coll-savedbook_template]].

  This template should have a link to load a saved collection.   In
  English Wikipedia this looks like::

    [{{fullurl:Special:Book|bookcmd=load_collection&amp;colltitle={{FULLPAGENAMEE}}}} Book&nbsp;Creator]

* To enable ZENO and Okawix export, uncomment the corresponding lines in
  ``$wgCollectionFormats`` (file Collection.php). These exports are devoted
  to the Wikimedia projects and their mirrors.

  They cannot be used on other wikis since they get data and search engine
  indexes from the cache of wikiwix.com.


Customization via System Messages
=================================

There are several system messages, which can be adjusted for a MediaWiki
installation. They can be changed by editing the wiki page
[[MediaWiki:SYSTEMMESSAGENAME]], where SYSTEMMESSAGENAME is the name of the
system message.

* ``Coll-helppage``: The name of the help page (see above).

  The default for English language is ``Help:Books``, and there exist
  translations for lots of different languages.

* ``Coll-user_book_prefix``: Prefix for titles of "user books" (i.e. books for
  personal use, as opposed to "community books"). If the system message is empty
  or '-' (the default), the title of user book pages is constructed
  as User:USERNAME/Books/BOOKTITLE. If the system message is set and its content
  is PREFIX, the title of user book pages is constructed by directly concatenating
  PREFIX and the BOOKTITLE, i.e. there's no implicitly inserted '/' inbetween!

* ``Coll-community_book_prefix``: Prefix for titles of "community books" (cf. "user
  books" above). If the system message is empty or '-' (the default), the title
  of community pages is constructed as NAMESPACE:Books/BOOKTITLE, where
  NAMESPACE depends on the value of $wgCommunityCollectionNamespace (see above).
  If the system message is set and its content is PREFIX, the title of community
  book pages is constructed by directly concatenating PREFIX and BOOKTITLE,
  i.e. there's no implicitly inserted '/' inbetween. Thus it's possible to
  define a custom namespace 'Book' and set the system message to 'Book:' to
  produce community book page titles Book:BOOKTITLE.

* ``Coll-savedbook_template``: The name of the template (w/out the Template: prefix)
  included at the top of saved book pages (see above).

  The default is: ``saved_book``, and there exist translations for lots of
  different languages.

* ``Coll-bookscategory``: Name of a category (w/out the Category: prefix) to which
  all saved book pages should be added (optional, set to an empty value or "-"
  to turn that feature off).

* ``Coll-book_creator_text_article``: The name of  a wiki page which is transcluded
  on the "Start book creator" page (the page which is shown when a user clicks
  on "Create a book").

  The default is: ``{{MediaWiki:Coll-helppage}}/Book creator text``
  i.e. a subpage of the configured help page named "Book creator text"

* ``Coll-suggest_enabled``: If set to 1, the suggestion tool is enabled. Any other
  value will disable the suggestion tool.

  The default is: '1', i.e. the suggestion tool is enabled.

* ``Coll-order_info_article``: The name of a wiki page which is included on the
  Special:Book page to show order information for printed books.

  The default value is: ``{{MediaWiki:Coll-helppage}}/PediaPress order information``
  i.e. a subpage of the configured help page named "PediaPress order information".

  This wiki page is used only if included in the ``$wgCollectionPODPartners``
  configuration.

* ``Coll-rendering_page_info_text_article``: The name of a wiki page with additional
  informations to be displayed when single pages are being rendered.

* ``Coll-rendering_collection_info_text_article``: The name of a wiki page with additional
  informations to be displayed when collections are being rendered.



.. _mwlib: http://mwlib.readthedocs.org/
.. _MediaWiki: https://www.mediawiki.org/
.. _OCG: https://www.mediawiki.org/wiki/Offline_content_generator
.. _`PediaPress GmbH`: http://pediapress.com/
.. _`Wikimedia Foundation`: https://wikimediafoundation.org/
.. _`Commonwealth of Learning`: http://www.col.org/
.. _`MediaWiki API`: https://www.mediawiki.org/wiki/API
.. _`Meta-Wiki`: https://meta.wikimedia.org/wiki/Book_tool/Help/Books
