/**
 * اسکریپت مدیریت ویزارد بیمه
 */

document.addEventListener('DOMContentLoaded', function() {
    // مدیریت رویدادهای wizard
    initWizardEvents();
    
    // مدیریت آپدیت وضعیت خانواده‌ها
    initFamilyStatusUpdate();
    
    // مدیریت نمایش و پنهان کردن جزئیات خانواده
    initFamilyDetailToggle();
    
    // مدیریت انیمیشن‌ها و ترنزیشن‌ها
    initAnimations();
    
    // مدیریت پیام‌های کاربری
    initNotifications();
});

/**
 * مدیریت رویدادهای wizard
 */
function initWizardEvents() {
    // گوش‌دهنده برای تغییر تب
    document.addEventListener('livewire:initialized', function () {
        Livewire.on('tabChanged', ({ tab }) => {
            // اسکرول به بالای صفحه برای تجربه کاربری بهتر
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // غیرفعال کردن موقت دکمه‌های تب برای جلوگیری از کلیک مکرر
            const tabButtons = document.querySelectorAll('.wizard-step');
            tabButtons.forEach(button => {
                button.classList.add('pointer-events-none', 'opacity-70');
            });
            
            // فعال‌سازی مجدد دکمه‌ها پس از 1.5 ثانیه
            setTimeout(() => {
                tabButtons.forEach(button => {
                    button.classList.remove('pointer-events-none', 'opacity-70');
                });
            }, 1500);
            
            // بروزرسانی کلاس‌های مراحل ویزارد
            updateWizardSteps(tab);
        });
    });
}

/**
 * بروزرسانی کلاس‌های مراحل ویزارد
 * @param {string} currentTab - تب فعلی
 */
function updateWizardSteps(currentTab) {
    const wizardSteps = ['pending', 'reviewing', 'approved', 'insured', 'renewal'];
    const currentIndex = wizardSteps.indexOf(currentTab);
    
    if (currentIndex === -1) return;
    
    // برای هر مرحله، وضعیت completed یا active را تنظیم می‌کنیم
    wizardSteps.forEach((tab, index) => {
        const stepElement = document.querySelector(`.wizard-step[data-step="${tab}"]`);
        if (!stepElement) return;
        
        if (index < currentIndex) {
            stepElement.classList.add('completed');
            stepElement.classList.remove('active');
        } else if (index === currentIndex) {
            stepElement.classList.add('active');
            stepElement.classList.remove('completed');
        } else {
            stepElement.classList.remove('active', 'completed');
        }
    });
}

/**
 * مدیریت آپدیت وضعیت خانواده‌ها
 */
function initFamilyStatusUpdate() {
    // تعریف فانکشن به صورت گلوبال
    window.updateFamiliesStatus = function(familyIds, status, currentStatus = null) {
        console.log('updateFamiliesStatus called with:', {
            familyIds: familyIds,
            status: status,
            currentStatus: currentStatus
        });
        
        // بررسی خالی بودن آرایه انتخاب‌ها
        if (!familyIds || familyIds.length === 0) {
            showNotification('error', 'لطفاً حداقل یک خانواده انتخاب کنید.');
            return;
        }
        
        // نمایش لودینگ
        if (typeof Livewire !== 'undefined') {
            Livewire.dispatch('showLoading', { message: 'در حال پردازش...' });
        }
        
        // ارسال درخواست به سرور
        fetch('/insurance/families/bulk-update-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                family_ids: familyIds,
                status: status,
                current_status: currentStatus
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            
            // مخفی کردن لودینگ
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('hideLoading');
            }
            
            if (data.success) {
                // اگر نیاز به سهم‌بندی داریم
                if (data.require_shares) {
                    console.log('Opening share allocation modal with family_ids:', data.family_ids);
                    
                    // ارسال پارامتر familyIds به صورت مستقیم و ساده
                    Livewire.dispatch('openShareAllocationModal', data.family_ids);
                } else {
                    // نمایش پیام موفقیت
                    showNotification('success', data.message || 'عملیات با موفقیت انجام شد');
                    
                    // بارگذاری مجدد برای نمایش تغییرات
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            } else {
                // نمایش پیام خطا
                showNotification('error', data.message || 'خطایی رخ داده است.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            
            // مخفی کردن لودینگ
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('hideLoading');
            }
            
            // نمایش پیام خطا
            showNotification('error', 'خطا در ارتباط با سرور: ' + error.message);
        });
    };
    
    // گوش‌دهنده برای تخصیص سهم‌ها
    window.addEventListener('sharesAllocated', function() {
        // نمایش پیام موفقیت آمیز
        showNotification('success', 'سهم‌بندی با موفقیت انجام شد.');
        
        // بارگذاری مجدد صفحه بعد از تأخیر
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    });
}

