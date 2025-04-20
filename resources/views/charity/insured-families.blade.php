<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('لیست خانواده‌های بیمه شده') }}
            </h2>
            <button class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">
                تنظیمات رتبه
            </button>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            <!-- جستجو و فیلتر -->
            <div class="bg-white p-4 rounded-lg shadow mb-5">
                <div class="flex flex-wrap gap-2 items-center">
                    <div class="flex-grow">
                        <div class="relative">
                            <input type="text" class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2" placeholder="جستجو بر اساس کد خانواده، نام...">
                            <div class="absolute right-3 top-2.5 text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <button class="bg-green-500 text-white px-4 py-2 rounded-lg">
                        تنظیمات رتبه
                    </button>
                </div>
            </div>

            <!-- جدول خانواده‌ها -->
            <div class="bg-white overflow-x-auto rounded-lg shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ردیف
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                استان
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                شهر/روستا
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                تعداد بیمه شده
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                معیار پذیرش
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                تعداد اعضا
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                سرپرست خانوار
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                خیریه معرف
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                تاریخ عضویت
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                تاریخ شروع بیمه
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                تاریخ پایان بیمه
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ثبت کننده حق بیمه (درصد مشارکت)
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                تاییدیه
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @for ($i = 1; $i <= 8; $i++)
                        <tr class="hover:bg-gray-50 cursor-pointer">
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $i }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                تهران
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                پاکدشت
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                ۳
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div class="inline-flex flex-col items-center">
                                    <span class="bg-gray-200 text-gray-800 text-xs px-2 py-1 rounded-full mb-1">اعتیاد</span>
                                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mb-1">از کار افتادگی</span>
                                    <span class="bg-pink-100 text-pink-800 text-xs px-2 py-1 rounded-full mb-1">بیماری خاص</span>
                                    <span class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded-full">معلولیت</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                ۳
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                پدر
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center justify-center">
                                    <img src="{{ asset('images/image.png') }}" alt="لوگو خیریه" class="h-8 w-8">
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                ۱۴۰۳/۰۷/۰۱
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                ۱۴۰۳/۰۷/۰۱
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                ۱۴۰۳/۰۷/۰۱
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div class="flex flex-col">
                                    <div class="flex items-center">
                                        <span class="ml-2">۵۰٪</span>
                                        <div class="h-3 w-12 bg-gray-200 rounded overflow-hidden">
                                            <div class="h-full bg-green-500" style="width: 50%"></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center mt-1">
                                        <span class="ml-2">۵۰٪</span>
                                        <div class="h-3 w-12 bg-gray-200 rounded overflow-hidden">
                                            <div class="h-full bg-blue-500" style="width: 50%"></div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                <button class="bg-blue-100 hover:bg-blue-200 text-blue-800 text-xs py-1 px-2 rounded-full">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            <!-- پیجینیشن -->
            <div class="bg-white p-4 mt-5 rounded-lg shadow flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        نمایش <span class="font-medium">۱-۱۰</span> از <span class="font-medium">۸۰۰</span> نتیجه
                    </p>
                </div>
                <div class="flex items-center space-x-2 space-x-reverse">
                    <button class="px-3 py-1 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                        قبلی
                    </button>
                    <button class="px-3 py-1 rounded-lg border border-blue-500 bg-blue-500 text-white">
                        ۱
                    </button>
                    <button class="px-3 py-1 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                        ۲
                    </button>
                    <button class="px-3 py-1 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                        ۳
                    </button>
                    <button class="px-3 py-1 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                        ۴
                    </button>
                    <button class="px-3 py-1 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                        ۵
                    </button>
                    <button class="px-3 py-1 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                        بعدی
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* استایل برای اسکرول افقی در موبایل */
        @media (max-width: 1280px) {
            .min-w-full {
                min-width: 1400px;
            }
        }
    </style>
</x-app-layout> 