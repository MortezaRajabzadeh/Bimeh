<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-green-50">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-gray-800">جزئیات سهم‌بندی بیمه</h1>
                    <a href="{{ url()->previous() }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        بازگشت
                    </a>
                </div>
            </div>

            <div class="p-6">
                <div class="bg-green-50 p-4 rounded-lg mb-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">اطلاعات خانواده</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-4 rounded-lg shadow-sm">
                            <h3 class="text-lg font-bold text-gray-700 mb-2 border-b pb-2">اطلاعات سرپرست</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">نام و نام خانوادگی:</span>
                                    <span class="font-bold">{{ $family->head?->first_name }} {{ $family->head?->last_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">کد ملی:</span>
                                    <span class="font-bold">{{ $family->head?->national_code ?? 'ثبت نشده' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">شماره موبایل:</span>
                                    <span class="font-bold">{{ $family->head?->mobile ?? 'ثبت نشده' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-sm">
                            <h3 class="text-lg font-bold text-gray-700 mb-2 border-b pb-2">اطلاعات بیمه</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">کد خانواده:</span>
                                    <span class="font-bold">{{ $family->family_code }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">تعداد اعضا:</span>
                                    <span class="font-bold">{{ $family->members->count() }} نفر</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">وضعیت:</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $family->status_fa ?? $family->status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 p-4 rounded-lg mb-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">جزئیات سهم‌بندی</h2>
                    
                    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                        <div class="flex justify-between items-center mb-2 border-b pb-2">
                            <h3 class="text-lg font-bold text-gray-700">منبع: {{ $share->fundingSource->name }}</h3>
                            <span class="px-3 py-1 text-sm rounded-full font-bold {{ $share->percentage >= 50 ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $share->percentage }}%
                            </span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">مبلغ تخصیص:</span>
                                <span class="font-bold">{{ number_format($share->amount) }} تومان</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">تاریخ تخصیص:</span>
                                <span class="font-bold">{{ jdate($share->created_at)->format('Y/m/d H:i') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">کاربر ثبت‌کننده:</span>
                                <span class="font-bold">{{ $share->creator->name ?? 'ناشناس' }}</span>
                            </div>
                            @if($share->description)
                            <div class="mt-2 pt-2 border-t">
                                <span class="text-gray-600 block mb-1">توضیحات:</span>
                                <p class="text-gray-800 bg-gray-50 p-2 rounded">{{ $share->description }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-lg font-bold text-gray-700 mb-2 border-b pb-2">خلاصه سهم‌بندی</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">مجموع درصدها:</span>
                                <span class="font-bold {{ abs($shareSummary['total_percentage'] - 100) < 0.01 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $shareSummary['total_percentage'] }}%
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">مجموع مبالغ:</span>
                                <span class="font-bold">{{ $shareSummary['formatted_total_amount'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">وضعیت تخصیص:</span>
                                @if($shareSummary['is_fully_allocated'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        تخصیص کامل (100%)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        تخصیص ناقص ({{ $shareSummary['total_percentage'] }}%)
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 p-4 rounded-lg">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">لیست تمام سهم‌های تخصیص یافته</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-right text-sm font-medium text-gray-700">منبع مالی</th>
                                    <th class="px-4 py-2 text-right text-sm font-medium text-gray-700">درصد</th>
                                    <th class="px-4 py-2 text-right text-sm font-medium text-gray-700">مبلغ</th>
                                    <th class="px-4 py-2 text-right text-sm font-medium text-gray-700">تاریخ</th>
                                    <th class="px-4 py-2 text-right text-sm font-medium text-gray-700">توضیحات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($shareSummary['shares'] as $shareItem)
                                <tr class="{{ $shareItem->id == $share->id ? 'bg-yellow-50' : '' }} hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm">{{ $shareItem->fundingSource->name }}</td>
                                    <td class="px-4 py-3 text-sm font-bold text-blue-600">{{ $shareItem->percentage }}%</td>
                                    <td class="px-4 py-3 text-sm">{{ number_format($shareItem->amount) }} تومان</td>
                                    <td class="px-4 py-3 text-sm">{{ jdate($shareItem->created_at)->format('Y/m/d') }}</td>
                                    <td class="px-4 py-3 text-sm">{{ Str::limit($shareItem->description, 50) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 