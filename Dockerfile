
FROM php:8.3-apache

# Install PostgreSQL dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module
RUN a2enmod rewrite

# Configure Apache to allow .htaccess files
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Configure Apache to use Render's PORT environment variable
RUN printf '%s\n' \
    '#!/bin/sh' \
    'set -e' \
    'PORT="${PORT:-10000}"' \
    'sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf' \
    'sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf' \
    'exec apache2-foreground' \
    > /usr/local/bin/start-apache.sh \
    && chmod +x /usr/local/bin/start-apache.sh

# Set index.php as the default homepage
RUN printf 'DirectoryIndex index.php\n' \
    > /etc/apache2/conf-available/dir.conf \
    && a2enconf dir

# Set project directory
WORKDIR /var/www/html

# Copy all project files
COPY . /var/www/html/

# Set file permissions
RUN chown -R www-data:www-data /var/www/html

# Expose Render's default port
EXPOSE 10000

# Start Apache
CMD ["/usr/local/bin/start-apache.sh"]
