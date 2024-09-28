ARG PHP_VERSION=8.3

FROM php:${PHP_VERSION}-alpine

# Install dependencies and YAML extension
RUN apk update && apk add --no-cache $PHPIZE_DEPS linux-headers yaml-dev \
    && pecl install xdebug yaml \
    && docker-php-ext-enable xdebug yaml \
    && docker-php-ext-install sockets

COPY .docker/php/kariricode-php.ini /usr/local/etc/php/conf.d/

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN apk del --purge $PHPIZE_DEPS && rm -rf /var/cache/apk/*

# Keep the container running without doing anything
CMD tail -f /dev/null
