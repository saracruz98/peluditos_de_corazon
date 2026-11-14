# Dockerfile for petstore_php app
# Base image with Apache and PHP 8.2
FROM php:8.2-apache

# Enable SQLite extension (already compiled in official image, but ensure)
RUN docker-php-ext-install pdo_sqlite

# Copy application source into Apache document root
COPY . /var/www/html/

# Set proper permissions for the web server user (www-data)
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose port 80 (default for Apache)
EXPOSE 80

# Use the default CMD from the base image (apache2-foreground)
