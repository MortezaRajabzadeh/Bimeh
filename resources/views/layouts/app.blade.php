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
            <main x-data="{}"
                x-bind:class="document.querySelector('.sidebar-menu').classList.contains('collapsed') ? 'mr-16 transition-all duration-300' : 'mr-64 transition-all duration-300'">
                {{ $slot }}
            </main>
        </div>

        @livewireScripts
    </body>
</html>
