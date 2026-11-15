# Dockerfile
FROM php:8.2-apache

RUN apt-get update && \
    apt-get install -y --no-install-recommends build-essential libsqlite3-dev && \
    docker-php-ext-install pdo_sqlite && \
    rm -rf /var/lib/apt/lists/*

# src/ va dentro de html/ para que las rutas __DIR__ . '/src/...' funcionen
COPY src/   /var/www/html/src/
COPY public/ /var/www/html/
COPY xlsx.php /var/www/html/../xlsx.php
COPY index.php /var/www/html/index.php
COPY start.sh /usr/local/bin/start.sh

RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod +x /usr/local/bin/start.sh

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/start.sh"]