/**
 * مدیریت نمایش و پنهان کردن جزئیات خانواده
 */
function initFamilyDetailToggle() {
    // گوش‌دهنده برای دکمه‌های نمایش/پنهان سازی جزئیات خانواده
    document.addEventListener('click', function(e) {
        if (e.target.closest('.toggle-family-btn')) {
            const btn = e.target.closest('.toggle-family-btn');
            const familyId = btn.getAttribute('data-family-id');
            
            // تغییر آیکون دکمه
            const icon = btn.querySelector('svg');
            if (icon) {
                icon.classList.toggle('rotate-180');
            }
        }
    });
}

/**
 * مدیریت انیمیشن‌ها و ترنزیشن‌ها
 */
function initAnimations() {
    // انیمیشن fade-in برای اجزای UI
    document.querySelectorAll('.animate-on-load').forEach(el => {
        el.classList.add('animate-fade-in');
    });
    
    // تاخیر در ظاهر شدن المان‌ها به ترتیب
    document.querySelectorAll('.stagger-item').forEach((el, index) => {
        el.style.animationDelay = `${index * 0.1}s`;
        el.classList.add('animate-fade-in');
    });
}

/**
 * مدیریت پیام‌های کاربری
 */
function initNotifications() {
    // تعریف تابع نمایش پیام به صورت گلوبال
    window.showNotification = function(type, message) {
        if (typeof Livewire !== 'undefined') {
            Livewire.dispatch('showToast', { 
                type: type, 
                message: message 
            });
        } else {
            alert(message);
        }
    };
    
    // گوش‌دهنده برای رویداد بستن مودال‌ها
    window.addEventListener('closeModalAfterDelay', function() {
        setTimeout(function() {
            if (typeof Alpine !== 'undefined') {
                // بستن مودال‌های آلپاین
                document.querySelectorAll('[x-data]').forEach(function(el) {
                    if (el.__x) {
                        if (typeof el.__x.$data.showShareModal !== 'undefined') {
                            el.__x.$data.showShareModal = false;
                        }
                        if (typeof el.__x.$data.showApproveModal !== 'undefined') {
                            el.__x.$data.showApproveModal = false;
                        }
                        if (typeof el.__x.$data.showApproveAndContinueModal !== 'undefined') {
                            el.__x.$data.showApproveAndContinueModal = false;
                        }
                        if (typeof el.__x.$data.showExcelUploadModal !== 'undefined') {
                            el.__x.$data.showExcelUploadModal = false;
                        }
                        if (typeof el.__x.$data.showDeleteModal !== 'undefined') {
                            el.__x.$data.showDeleteModal = false;
                        }
                    }
                });
            }
        }, 2000);
    });
    
    // گوش‌دهنده برای رویداد تغییر مسیر
    window.addEventListener('redirectAfterShares', function(event) {
        console.log('redirectAfterShares event received:', event.detail);
        
        if (event.detail && event.detail.url) {
            // نمایش پیام هدایت
            showNotification('info', 'در حال هدایت به صفحه گزارش مالی...');
            
            // ذخیره آدرس برای هدایت
            const redirectUrl = event.detail.url;
            
            // تاخیر در هدایت
            setTimeout(function() {
                console.log('Redirecting to:', redirectUrl);
                window.location.href = redirectUrl;
            }, event.detail.delay || 2000);
        }
    });
} 