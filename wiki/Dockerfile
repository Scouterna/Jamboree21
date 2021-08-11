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


# Patch Media-wiki, set 403-code on permission errors
COPY patches /tmp/patches
RUN sed 's/\x0D$//g' -i /var/www/html/includes/OutputPage.php /tmp/patches/OutputPage-403.patch
RUN patch /var/www/html/includes/OutputPage.php < /tmp/patches/OutputPage-403.patch

RUN	chown -R www-data:www-data extensions skins cache images

# Install composer
RUN apt-get update && apt-get install -y composer && rm -rf /var/lib/apt/lists/*

RUN COMPOSER=composer.local.json php composer.phar require --no-update mediawiki/semantic-media-wiki 

# Run composer
RUN php composer.phar update --no-dev
