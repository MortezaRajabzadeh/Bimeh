<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

    <!-- فونت و استایل‌ها -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- اسکریپت‌های لیوایر -->
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-vazir antialiased">
    <div class="min-h-screen">
        <!-- نمایش پیام‌های فلش -->
        @if (session()->has('success'))
            @include('components.notification-popup', ['type' => 'success', 'slot' => session('success')])
        @endif

        @if (session()->has('error'))
            @include('components.notification-popup', ['type' => 'error', 'slot' => session('error')])
        @endif

        @if (session()->has('warning'))
            @include('components.notification-popup', ['type' => 'warning', 'slot' => session('warning')])
        @endif

        <main>
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html> 