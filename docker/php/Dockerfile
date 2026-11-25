# PHP 8.4 with the extensions the application and its quality gate need: pdo_mysql for MySQL,
# intl/bcmath for formatting and decimal maths, pcntl for the queue worker, pcov for coverage, and
# gd (with JPEG and WebP) to re-encode and scale profile pictures.
FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev \
        libjpeg62-turbo-dev libpng-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql zip intl bcmath pcntl gd \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY entrypoint.sh /usr/local/bin/eletrontech-entrypoint

# Composer and PsySH write to $HOME; the container runs as the host user, who has no home inside it.
ENV HOME=/tmp \
    COMPOSER_HOME=/tmp/composer

WORKDIR /var/www/html
ENTRYPOINT ["eletrontech-entrypoint"]
