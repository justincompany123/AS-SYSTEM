
FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf

RUN sed -i 's/<VirtualHost \*:80>/<VirtualHost *:10000>/' \
    /etc/apache2/sites-available/000-default.conf

RUN printf 'DirectoryIndex index.php index.html\n' \
    > /etc/apache2/conf-available/dir.conf \
    && a2enconf dir

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000

CMD ["apache2-foreground"]
