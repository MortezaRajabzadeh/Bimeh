<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('images/mb6.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
@vite(['resources/css/app.css', 'resources/js/app.js'])        
        <style>
            .back-button {
                position: fixed;
                left: 0;
                top: 50%; /* تغییر موقعیت به وسط صفحه */
                transform: translateY(-50%); /* سنتر کردن عمودی */
                background-color: #4ADE80;
                width: 20px;
                height: 80%; /* ارتفاع ثابت به جای کل صفحه */
                border-top-right-radius: 6px; /* گرد کردن گوشه‌ها */
                border-bottom-right-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
                transition: all 0.3s ease;
                z-index: 40;
                margin-right: 4rem; /* تنظیم برای حالت collapsed */
            }
            
            @media (max-width: 768px) {
                .back-button {
                    height: 60px; /* ارتفاع کمتر در موبایل */
                    margin-right: 0; /* حذف مارجین در موبایل */
                }
            }
            
            /* حالت منوی باز - بدون مارجین اضافی */
            #main-wrapper.sidebar-expanded .back-button {
                margin-right: 0;
            }
            
            /* حالت منوی بسته */
            #main-wrapper.sidebar-collapsed .back-button,
            .sidebar-collapsed .back-button {
                margin-right: 4rem;
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
            
            /* استایل overlay سایدبار */
            #sidebar-overlay {
                transition: opacity 0.3s ease;
            }
            
            @media (min-width: 1024px) {
                #main-wrapper {
                    margin-right: 4rem; /* برابر عرض سایدبار collapsed */
                    transition: margin-right 0.3s ease-in-out;
                }
                
                #main-wrapper.sidebar-expanded {
                    margin-right: 16rem; /* برابر عرض سایدبار expanded */
                }
                
                #main-wrapper.sidebar-collapsed {
                    margin-right: 4rem; /* برابر عرض سایدبار collapsed */
                }
            }
            
            /* تنظیمات ویژه برای HD resolution (1366x768 و مشابه) */
            @media (min-width: 769px) and (max-width: 1600px) {
                #main-wrapper {
                    margin-right: 4rem !important;
                    transition: margin-right 0.3s ease-in-out !important;
                }
                
                #main-wrapper.sidebar-expanded {
                    margin-right: 16rem !important;
                }
                
                #main-wrapper.sidebar-collapsed {
                    margin-right: 4rem !important;
                }
                
                /* جلوگیری از overflow محتوا */
                main {
                    max-width: calc(100vw - 4rem - 2rem);
                }
                
                #main-wrapper.sidebar-expanded main {
                    max-width: calc(100vw - 16rem - 2rem);
                }
                
                /* تنظیم عرض جداول و محتوای scrollable */
                .w-full.overflow-x-auto {
                    max-width: calc(100vw - 4rem - 3rem);
                }
                
                #main-wrapper.sidebar-expanded .w-full.overflow-x-auto {
                    max-width: calc(100vw - 16rem - 3rem);
                }
            }
        </style>
        
        <!-- Livewire Styles -->
        @livewireStyles
    </head>
    <body class="font-vazirmatn antialiased bg-gray-100">
        <!-- Sidebar overlay for mobile - hidden by default -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>
        
        <div class="flex flex-col min-h-screen sidebar-loading" id="main-wrapper">
            @include('layouts.sidebar')
            <div class="flex-1">
                @include('layouts.navigation')
                <main class="p-4">
                    {{ $slot }}
                </main>
            </div>
        </div>
        
        @if(auth()->check() && auth()->user()->isActiveAs('admin'))
        <a href="{{ route('admin.dashboard') }}" class="back-button" title="برگشت به صفحه اصلی">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-left">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
        @endif
        
        @livewireScripts
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const sidebar = document.getElementById('sidebar');
                const mainWrapper = document.getElementById('main-wrapper');
                const sidebarToggle = document.getElementById('sidebar-toggle');
                const sidebarOverlay = document.getElementById('sidebar-overlay');
                const backButton = document.querySelector('.back-button');
                
                // رویداد سفارشی برای باز/بسته کردن سایدبار
                document.addEventListener('sidebarToggle', function(event) {
                    if (event.detail && mainWrapper) {
                        if (event.detail.collapsed) {
                            mainWrapper.classList.add('sidebar-collapsed');
                            mainWrapper.classList.remove('sidebar-expanded');
                            if (backButton) {
                                backButton.style.marginRight = '4rem';
                            }
                        } else {
                            mainWrapper.classList.remove('sidebar-collapsed');
                            mainWrapper.classList.add('sidebar-expanded');
                            if (backButton) {
                                backButton.style.marginRight = '0';
                            }
                        }
                    }
                });
                
                // رویداد سفارشی برای sidebar-toggle از sidebar.blade.php
                document.addEventListener('sidebar-toggle', function(event) {
                    if (event.detail && mainWrapper) {
                        if (event.detail.collapsed) {
                            mainWrapper.classList.add('sidebar-collapsed');
                            mainWrapper.classList.remove('sidebar-expanded');
                            if (backButton) {
                                backButton.style.marginRight = '4rem';
                            }
                        } else {
                            mainWrapper.classList.remove('sidebar-collapsed');
                            mainWrapper.classList.add('sidebar-expanded');
                            if (backButton) {
                                backButton.style.marginRight = '0';
                            }
                        }
                    }
                });
                
                // تنظیم رویدادهای مربوط به موبایل برای overlay
                if (sidebarToggle && sidebar && sidebarOverlay) {
                    sidebarToggle.addEventListener('click', function() {
                        sidebar.classList.toggle('-translate-x-full');
                        sidebarOverlay.classList.toggle('hidden');
                        // توقف اسکرول بدنه هنگام باز بودن سایدبار در موبایل
                        if (sidebar.classList.contains('-translate-x-full')) {
                            document.body.classList.remove('overflow-hidden');
                        } else {
                            document.body.classList.add('overflow-hidden');
                        }
                    });
                    
                    sidebarOverlay.addEventListener('click', function() {
                        sidebar.classList.add('-translate-x-full');
                        sidebarOverlay.classList.add('hidden');
                        document.body.classList.remove('overflow-hidden');
                    });
                }
                
                // بستن منو با کلیک خارج از آن در موبایل
                document.addEventListener('click', function(event) {
                    if (sidebar && !sidebar.contains(event.target) && 
                        event.target !== sidebarToggle && 
                        !sidebar.classList.contains('-translate-x-full') && 
                        window.innerWidth < 1024) {
                        sidebar.classList.add('-translate-x-full');
                        if (sidebarOverlay) {
                            sidebarOverlay.classList.add('hidden');
                            document.body.classList.remove('overflow-hidden');
                        }
                    }
                });
                
                // پاسخگویی به تغییر اندازه صفحه
                window.addEventListener('resize', function() {
                    if (window.innerWidth >= 1024 && sidebarOverlay) {
                        sidebarOverlay.classList.add('hidden');
                        document.body.classList.remove('overflow-hidden');
                    }
                });
                
                // تنظیم حالت اولیه سایدبار
                const storedState = localStorage.getItem('sidebarState');
                if (mainWrapper) {
                    if (storedState === 'expanded') {
                        mainWrapper.classList.remove('sidebar-collapsed');
                        mainWrapper.classList.add('sidebar-expanded');
                        if (backButton) {
                            backButton.style.marginRight = '0';
                        }
                    } else {
                        // حالت پیش‌فرض: منو بسته است (collapsed)
                        mainWrapper.classList.add('sidebar-collapsed');
                        mainWrapper.classList.remove('sidebar-expanded');
                        if (backButton) {
                            backButton.style.marginRight = '4rem';
                        }
                    }
                }
            });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/min/moment.min.js"></script>
        <script type="text/javascript" src="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js"></script>
        @livewire('components.toast-notifications', [])
        @stack('scripts')
    </body>
</html>
