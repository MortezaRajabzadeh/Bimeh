# راهنمای استفاده از ربات تلگرام

این مستند نحوه راه‌اندازی و استفاده از ربات تلگرام در پروژه را توضیح می‌دهد.

## پیش‌نیازها

1. توکن ربات تلگرام (از [BotFather](https://t.me/BotFather) دریافت کنید)
2. نام کاربری ربات بدون علامت `@`
3. شناسه عددی ادمین تلگرام (اختیاری)

## تنظیمات

متغیرهای محیطی زیر را در فایل `.env` تنظیم کنید:

```
TELEGRAM_BOT_TOKEN=123456789:ABCDefGhIJKlmNoPQRsTUVwxyZ
TELEGRAM_BOT_USERNAME=MyBotUsername
TELEGRAM_ADMIN_USER_ID=123456789
```

## مسیرهای تعریف شده

مسیرهای زیر برای تلگرام تعریف شده‌اند:

- `POST /telegram/webhook`: پردازش درخواست‌های webhook از سرور تلگرام
- `GET /telegram/setup-webhook`: تنظیم webhook (فقط برای ادمین‌ها)
- `GET /telegram/bot-info`: نمایش اطلاعات ربات (فقط برای ادمین‌ها)

## راه‌اندازی Webhook

برای راه‌اندازی webhook، به عنوان یک ادمین وارد سیستم شوید و به آدرس زیر مراجعه کنید:

```
https://yourdomain.com/telegram/setup-webhook
```

## استفاده از سرویس اعلان تلگرام

برای ارسال پیام به کاربران از طریق تلگرام، از کلاس `NotificationService` استفاده کنید:

```php
use App\Services\Notification\NotificationService;

class ExampleController extends Controller
{
    protected NotificationService $notificationService;
    
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    public function sendMessage()
    {
        // ارسال پیام متنی ساده
        $this->notificationService->viaTelegram(123456789, 'سلام! این یک پیام آزمایشی است.');
    }
}
```

## استفاده مستقیم از TelegramChannel

برای استفاده از امکانات پیشرفته‌تر، می‌توانید مستقیماً از کلاس `TelegramChannel` استفاده کنید:

```php
use App\Services\Notification\TelegramChannel;

class ExampleController extends Controller
{
    protected TelegramChannel $telegramChannel;
    
    public function __construct(TelegramChannel $telegramChannel)
    {
        $this->telegramChannel = $telegramChannel;
    }
    
    public function sendAdvancedMessages()
    {
        // ارسال پیام با دکمه‌های درون خطی
        $buttons = [
            ['text' => 'دکمه ۱', 'callback_data' => 'action_1'],
            ['text' => 'دکمه ۲', 'callback_data' => 'action_2'],
            ['text' => 'مشاهده وب‌سایت', 'url' => 'https://example.com'],
        ];
        
        $this->telegramChannel->sendWithInlineButtons(
            123456789,
            'لطفاً یکی از گزینه‌ها را انتخاب کنید:',
            $buttons
        );
        
        // ارسال تصویر
        $this->telegramChannel->sendPhoto(
            123456789,
            'https://example.com/image.jpg',
            'توضیحات تصویر'
        );
        
        // ارسال فایل
        $this->telegramChannel->sendDocument(
            123456789,
            'https://example.com/document.pdf',
            'سند مورد نیاز'
        );
    }
}
```

## نکات مهم

- برای دریافت شناسه چت کاربر، باید ابتدا کاربر با ربات شما ارتباط برقرار کند
- شناسه چت کاربران در تلگرام می‌تواند یک عدد مثبت (برای کاربران) یا منفی (برای گروه‌ها) باشد
- تأکید می‌شود از webhook استفاده کنید و از long polling در محیط تولید خودداری نمایید

## توسعه و گسترش

برای گسترش قابلیت‌های ربات، می‌توانید:

1. کامندهای جدید در دایرکتوری `app/Commands` ایجاد کنید (به مستندات [php-telegram-bot](https://github.com/php-telegram-bot/core) مراجعه کنید)
2. متدهای جدید به کلاس `TelegramChannel` اضافه کنید
3. منطق پردازش webhook در `TelegramController` را سفارشی‌سازی کنید 