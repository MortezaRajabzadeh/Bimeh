<div>
    {{-- Progress Bar در بالای صفحه --}}
    <div 
        wire:loading.delay.long
        class="fixed top-0 left-0 right-0 z-[60] h-1 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 animate-loading-bar"
        role="status"
        aria-label="در حال بارگذاری"
    >
        <div class="h-full bg-white opacity-30 animate-loading-shimmer"></div>
    </div>

    {{-- Loading Overlay برای عملیات خیلی زمان‌بر --}}
    <div 
        wire:loading.delay.longest
        class="fixed inset-0 z-50 flex items-center justify-center"
        style="display: none;"
    >
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm"></div>
        
        {{-- محتوای Loading --}}
        <div class="relative bg-white rounded-lg shadow-2xl p-8 flex flex-col items-center justify-center space-y-4 max-w-sm mx-4">
            {{-- استفاده از کامپوننت loading-spinner موجود --}}
            <x-loading-spinner 
                type="spin" 
                size="lg" 
                color="text-blue-600" 
            />
            
            <div class="text-gray-700 text-center font-medium text-base">
                لطفاً صبر کنید...
            </div>
            
            <div class="text-gray-500 text-center text-sm">
                در حال پردازش درخواست شما
            </div>
        </div>
    </div>
</div>

<style>
    /* انیمیشن Progress Bar */
    @keyframes loading-bar {
        0% {
            transform: translateX(-100%);
        }
        100% {
            transform: translateX(100%);
        }
    }
    
    .animate-loading-bar {
        animation: loading-bar 1.5s ease-in-out infinite;
    }
    
    /* انیمیشن Shimmer */
    @keyframes loading-shimmer {
        0% {
            transform: translateX(-100%);
        }
        100% {
            transform: translateX(100%);
        }
    }
    
    .animate-loading-shimmer {
        animation: loading-shimmer 1s ease-in-out infinite;
    }
    
    /* جلوگیری از scroll هنگام نمایش overlay */
    body:has([wire\:loading\.delay\.longest]:not([style*="display: none"])) {
        overflow: hidden;
    }
</style>
