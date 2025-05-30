#!/bin/bash

# 🚀 Microbime Complete Deployment Script for Liara
echo "🎯 شروع deploy کامل پروژه میکروبیمه به Liara..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_step() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if Liara CLI is installed
if ! command -v liara &> /dev/null; then
    print_warning "Liara CLI موجود نیست. در حال نصب..."
    npm install -g @liara/cli
fi

print_step "نصب dependencies..."
composer install --optimize-autoloader --no-dev
npm ci

print_step "Build کردن assets..."
npm run build

print_step "آماده‌سازی Laravel برای production..."

# Generate APP_KEY if not exists
if [ ! -f .env ]; then
    print_warning "فایل .env موجود نیست. در حال کپی از .env.example..."
    cp .env.example .env
fi

# Generate APP_KEY
php artisan key:generate

print_step "Cache کردن configs..."
php artisan config:cache
php artisan route:cache  
php artisan view:cache
php artisan event:cache

print_step "بررسی migrations..."
php artisan migrate:status

print_step "بررسی آماده‌سازی files..."

# Check critical files
if [ ! -f "liara.json" ]; then
    print_error "فایل liara.json موجود نیست!"
    exit 1
fi

if [ ! -f "composer.json" ]; then
    print_error "فایل composer.json موجود نیست!"
    exit 1
fi

if [ ! -d "public/build" ]; then
    print_error "Assets build نشده! ابتدا npm run build اجرا کنید."
    exit 1
fi

print_step "تنظیم permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache

print_step "پاک کردن cache های اضافی..."
php artisan cache:clear
php artisan config:clear

print_step "✨ همه چیز آماده! حالا می‌توانید deploy کنید:"
echo ""
echo "🔗 Commands بعدی:"
echo "   liara auth:login"
echo "   liara deploy --app microbime --platform laravel"
echo ""
echo "📋 یادتون نره:"
echo "   1. Database رو در Liara ایجاد کنید"
echo "   2. Environment variables رو تنظیم کنید"  
echo "   3. بعد از deploy: php artisan db:seed"
echo ""
print_step "🎉 آماده deploy!" 