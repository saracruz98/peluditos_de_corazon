# Dockerfile – estructura reorganizada
# Imagen base con Apache y PHP 8.2
FROM php:8.2-apache

# Instalar dependencias necesarias para la extensión SQLite y compilar pdo_sqlite
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        build-essential \
        libsqlite3-dev && \
    docker-php-ext-install pdo_sqlite && \
    rm -rf /var/lib/apt/lists/*

# Copiar código fuente y archivos públicos
COPY src/ /var/www/html/src/
COPY public/ /var/www/html/
COPY index.php /var/www/html/
COPY start.sh /usr/local/bin/start.sh

# Permisos y script de inicio
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod +x /usr/local/bin/start.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/start.sh"]
