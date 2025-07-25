<!-- منوی اصلی سایت -->
<aside id="sidebar" class="fixed top-0 bottom-0 right-0 z-40 h-screen transition-all duration-300 bg-white shadow-lg
                           w-64 lg:w-16 translate-x-full lg:translate-x-0">
    <!-- بخش لوگو -->
    <div class="flex items-center justify-center py-3 border-b border-gray-200">
        <div class="logo-container">
            <img
                src="{{ asset('images/logo.jpg') }}"
                srcset="{{ asset('images/logo.jpg') }} 1x, {{ asset('images/logo@2x.jpg') }} 2x"
                alt="لوگو خیریه"
                class="logo-image collapsed-logo"
                loading="lazy"
                width="120"
                height="40">
            <img
                src="{{ asset('images/image.png') }}"
                srcset="{{ asset('images/image.png') }} 1x, {{ asset('images/image@2x.png') }} 2x"
                alt="لوگو خیریه"
                class="logo-image expanded-logo"
                loading="lazy"
                width="180"
                height="60">
        </div>
    </div>

    <!-- آیتم‌های منو -->
    <nav class="flex flex-col flex-grow py-2">
        @php
            $userType = $current_user_type ?? auth()->user()->user_type ?? 'guest';
            // Add default values to prevent undefined variable errors
            $insuredFamilies = $insuredFamilies ?? 0;
            $insuredMembers = $insuredMembers ?? 0;
            $uninsuredFamilies = $uninsuredFamilies ?? 0;
            $uninsuredMembers = $uninsuredMembers ?? 0;
        @endphp

        <a href="{{ auth()->check() && $userType === 'admin' ? route('admin.dashboard') : (auth()->check() && $userType === 'charity' ? route('charity.dashboard') : (auth()->check() && $userType === 'insurance' ? route('insurance.dashboard') : '#')) }}"
           class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('*.dashboard') ? 'bg-blue-500 text-white' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3 {{ request()->routeIs('*.dashboard') ? 'text-white' : 'text-gray-700' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
            </svg>
            <span class="sidebar-text">داشبورد</span>
        </a>

        <!-- منوی مخصوص خیریه -->
        @if(auth()->check() && ($userType === 'charity' || $userType === 'admin'))
            <a href="{{ route('charity.insured-families') }}"
               class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('charity.insured-families') ? 'bg-green-500 text-white' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3 {{ request()->routeIs('charity.insured-families') ? 'text-white' : 'text-gray-700' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <div class="flex flex-col sidebar-text">
                    <span>خانواده‌های بیمه شده</span>
                    <p class="text-sm {{ request()->routeIs('charity.insured-families') ? 'text-gray-200' : 'text-gray-600' }}">{{ $insuredFamilies }} خانواده - {{ $insuredMembers }} نفر</p>
                </div>
            </a>

            <a href="{{ route('charity.uninsured-families') }}"
               class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('charity.uninsured-families') ? 'bg-red-500 text-white' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3 {{ request()->routeIs('charity.uninsured-families') ? 'text-white' : 'text-gray-700' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <div class="flex flex-col sidebar-text">
                    <span>خانواده‌های بدون پوشش</span>
                    <p class="text-sm {{ request()->routeIs('charity.uninsured-families') ? 'text-gray-200' : 'text-gray-600' }}">{{ $uninsuredFamilies }} خانواده - {{ $uninsuredMembers }} نفر</p>
                </div>
            </a>

            @if(auth()->check() && $userType === 'charity')
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
        @if(auth()->check() && ($userType === 'insurance'))

            <a href="{{ route('insurance.insured-families') }}"
               class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('insurance.insured-families') ? 'bg-green-500 text-white' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3 {{ request()->routeIs('insurance.insured-families') ? 'text-white' : 'text-gray-700' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <div class="flex flex-col sidebar-text">
                    <span>خانواده‌های بیمه شده</span>
                    <p class="text-sm {{ request()->routeIs('insurance.insured-families') ? 'text-gray-200' : 'text-gray-600' }}">{{ $insuredFamilies }} خانواده - {{ $insuredMembers }} نفر</p>
                </div>
            </a>

            <a href="{{ route('insurance.families.approval') }}"
               class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('insurance.families.approval') ? 'bg-red-500 text-white' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3 {{ request()->routeIs('insurance.families.approval') ? 'text-white' : 'text-gray-700' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <div class="flex flex-col sidebar-text">
                    <span>خانواده‌های بدون پوشش</span>
                    <p class="text-sm {{ request()->routeIs('insurance.families.approval') ? 'text-gray-200' : 'text-gray-600' }}">{{ $uninsuredFamilies }} خانواده - {{ $uninsuredMembers }} نفر</p>
                </div>
            </a>


        @endif

        <!-- منوی فقط مخصوص ادمین -->
        @if(auth()->check() && $userType === 'admin' && (!isset($admin_acting_as) || $admin_acting_as === 'admin'))
            <a href="{{ route('admin.users.index') }}" class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('admin.users.*') ? 'bg-blue-500 text-white' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3 {{ request()->routeIs('admin.users.*') ? 'text-white' : 'text-gray-700' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="sidebar-text">مدیریت کاربران</span>
            </a>

            <a href="{{ route('admin.organizations.index') }}" class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('admin.organizations.*') ? 'bg-blue-500 text-white' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3 {{ request()->routeIs('admin.organizations.*') ? 'text-white' : 'text-gray-700' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span class="sidebar-text">سازمان‌ها</span>
            </a>
        @endif

        @if(auth()->check() && $userType === 'insurance')
            <a href="{{ route('insurance.financial-report') }}"
               class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->routeIs('insurance.financial-report') ? 'bg-blue-500 text-white' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3 {{ request()->routeIs('insurance.financial-report') ? 'text-white' : 'text-gray-700' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                   class="sidebar-item flex items-center py-3 px-6 hover:bg-gray-100 {{ request()->url() === $settingsRoute ? 'bg-blue-500 text-white' : '' }}">

                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-3 {{ request()->url() === $settingsRoute ? 'text-white' : 'text-gray-700' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.591 1.106c1.527-.878 3.286.88 2.408 2.408a1.724 1.724 0 001.107 2.592c1.755.425 1.755 2.923 0 3.349a1.724 1.724 0 00-1.107 2.592c-.878 1.527-.881 3.286-2.408 2.408a1.724 1.724 0 00-2.592 1.107c-.425 1.755-2.923 1.755-3.349 0a1.724 1.724 0 00-2.592-1.107c-1.527.878-3.286-.881-2.408-2.408a1.724 1.724 0 00-1.107-2.592c-1.755-.426-1.755-2.924 0-3.35a1.724 1.724 0 001.107-2.592c-.878-1.527.881-3.286 2.408-2.408.996.574 2.25.072 2.592-1.106z" />
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
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
    // document.addEventListener('DOMContentLoaded', function() {
    //     const sidebar = document.getElementById('sidebar');
    //     let sidebarTimer = null;

    //     // تابع برای باز کردن منو
    //     function expandSidebar() {
    //         if (sidebar && sidebar.classList.contains('sidebar-collapsed')) {
    //             sidebar.classList.remove('sidebar-collapsed');
    //             sidebar.classList.add('sidebar-expanded');

    //             // ارسال رویداد برای آگاهی app.blade.php
    //             document.dispatchEvent(new CustomEvent('sidebar-toggle', {
    //                 detail: { collapsed: false }
    //             }));

    //             // لغو هر تایمر فعال
    //             if (sidebarTimer) {
    //                 clearTimeout(sidebarTimer);
    //                 sidebarTimer = null;
    //             }
    //         }
    //     }

    //     // تابع برای بستن منو
    //     function collapseSidebar() {
    //         if (sidebar && !sidebar.classList.contains('sidebar-collapsed')) {
    //             sidebar.classList.add('sidebar-collapsed');
    //             sidebar.classList.remove('sidebar-expanded');

    //             // ارسال رویداد برای آگاهی app.blade.php
    //             document.dispatchEvent(new CustomEvent('sidebar-toggle', {
    //                 detail: { collapsed: true }
    //             }));
    //         }
    //     }

    //     // تنظیم وضعیت اولیه منو
    //     function initSidebar() {
    //         if (sidebar) {
    //             // در ابتدا منو بسته باشد
    //             sidebar.classList.add('sidebar-collapsed');
    //             sidebar.classList.remove('sidebar-expanded');

    //             // ارسال رویداد برای آگاهی app.blade.php
    //             document.dispatchEvent(new CustomEvent('sidebar-toggle', {
    //                 detail: { collapsed: true }
    //             }));

    //             // در localStorage هم ذخیره کنیم برای هماهنگی با app.blade.php
    //             localStorage.setItem('sidebarState', 'collapsed');
    //         }
    //     }

    //     // اجرای تنظیمات اولیه
    //     initSidebar();

    //     // باز کردن منو با هاور ماوس
    //     if (sidebar) {
    //         sidebar.addEventListener('mouseenter', function() {
    //             expandSidebar();
    //         });

    //         // بستن منو وقتی ماوس از روی آن برداشته می‌شود - بلافاصله
    //         sidebar.addEventListener('mouseleave', function() {
    //             collapseSidebar(); // بستن بلافاصله بدون تایمر
    //         });
    //     }

    //     // ریست تایمر با کلیک روی آیتم‌های منو - دیگر نیازی نیست
    //     // اما برای کلیک‌های داخل منو، منو را باز نگه‌داریم
    //     const sidebarItems = document.querySelectorAll('.sidebar-item');
    //     sidebarItems.forEach(item => {
    //         item.addEventListener('click', function(event) {
    //             // جلوگیری از بسته شدن منو هنگام کلیک روی آیتم‌ها
    //             event.stopPropagation();
    //         });
    //     });

    //     // پاسخگویی به تغییر اندازه صفحه
    //     window.addEventListener('resize', function() {
    //         if (window.innerWidth < 1024) {
    //             // در نمای موبایل، منو را پنهان کن
    //             if (!sidebar.classList.contains('sidebar-mobile-open')) {
    //                 sidebar.classList.add('-translate-x-full');
    //             }
    //         } else {
    //             // در نمای دسکتاپ، منو را نمایش بده
    //             sidebar.classList.remove('-translate-x-full');
    //             sidebar.classList.remove('sidebar-mobile-open');
    //         }
    //     });

    //     // دکمه موبایل برای باز و بسته کردن منو
    //     const mobileMenuButton = document.getElementById('mobile-menu-button');
    //     if (mobileMenuButton) {
    //         mobileMenuButton.addEventListener('click', function() {
    //             sidebar.classList.toggle('-translate-x-full');
    //             sidebar.classList.toggle('sidebar-mobile-open');
    //         });
    //     }

    //     // بستن منو موبایل با کلیک بیرون از آن
    //     document.addEventListener('click', function(event) {
    //         if (window.innerWidth < 1024 && sidebar.classList.contains('sidebar-mobile-open')) {
    //             if (!sidebar.contains(event.target) && !mobileMenuButton?.contains(event.target)) {
    //                 sidebar.classList.add('-translate-x-full');
    //                 sidebar.classList.remove('sidebar-mobile-open');
    //             }
    //         }
    //     });
    // });
    document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    let expandTimer = null;
    let collapseTimer = null;
    let isMouseInside = false;
    let isExpanded = false;

    // تنظیمات قابل تنظیم
    const TIMING = {
        EXPAND_DELAY: 150,      // 150ms تاخیر برای باز کردن (debouncing)
        COLLAPSE_DELAY: 300,    // 300ms تاخیر برای بستن (UX بهتر)
        ANIMATION_DURATION: 300 // مدت زمان انیمیشن CSS
    };

    /**
     * تابع debounced برای باز کردن منو
     */
    function requestExpand() {
        // لغو تایمر بستن در صورت وجود
        if (collapseTimer) {
            clearTimeout(collapseTimer);
            collapseTimer = null;
        }

        // اگر منو از قبل باز است، کاری نکن
        if (isExpanded) return;

        // تنظیم تایمر برای باز کردن
        if (!expandTimer) {
            expandTimer = setTimeout(() => {
                expandSidebar();
                expandTimer = null;
            }, TIMING.EXPAND_DELAY);
        }
    }

    /**
     * تابع debounced برای بستن منو
     */
    function requestCollapse() {
        // لغو تایمر باز کردن در صورت وجود
        if (expandTimer) {
            clearTimeout(expandTimer);
            expandTimer = null;
        }

        // اگر منو از قبل بسته است، کاری نکن
        if (!isExpanded) return;

        // تنظیم تایمر برای بستن
        if (!collapseTimer) {
            collapseTimer = setTimeout(() => {
                collapseSidebar();
                collapseTimer = null;
            }, TIMING.COLLAPSE_DELAY);
        }
    }

    /**
     * تابع اصلی باز کردن منو
     */
    function expandSidebar() {
        if (!sidebar || isExpanded) return;

        sidebar.classList.remove('sidebar-collapsed');
        sidebar.classList.add('sidebar-expanded');
        isExpanded = true;

        // ارسال رویداد برای آگاهی app.blade.php
        document.dispatchEvent(new CustomEvent('sidebar-toggle', {
            detail: { collapsed: false }
        }));

        // ذخیره وضعیت در localStorage
        localStorage.setItem('sidebarState', 'expanded');
    }

    /**
     * تابع اصلی بستن منو
     */
    function collapseSidebar() {
        if (!sidebar || !isExpanded) return;

        sidebar.classList.add('sidebar-collapsed');
        sidebar.classList.remove('sidebar-expanded');
        isExpanded = false;

        // ارسال رویداد برای آگاهی app.blade.php
        document.dispatchEvent(new CustomEvent('sidebar-toggle', {
            detail: { collapsed: true }
        }));

        // ذخیره وضعیت در localStorage
        localStorage.setItem('sidebarState', 'collapsed');
    }

    /**
     * لغو تمام تایمرها
     */
    function clearAllTimers() {
        if (expandTimer) {
            clearTimeout(expandTimer);
            expandTimer = null;
        }
        if (collapseTimer) {
            clearTimeout(collapseTimer);
            collapseTimer = null;
        }
    }

    /**
     * تنظیم وضعیت اولیه منو
     */
    function initSidebar() {
        if (!sidebar) return;

        // خواندن وضعیت از localStorage
        const savedState = localStorage.getItem('sidebarState');
        const shouldExpand = savedState === 'expanded';

        if (shouldExpand) {
            sidebar.classList.remove('sidebar-collapsed');
            sidebar.classList.add('sidebar-expanded');
            isExpanded = true;
        } else {
            sidebar.classList.add('sidebar-collapsed');
            sidebar.classList.remove('sidebar-expanded');
            isExpanded = false;
        }

        // ارسال رویداد اولیه
        document.dispatchEvent(new CustomEvent('sidebar-toggle', {
            detail: { collapsed: !isExpanded }
        }));
    }

    /**
     * تنظیم event listeners
     */
    function setupEventListeners() {
        if (!sidebar) return;

        // Mouse enter - شروع فرآیند باز کردن
        sidebar.addEventListener('mouseenter', function(e) {
            isMouseInside = true;
            requestExpand();
        });

        // Mouse leave - شروع فرآیند بستن
        sidebar.addEventListener('mouseleave', function(e) {
            isMouseInside = false;

            // بررسی اینکه واقعاً ماوس از منو خارج شده
            const rect = sidebar.getBoundingClientRect();
            const { clientX, clientY } = e;

            if (clientX < rect.left || clientX > rect.right ||
                clientY < rect.top || clientY > rect.bottom) {
                requestCollapse();
            }
        });

        // Mouse move داخل sidebar - لغو بستن
        sidebar.addEventListener('mousemove', function(e) {
            if (isMouseInside && collapseTimer) {
                clearTimeout(collapseTimer);
                collapseTimer = null;
            }
        });

        // Click روی آیتم‌های منو - باز نگه داشتن موقت
        const sidebarItems = sidebar.querySelectorAll('.sidebar-item');
        sidebarItems.forEach(item => {
            item.addEventListener('click', function(e) {
                // منو را برای 2 ثانیه باز نگه دار بعد از کلیک
                clearAllTimers();

                setTimeout(() => {
                    if (!isMouseInside) {
                        requestCollapse();
                    }
                }, 2000);
            });
        });

        // Keyboard accessibility - ESC برای بستن
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isExpanded) {
                clearAllTimers();
                collapseSidebar();
            }
        });

        // Focus events برای accessibility
        sidebar.addEventListener('focusin', function() {
            requestExpand();
        });

        sidebar.addEventListener('focusout', function(e) {
            // بررسی اینکه focus به خارج از sidebar رفته
            if (!sidebar.contains(e.relatedTarget)) {
                requestCollapse();
            }
        });
    }

    /**
     * تنظیم responsive behavior
     */
    function setupResponsive() {
        function handleResize() {
            const isMobile = window.innerWidth < 1024;

            if (isMobile) {
                // در موبایل منطق متفاوت است
                clearAllTimers();

                if (!sidebar.classList.contains('sidebar-mobile-open')) {
                    sidebar.classList.add('-translate-x-full');
                }
            } else {
                // در دسکتاپ منو را نمایش بده
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.remove('sidebar-mobile-open');
            }
        }

        // Debounced resize handler
        let resizeTimer = null;
        window.addEventListener('resize', function() {
            if (resizeTimer) clearTimeout(resizeTimer);
            resizeTimer = setTimeout(handleResize, 100);
        });

        // اجرای اولیه
        handleResize();
    }

    /**
     * تنظیم موبایل menu
     */
    function setupMobileMenu() {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        if (!mobileMenuButton) return;

        mobileMenuButton.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('-translate-x-full');
            sidebar.classList.toggle('sidebar-mobile-open');
        });

        // بستن منوی موبایل با کلیک بیرون
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 1024 &&
                sidebar.classList.contains('sidebar-mobile-open')) {

                if (!sidebar.contains(e.target) &&
                    !mobileMenuButton.contains(e.target)) {
                    sidebar.classList.add('-translate-x-full');
                    sidebar.classList.remove('sidebar-mobile-open');
                }
            }
        });
    }

    /**
     * cleanup function برای page unload
     */
    function cleanup() {
        clearAllTimers();
    }

    // راه‌اندازی اولیه
    initSidebar();
    setupEventListeners();
    setupResponsive();
    setupMobileMenu();

    // Cleanup on page unload
    window.addEventListener('beforeunload', cleanup);

    // Performance monitoring (فقط در development)
    if (window.location.hostname === 'localhost') {
        let hoverCount = 0;
        sidebar?.addEventListener('mouseenter', () => {
            hoverCount++;
            if (hoverCount % 10 === 0) {
                console.log(`Sidebar hover count: ${hoverCount}`);
            }
        });
    }
});
</script>

<style>
    /* استایل‌های سایدبار برای RTL */
    #sidebar {
    /* انیمیشن‌های بهینه شده با GPU acceleration */
    transform: translateX(0);
    transition: width 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    will-change: width, transform;

    /* بهبود performance */
    backface-visibility: hidden;
    perspective: 1000px;

    /* Layout optimizations */
    display: flex;
    flex-direction: column;
    contain: layout style;
}


.logo-container {
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;

    /* انیمیشن smooth تر */
    transition: width 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                height 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    will-change: width, height;
}

.logo-image {
    width: 100%;
    height: 100%;
    object-fit: contain;

    /* انیمیشن opacity برای تعویض نرم */
    transition: opacity 0.2s ease-in-out,
                transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    will-change: opacity, transform;
}

    .collapsed-logo {
        display: block;
    }

    .expanded-logo {
        display: none;
    }

    @media (min-width: 1024px) {
        #sidebar.sidebar-expanded .logo-container {
            width: 5rem;
            height: 5rem;
        }

        #sidebar.sidebar-expanded .collapsed-logo {
            display: none;
        }

        #sidebar.sidebar-expanded .expanded-logo {
            display: block;
        }
    }

    /* حالت موبایل - پنهان به صورت کامل */
    @media (max-width: 1023px) {
        #sidebar {
            width: 16rem !important; /* عرض کامل در موبایل */
        }

        #sidebar.sidebar-mobile-open {
            transform: translateX(0);
        }

        /* در موبایل همیشه متن‌ها نمایش داده شوند */
        #sidebar .sidebar-text {
            display: block !important;
        }

        #sidebar .sidebar-item {
            justify-content: flex-start !important;
            padding: 0.75rem 1.5rem !important;
        }

        #sidebar .sidebar-item svg {
            margin-left: 0.75rem !important;
        }

        /* در موبایل همیشه لوگوی بزرگ نمایش داده شود */
        .collapsed-logo {
            display: none !important;
        }

        .expanded-logo {
            display: block !important;
        }
    }

    /* حالت دسکتاپ */
    @media (min-width: 1024px) {
        /* استایل‌های جدید برای حالت جمع شده */
        #sidebar.sidebar-collapsed {
            width: 4rem; /* 64px */
        }

        #sidebar.sidebar-expanded {
            width: 16rem; /* 256px */
        }

        /* مخفی کردن متن‌ها در حالت جمع شده فقط در دسکتاپ */
        #sidebar.sidebar-collapsed .sidebar-text {
            display: none;
        }

        /* تنظیم آیکون‌ها در حالت جمع شده فقط در دسکتاپ */
        #sidebar.sidebar-collapsed .sidebar-item {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }

        #sidebar.sidebar-collapsed .sidebar-item svg {
            margin-left: 0;
        }
    }

    /* تنظیمات ویژه برای رزولوشن 1600x900 */
    @media (min-width: 1600px) and (max-height: 900px) {
        #sidebar.sidebar-collapsed {
            width: 3.5rem; /* 56px - کمی کوچکتر */
        }

        #sidebar.sidebar-expanded {
            width: 14rem; /* 224px - کمی کوچکتر */
        }

        /* تنظیم اندازه لوگو */
        #sidebar.sidebar-expanded .logo-container {
            width: 4rem;
            height: 4rem;
        }

        /* تنظیم padding آیتم‌ها */
        #sidebar.sidebar-expanded .sidebar-item {
            padding: 0.5rem 1rem;
        }

        /* تنظیم اندازه آیکون‌ها */
        #sidebar .sidebar-item svg {
            width: 1.25rem;
            height: 1.25rem;
        }
    }

    /* تنظیمات RTL */
    .sidebar-item {
        direction: rtl;
        text-align: right;
    }

    /* Overlay برای موبایل */
    @media (max-width: 1023px) {
        #sidebar.sidebar-mobile-open::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: -1;
        }
    }

</style

        // گوش دادن به تغییرات localStorage از سایر کامپوننت‌ها
        window.addEventListener('storage', function(e) {
            if (e.key === 'sidebarState') {
                const newState = e.newValue;
                if (newState === 'collapsed' && isExpanded) {
                    collapseSidebar();
                } else if (newState === 'expanded' && !isExpanded) {
                    expandSidebar();
                }
            }
        });
        
        // گوش دادن به رویداد sidebar-toggle
        window.addEventListener('sidebar-toggle', function(e) {
            if (e.detail.collapsed && isExpanded) {
                collapseSidebar();
            } else if (!e.detail.collapsed && !isExpanded) {
                expandSidebar();
            }
        });
