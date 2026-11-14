# Dockerfile for petstore_php app
# Base image with Apache and PHP 8.2
FROM php:8.2-apache

# Install dependencies required to compile the SQLite extension
RUN apt-get update && \
    apt-get install -y --no-install-recommends libsqlite3-dev && \
    docker-php-ext-install pdo_sqlite && \
    rm -rf /var/lib/apt/lists/*

# Copy application source into Apache document root
COPY . /var/www/html/

# Set proper permissions for the web server user (www-data)
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose port 80 (default for Apache)
EXPOSE 80

# Use the default CMD from the base image (apache2-foreground)
