FROM php:8.3
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y libzip-dev
RUN docker-php-ext-install sockets pdo_mysql zip

WORKDIR /app
COPY composer.json .
COPY src src
COPY scripts scripts
COPY config_env.php config.php

RUN composer update

CMD php scripts/passing_saver env