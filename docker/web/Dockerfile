FROM php:8.0-apache
RUN docker-php-ext-install mysqli
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN mkdir -p /var/www/html/uploads && chown www-data:www-data /var/www/html/uploads
RUN mkdir -p /var/lib/php/sessions && chown www-data:www-data /var/lib/php/sessions \
 && echo "session.save_path = /var/lib/php/sessions" > "$PHP_INI_DIR/conf.d/session.ini"
