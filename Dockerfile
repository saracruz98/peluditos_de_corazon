# Dockerfile – reorganized project structure
# Base image with Apache and PHP 8.2
FROM php:8.2-apache

# Install dependencies for SQLite extension
RUN apt-get update && \
    apt-get install -y --no-install-recommends libsqlite3-dev && \
    docker-php-ext-install pdo_sqlite && \
    rm -rf /var/lib/apt/lists/*

# Copy source code (src) and public web files
COPY src/ /var/www/html/src/
COPY public/ /var/www/html/

# Set proper permissions for www-data user
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose port 80 (Render maps this to $PORT)
EXPOSE 80

# Default CMD from base image (apache2-foreground)
