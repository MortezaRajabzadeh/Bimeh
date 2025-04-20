<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700&display=swap" rel="stylesheet">


        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            .back-button {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                transform: none;
                background-color: #4ADE80;
                width: 20px;
                height: 100vh;
                border-top-right-radius: 0;
                border-bottom-right-radius: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
                transition: all 0.3s ease;
                z-index: 1000;
            }
            
            .back-button:hover {
                background-color: #22C55E;
                width: 50px;
            }
            
            /* CSS برای حالت باز/بسته بودن منو */
            .main-with-normal-sidebar {
                margin-right: 16rem; /* 64px */
                transition: margin 0.3s ease-in-out;
            }
            
            .main-with-collapsed-sidebar {
                margin-right: 4rem; /* 16px */
                transition: margin 0.3s ease-in-out;
            }
        </style>
    </head>
    <body class="font-vazirmatn antialiased">
        <div class="min-h-screen bg-gray-100">
            <!-- منوی کناری سایت به عنوان منوی اصلی -->
            @include('layouts.sidebar')
            
            <!-- منوی بالا -->
            @include('layouts.navigation')
            
            <livewire:components.toast-notifications />

            <!-- دکمه برگشت به صفحه انتخاب پنل -->
            <a href="{{ route('admin.dashboard') }}" class="back-button" title="برگشت به صفحه اصلی">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-left">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </a>

            <!-- Page Content -->
            <main id="main-content" class="main-with-normal-sidebar">
                {{ $slot }}
            </main>
        </div>

        @livewireScripts
        
        <script>
            // بررسی وضعیت منو با JavaScript خالص بدون استفاده از Alpine.js
            document.addEventListener('DOMContentLoaded', function() {
                const sidebarMenu = document.querySelector('.sidebar-menu');
                const mainContent = document.getElementById('main-content');
                
                // تابع بررسی وضعیت منو و تنظیم کلاس مناسب
                function checkSidebarState() {
                    if (sidebarMenu && mainContent) {
                        if (sidebarMenu.classList.contains('collapsed')) {
                            mainContent.classList.remove('main-with-normal-sidebar');
                            mainContent.classList.add('main-with-collapsed-sidebar');
                        } else {
                            mainContent.classList.remove('main-with-collapsed-sidebar');
                            mainContent.classList.add('main-with-normal-sidebar');
                        }
                    }
                }
                
                // بررسی اولیه
                checkSidebarState();
                
                // گوش دادن به تغییرات در کلاس منو
                if (sidebarMenu) {
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.attributeName === 'class') {
                                checkSidebarState();
                            }
                        });
                    });
                    
                    observer.observe(sidebarMenu, { attributes: true });
                }
                
                // گوش دادن به رویداد سفارشی برای تغییر وضعیت منو
                document.addEventListener('sidebar-toggle', function() {
                    checkSidebarState();
                });
            });
        </script>
    </body>
</html>
