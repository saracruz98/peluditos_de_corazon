# Dockerfile
FROM php:8.2-apache

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        build-essential \
        libsqlite3-dev \
        libzip-dev \
        zip \
        unzip && \
    docker-php-ext-install pdo_sqlite zip && \
    rm -rf /var/lib/apt/lists/*

# Copiar archivos al DocumentRoot
COPY public/  /var/www/html/
COPY src/     /var/www/html/src/
COPY xlsx.php /var/www/html/xlsx.php
COPY index.php /var/www/html/index.php
COPY start.sh /usr/local/bin/start.sh

# Dar permisos correctos
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod +x /usr/local/bin/start.sh && \
    chmod 1777 /tmp

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/start.sh"]
