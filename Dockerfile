FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

COPY . /var/www/html/
COPY .devcontainer/000-default.conf /etc/apache2/sites-available/000-default.conf

ENV UPLOAD_DIR=/var/www/html/uploads \
    UPLOAD_URL=/uploads

RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80
