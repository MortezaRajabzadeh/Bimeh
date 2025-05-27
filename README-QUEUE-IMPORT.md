# 🚀 سیستم Queue-based Import خانواده‌ها

سیستم آپلود فایل‌های اکسل برای ثبت گروهی خانواده‌ها به صورت هوشمند طراحی شده که بسته به سایز فایل، به دو روش مختلف عمل می‌کند:

## 📊 تشخیص خودکار نوع پردازش

### فایل‌های کوچک (کمتر از 2MB)
- پردازش **مستقیم** و **بی‌درنگ**
- نمایش نتیجه فوری در همان صفحه
- مناسب برای فایل‌های حاوی کمتر از 100 خانواده

### فایل‌های بزرگ (بیشتر از 2MB)  
- پردازش در **پس‌زمینه** (Background Queue)
- نمایش پیش‌رفت به صورت زنده
- اعلان‌رسانی از طریق تلگرام (اختیاری)
- مناسب برای فایل‌های حاوی 1000+ خانواده

## 🛠️ مزایای سیستم Queue

### ✅ مزایا:
- **جلوگیری از Timeout**: فایل‌های بزرگ بدون قطع شدن پردازش می‌شوند
- **مدیریت منابع**: عدم مصرف زیاد حافظه سرور
- **تجربه کاربری بهتر**: کاربر نیازی به انتظار طولانی ندارد
- **اعلان‌رسانی**: کاربر از نتیجه مطلع می‌شود
- **Progress Tracking**: نمایش وضعیت پردازش به صورت زنده
- **خطایابی بهتر**: لاگ کامل از تمام مراحل
- **قابلیت Retry**: تلاش مجدد در صورت شکست

### 🔄 فرآیند پردازش در Queue:

1. **آپلود فایل** → ذخیره موقت در storage
2. **تشخیص سایز** → تصمیم‌گیری روش پردازش  
3. **Queue Job** → ارسال به صف پردازش
4. **Progress Tracking** → بروزرسانی وضعیت هر 3 ثانیه
5. **پردازش اکسل** → خواندن و اعتبارسنجی داده‌ها
6. **ثبت در دیتابیس** → ایجاد خانواده‌ها و اعضا
7. **اعلان نتیجه** → نمایش در داشبورد + تلگرام

## 📁 فایل‌های مربوطه:

### Backend:
- `app/Jobs/ProcessFamiliesImport.php` - Job اصلی پردازش
- `app/Http/Controllers/Charity/ImportController.php` - کنترلر آپلود
- `app/Imports/FamiliesImport.php` - کلاس import اکسل

### Frontend:  
- `resources/views/charity/dashboard.blade.php` - نمایش وضعیت
- `resources/views/livewire/charity/family-search.blade.php` - مودال آپلود

### Routes:
- `POST /charity/import` - آپلود فایل
- `GET /charity/import/status` - چک وضعیت job

## 🔧 تنظیمات مورد نیاز:

### 1. Queue Driver
برای production بهتر است از Redis یا Database استفاده کنید:
```bash
# در .env
QUEUE_CONNECTION=database
# یا 
QUEUE_CONNECTION=redis
```

### 2. اجرای Queue Worker
```bash
# تست (یک بار)
php artisan queue:work --once

# Production (دایمی)
php artisan queue:work --daemon --tries=3

# با Supervisor (پیشنهادی)
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

### 3. تنظیم Cache
برای ذخیره وضعیت job‌ها:
```bash
# در .env
CACHE_DRIVER=redis
# یا
CACHE_DRIVER=database
```

## 📱 اعلان‌رسانی تلگرام:

اگر سرویس تلگرام راه‌اندازی شده باشد، کاربران اعلان دریافت می‌کنند:
- ✅ موفقیت: تعداد خانواده‌ها و اعضای ثبت شده
- ❌ خطا: توضیح خطا و راهنمای رفع

## 🚨 نکات مهم:

### امنیت:
- فقط کاربران با role `charity` دسترسی دارند
- Permission `create family` الزامی است
- فایل‌های موقت بعد از پردازش حذف می‌شوند

### عملکرد:
- Cache: وضعیت job‌ها 1 ساعت نگهداری می‌شود
- Timeout: Job‌ها حداکثر 30 دقیقه فرصت دارند
- Retry: در صورت شکست، 3 بار تلاش می‌شود

### فرمت فایل:
- پشتیبانی: `.xlsx`, `.xls`, `.csv`
- حداکثر سایز: 10MB
- حداکثر تعداد ردیف: بدون محدودیت (با Queue)

## 🔍 نمونه استفاده:

```php
// تست manual
$job = new ProcessFamiliesImport($user, $regionId, $filePath, $fileName);
dispatch($job);

// چک وضعیت  
$status = Cache::get("import_job_{$jobId}");
```

## 📈 مانیتورینگ:

### لاگ‌ها:
- `storage/logs/laravel.log` - لاگ‌های کلی
- Queue failures در جدول `failed_jobs`

### آمار:
- تعداد job‌های در صف: `php artisan queue:status`
- کارایی worker: مانیتور تعداد پردازش شده

---

> **نکته**: برای تست محلی می‌توانید با دستور `php artisan queue:work` worker را اجرا کنید و فایل آپلود کنید. 