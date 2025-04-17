// اسکریپت برای صفحه خانه اپلیکیشن

document.addEventListener('DOMContentLoaded', function() {
    // برای سازگاری با لیوایر، منتظر رویدادهای مرتبط با لیوایر می‌مانیم
    document.addEventListener('livewire:initialized', function() {
        setupApp();
    });
    
    // راه‌اندازی اولیه برنامه حتی اگر لیوایر در دسترس نباشد
    setupApp();
});

function setupApp() {
    // انیمیشن آیکون منو
    const menuIcons = document.querySelectorAll('.menu-icon');
    menuIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            this.classList.toggle('active');
        });
    });

    // نمایش کلاس فعال برای آیتم منوی فوتر
    const footerItems = document.querySelectorAll('.footer-item');
    footerItems.forEach(item => {
        item.addEventListener('click', function() {
            footerItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // اگر لیوایر در دسترس است، داده‌های توگل را با رویدادها همگام می‌کنیم
    if (window.Livewire) {
        const toggleInputs = document.querySelectorAll('.toggle-switch input[type="checkbox"]');
        toggleInputs.forEach(input => {
            input.addEventListener('change', function() {
                // به لیوایر اطلاع می‌دهیم که مقدار تغییر کرده است
                const livewireComponent = window.Livewire.find(
                    input.closest('[wire\\:id]').getAttribute('wire:id')
                );
                
                if (livewireComponent) {
                    livewireComponent.call('toggleApp', this.checked);
                }
            });
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // تاگل سوییچ فعال‌سازی اپلیکیشن
    const appToggle = document.getElementById('app-toggle');
    const infoBox = document.querySelector('.info-box');
    
    // بررسی وضعیت ذخیره شده از قبل
    const savedToggleState = localStorage.getItem('appToggleState');
    if (savedToggleState === 'true') {
        appToggle.checked = true;
        infoBox.style.display = 'none';
    } else {
        appToggle.checked = false;
        infoBox.style.display = 'flex';
    }
    
    // تغییر وضعیت تاگل و ذخیره آن
    appToggle.addEventListener('change', () => {
        if (appToggle.checked) {
            // اپلیکیشن فعال شده
            localStorage.setItem('appToggleState', 'true');
            infoBox.style.display = 'none';
        } else {
            // اپلیکیشن غیرفعال شده
            localStorage.setItem('appToggleState', 'false');
            infoBox.style.display = 'flex';
        }
    });
    
    // دکمه ثبت سفر جدید
    const addTripButton = document.querySelector('.add-trip-button');
    addTripButton.addEventListener('click', () => {
        // در حالت واقعی، هدایت به صفحه ثبت سفر جدید
        alert('در حال انتقال به صفحه ثبت سفر جدید');
    });
    
    // آیتم‌های منوی پایین
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            // حذف کلاس active از همه آیتم‌ها
            navItems.forEach(navItem => navItem.classList.remove('active'));
            
            // اضافه کردن کلاس active به آیتم کلیک شده
            item.classList.add('active');
            
            // در حالت واقعی، هدایت به صفحه مربوطه
            const navText = item.querySelector('.nav-text').textContent;
            if (navText !== 'خانه') {
                alert(`در حال انتقال به صفحه ${navText}`);
            }
        });
    });
}); 