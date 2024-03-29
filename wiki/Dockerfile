FROM mediawiki:1.36

COPY logo.png logo.png

# install extensions
COPY vendor/Auth_remoteuser extensions/Auth_remoteuser
COPY vendor/CategoryTree extensions/CategoryTree
COPY vendor/MassEditRegex extensions/MassEditRegex
COPY vendor/MinervaNeue skins/MinervaNeue
COPY vendor/MixedNamespaceSearchSuggestions extensions/MixedNamespaceSearchSuggestions
COPY vendor/MobileFrontend extensions/MobileFrontend
COPY vendor/PageForms extensions/PageForms
COPY vendor/PluggableAuth extensions/PluggableAuth
COPY vendor/Preloader extensions/Preloader
COPY vendor/Renameuser extensions/Renameuser
COPY vendor/SimpleSAMLphp extensions/SimpleSAMLphp
COPY vendor/Collection extensions/Collection
COPY download-composer.sh /tmp/download-composer.sh


# Patch Media-wiki, set 403-code on permission errors
COPY patches /tmp/patches
RUN sed 's/\x0D$//g' -i /var/www/html/includes/OutputPage.php /tmp/patches/OutputPage-403.patch
RUN patch /var/www/html/includes/OutputPage.php < /tmp/patches/OutputPage-403.patch

RUN chown -R www-data:www-data extensions skins cache images

# Install composer and htmldoc
RUN apt-get update && apt-get install -y unzip --no-install-recommends && rm -rf /var/lib/apt/lists/*
RUN bash /tmp/download-composer.sh
RUN php composer.phar config --no-plugins allow-plugins.composer/installers true

COPY composer.local.json composer.local.json
COPY composer.local.lock composer.local.lock

# Run composer
RUN php composer.phar install
RUN php composer.phar update --no-dev

# Copy conf
COPY LocalSettings.php LocalSettings.php
COPY smw.json /var/www/html/extensions/SemanticMediaWiki/.smw.json
