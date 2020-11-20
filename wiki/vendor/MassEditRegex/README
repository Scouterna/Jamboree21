## MassEditRegex

MassEditRegex is an extension to MediaWiki that allowing to use regular expressions for editing many pages
in one operation.


### Copyright
Copyright © 2009, 2013 Adam Nielsen <malvineous@shikadi.net> according to the GNU General Public License 2.0
or later (GPL-2.0-or-later). See also the "COPYING" file provided with the code.


### Compatibility

* PHP 5.3 and later
* MediaWiki 1.17 and later

See also the "CHANGELOG" file provided with the code.


### Installation

(1) Obtain the code from [GitHub](https://github.com/wikimedia/mediawiki-extensions-MassEditRegex/releases)

(2) Extract the files in a directory called `MassEditRegex` in your `extensions/` folder.

(3) Add the following code at the bottom of your "LocalSettings.php" file:
```
require_once "$IP/extensions/MassEditRegex/MassEditRegex.php";
```
(4) Define which user groups may use this extension by configuring it as outlined in the "Configuration" section
of this file.

(5) Go to "Special:Version" on your wiki to verify that the extension is successfully installed.

(6) Done.


### Configuration

This extension comes with an extra user right called "masseditregex" to allow fine grained control of which user groups
may use this extension. By default it is assigned to no user group. In case you would like to assign it to a user group
e.g. "masseditregexeditor", add the following code to you "LocalSettings.php" file right after the lines added in step (3)
of the installation process:

```
$wgGroupPermissions['masseditregexeditor']['masseditregex'] = true;
```
Alternatively you may grant the permission to an existing user group, e.g. "sysop" by adding the following line:

```
$wgGroupPermissions['sysop']['masseditregex'] = true;
```

### Usage
See the extensions homepage at https://www.mediawiki.org/wiki/Extension:MassEditRegex for full instructions on how to
use this extension.
