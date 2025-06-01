<div class="p-6 bg-white rounded-lg shadow-md">
    <h3 class="text-xl font-bold mb-4 text-gray-800">تست سیستم اعلان‌های توست</h3>
    <p class="text-gray-600 mb-4">برای تست انواع مختلف اعلان‌های توست، روی دکمه‌های زیر کلیک کنید:</p>
    
    <div class="flex flex-wrap gap-3">
        <button wire:click="showSuccessToast" class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors">
            <i class="fas fa-check-circle ml-1"></i>
            نمایش پیام موفقیت
        </button>
        
        <button wire:click="showErrorToast" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors">
            <i class="fas fa-times-circle ml-1"></i>
            نمایش پیام خطا
        </button>
        
        <button wire:click="showWarningToast" class="px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 transition-colors">
            <i class="fas fa-exclamation-triangle ml-1"></i>
            نمایش پیام هشدار
        </button>
        
        <button wire:click="showInfoToast" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
            <i class="fas fa-info-circle ml-1"></i>
            نمایش پیام اطلاع‌رسانی
        </button>
    </div>
    
    <div class="mt-8 p-4 bg-gray-50 rounded-md border border-gray-200">
        <h4 class="font-bold text-gray-700 mb-2">راهنمای استفاده از توست‌ها در کد</h4>
        <p class="text-gray-600 mb-2">برای استفاده از توست‌ها در کامپوننت‌های لایوویر:</p>
        <pre class="bg-gray-800 text-gray-100 p-3 rounded-md overflow-x-auto text-sm">$this->dispatch('toast', 'متن پیام', 'نوع پیام');</pre>
        
        <p class="text-gray-600 mt-3 mb-2">برای استفاده در کنترلرها:</p>
        <pre class="bg-gray-800 text-gray-100 p-3 rounded-md overflow-x-auto text-sm">return redirect()->route('some.route')->with('success', 'پیام موفقیت');</pre>
        
        <p class="text-gray-600 mt-3">انواع پیام: <code>success</code>، <code>error</code>، <code>warning</code>، <code>info</code></p>
    </div>
</div>
