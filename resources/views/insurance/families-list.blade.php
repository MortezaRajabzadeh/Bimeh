<x-app-layout>
<div class="container mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold text-center mb-8 text-gray-800">لیست خانواده‌های بیمه‌شده</h2>
    @if($families->count())
        <div class="w-full overflow-hidden shadow-sm border border-gray-200 rounded-lg">
            <div class="w-full overflow-x-auto">
                <table class="w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50 text-xs text-gray-700">
                            <th class="px-5 py-3 text-right border-b border-gray-200 font-medium">کد خانواده</th>
                            <th class="px-5 py-3 text-right border-b border-gray-200 font-medium">سرپرست</th>
                            <th class="px-5 py-3 text-right border-b border-gray-200 font-medium">استان</th>
                            <th class="px-5 py-3 text-right border-b border-gray-200 font-medium">شهر</th>
                            <th class="px-5 py-3 text-right border-b border-gray-200 font-medium">تعداد اعضا</th>
                            <th class="px-5 py-3 text-right border-b border-gray-200 font-medium">وضعیت</th>
                            <th class="px-5 py-3 text-right border-b border-gray-200 font-medium">جزئیات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($families as $family)
                        <tr class="hover:bg-gray-50 group">
                            <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">{{ $family->family_code }}</td>
                            <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">{{ optional($family->head)->full_name ?? '-' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">{{ $family->province->name ?? '-' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">{{ $family->city->name ?? '-' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">{{ $family->members->count() }}</td>
                            <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                @switch($family->status)
                                    @case('pending')
                                        <span class="bg-orange-100 text-orange-800 text-xs py-1 px-2 rounded-full flex items-center">در انتظار بررسی</span>
                                        @break
                                    @case('reviewing')
                                        <span class="bg-yellow-100 text-yellow-800 text-xs py-1 px-2 rounded-full flex items-center">در حال بررسی</span>
                                        @break
                                    @case('approved')
                                        <span class="bg-blue-100 text-blue-800 text-xs py-1 px-2 rounded-full flex items-center">تایید شده</span>
                                        @break
                                    @case('insured')
                                        <span class="bg-green-100 text-green-800 text-xs py-1 px-2 rounded-full flex items-center">بیمه شده</span>
                                        @break
                                    @case('renewal')
                                        <span class="bg-indigo-100 text-indigo-800 text-xs py-1 px-2 rounded-full flex items-center">در انتظار تمدید</span>
                                        @break
                                    @case('rejected')
                                        <span class="bg-red-100 text-red-800 text-xs py-1 px-2 rounded-full flex items-center">رد شده</span>
                                        @break
                                    @case('deleted')
                                        <span class="bg-gray-200 text-gray-500 text-xs py-1 px-2 rounded-full flex items-center">حذف شده</span>
                                        @break
                                    @default
                                        <span class="bg-gray-100 text-gray-800 text-xs py-1 px-2 rounded-full flex items-center">-</span>
                                @endswitch
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                <details class="group">
                                    <summary class="cursor-pointer text-blue-600 hover:underline text-xs">نمایش جزئیات خانواده و اعضا</summary>
                                    <div class="mt-2 bg-gray-50 rounded-lg shadow-inner p-2">
                                        <div class="mb-2 text-xs text-gray-700">
                                            <span class="font-bold">سرپرست:</span> {{ optional($family->head)->full_name ?? '-' }}
                                            <span class="mx-2">|</span>
                                            <span class="font-bold">کد خانواده:</span> {{ $family->family_code }}
                                            <span class="mx-2">|</span>
                                            <span class="font-bold">تعداد اعضا:</span> {{ $family->members->count() }}
                                        </div>
                                        <table class="min-w-full table-auto bg-white border border-gray-200 rounded-lg text-xs">
                                            <thead>
                                                <tr class="bg-gray-100 border-b border-gray-200">
                                                    <th class="px-3 py-2 text-right">نام و نام خانوادگی</th>
                                                    <th class="px-3 py-2 text-right">کد ملی</th>
                                                    <th class="px-3 py-2 text-right">نسبت</th>
                                                    <th class="px-3 py-2 text-right">تاریخ تولد</th>
                                                    <th class="px-3 py-2 text-right">شغل</th>
                                                    <th class="px-3 py-2 text-right">نوع بیمه</th>
                                                    <th class="px-3 py-2 text-right">پرداخت‌کننده</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($family->members as $member)
                                                
                                                <tr>
                                                @php
                                                    $types = $family->insuranceTypes();
                                                    $payers = $family->insurancePayers();
                                                @endphp

                                                    <td class="px-3 py-2">{{ $member->full_name ?? ($member->first_name . ' ' . $member->last_name) }}</td>
                                                    <td class="px-3 py-2">{{ $member->national_code ?? '-' }}</td>
                                                    <td class="px-3 py-2">{{ $member->relationship_fa ?? '-' }}</td>
                                                    <td class="px-3 py-2">{{ $member->birth_date ?? '-' }}</td>
                                                    
                                                    <td class="px-3 py-2">{{ $member->job ?? '-' }}</td>
                                                    @if($types->count())
                                                        @foreach($types as $type)
                                                        <td class="px-3 py-2">{{ $type }}</td>
                                                        @endforeach
                                                    @else
                                                    <td class="px-3 py-2">{{ $member->insurance_type ?? '-' }}</td>
                                                    @endif
                                                    @if($payers->count())
                                                        @foreach($payers as $payer)
                                                        <td class="px-3 py-2">{{ $payer }}</td>
                                                        @endforeach
                                                    @endif
                            
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="text-center text-gray-500 py-8">خانواده‌ای یافت نشد.</div>
    @endif
    <div class="mt-8 text-center">
        <a href="javascript:window.close()" class="inline-block px-6 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition">بستن</a>
    </div>
</div>
</x-app-layout>