<!-- منوی اصلی سایت -->
<aside id="sidebar" class="fixed top-0 bottom-0 right-0 z-40 h-screen transition-transform lg:translate-x-0 bg-white shadow-lg sidebar-collapsed">
    <!-- بخش لوگو -->
    <div class="flex items-center justify-center py-3 border-b border-gray-200">
        <div class="logo-container">
            <img src="{{ asset('images/image.png') }}" alt="لوگو خیریه" class="logo-image">
        </div>
    </div>

    <!-- آیتم‌های منو -->
    <nav class="flex flex-col flex-grow py-2">
        <a href="{{ auth()->check() && auth()->user()->user_type === 'admin' ? route('admin.dashboard') : (auth()->check() && auth()->user()->user_type === 'charity' ? route('charity.dashboard') : (auth()->check() && auth()->user()->user_type === 'insurance' ? route('insurance.dashboard') : '#')) }}" 
           class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('*.dashboard') ? 'bg-gray-100' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
            </svg>
            <span class="sidebar-text">داشبورد</span>
        </a>

        <!-- منوی مخصوص خیریه -->
        @if(auth()->check() && (auth()->user()->user_type === 'charity' || auth()->user()->user_type === 'admin'))
            <a href="{{ route('charity.insured-families') }}" 
               class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('charity.insured-families') ? 'bg-green-500 text-white' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3 {{ request()->routeIs('charity.insured-families') ? 'text-white' : 'text-gray-700' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <div class="flex flex-col sidebar-text">
                    <span>خانواده‌های بیمه شده</span>
                </div>
            </a>
            
            <a href="{{ route('charity.uninsured-families') }}" 
               class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('charity.uninsured-families') ? 'bg-red-500 text-white' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3 {{ request()->routeIs('charity.uninsured-families') ? 'text-white' : 'text-gray-700' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
                <div class="flex flex-col sidebar-text">
                    <span>خانواده‌های بدون پوشش</span>
                </div>
            </a>

            @if(auth()->check() && auth()->user()->user_type === 'charity')
                <a href="{{ route('charity.add-family') }}" 
                   class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('charity.add-family') ? 'bg-blue-500 text-white' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3 {{ request()->routeIs('charity.add-family') ? 'text-white' : 'text-gray-700' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span class="sidebar-text">افزودن خانواده جدید</span>
                </a>
            @endif
        @endif

        <!-- منوی مخصوص بیمه -->
        @if(auth()->check() && (auth()->user()->user_type === 'insurance' ))
        
            <a href="{{ route('insurance.insured-families') }}" 
               class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('insurance.insured-families') ? 'bg-green-500 text-white' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3 {{ request()->routeIs('insurance.insured-families') ? 'text-white' : 'text-gray-700' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <div class="flex flex-col sidebar-text">
                    <span>خانواده‌های بیمه شده</span>
                </div>
            </a>
            
            <a href="{{ route('insurance.uninsured-families') }}" 
               class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('insurance.uninsured-families') ? 'bg-red-500 text-white' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3 {{ request()->routeIs('insurance.uninsured-families') ? 'text-white' : 'text-gray-700' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
                <div class="flex flex-col sidebar-text">
                    <span>خانواده‌های بدون پوشش</span>
                </div>
            </a>

            <a href="#" class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100" onclick="event.preventDefault();">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.591 1.106c1.527-.878 3.286.88 2.408 2.408a1.724 1.724 0 001.107 2.592c1.755.425 1.755 2.923 0 3.349a1.724 1.724 0 00-1.107 2.592c.878 1.527-.881 3.286-2.408 2.408a1.724 1.724 0 00-2.592 1.107c-.425 1.755-2.923 1.755-3.349 0a1.724 1.724 0 00-2.592-1.107c-1.527.878-3.286-.881-2.408-2.408a1.724 1.724 0 00-1.107-2.592c-1.755-.426-1.755-2.924 0-3.35a1.724 1.724 0 001.107-2.592c-.878-1.527.881-3.286 2.408-2.408.996.574 2.25.072 2.592-1.106z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="sidebar-text">درخواست‌های بیمه</span>
            </a>
            
            <a href="{{ route('insurance.families.approval') }}" class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span class="sidebar-text">تایید خانواده‌ها</span>
            </a>
        @endif

        <!-- منوی فقط مخصوص ادمین -->
        @if(auth()->check() && auth()->user()->user_type === 'admin')
            <a href="{{ route('admin.users.index') }}" class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('admin.users.*') ? 'bg-gray-100' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="sidebar-text">مدیریت کاربران</span>
            </a>

            <a href="{{ route('admin.organizations.index') }}" class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('admin.organizations.*') ? 'bg-gray-100' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span class="sidebar-text">سازمان‌ها</span>
            </a>
        @endif

        @if(auth()->check() && auth()->user()->user_type === 'insurance')
            <a href="{{ route('insurance.financial-report') }}"
               class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('insurance.financial-report') ? 'bg-gray-100' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm0 10c-4.41 0-8-1.79-8-4V6c0-2.21 3.59-4 8-4s8 1.79 8 4v8c0 2.21-3.59 4-8 4z" />
                </svg>
                <span class="sidebar-text">گزارش مالی</span>
            </a>
        @endif
    </nav>
    
    <!-- منوی پایین (تنظیمات و خروج) -->
    <div class="mt-auto border-t border-gray-200">
        @if(auth()->check())
            @php
                $settingsRoute = null;
                $settingsText = null;
                if(auth()->user()->hasRole('admin')) {
                    $settingsRoute = route('admin.settings');
                    $settingsText = 'تنظیمات ادمین';
                } elseif(auth()->user()->hasRole('charity')) {
                    $settingsRoute = route('charity.settings');
                    $settingsText = 'تنظیمات خیریه';
                } elseif(auth()->user()->hasRole('insurance')) {
                    $settingsRoute = route('insurance.settings');
                    $settingsText = 'تنظیمات بیمه';
                }
            @endphp
            @if($settingsRoute)
                <a href="{{ $settingsRoute }}"
                   class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->url() === $settingsRoute ? 'bg-gray-100' : '' }}">
            
                    <svg 
                    xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                       
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.591 1.106c1.527-.878 3.286.88 2.408 2.408a1.724 1.724 0 001.107 2.592c1.755.425 1.755 2.923 0 3.349a1.724 1.724 0 00-1.107 2.592c.878 1.527-.881 3.286-2.408 2.408a1.724 1.724 0 00-2.592 1.107c-.425 1.755-2.923 1.755-3.349 0a1.724 1.724 0 00-2.592-1.107c-1.527.878-3.286-.881-2.408-2.408a1.724 1.724 0 00-1.107-2.592c-1.755-.426-1.755-2.924 0-3.35a1.724 1.724 0 001.107-2.592c-.878-1.527.881-3.286 2.408-2.408.996.574 2.25.072 2.592-1.106z" />
                         <path stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="sidebar-text">{{ $settingsText }}</span>
                </a>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="sidebar-item w-full flex items-center py-3 px-6 hover:bg-gray-100 text-red-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="sidebar-text">خروج</span>
                </button>
            </form>
        @endif
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        let sidebarTimer = null;
        
        // تابع برای باز کردن منو
        function expandSidebar() {
            if (sidebar && sidebar.classList.contains('sidebar-collapsed')) {
                sidebar.classList.remove('sidebar-collapsed');
                sidebar.classList.add('sidebar-expanded');
                
                // ارسال رویداد برای آگاهی app.blade.php
                document.dispatchEvent(new CustomEvent('sidebar-toggle', {
                    detail: { collapsed: false }
                }));
                
                // لغو هر تایمر فعال
                if (sidebarTimer) {
                    clearTimeout(sidebarTimer);
                    sidebarTimer = null;
                }
            }
        }
        
        // تابع برای بستن منو
        function collapseSidebar() {
            if (sidebar && !sidebar.classList.contains('sidebar-collapsed')) {
                sidebar.classList.add('sidebar-collapsed');
                sidebar.classList.remove('sidebar-expanded');
                
                // ارسال رویداد برای آگاهی app.blade.php
                document.dispatchEvent(new CustomEvent('sidebar-toggle', {
                    detail: { collapsed: true }
                }));
            }
        }
        
        // تنظیم وضعیت اولیه منو
        function initSidebar() {
            if (sidebar) {
                // در ابتدا منو بسته باشد
                sidebar.classList.add('sidebar-collapsed');
                sidebar.classList.remove('sidebar-expanded');
                
                // ارسال رویداد برای آگاهی app.blade.php
                document.dispatchEvent(new CustomEvent('sidebar-toggle', {
                    detail: { collapsed: true }
                }));
                
                // در localStorage هم ذخیره کنیم برای هماهنگی با app.blade.php
                localStorage.setItem('sidebarState', 'collapsed');
            }
        }
        
        // اجرای تنظیمات اولیه
        initSidebar();
        
        // باز کردن منو با هاور ماوس
        if (sidebar) {
            sidebar.addEventListener('mouseenter', function() {
                expandSidebar();
            });
            
            // بستن منو وقتی ماوس از روی آن برداشته می‌شود - بلافاصله
            sidebar.addEventListener('mouseleave', function() {
                collapseSidebar(); // بستن بلافاصله بدون تایمر
            });
        }
        
        // ریست تایمر با کلیک روی آیتم‌های منو - دیگر نیازی نیست
        // اما برای کلیک‌های داخل منو، منو را باز نگه‌داریم
        const sidebarItems = document.querySelectorAll('.sidebar-item');
        sidebarItems.forEach(item => {
            item.addEventListener('click', function(event) {
                // جلوگیری از بسته شدن منو هنگام کلیک روی آیتم‌ها
                event.stopPropagation();
            });
        });
        
        // پاسخگویی به تغییر اندازه صفحه
        window.addEventListener('resize', function() {
            if (window.innerWidth < 1024) {
                // در نمای موبایل، منو را به صورت کامل پنهان کن
                sidebar.classList.add('-translate-x-full');
            } else {
                // در نمای دسکتاپ، منو را نمایش بده
                sidebar.classList.remove('-translate-x-full');
            }
        });
    });
</script>

<style>
    /* استایل‌های سایدبار */
    #sidebar {
        width: 4rem; /* حالت پیش‌فرض بسته */
        transition: width 0.3s ease-in-out;
        display: flex;
        flex-direction: column;
    }
    
    /* استایل‌های جدید برای حالت جمع شده */
    #sidebar.sidebar-collapsed {
        width: 4rem; /* 64px */
    }
    
    #sidebar.sidebar-expanded {
        width: 16rem; /* 256px */
    }
    
    /* استایل‌های لوگو */
    .logo-container {
        width: 2.5rem;
        height: 2.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease-in-out;
    }
    
    #sidebar.sidebar-expanded .logo-container {
        width: 5rem;
        height: 5rem;
    }
    
    .logo-image {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    
    /* مخفی کردن متن‌ها در حالت جمع شده */
    #sidebar.sidebar-collapsed .sidebar-text {
        display: none;
    }
    
    /* تنظیم آیکون‌ها در حالت جمع شده */
    #sidebar.sidebar-collapsed .sidebar-item {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }
    
    #sidebar.sidebar-collapsed .sidebar-item svg {
        margin-left: 0;
    }

    /* حالت دسکتاپ - تنظیم فضای اصلی با توجه به وضعیت منو */
    /* @media (min-width: 1024px) {
        #main-wrapper:not(.sidebar-collapsed) {
            margin-right: 16rem;
            transition: margin 0.3s ease-in-out;
        }
        
        #main-wrapper.sidebar-collapsed {
            margin-right: 4rem;
            transition: margin 0.3s ease-in-out;
        }
    } */
    
    /* اصلاح مشکل مارجین در حالت جمع شده و گسترده */
    /* #sidebar.sidebar-collapsed ~ #main-wrapper {
        margin-right: 4rem !important;
    }
    
    #sidebar.sidebar-expanded ~ #main-wrapper {
        margin-right: 16rem !important;
    } */
</style> 