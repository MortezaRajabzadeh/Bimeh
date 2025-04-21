<!-- منوی اصلی سایت -->
<div class="sidebar-menu" id="sidebar-menu">
    <div class="fixed h-screen right-0 top-0 w-64 bg-white shadow-md py-5 z-40 transition-all duration-300">
        <!-- دکمه‌ی باز/بسته کردن منو -->
        <button id="sidebar-toggle-btn" class="absolute top-5 left-0 transform -translate-x-full p-2 bg-white rounded-r-lg shadow-md">
            <svg id="collapse-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <svg id="expand-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
        
        <!-- Logo Section -->
        <div class="flex flex-col items-center justify-center py-6 border-b border-gray-200">
            <div class="w-20 h-20 transition-all duration-300">
                <img src="{{ asset('images/image.png') }}" alt="لوگو خیریه" class="w-full">
            </div>
        </div>

        <!-- Menu Items -->
        <div class="flex-grow">
            <a href="{{ route('dashboard') }}" class="flex items-center py-4 px-6 hover:bg-gray-100 {{ request()->routeIs('dashboard') ? 'bg-gray-100' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-700 ml-3 menu-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                </svg>
                <span class="menu-text">داشبورد</span>
            </a>

            <!-- منوی مخصوص خیریه -->
            @if(auth()->check() && (auth()->user()->user_type === 'charity' || auth()->user()->user_type === 'admin'))
                <a href="{{ route('charity.insured-families') }}" class="flex items-center py-4 px-6 hover:bg-gray-100 {{ request()->routeIs('charity.insured-families') ? 'bg-green-500 text-white' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-3 menu-icon {{ request()->routeIs('charity.insured-families') ? 'text-white' : 'text-gray-700' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <div class="flex flex-col menu-text">
                        <span>خانواده‌های بیمه شده</span>

                        <span class="text-xs {{ request()->routeIs('charity.insured-families') ? 'text-white' : 'text-gray-500' }}">
                            @if(isset($insuredFamilies) && isset($insuredMembers))
                                ({{ $insuredFamilies }} خانواده - {{ $insuredMembers }} نفر)
                            @else
                                (۰ خانواده - ۰ نفر)
                            @endif
                        </span>
                    </div>
                </a>
                
                <a href="{{ route('charity.uninsured-families') }}" class="flex items-center py-4 px-6 hover:bg-gray-100 {{ request()->routeIs('charity.uninsured-families') ? 'bg-red-500 text-white' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-3 menu-icon {{ request()->routeIs('charity.uninsured-families') ? 'text-white' : 'text-gray-700' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    <div class="flex flex-col menu-text">
                        <span>خانواده‌های بدون پوشش</span>
                        
                        <span class="text-xs {{ request()->routeIs('charity.uninsured-families') ? 'text-white' : 'text-gray-500' }}">
                            @if(isset($uninsuredFamilies) && isset($uninsuredMembers))
                                ({{ $uninsuredFamilies }} خانواده - {{ $uninsuredMembers }} نفر)
                            @else
                                (۰ خانواده - ۰ نفر)
                            @endif
                        </span>
                    </div>
                </a>

                <a href="{{ route('charity.add-family') }}" class="flex items-center py-4 px-6 hover:bg-gray-100 {{ request()->routeIs('charity.add-family') ? 'bg-blue-500 text-white' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-3 menu-icon {{ request()->routeIs('charity.add-family') ? 'text-white' : 'text-green-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span class="menu-text">افزودن خانواده جدید</span>
                </a>
            @endif

            <!-- منوی مخصوص بیمه -->
            @if(auth()->check() && (auth()->user()->user_type === 'insurance' || auth()->user()->user_type === 'admin'))
                <a href="#" class="flex items-center py-4 px-6 hover:bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-700 ml-3 menu-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="menu-text">درخواست‌های بیمه</span>
                </a>
            @endif

            <!-- منوی فقط مخصوص ادمین -->
            @if(auth()->check() && auth()->user()->user_type === 'admin')
                <a href="#" class="flex items-center py-4 px-6 hover:bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-700 ml-3 menu-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="menu-text">مدیریت کاربران</span>
                </a>

                <a href="#" class="flex items-center py-4 px-6 hover:bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-700 ml-3 menu-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span class="menu-text">سازمان‌ها</span>
                </a>
            @endif
        </div>

        <!-- Footer Menu Items -->
        <div class="border-t border-gray-200 py-2">
            @if(auth()->check() && auth()->user()->user_type === 'charity')
                <a href="{{ route('charity.settings') }}" class="flex items-center py-4 px-6 hover:bg-gray-100 {{ request()->routeIs('charity.settings') ? 'bg-gray-100' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-700 ml-3 menu-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="menu-text">تنظیمات</span>
                </a>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="w-full flex items-center py-4 px-6 hover:bg-gray-100 text-red-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-3 menu-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="menu-text">خروج</span>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    /* استایل‌های مربوط به آیکون‌ها در حالت بسته منو */
    .sidebar-menu.collapsed .menu-icon {
        margin-left: 0;
        margin-right: 0;
        display: flex;
        justify-content: center;
        width: 100%;
    }
    
    /* تنظیم عرض آیتم‌های منو در حالت بسته */
    .sidebar-menu.collapsed a, 
    .sidebar-menu.collapsed button {
        padding-left: 0;
        padding-right: 0;
        justify-content: center;
    }
    
    /* مرکز کردن آیکون‌ها در حالت بسته منو */
    .sidebar-menu.collapsed .fixed.w-16 {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    /* استایل های جدید برای مطمئن شدن از عملکرد درست */
    .sidebar-menu .menu-text {
        display: inline-block;
        transition: all 0.2s ease;
    }
    
    .sidebar-transition {
        transition: all 0.3s ease;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarMenu = document.getElementById('sidebar-menu');
        const toggleBtn = document.getElementById('sidebar-toggle-btn');
        const collapseIcon = document.getElementById('collapse-icon');
        const expandIcon = document.getElementById('expand-icon');
        const menuTexts = document.querySelectorAll('.menu-text');
        
        // خواندن وضعیت قبلی منو از localStorage
        const isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        
        function collapseSidebar() {
            // تنظیم کلاس برای منو
            sidebarMenu.classList.add('collapsed');
            
            // تغییر عرض به 16px در حالت بسته
            const sidebarContainer = sidebarMenu.querySelector('.fixed');
            if (sidebarContainer.classList.contains('w-64')) {
                sidebarContainer.classList.remove('w-64');
                sidebarContainer.classList.add('w-16', 'sidebar-transition');
            }
            
            // مخفی کردن متن‌ها
            menuTexts.forEach(el => {
                el.style.display = 'none';
                el.style.opacity = '0';
            });
            
            // تغییر آیکون دکمه
            collapseIcon.classList.add('hidden');
            expandIcon.classList.remove('hidden');
            
            // کوچک کردن لوگو
            const logoContainer = document.querySelector('.transition-all');
            if (logoContainer.classList.contains('w-20')) {
                logoContainer.classList.remove('w-20', 'h-20');
                logoContainer.classList.add('w-10', 'h-10', 'sidebar-transition');
            }
            
            // ذخیره وضعیت
            localStorage.setItem('sidebarCollapsed', 'true');
            
            // ارسال رویداد برای آگاه کردن سایر اسکریپت‌ها
            document.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: { collapsed: true } }));
        }
        
        function expandSidebar() {
            // تنظیم کلاس برای منو
            sidebarMenu.classList.remove('collapsed');
            
            // تغییر عرض به 64px در حالت باز
            const sidebarContainer = sidebarMenu.querySelector('.fixed');
            if (sidebarContainer.classList.contains('w-16')) {
                sidebarContainer.classList.remove('w-16');
                sidebarContainer.classList.add('w-64', 'sidebar-transition');
            }
            
            // نمایش متن‌ها
            menuTexts.forEach(el => {
                el.style.display = 'inline-block';
                el.style.opacity = '1';
            });
            
            // تغییر آیکون دکمه
            expandIcon.classList.add('hidden');
            collapseIcon.classList.remove('hidden');
            
            // بزرگ کردن لوگو
            const logoContainer = document.querySelector('.transition-all');
            if (logoContainer.classList.contains('w-10')) {
                logoContainer.classList.remove('w-10', 'h-10');
                logoContainer.classList.add('w-20', 'h-20', 'sidebar-transition');
            }
            
            // ذخیره وضعیت
            localStorage.setItem('sidebarCollapsed', 'false');
            
            // ارسال رویداد برای آگاه کردن سایر اسکریپت‌ها
            document.dispatchEvent(new CustomEvent('sidebar-toggle', { detail: { collapsed: false } }));
        }
        
        // اعمال وضعیت ذخیره شده هنگام بارگذاری صفحه
        if (isSidebarCollapsed) {
            collapseSidebar();
        } else {
            expandSidebar();
        }
        
        // افزودن رویداد کلیک به دکمه
        if (toggleBtn && sidebarMenu) {
            toggleBtn.addEventListener('click', function() {
                if (sidebarMenu.classList.contains('collapsed')) {
                    expandSidebar();
                } else {
                    collapseSidebar();
                }
            });
        }
    });
</script> 