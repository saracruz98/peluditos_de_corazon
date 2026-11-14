# Dockerfile – estructura reorganizada
# Imagen base con Apache y PHP 8.2
FROM php:8.2-apache

# Instalar dependencias necesarias para la extensión SQLite y compilar pdo_sqlite
RUN apt-get update \
    && apt-get install -y --no-install-recommends 
        build-essential 
        libsqlite3-dev 
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Copiar el código fuente y los archivos públicos
COPY src/ /var/www/html/src/
COPY public/ /var/www/html/
COPY start.sh /usr/local/bin/start.sh

# Dar permisos de ejecución al script de inicio y al directorio web
RUN chmod +x /usr/local/bin/start.sh \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exponer el puerto 80 (Render lo mapea a $PORT)
EXPOSE 80

# Usar el script de inicio que crea la base de datos y luego lanza Apache
ENTRYPOINT ["/usr/local/bin/start.sh"]
