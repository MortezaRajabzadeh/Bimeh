import './bootstrap';
import './validation';

// تشخیص نقش فعال کاربر ادمین
function getActiveRole() {
    const cookies = document.cookie.split(';');
    for (let i = 0; i < cookies.length; i++) {
        const cookie = cookies[i].trim();
        if (cookie.startsWith('active_role=')) {
            return cookie.substring('active_role='.length, cookie.length);
        }
    }
    return null;
}

// اجرای زمانی که صفحه لود می‌شود
document.addEventListener('DOMContentLoaded', function() {
    const activeRole = getActiveRole();
    
    if (activeRole) {
        // تغییر کلاس‌های CSS بر اساس نقش فعال
        document.body.classList.add(`role-${activeRole}`);
        
        // می‌توانید کلاس‌های خاصی را به المان‌های منو اضافه کنید
        const menuItems = document.querySelectorAll('.role-based-menu');
        menuItems.forEach(item => {
            if (item.dataset.role === activeRole) {
                item.classList.add('active-role-menu');
            }
        });
    }
});
