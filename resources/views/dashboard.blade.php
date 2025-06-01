<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('داشبورد') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900">به پنل مدیریت خوش آمدید</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            از این صفحه می‌توانید به بخش‌های مختلف سیستم دسترسی داشته باشید.
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-indigo-50 rounded-lg p-6 border border-indigo-200">
                            <h3 class="text-lg font-medium text-indigo-800 mb-3">مدیریت کاربران</h3>
                            <p class="text-sm text-indigo-600 mb-4">مدیریت دسترسی‌ها و کاربران سیستم</p>
                            <a href="{{ route('profile.edit') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                مشاهده پروفایل
                            </a>
                        </div>
                        
                        <div class="bg-green-50 rounded-lg p-6 border border-green-200">
                            <h3 class="text-lg font-medium text-green-800 mb-3">مدیریت سیستم</h3>
                            <p class="text-sm text-green-600 mb-4">تنظیمات و پیکربندی سیستم</p>
                            <a href="#" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                مشاهده تنظیمات
                            </a>
                        </div>
                        
                        <div class="bg-purple-50 rounded-lg p-6 border border-purple-200">
                            <h3 class="text-lg font-medium text-purple-800 mb-3">گزارش‌ها</h3>
                            <p class="text-sm text-purple-600 mb-4">مشاهده و استخراج گزارش‌های سیستم</p>
                            <a href="#" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                مشاهده گزارش‌ها
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>