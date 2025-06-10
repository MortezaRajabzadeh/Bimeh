<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('نتایج جستجو') }}
            </h2>
            <div class="flex gap-2">
                <!-- دکمه دانلود اکسل -->
                @if($results->isNotEmpty())
                <a href="{{ route('charity.export-excel', ['q' => $query, 'status' => $status]) }}" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    دانلود اکسل
                </a>
                @endif
                <a href="{{ route('charity.insured-families') }}" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition text-sm">
                    خانواده‌های بیمه شده
                </a>
                <a href="{{ route('charity.uninsured-families') }}" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition text-sm">
                    خانواده‌های بدون پوشش
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            <!-- فرم جستجو -->
            <div class="bg-white p-4 rounded-lg shadow mb-5">
                <form action="{{ route('charity.search') }}" method="GET">
                    <div class="flex flex-wrap gap-4 items-center">
                        <div class="flex-grow">
                            <div class="relative">
                                <input type="text" name="q" value="{{ $query }}" class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2" placeholder="جستجو بر اساس کد خانواده، نام سرپرست یا کد ملی...">
                                <div class="absolute right-3 top-2.5 text-gray-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div>
                            <select name="status" class="border border-gray-300 rounded-lg px-4 py-2">
                                <option value="">همه وضعیت‌ها</option>
                                <option value="insured" {{ $status == 'insured' ? 'selected' : '' }}>بیمه شده</option>
                                <option value="uninsured" {{ $status == 'uninsured' ? 'selected' : '' }}>بدون پوشش</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">
                            جستجو
                        </button>
                    </div>
                </form>
            </div>

            @if($results->isEmpty())
                <div class="bg-yellow-50 p-6 rounded-lg shadow-sm border border-yellow-100 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-yellow-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">نتیجه‌ای یافت نشد</h3>
                    <p class="text-gray-600">لطفاً عبارت جستجوی دیگری را امتحان کنید.</p>
                </div>
            @else
                <!-- جدول خانواده‌ها -->
                <div class="bg-white overflow-x-auto rounded-lg shadow">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    کد خانواده
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    سرپرست خانوار
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    استان
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    شهر/روستا
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    تعداد اعضا
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    وضعیت
                                </th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    عملیات
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($results as $family)
                            <tr class="hover:bg-gray-50 cursor-pointer">
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $family->family_code }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($family->head())
                                        {{ $family->head()->full_name }}
                                    @else
                                        <span class="text-gray-400">نامشخص</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $family->region->province ?? 'نامشخص' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $family->region->name ?? 'نامشخص' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $family->members->count() }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                    @if($family->is_insured)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            بیمه شده
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            بدون پوشش
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex space-x-reverse space-x-2">
                                        <a href="#" class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            مشاهده
                                        </a>
                                        <a href="#" class="inline-flex items-center px-2.5 py-1.5 border border-blue-300 text-xs font-medium rounded text-blue-700 bg-blue-50 hover:bg-blue-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            ویرایش
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- پیجینیشن -->
                <div class="mt-5">
                    {{ $results->appends(['q' => $query, 'status' => $status])->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>