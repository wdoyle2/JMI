FROM php:8.3-cli-bookworm

LABEL maintainer="Wind4Life"

ARG WWWGROUP=1000
ARG WWWUSER=1337

ENV DEBIAN_FRONTEND=noninteractive \
    TZ=UTC \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# System deps + headers needed to compile the PHP extensions we care about.
RUN set -eux \
    && ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates curl git unzip zip gosu \
        libzip-dev libicu-dev libonig-dev libxml2-dev \
        libpng-dev libjpeg-dev libfreetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        bcmath intl mbstring zip pcntl gd opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Composer from official image (matches version pinned upstream).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Non-root runtime user matching Sail's conventions so file ownership lines up
# with the host bind mount.
RUN groupadd --force -g $WWWGROUP sail \
    && useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u $WWWUSER sail

EXPOSE 80/tcp

USER sail

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
