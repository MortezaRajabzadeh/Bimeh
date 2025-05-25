#!/bin/bash

echo "🔧 Fixing login refresh issue..."

# 1. Clear all caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 2. Generate new APP_KEY if needed
echo "🔑 Checking APP_KEY..."
php artisan key:generate --force

# 3. Create sessions table if not exists
echo "🗄️ Setting up sessions..."
php artisan session:table --force
php artisan migrate --force

# 4. Set correct permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# 5. Optimize for production
echo "⚡ Optimizing..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Test database connection
echo "📡 Testing database connection..."
php artisan migrate:status

echo "✅ Fix completed! Try logging in again."
echo ""
echo "📝 Environment variables to check in Liara:"
echo "   APP_KEY=base64:..."
echo "   APP_URL=https://yourdomain.liara.run"
echo "   SESSION_DRIVER=database"
echo "   SESSION_SECURE_COOKIE=true"
echo "   DB_CONNECTION=mysql" 