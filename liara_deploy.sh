#!/bin/bash

# Microbime Deployment Script for Liara
echo "🚀 Starting Microbime deployment to Liara..."

# Install dependencies
echo "📦 Installing Composer dependencies..."
composer install --optimize-autoloader --no-dev

echo "📦 Installing NPM dependencies..."
npm ci

# Build assets
echo "🔨 Building production assets..."
npm run build

# Clear and cache config
echo "⚙️ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Clear application cache
echo "🧹 Clearing application cache..."
php artisan cache:clear
php artisan config:clear

# Set proper permissions
echo "🔐 Setting file permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache

echo "✅ Deployment completed successfully!"
echo "🌐 Your application is ready at: https://microbime.com" 