# 🏥 سیستم مدیریت بیمه خرد

سیستم جامع مدیریت بیمه‌های خرد برای خیریه‌ها و موسسات حمایتی

## 🎯 ویژگی‌های اصلی

### 👥 مدیریت چندسطحه کاربران
- **ادمین سیستم:** مدیریت کل سیستم
- **شرکت بیمه:** بررسی و تایید درخواست‌ها
- **خیریه‌ها:** ثبت خانواده‌ها و مدیریت اعضا

### 📊 قابلیت‌های کلیدی
- ✅ ثبت و مدیریت خانواده‌های نیازمند
- ✅ سیستم تایید چندمرحله‌ای
- ✅ آپلود و پردازش فایل‌های Excel
- ✅ گزارش‌گیری مالی تفصیلی
- ✅ مدیریت پرداخت‌ها
- ✅ سیستم notification

### 🔐 امنیت و دسترسی
- ✅ احراز هویت با OTP
- ✅ مدیریت نقش‌ها و مجوزها
- ✅ لاگ تمام فعالیت‌ها
- ✅ امنیت اطلاعات شخصی

---

## 🛠️ تکنولوژی‌های استفاده شده

- **Backend:** Laravel 12
- **Frontend:** Livewire 3 + Alpine.js
- **UI:** Tailwind CSS
- **Database:** MySQL
- **Queue:** Database Queue
- **Cache:** Database Cache
- **Build:** Vite

### 📦 پکیج‌های اصلی:
- `spatie/laravel-permission` - مدیریت دسترسی‌ها
- `maatwebsite/excel` - پردازش فایل‌های Excel
- `shetabit/payment` - درگاه پرداخت
- `pamenary/laravel-sms` - ارسال SMS
- `longman/telegram-bot` - ربات تلگرام

---

## 🚀 راه‌اندازی

### پیش‌نیازها:
- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL

### نصب محلی:
```bash
# کلون کردن پروژه
git clone [repository-url]
cd microbime

# نصب dependencies
composer install
npm install

# تنظیم .env
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Build assets
npm run dev
```

### Deploy روی Liara:
مطالعه کنید: [راهنمای Deploy](LIARA_DEPLOYMENT_GUIDE.md)

---

## 📱 صفحات و عملکردها

### 🏢 داشبورد ادمین
- `/admin/dashboard` - آمار کلی سیستم
- `/admin/users` - مدیریت کاربران
- `/admin/organizations` - مدیریت سازمان‌ها
- `/admin/regions` - مدیریت مناطق
- `/admin/logs` - لاگ فعالیت‌ها

### 🏦 داشبورد بیمه
- `/insurance/dashboard` - آمار خانواده‌ها
- `/insurance/families/approval` - تایید درخواست‌ها
- `/insurance/financial-report` - گزارش مالی
- `/insurance/funding-manager` - مدیریت بودجه

### ❤️ داشبورد خیریه
- `/charity/dashboard` - آمار خیریه
- `/charity/families` - مدیریت خانواده‌ها
- `/charity/import` - آپلود Excel
- `/charity/export-excel` - دانلود گزارش

---

## 🔧 API و نقاط پایانی

### Health Check:
- `GET /health` - وضعیت سیستم

### Authentication:
- `POST /login` - احراز هویت با OTP
- `POST /logout` - خروج از سیستم

---

## 📋 Structure پروژه

```
├── app/
│   ├── Http/Controllers/          # کنترلرها
│   ├── Livewire/                 # کامپوننت‌های Livewire
│   ├── Models/                   # مدل‌های دیتابیس
│   ├── Services/                 # سرویس‌ها
│   └── Http/Middleware/          # Middleware ها
├── database/
│   ├── migrations/               # Migration ها
│   └── seeders/                  # Seeder ها
├── resources/
│   ├── views/                    # View ها
│   └── js/                       # Frontend assets
└── routes/
    ├── web.php                   # Route های اصلی
    └── auth.php                  # Route های احراز هویت
```

---

## 🎨 UI/UX

### طراحی:
- ✅ Responsive Design
- ✅ RTL Support (فارسی)
- ✅ Dark/Light Mode Ready
- ✅ Mobile Friendly

### کامپوننت‌ها:
- فرم‌های پیشرفته با validation
- جداول قابل جستجو و مرتب‌سازی
- Modal ها و Dropdown ها
- Charts و گراف‌ها

---

## 🔍 Testing

```bash
# اجرای تست‌ها
php artisan test

# تست‌های مخصوص
php artisan test --filter=AuthTest
```

---

## 📞 پشتیبانی

### مستندات:
- [راهنمای Deploy](LIARA_DEPLOYMENT_GUIDE.md)
- [راهنمای نقش‌ها و مجوزها](ROLES_PERMISSIONS_GUIDE.md)

### در صورت مشکل:
1. بررسی logs در `storage/logs/`
2. چک کردن Health Check: `/health`
3. مطالعه مستندات Laravel

---

## 📄 مجوز

این پروژه توسط [نام فریلنسر] توسعه یافته و برای استفاده اختصاصی تحویل داده شده است.

---

**🎉 موفق باشید!**
