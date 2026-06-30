FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    pdo_sqlite

RUN a2enmod rewrite

WORKDIR /var/www/html

COPY . /var/www/html

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

RUN printf '%s\n' \
  '<Directory /var/www/html/public>' \
  '    AllowOverride All' \
  '    Require all granted' \
  '</Directory>' \
  > /etc/apache2/conf-available/lifeflow-public.conf \
  && a2enconf lifeflow-public
