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
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
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
                margin-right: 4rem; /* ثابت نگه داشتن */
            }
            
            @media (max-width: 768px) {
                .back-button {
                    height: 60px; /* ارتفاع کمتر در موبایل */
                    margin-right: 0; /* حذف مارجین در موبایل */
                }
            }
            
            .back-button:hover {
                background-color: #22C55E;
                width: 50px;
            }
            
            /* استایل overlay سایدبار */
            #sidebar-overlay {
                transition: opacity 0.3s ease;
            }
            
            /* استایل برای هایلایت کردن عنصر اسکرول شده */
            .highlight-member {
                animation: highlight-pulse 3s ease-in-out;
            }
            
            @keyframes highlight-pulse {
                0% { background-color: rgba(59, 130, 246, 0.3); }
                50% { background-color: rgba(59, 130, 246, 0.1); }
                100% { background-color: transparent; }
            }
        </style>
        
        <!-- Livewire Styles -->
        @livewireStyles
    </head>
    <body class="font-vazirmatn antialiased bg-gray-100 font-iranyekan text-gray-900">
        <!-- Sidebar overlay for mobile - hidden by default -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>
        
        <div class="flex flex-col min-h-screen" id="main-wrapper">
            @include('layouts.sidebar')
            <div class="flex-1">
                @include('layouts.navigation')
                <div class="container mx-auto px-4">
                    <x-impersonation-banner />
                </div>
                <main class="p-4">
                    {{ $slot }}
                </main>
            </div>
        </div>
        
        @if(auth()->check() && auth()->user()->hasRole('admin'))
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
            });
        </script>
        
        <!-- اسکریپت اسکرول به عنصر مشخص شده -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // بررسی وجود اطلاعات اسکرول در localStorage
                const scrollMemberId = "{{ session('scroll_to_member') }}";
                if (scrollMemberId) {
                    window.scrollToMember = scrollMemberId;
                    
                    // اسکرول به عنصر مشخص شده
                    const memberElement = document.getElementById('member-' + scrollMemberId);
                    if (memberElement) {
                        setTimeout(function() {
                            memberElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            memberElement.classList.add('highlight-member');
                            
                            // حذف کلاس هایلایت بعد از چند ثانیه
                            setTimeout(function() {
                                memberElement.classList.remove('highlight-member');
                            }, 3000);
                        }, 500);
                    }
                }
            });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/min/moment.min.js"></script>
        <script type="text/javascript" src="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js"></script>
        @livewire('components.toast-notifications')
        @stack('scripts')
    </body>
</html>
