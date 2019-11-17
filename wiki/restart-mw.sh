#! /bin/bash
podman stop mediawiki
podman rm mediawiki
podman create --pod wiki -e MEDIAWIKI_DB_NAME=$MYSQL_DATABASE \
-e MEDIAWIKI_DB_USER=$MYSQL_USER \
	-e MEDIAWIKI_DB_PASSWORD=$MYSQL_PASSWORD \
	-e MEDIAWIKI_ADMIN_PASS=admin \
	-e MEDIAWIKI_ADMIN_USER=admin \
	-e MEDIAWIKI_DB_HOST=127.0.0.1 \
	-e MEDIAWIKI_DB_TYPE=mysql \
	-e MEDIAWIKI_DB_PORT="3306" \
	-e MEDIAWIKI_SITE_LANG=sv \
	-e MEDIAWIKI_UPDATE="false" \
	-v /home/jmanner/projekt/Jamboree21/wiki/LocalSettings.php:/var/www/html/LocalSettings.php \
	-d --name mediawiki \
	localhost/mediawiki:dev
podman start mediawiki
