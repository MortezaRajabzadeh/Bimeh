<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('داشبورد خیریه') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-2xl mb-6">به سیستم میکروبیمه خوش آمدید</h1>
                    
                    <!-- کارت‌های اطلاعاتی -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                        <!-- کارت خانواده‌های بیمه شده -->
                        <div class="bg-green-50 p-6 rounded-lg shadow-sm border border-green-100">
                            <div class="flex items-center mb-4">
                                <div class="bg-green-500 p-3 rounded-full mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">خانواده‌های بیمه شده</h3>
                                    <p class="text-sm text-gray-600">{{ $insuredFamilies ?? '۸۰۰' }} خانواده - {{ $insuredMembers ?? '۹۵۰' }} نفر</p>
                                </div>
                            </div>
                            <a href="{{ route('charity.insured-families') }}" class="w-full block text-center py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">مشاهده</a>
                        </div>

                        <!-- کارت خانواده‌های بدون پوشش -->
                        <div class="bg-red-50 p-6 rounded-lg shadow-sm border border-red-100">
                            <div class="flex items-center mb-4">
                                <div class="bg-red-500 p-3 rounded-full mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">خانواده‌های بدون پوشش</h3>
                                    <p class="text-sm text-gray-600">{{ $uninsuredFamilies ?? '۸۰۰' }} خانواده - {{ $uninsuredMembers ?? '۹۵۰' }} نفر</p>
                                </div>
                            </div>
                            <a href="{{ route('charity.uninsured-families') }}" class="w-full block text-center py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">مشاهده</a>
                        </div>

                        <!-- کارت افزودن خانواده جدید -->
                        <div class="bg-blue-50 p-6 rounded-lg shadow-sm border border-blue-100">
                            <div class="flex items-center mb-4">
                                <div class="bg-blue-500 p-3 rounded-full mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">افزودن خانواده جدید</h3>
                                    <p class="text-sm text-gray-600">ثبت خانواده نیازمند جدید</p>
                                </div>
                            </div>
                            <a href="{{ route('charity.add-family') }}" class="w-full block text-center py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">افزودن</a>
                        </div>
                    </div>
                    <br>
                    </br>
                    <!-- جستجو و فیلتر -->
                    <div class="mb-6 flex gap-2">
                        <form action="{{ route('charity.search') }}" method="GET" class="w-full flex flex-wrap items-center gap-2">
                            <input type="text" name="q" placeholder="جستجو..." class="border border-gray-300 rounded p-2 flex-grow" value="{{ request('q') }}">
                            
                            <select name="status" class="border border-gray-300 rounded p-2 bg-white">
                                <option value="">همه وضعیت‌ها</option>
                                <option value="insured" {{ request('status') === 'insured' ? 'selected' : '' }}>بیمه شده</option>
                                <option value="uninsured" {{ request('status') === 'uninsured' ? 'selected' : '' }}>بدون بیمه</option>
                            </select>
                            
                            <select name="region" class="border border-gray-300 rounded p-2 bg-white">
                                <option value="">همه مناطق</option>
                                @foreach($regions ?? [] as $region)
                                    <option value="{{ $region->id }}" {{ request('region') == $region->id ? 'selected' : '' }}>
                                        {{ $region->name }}
                                    </option>
                                @endforeach
                            </select>
                            
                            <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600 transition">
                                جستجو
                            </button>
                        </form>
                    </div>

                    <!-- جدول خانواده‌ها -->
                    <div class="w-full overflow-x-auto">
                        <table class="w-full border border-gray-200">
                            <thead>
                                <tr class="bg-gray-50 text-xs text-gray-700">
                                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                        <button class="flex items-center justify-end w-full">
                                            شناسه
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                        <button class="flex items-center justify-end w-full">
                                            رتبه
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                        <button class="flex items-center justify-end w-full">
                                            استان
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                        <button class="flex items-center justify-end w-full">
                                            شهر/روستا
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                        <button class="flex items-center justify-end w-full">
                                            تعداد بیمه ها
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                        <button class="flex items-center justify-end w-full">
                                            معیار پذیرش
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                        <button class="flex items-center justify-end w-full">
                                            تعداد اعضا
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                        <button class="flex items-center justify-end w-full">
                                            سرپرست خانوار
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                        <button class="flex items-center justify-end w-full">
                                            ضریبه مصرف
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                        <button class="flex items-center justify-end w-full">
                                            تاریخ عضویت
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                        <button class="flex items-center justify-end w-full">
                                            پرداخت کننده حق بیمه
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                        <button class="flex items-center justify-end w-full">
                                            درصد مشارکت
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                        <button class="flex items-center justify-end w-full">
                                            تاییدیه
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                
                                {{-- اطلاعات دیباگ --}}
                                @if(isset($families))
                                <tr class="bg-gray-100">
                                    <td colspan="13" class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        <div class="bg-yellow-50 p-4 rounded border border-yellow-200 text-yellow-800">
                                            <h3 class="font-bold mb-2">اطلاعات دیباگ:</h3>
                                            <p>تعداد خانواده‌ها: {{ $families->count() }} از {{ $families->total() }}</p>
                                            <p>صفحه: {{ $families->currentPage() }} از {{ $families->lastPage() }}</p>
                                            <p>نوع متغیر $families: {{ get_class($families) }}</p>
                                            <p>آیا خالی است؟ {{ $families->isEmpty() ? 'بله' : 'خیر' }}</p>
                                        </div>
                                    </td>
                                </tr>
                                @else
                                <tr>
                                    <td colspan="13" class="px-5 py-4 text-sm text-red-500 border-b border-gray-200 text-center">
                                        متغیر $families تعریف نشده است!
                                    </td>
                                </tr>
                                @endif
                                
                                @forelse($families ?? [] as $index => $family)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        {{ $family->id }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        {{ $family->region->province ?? 'تهران' }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        {{ $family->region->name ?? 'پاکدشت' }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        {{ $family->members->where('is_insured', true)->count() }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1">
                                            از کار افتادگی
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        {{ $family->members->count() }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        {{ $family->head()?->full_name ?? 'نامشخص' }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        ۵۰٪
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        {{ $family->created_at ? jdate($family->created_at)->format('Y/m/d') : '۱۴۰۳/۰۷/۰۱' }}
                                    </td>
                                    <td class="px-5 py-4 text-sm border-b border-gray-200">
                                        <div class="flex items-center">
                                            <span>خیریه</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        <div class="flex items-center">
                                            <span class="ml-2">۵۰٪</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        <button class="bg-blue-100 hover:bg-blue-200 text-blue-800 text-xs py-1 px-2 rounded-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="13" class="px-5 py-4 text-sm text-gray-500 border-b border-gray-200 text-center">
                                        هیچ رکوردی یافت نشد.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- پیجینیشن -->
                    @if(!empty($families ?? []) && $families->hasPages())
                    <div class="mt-4">
                        {{ $families->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        /* استایل برای اسکرول افقی در موبایل */
        @media (max-width: 1280px) {
            .overflow-x-auto {
                overflow-x: auto;
            }
        }
    </style>
</x-app-layout> 