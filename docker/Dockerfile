FROM php:8.3-fpm-alpine as php-converter

ARG SCRIPT_URL='https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions'
RUN set -eux && \
    curl -sSLf -o /usr/local/bin/install-php-extensions ${SCRIPT_URL} && \
    chmod +x /usr/local/bin/install-php-extensions && \
    apk add git bash gettext openssh && \
    touch /var/log/xdebug.log && chmod 0666 /var/log/xdebug.log && \
    install-php-extensions @composer intl zip opcache pcntl sockets xsl

ARG PHP_IDE_CONFIG="serverName=Docker"
ENV PHP_IDE_CONFIG=$PHP_IDE_CONFIG
ARG XDEBUG_AUTOSTART=trigger
ARG DEV_HOST_IP=172.18.7.1
ARG DEV_XDEBUG_IDE_KEY=PHPSTORM
ARG XDEBUG_FILE=/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN install-php-extensions xdebug gd && \
      echo "xdebug.mode=debug" >> $XDEBUG_FILE && \
      echo "xdebug.start_with_request=$XDEBUG_AUTOSTART" >> $XDEBUG_FILE && \
      echo "xdebug.client_host=$DEV_HOST_IP" >> $XDEBUG_FILE && \
      echo "xdebug.client_port=9003" >> $XDEBUG_FILE && \
      echo "xdebug.log=/var/log/xdebug.log" >> $XDEBUG_FILE && \
      echo "xdebug.idekey=$DEV_XDEBUG_IDE_KEY" >> $XDEBUG_FILE && \
      apk add --no-cache ranger vim nano vifm;

COPY --from=cytopia/gofmt:latest /usr/local/bin/gofmt /usr/bin/gofmt
COPY ./docker/memory_limit.ini /usr/local/etc/php/conf.d/memory_limit.ini

ARG UID=1000
ARG GID=1000
RUN addgroup -g $GID app && adduser -D -u $UID -G app app && addgroup app www-data
USER app
COPY --chown=app:app ../ /var/www/php-converter
WORKDIR /var/www/php-converter
USER www-data
