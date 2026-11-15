#!/bin/sh
# Crear el archivo de base de datos con permisos correctos para www-data
touch /tmp/petstore.db
chown www-data:www-data /tmp/petstore.db
chmod 664 /tmp/petstore.db

# Inicializar las tablas (corre como www-data para que el archivo quede con el dueño correcto)
su -s /bin/sh www-data -c "php /var/www/html/src/init_db.php"

# Lanzar Apache
exec apache2-foreground
