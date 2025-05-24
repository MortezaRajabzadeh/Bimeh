<div class="min-h-screen flex flex-col items-center bg-gray-800 p-4">
    <!-- عنوان صفحه -->
    <div class="text-center text-gray-100 mb-6 w-full">
        <h1 class="text-xl font-semibold">نوع کار خود را انتخاب کنید</h1>
    </div>

    <!-- پیام توضیحات -->
    <div class="bg-white rounded-lg p-4 border-r-4 border-red-500 text-gray-800 mb-8 w-full max-w-7xl">
        <div class="flex items-center">
            <div class="ml-2">
                <p class="text-sm">در جلسه‌ی روز مقرر شد که بتوانیم یک اعمال انجام شده در سیستم را به صورت تاریخ دار ببینیم.</p>
            </div>
        </div>
    </div>

    <!-- کارت‌های انتخاب -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full max-w-7xl">
        <!-- کارت خیریه -->
        <div class="bg-white p-8 rounded-lg shadow-md flex flex-col items-center justify-center">
            <div class="mb-4">
                <svg class="h-16 w-16 text-green-500" viewBox="0 0 640 512" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M144 160c-44.2 0-80-35.8-80-80S99.8 0 144 0s80 35.8 80 80-35.8 80-80 80zm368 0c-44.2 0-80-35.8-80-80s35.8-80 80-80 80 35.8 80 80-35.8 80-80 80zM0 298.7C0 310.4 9.6 320 21.3 320h321.4c11.7 0 21.3-9.6 21.3-21.3 0-38.2-30.9-69.3-69.1-69.3H69.1C30.9 229.3 0 260.5 0 298.7zM412 160h-60c-6.5 0-12.3 3.9-14.8 9.9-8.5 20.6-15.4 42.2-20.5 64.4 34.2 3.8 65 19.5 88.3 44.5 5.3-9.4 8.9-19.8 11-31.2 2.5-14 3.6-24.2 3.6-24.2.4-2.2.6-4.5.6-6.8 0-28.9-24.5-56.6-53.3-56.6h-12.8c15.7 13.5 25.1 31.7 28.3 40.9-4.2-1.6-11.9-4.4-15.8-5.9z"/>
                </svg>
            </div>
            <button wire:click="loginAsCharity" class="bg-green-500 hover:bg-green-600 text-white py-2 px-6 rounded-full transition duration-300">
                ورود با دسترسی خیریه
            </button>
        </div>
        
        <!-- کارت شرکت بیمه -->
        <div class="bg-white p-8 rounded-lg shadow-md flex flex-col items-center justify-center">
            <div class="mb-4">
                <svg class="h-16 w-16 text-green-500" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M16 2C8.2 2 2 8.2 2 16s6.2 14 14 14 14-6.2 14-14S23.8 2 16 2zm0 2c6.6 0 12 5.4 12 12 0 6.6-5.4 12-12 12-6.6 0-12-5.4-12-12 0-6.6 5.4-12 12-12zm0 2c-5.5 0-10 4.5-10 10 0 2.4.8 4.6 2.2 6.4.4-.3.8-.6 1.2-1 .4-.4.8-.9 1.1-1.4-1-1.1-1.5-2.5-1.5-4 0-3.3 2.7-6 6-6V8h2v2c3.3 0 6 2.7 6 6 0 1.5-.5 2.9-1.5 4 .3.5.7 1 1.1 1.4.4.4.8.7 1.2 1 1.4-1.8 2.2-4 2.2-6.4 0-5.5-4.5-10-10-10z"/>
                </svg>
            </div>
            <button wire:click="loginAsInsurance" class="bg-green-500 hover:bg-green-600 text-white py-2 px-6 rounded-full transition duration-300">
                ورود با دسترسی شرکت بیمه
            </button>
        </div>
        
        <!-- کارت مدیریت سیستم -->
        <div class="bg-white p-8 rounded-lg shadow-md flex flex-col items-center justify-center">
            <div class="mb-4">
                <svg class="h-16 w-16 text-green-500" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M12 16c2.206 0 4-1.794 4-4s-1.794-4-4-4-4 1.794-4 4 1.794 4 4 4zm0-6c1.084 0 2 .916 2 2s-.916 2-2 2-2-.916-2-2 .916-2 2-2z"/>
                    <path fill="currentColor" d="m2.845 16.136 1 1.73c.531.917 1.809 1.261 2.73.73l.529-.306A8.1 8.1 0 0 0 9 19.402V20c0 1.103.897 2 2 2h2c1.103 0 2-.897 2-2v-.598a8.132 8.132 0 0 0 1.896-1.111l.529.306c.923.53 2.198.188 2.731-.731l.999-1.729a2.001 2.001 0 0 0-.731-2.732l-.505-.292a7.718 7.718 0 0 0 0-2.224l.505-.292a2.002 2.002 0 0 0 .731-2.732l-.999-1.729c-.531-.92-1.808-1.265-2.731-.732l-.529.306A8.1 8.1 0 0 0 15 4.598V4c0-1.103-.897-2-2-2h-2c-1.103 0-2 .897-2 2v.598a8.132 8.132 0 0 0-1.896 1.111l-.529-.306c-.924-.531-2.2-.187-2.731.732l-.999 1.729a2.001 2.001 0 0 0 .731 2.732l.505.292a7.683 7.683 0 0 0 0 2.223l-.505.292a2.003 2.003 0 0 0-.731 2.733zm3.326-2.758A5.703 5.703 0 0 1 6 12c0-.462.058-.926.17-1.378a.999.999 0 0 0-.47-1.108l-1.123-.65.998-1.729 1.145.662a.997.997 0 0 0 1.188-.142 6.071 6.071 0 0 1 2.384-1.399A1 1 0 0 0 11 5.3V4h2v1.3a1 1 0 0 0 .708.956 6.083 6.083 0 0 1 2.384 1.399.999.999 0 0 0 1.188.142l1.144-.661 1 1.729-1.124.649a1 1 0 0 0-.47 1.108c.112.452.17.916.17 1.378 0 .461-.058.925-.171 1.378a1 1 0 0 0 .471 1.108l1.123.649-.998 1.729-1.145-.661a.996.996 0 0 0-1.188.142 6.071 6.071 0 0 1-2.384 1.399A1 1 0 0 0 13 18.7l.002 1.3H11v-1.3a1 1 0 0 0-.708-.956 6.083 6.083 0 0 1-2.384-1.399.992.992 0 0 0-1.188-.141l-1.144.662-1-1.729 1.124-.651a1 1 0 0 0 .471-1.108z"/>
                </svg>
            </div>
            <button wire:click="loginAsAdmin" class="bg-green-500 hover:bg-green-600 text-white py-2 px-6 rounded-full transition duration-300">
                ورود برای انجام تنظیمات
            </button>
        </div>
        
        <!-- کارت لاگ تغییرات -->
        <div class="bg-white p-8 rounded-lg shadow-md flex flex-col items-center justify-center">
            <div class="mb-4">
                <svg class="h-16 w-16 text-green-500" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path fill="currentColor" d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
                </svg>
            </div>
            <button wire:click="viewChangeLogs" class="bg-green-500 hover:bg-green-600 text-white py-2 px-6 rounded-full transition duration-300">
                لاگ تغییرات
            </button>
        </div>
    </div>
</div> 