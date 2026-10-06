#!/bin/sh
# Aktualisiert die Installation auf dem Server.
set -e

php artisan down --retry=60

git pull

# PHP-Abhängigkeiten (ohne Entwicklungspakete)
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader

# Frontend bauen (Tailwind/Vite). Ohne Node auf dem Server: lokal "npm run build" ausführen und public/build hochladen.
if command -v npm >/dev/null 2>&1; then
    npm ci --no-audit --no-fund
    npm run build
else
    echo "Hinweis: npm nicht gefunden – public/build muss bereits aktuell sein."
fi

php artisan migrate --force

# Caches neu aufbauen
php artisan optimize:clear
php artisan optimize

# Laufende Queue-Worker neu starten (falls QUEUE_CONNECTION=database)
php artisan queue:restart

php artisan up
