#!/bin/sh
# start.sh - Inicializa la base de datos y lanza Apache

# Determinar ruta de la base de datos
DB_PATH="${DATABASE_PATH:-/data/petstore.db}"

# Crear el directorio si no existe (para el disco persistente de Render)
DB_DIR=$(dirname "$DB_PATH")
mkdir -p "$DB_DIR"

# Crear el archivo de base de datos con permisos correctos para www-data
touch "$DB_PATH"
chown www-data:www-data "$DB_PATH"
chmod 664 "$DB_PATH"

# Tambien dar permisos al directorio
chown www-data:www-data "$DB_DIR"
chmod 755 "$DB_DIR"

echo "Base de datos en: $DB_PATH"

# Inicializar las tablas (como www-data para mantener permisos correctos)
DATABASE_PATH="$DB_PATH" su -s /bin/sh www-data -c "php /var/www/html/src/init_db.php"

# Lanzar Apache
exec apache2-foreground
