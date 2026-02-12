#!/bin/bash
RUTA_PROYECTO="/var/www/html/estacionamiento_plg"
cd "$RUTA_PROYECTO"
php artisan backup:run --only-db

