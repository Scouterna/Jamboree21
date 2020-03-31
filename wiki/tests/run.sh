#!/bin/bash
cd "${0%/*}"
php composer.phar install --no-plugins --no-scripts
vendor/bin/phpunit --log-junit results.xml testcases/
