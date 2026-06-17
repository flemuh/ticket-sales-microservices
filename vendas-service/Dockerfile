FROM php:8.2-cli

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git unzip curl libpq-dev default-mysql-client

RUN docker-php-ext-install pdo pdo_mysql sockets

COPY . .

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install

EXPOSE 80

CMD ["php", "-S", "0.0.0.0:80", "-t", "public"]