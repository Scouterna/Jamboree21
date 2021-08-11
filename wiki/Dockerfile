FROM mediawiki:stable

COPY LocalSettings.php LocalSettings.php
COPY logo.png logo.png

# install extensions
COPY vendor/Auth_remoteuser extensions/Auth_remoteuser
COPY vendor/CategoryTree extensions/CategoryTree
COPY vendor/MassEditRegex extensions/MassEditRegex
COPY vendor/MinervaNeue skins/MinervaNeue
COPY vendor/MixedNamespaceSearchSuggestions extensions/MixedNamespaceSearchSuggestions
COPY vendor/MobileFrontend extensions/MobileFrontend
COPY vendor/PluggableAuth extensions/PluggableAuth
COPY vendor/Renameuser extensions/Renameuser
COPY vendor/SimpleSAMLphp extensions/SimpleSAMLphp

# SimpleSAML setup
COPY vendor/simplesamlphp-1.18.4 simplesamlphp
RUN ln -sf simplesamlphp/www simplesaml
COPY simplesamlconfig/config.php simplesamlphp/config/config.php
COPY simplesamlconfig/saml20-idp-remote.php simplesamlphp/metadata/saml20-idp-remote.php
COPY simplesamlconfig/authsources.php simplesamlphp/config/authsources.php


# Patch Media-wiki, set 403-code on permission errors
COPY patches /tmp/patches
RUN sed 's/\x0D$//g' -i /var/www/html/includes/OutputPage.php /tmp/patches/OutputPage-403.patch
RUN patch /var/www/html/includes/OutputPage.php < /tmp/patches/OutputPage-403.patch
RUN patch /var/www/html/includes/MediaWiki.php < /tmp/patches/Fix-bug-in-content-encoding.patch

RUN	chown -R www-data:www-data extensions skins cache images
