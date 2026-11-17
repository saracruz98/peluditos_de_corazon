#!/bin/sh
# start.sh

# Si hay DATABASE_URL (PostgreSQL), no necesitamos crear archivo local
if [ -z "$DATABASE_URL" ]; then
    # Fallback SQLite: crear archivo en /tmp
    DB_PATH="${DATABASE_PATH:-/tmp/petstore.db}"
    DB_DIR=$(dirname "$DB_PATH")
    mkdir -p "$DB_DIR"
    touch "$DB_PATH"
    chown www-data:www-data "$DB_PATH" 2>/dev/null || true
    chmod 664 "$DB_PATH"
    echo "Usando SQLite en: $DB_PATH"
else
    echo "Usando PostgreSQL (DATABASE_URL configurado)"
fi

# Inicializar tablas
su -s /bin/sh www-data -c "php /var/www/html/src/init_db.php"

# Generar íconos PWA si no existen
if [ ! -f /var/www/html/icons/icon-192.png ]; then
    echo "Generando iconos PWA..."
    mkdir -p /var/www/html/icons
    php /var/www/html/generate_icons.php
fi

# Lanzar Apache
exec apache2-foreground
