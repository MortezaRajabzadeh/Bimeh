<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'میکرو بیمه') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=vazirmatn:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Styles -->
        <style>
            :root {
                --primary-color: #1AB357;
                --primary-hover: #159a49;
                --text-color: #333;
                --body-bg: #f3f4f6;
                --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                --input-border: #d1d5db;
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Vazirmatn', sans-serif;
            }
            
            body {
                background-color: var(--body-bg);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                font-family: 'Vazirmatn', sans-serif;
                color: var(--text-color);
                direction: rtl;
                text-align: right;
            }
            
            .login-card {
                background: white;
                border-radius: 10px;
                padding: 30px;
                width: 100%;
                max-width: 400px;
                box-shadow: var(--box-shadow);
                text-align: center;
                margin: 0 auto;
            }
            
            .logo-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                margin-bottom: 20px;
            }
            
            .logo {
                width: 60px;
                height: 60px;
                background-color: var(--primary-color);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 10px;
            }
            
            .logo svg {
                width: 40px;
                height: 40px;
                fill: white;
            }
            
            .logo-text {
                color: var(--primary-color);
                font-size: 1.2rem;
                font-weight: bold;
                margin-bottom: 5px;
            }
            
            .description {
                font-size: 0.9rem;
                color: #666;
                margin-bottom: 20px;
                line-height: 1.5;
            }
            
            .input-group {
                margin-bottom: 15px;
                text-align: right;
                width: 100%;
            }
            
            .input-group label {
                display: block;
                margin-bottom: 5px;
                font-size: 0.9rem;
                color: #555;
                font-weight: 500;
                text-align: right;
            }
            
            .input-field {
                width: 100%;
                padding: 10px 15px;
                border: 1px solid var(--input-border);
                border-radius: 8px;
                font-size: 1rem;
                transition: border-color 0.3s;
                text-align: right;
                direction: rtl;
            }
            
            .input-field:focus {
                border-color: var(--primary-color);
                outline: none;
            }
            
            .login-btn {
                width: 100%;
                background-color: var(--primary-color);
                color: white;
                border: none;
                padding: 12px;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: background-color 0.3s;
            }
            
            .login-btn:hover {
                background-color: var(--primary-hover);
            }
            
            .error-message {
                color: #e53e3e;
                font-size: 0.85rem;
                margin-top: 5px;
                text-align: right;
            }
            
            /* فایل‌های SVG آیکون‌های کاربران */
            .users-svg {
                width: 35px;
                height: 35px;
                fill: white;
            }
            
            /* استایل‌های اضافی */
            .alert {
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 15px;
                font-size: 0.9rem;
            }
            
            .alert-success {
                background-color: #d1fae5;
                color: #065f46;
                border: 1px solid #a7f3d0;
            }
            
            .alert-error {
                background-color: #fee2e2;
                color: #b91c1c;
                border: 1px solid #fca5a5;
            }
            
            .forgot-password {
                margin-top: 15px;
                text-align: center;
            }
            
            .forgot-password a {
                color: var(--primary-color);
                text-decoration: none;
                font-size: 0.9rem;
                transition: color 0.3s;
            }
            
            .forgot-password a:hover {
                color: var(--primary-hover);
                text-decoration: underline;
            }
            
            .resend-timer {
                margin-top: 10px;
                font-size: 0.85rem;
                color: #666;
            }
            
            .resend-button {
                background: none;
                border: none;
                color: var(--primary-color);
                font-size: 0.9rem;
                cursor: pointer;
                margin-top: 10px;
            }
            
            .resend-button:hover {
                color: var(--primary-hover);
                text-decoration: underline;
            }
            
            .mt-3 {
                margin-top: 0.75rem;
            }
            
            .mt-4 {
                margin-top: 1rem;
            }
            
            .text-center {
                text-align: center;
            }
            
            .button-container {
                width: 100%;
                margin-top: 20px;
            }
            
            @media (max-width: 640px) {
                .login-card {
                    max-width: 90%;
                    padding: 20px;
                }
            }
        </style>
        
        @livewireStyles
    </head>
    <body>
        <main>
            {{ $slot }}
        </main>
        
        @livewireScripts
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </body>
</html> 