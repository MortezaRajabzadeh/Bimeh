document.addEventListener('DOMContentLoaded', () => {
    const phoneInput = document.getElementById('phone');
    const checkIcon = document.querySelector('.check-icon');
    const submitButton = document.querySelector('.submit-button');
    
    // مخفی کردن آیکون تایید در ابتدا
    checkIcon.style.display = 'none';
    
    // بررسی ورودی شماره موبایل
    phoneInput.addEventListener('input', (e) => {
        const value = e.target.value;
        
        // نمایش آیکون تایید اگر شماره موبایل معتبر باشد
        if (value.length === 11 && value.startsWith('09')) {
            checkIcon.style.display = 'flex';
        } else {
            checkIcon.style.display = 'none';
        }
    });
    
    // ارسال فرم
    submitButton.addEventListener('click', () => {
        const phoneNumber = phoneInput.value;
        
        if (phoneNumber.length === 11 && phoneNumber.startsWith('09')) {
            // در حالت واقعی، اینجا کد ارسال درخواست به سرور قرار می‌گیرد
            alert('کد تایید به شماره ' + phoneNumber + ' ارسال شد.');
        } else {
            alert('لطفا یک شماره موبایل معتبر وارد کنید.');
        }
    });
}); 