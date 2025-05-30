/**
 * فارسی‌سازی پیام‌های validation سمت کلاینت
 */

// پیام‌های فارسی برای انواع validation
const persianValidationMessages = {
    valueMissing: 'لطفاً این فیلد را تکمیل کنید.',
    typeMismatch: 'فرمت وارد شده صحیح نیست.',
    patternMismatch: 'فرمت وارد شده با الگوی مورد انتظار مطابقت ندارد.',
    tooLong: 'متن وارد شده بیش از حد طولانی است.',
    tooShort: 'متن وارد شده بیش از حد کوتاه است.',
    rangeUnderflow: 'مقدار وارد شده کمتر از حد مجاز است.',
    rangeOverflow: 'مقدار وارد شده بیشتر از حد مجاز است.',
    stepMismatch: 'مقدار وارد شده معتبر نیست.',
    badInput: 'ورودی نامعتبر است.'
};

// فارسی‌سازی پیام‌های validation
function setPersianValidationMessages() {
    const inputs = document.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        // رویداد invalid برای تغییر پیام خطا
        input.addEventListener('invalid', function(e) {
            const validity = e.target.validity;
            let message = '';
            
            // تشخیص نوع خطای validation
            if (validity.valueMissing) {
                message = persianValidationMessages.valueMissing;
            } else if (validity.typeMismatch) {
                if (e.target.type === 'email') {
                    message = 'لطفاً یک آدرس ایمیل معتبر وارد کنید.';
                } else if (e.target.type === 'url') {
                    message = 'لطفاً یک آدرس اینترنتی معتبر وارد کنید.';
                } else {
                    message = persianValidationMessages.typeMismatch;
                }
            } else if (validity.patternMismatch) {
                // پیام‌های خاص برای الگوهای مختلف
                if (e.target.name === 'mobile' || e.target.name === 'phone') {
                    message = 'فرمت شماره موبایل صحیح نیست. (مثال: ۰۹۱۲۳۴۵۶۷۸۹)';
                } else if (e.target.name === 'national_code') {
                    message = 'کد ملی باید ۱۰ رقم باشد.';
                } else {
                    message = persianValidationMessages.patternMismatch;
                }
            } else if (validity.tooLong) {
                message = persianValidationMessages.tooLong;
            } else if (validity.tooShort) {
                message = persianValidationMessages.tooShort;
            } else if (validity.rangeUnderflow) {
                message = persianValidationMessages.rangeUnderflow;
            } else if (validity.rangeOverflow) {
                message = persianValidationMessages.rangeOverflow;
            } else if (validity.stepMismatch) {
                message = persianValidationMessages.stepMismatch;
            } else if (validity.badInput) {
                message = persianValidationMessages.badInput;
            }
            
            // تنظیم پیام سفارشی
            if (message) {
                e.target.setCustomValidity(message);
            }
        });
        
        // پاک کردن پیام سفارشی هنگام تغییر ورودی
        input.addEventListener('input', function(e) {
            e.target.setCustomValidity('');
        });
    });
}

// اجرای function بعد از بارگذاری صفحه
document.addEventListener('DOMContentLoaded', setPersianValidationMessages);

// اجرای function برای محتوای dynamic (مثل Livewire)
document.addEventListener('livewire:load', setPersianValidationMessages);
document.addEventListener('livewire:update', setPersianValidationMessages);

// Export برای استفاده در سایر فایل‌ها
window.setPersianValidationMessages = setPersianValidationMessages; 