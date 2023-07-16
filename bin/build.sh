#!/bin/bash

set -e

rm -rf build && \
mkdir build && \
cp -r . build/ && \
rm -rf build/node_modules build/tests build/coverage build/tools build/.github && \
composer install -d build/ --no-dev && \
php -d phar.readonly=Off tools/phar-composer-1.4.0.phar build