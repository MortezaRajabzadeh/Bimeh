# 🚨 راهنمای رفع مشکلات Deployment در لیارا

## مشکل: صفحه لاگین رفرش می‌شود و ورود امکان‌پذیر نیست

### 🔍 علل احتمالی:

#### 1. مشکل در متغیرهای محیطی (.env)
```bash
# متغیرهای ضروری که باید در لیارا تنظیم شوند:
APP_KEY=base64:YOUR_GENERATED_KEY
APP_URL=https://yourdomain.liara.run
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

#### 2. مشکل در APP_KEY
```bash
# در terminal لیارا اجرا کنید:
php artisan key:generate --force
```

#### 3. مشکل در Session Driver
```bash
# اگر جدول sessions وجود ندارد:
php artisan migrate
```

#### 4. مشکل در Cache و Config
```bash
# پاک کردن cache ها:
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
```

### 🔧 مراحل رفع مشکل:

#### مرحله 1: بررسی Environment Variables
در پنل لیارا > تنظیمات > متغیرهای محیطی:
- `APP_KEY` را تنظیم کنید
- `APP_URL` را به آدرس لیارا تغییر دهید
- `SESSION_DRIVER=database` تنظیم کنید
- `APP_DEBUG=false` قرار دهید

#### مرحله 2: بررسی دیتابیس
```sql
-- بررسی وجود جدول sessions:
SHOW TABLES LIKE 'sessions';

-- اگر وجود ندارد، migration اجرا کنید:
php artisan migrate
```

#### مرحله 3: بررسی Permissions
```bash
# تنظیم permissions:
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

#### مرحله 4: تست با Browser مختلف
- Chrome (حالت incognito)
- Firefox
- Safari
- پاک کردن cookies و cache

### 🔄 Commands مفید برای Debug:

```bash
# نمایش config فعلی:
php artisan config:show session

# بررسی connection دیتابیس:
php artisan migrate:status

# تست route ها:
php artisan route:list | grep login

# بررسی logs:
tail -f storage/logs/laravel.log
```

### 🌐 تنظیمات خاص لیارا:

#### در فایل liara.json:
```json
{
  "platform": "laravel",
  "app": "microbime",
  "build": {
    "buildpack": "heroku/php",
    "location": "./"
  },
  "run": {
    "args": [
      "php", "artisan", "serve", 
      "--host=0.0.0.0", 
      "--port=$PORT"
    ]
  }
}
```

#### اسکریپت Deploy:
```bash
#!/bin/bash
echo "🚀 Starting deployment..."

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate --force

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

echo "✅ Deployment completed!"
```

### 🔐 تنظیمات امنیتی Production:

```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SANCTUM_STATEFUL_DOMAINS=yourdomain.liara.run
```

### 🩺 تشخیص مشکل از طریق Browser DevTools:

1. F12 > Network Tab
2. تلاش برای لاگین
3. بررسی HTTP Status Codes:
   - 500: مشکل سرور (بررسی logs)
   - 419: مشکل CSRF Token
   - 302: Redirect مداوم (مشکل Session)

### 📞 تماس با پشتیبانی:
اگر مشکل حل نشد، لاگ‌های زیر را ارسال کنید:
- `storage/logs/laravel.log`
- خروجی `php artisan config:show`
- Network tab از Browser DevTools 