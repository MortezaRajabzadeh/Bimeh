<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('داشبورد بیمه') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="text-xl mb-4">به پنل مدیریت بیمه خوش آمدید</div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <div class="bg-blue-100 p-4 rounded-lg shadow">
                            <h3 class="text-lg font-semibold mb-2">مشاهده خانواده‌ها</h3>
                            <p class="text-sm text-gray-600 mb-4">مشاهده و تایید خانواده‌های ارسال شده</p>
                            <a href="{{ route('insurance.families.index') }}" class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">مشاهده</a>
                        </div>
                        
                        <div class="bg-green-100 p-4 rounded-lg shadow">
                            <h3 class="text-lg font-semibold mb-2">گزارش‌ها</h3>
                            <p class="text-sm text-gray-600 mb-4">مشاهده گزارش‌های سیستم</p>
                            <a href="{{ route('insurance.reports.index') }}" class="inline-block bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">مشاهده</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 