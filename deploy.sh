#!/bin/bash

echo "🚀 Starting Laravel deployment to Liara..."

# بررسی وجود فایل‌های ضروری
if [ ! -f "composer.json" ]; then
    echo "❌ composer.json not found!"
    exit 1
fi

# نصب dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "📦 Installing Node dependencies..."
npm ci --silent

# Build assets
echo "🔨 Building frontend assets..."
npm run build

# پاک کردن cache ها
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# تولید APP_KEY در صورت عدم وجود
if [ -z "$APP_KEY" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force --no-interaction
fi

# اجرای migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force --no-interaction

# بررسی وجود جدول sessions
echo "🔄 Checking sessions table..."
php artisan migrate:status | grep -q "sessions" || php artisan session:table && php artisan migrate --force

# Cache کردن configs برای production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# تنظیم permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# ایجاد کاربران پیش‌فرض
echo "👥 Creating default users..."
php artisan db:seed --class=DatabaseSeeder --force || echo "⚠️ Seeding failed or already exists"

echo "✅ Deployment completed successfully!"
echo "🌐 Your application should be available at: $APP_URL" 