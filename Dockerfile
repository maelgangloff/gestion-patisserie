FROM php:8.2.0-apache
 
RUN a2enmod rewrite
 
RUN apt-get update \
  && apt-get install -y libzip-dev libpng-dev libicu-dev --no-install-recommends \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN docker-php-ext-install pdo pdo_mysql zip intl gd;
 
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
 
COPY docker/apache.conf /etc/apache2/sites-enabled/000-default.conf
COPY . /var/www
 
WORKDIR /var/www
RUN /usr/bin/composer install

 
CMD ["apache2-foreground"]
