#!/bin/bash

set -e

rm -rf build && \
mkdir build && \
cp -r src build/src && \
cp -r bin build/bin && \
cp -r LICENSE build/LICENSE && \
cp -r composer.json build/composer.json && \
cp -r composer.lock build/composer.lock && \
composer install -d build/ --no-dev && \
php -d phar.readonly=Off tools/phar-composer-1.4.0.phar build build/
mv php-converter.phar build/