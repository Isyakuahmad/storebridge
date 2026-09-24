FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

COPY . /var/www/html/
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/storebridge-entrypoint

ENV UPLOAD_DIR=/var/www/html/uploads \
    UPLOAD_URL=/uploads

RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod +x /usr/local/bin/storebridge-entrypoint

ENTRYPOINT ["/usr/local/bin/storebridge-entrypoint"]
