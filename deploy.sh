#!/bin/bash

cd /var/www/aminhub || exit

echo "Pulling latest code..."
git reset --hard
git pull origin

echo "Installing composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo "Running migrations..."
php artisan migrate --force

echo "Clearing cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

echo "Fixing permissions..."
chown -R www-data:www-data /var/www/aminhub
chmod -R 775 storage bootstrap/cache
chmod +x deploy.sh
echo "Deployment finished!"
