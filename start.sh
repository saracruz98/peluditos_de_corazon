#!/bin/sh
# start.sh – runs DB init then starts Apache
php /var/www/html/src/init_db.php || exit 1
exec apache2-foreground
