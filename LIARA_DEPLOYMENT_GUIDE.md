# 🚀 راهنمای Deploy - سیستم مدیریت بیمه خرد

## 📋 مشخصات پروژه

**نام:** سیستم مدیریت بیمه خرد (میکروبیمه)  
**فریمورک:** Laravel 12  
**پلتفرم:** Liara Cloud  
**توسعه دهنده:** [نام شما]

---

## ✅ فایل‌های آماده Deploy

- ✅ `liara.json` - کانفیگ Liara
- ✅ `liara_deploy.sh` - اسکریپت deploy
- ✅ `deploy.sh` - اسکریپت آماده‌سازی
- ✅ Migration files - آماده
- ✅ Seeder files - برای roles و permissions

---

## 🛠️ مراحل Deploy

### 1. نصب Liara CLI:
```bash
npm install -g @liara/cli
```

### 2. لاگین به Liara:
```bash
liara auth:login
```

### 3. ایجاد دیتابیس:
```bash
liara database:create --name microbime-db --plan 1 --type mysql
```

### 4. تنظیم Environment Variables:

در پنل Liara، بخش Environment Variables پروژه، این متغیرها رو اضافه کنید:

```env
APP_NAME=میکروبیمه
APP_ENV=production
APP_KEY=[باید generate کنید]
APP_DEBUG=false
APP_URL=https://[domain-shoma].liara.run

# اطلاعات دیتابیس (از بخش Databases کپی کنید)
DB_HOST=[database-host]
DB_DATABASE=[database-name] 
DB_USERNAME=[database-user]
DB_PASSWORD=[database-password]

# اختیاری - SMS
SMS_DRIVER=melipayamak
SMS_USERNAME=[username-sms]
SMS_PASSWORD=[password-sms]

# اختیاری - Payment Gateway
PAYMENT_GATEWAY=zarinpal
ZARINPAL_MERCHANT_ID=[merchant-id]

# اختیاری - Telegram Bot
TELEGRAM_BOT_TOKEN=[bot-token]
TELEGRAM_BOT_USERNAME=[bot-username]
```

### 5. تولید APP_KEY:
```bash
php artisan key:generate --show
```
مقدار تولید شده رو در `APP_KEY` قرار بدید.

### 6. Deploy:
```bash
liara deploy --app [app-name] --platform laravel
```

### 7. اجرای Database Seeds:
```bash
# در terminal Liara یا SSH
php artisan db:seed
```

---

## 🔧 تنظیمات اولیه بعد از Deploy

### 1. ایجاد ادمین اول:
```bash
php artisan tinker
>>> User::create([
    'name' => 'مدیر سیستم',
    'mobile' => '09123456789',
    'user_type' => 'admin'
]);
>>> exit
```

### 2. تست سیستم:
- بازدید از صفحه اصلی
- لاگین با شماره موبایل ادمین
- چک کردن داشبوردها

---

## 📊 امکانات سیستم

### 🏢 Dashboard ادمین:
- مدیریت کاربران
- مدیریت مناطق
- مدیریت سازمان‌ها
- گزارش‌های کلی

### 🏦 Dashboard بیمه:
- بررسی خانواده‌ها
- تایید/رد درخواست‌ها
- مدیریت پرداخت‌ها
- گزارش مالی

### ❤️ Dashboard خیریه:
- ثبت خانواده‌ها
- مدیریت اعضای خانواده
- آپلود اکسل
- گزارش‌گیری

---

## 🔐 نقش‌های کاربری

### Admin (ادمین سیستم):
- دسترسی کامل
- مدیریت کاربران
- تنظیمات سیستم

### Insurance (بیمه):
- مشاهده همین خانواده‌ها
- تایید/رد درخواست‌ها
- گزارش‌های پیشرفته

### Charity (خیریه):
- ثبت خانواده‌های خودی
- مدیریت اعضا
- گزارش‌های محدود

---

## 🚨 نکات مهم

### امنیت:
- ✅ همیشه `APP_DEBUG=false` در production
- ✅ پسورد قوی برای دیتابیس
- ✅ backup منظم دیتابیس

### عملکرد:
- ✅ Cache فعال
- ✅ Session روی database
- ✅ Log level مناسب

### نگهداری:
- ✅ بررسی logs منظم
- ✅ بروزرسانی Laravel
- ✅ مانیتورینگ performance

---

## 🆘 حل مشکلات رایج

### خطای 500:
1. چک کردن logs در Liara
2. بررسی APP_KEY
3. مجوزهای فایل‌ها

### مشکل Database:
1. اطلاعات اتصال
2. migration ها
3. seeder ها

### مشکل Assets:
1. `npm run build`
2. چک کردن public/build

---

## 📞 پشتیبانی

**Health Check:** `https://[domain]/health`  
**Laravel Version:** 12.15.0  
**PHP Version:** 8.3+

در صورت نیاز به پشتیبانی فنی، مراحل انجام شده و error logs را ارسال نمایید.

---

## ✅ Checklist Deploy

- [ ] Liara CLI نصب شده
- [ ] Database ایجاد شده
- [ ] Environment variables تنظیم شده
- [ ] APP_KEY generate شده
- [ ] Deploy موفق
- [ ] Migrations اجرا شده
- [ ] Seeds اجرا شده
- [ ] Admin user ایجاد شده
- [ ] تست کامل انجام شده

---

**🎉 سیستم آماده استفاده است!** 