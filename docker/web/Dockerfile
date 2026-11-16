FROM php:8.0-apache
RUN docker-php-ext-install mysqli
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN mkdir -p /var/www/html/uploads && chown www-data /var/www/html/uploads

