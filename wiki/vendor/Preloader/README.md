Preloader MediaWiki Extension
=============================

See http://www.mediawiki.org/wiki/Extension:Preloader

## Overview

1. Introduction
2. Requirements
3. Installation
4. Configuration
5. Boilerplate
6. Feedback

## 1. Introduction

The Preloader extension allows the creation of boilerplate text which will be inserted into the edit form when creating new pages. Different boilerplate text can be specified for different namespaces.

This extension supports the standard `<includeonly>` and `<noinclude>` tags in the boilerplate text.

## 2. Requirements

The Preloader extension requires MediaWiki 1.25.0 or later.

## 3. Installation

Git - clone the GitLab repository into your extensions subdirectory:

```
cd /path/to/wiki/extensions
git clone https://gitlab.com/troyengel/Preloader/
```

Composer - the package is available via Composer / Packagist:

* https://packagist.org/packages/troyengel/preloader

Then edit your `LocalSettings.php` file and add the following line:

```
wfLoadExtension( 'Preloader' );
```

Installation can be verified through the **Special:Version** page on your wiki.

## 4. Configuration

Configuration of the boilerplate sources is done via the `$wgPreloaderSource` configuration variable, which takes the following format:

```
$wgPreloaderSource[<namespace index>] = PAGE TITLE;
```

For instance:

```
$wgPreloaderSource[NS_MAIN] = 'Template:Boilerplate';
$wgPreloaderSource[NS_HELP] = 'Template:Boilerplate help';
```

...indicates that the boilerplate text for pages in the main namespace should be loaded from **Template:Boilerplate**, while pages in the Help namespace will be preloaded from **Template:Boilerplate_help**. Other namespaces have no boilerplate configured.

## 5. Boilerplate

Using the above example configuration, create the wiki page **Template:Boilerplate** and use the standard include tags to indicate which parts are the template and which are the generic wiki text:

```
<includeonly>
__TOC__

== Overview ==

[[Category:MyDefaultCategory]]
</includeonly>
<noinclude>
This template is included by the Preloader extension on new article creation.
</noinclude>
```

When this template is included into a new wiki page, only this text is included and ready to edit:

```
__TOC__

== Overview ==

[[Category:MyDefaultCategory]]
```

## 6. Feedback

Please submit comments, suggestions and bug reports to https://gitlab.com/troyengel/Preloader/.
