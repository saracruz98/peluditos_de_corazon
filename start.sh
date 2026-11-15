#!/bin/sh
# Inicializar la base de datos y lanzar Apache
php /var/www/html/src/init_db.php
exec apache2-foreground
