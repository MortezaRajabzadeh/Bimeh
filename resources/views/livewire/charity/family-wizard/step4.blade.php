<div class="mb-8">
    <h3 class="text-lg font-bold mb-4 border-b pb-2">بررسی نهایی و ثبت اطلاعات</h3>
    
    <!-- خلاصه اطلاعات خانواده -->
    <div class="mb-6 p-4 border border-gray-200 rounded-lg">
        <h4 class="font-semibold mb-3">اطلاعات خانواده</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div><span class="font-medium">کد خانواده:</span> {{ $family_code }}</div>
            <div><span class="font-medium">منطقه:</span> {{ $regions[$region_id] ?? '-' }}</div>
            <div><span class="font-medium">کد پستی:</span> {{ $postal_code }}</div>
            <div>
                <span class="font-medium">وضعیت مسکن:</span>
                @switch($housing_status)
                    @case('owned') ملکی @break
                    @case('rented') استیجاری @break
                    @case('relative') منزل اقوام @break
                    @case('organizational') سازمانی @break
                    @default -
                @endswitch
            </div>
            <div class="md:col-span-2"><span class="font-medium">آدرس:</span> {{ $address }}</div>
            @if($housing_description)
                <div class="md:col-span-2"><span class="font-medium">توضیحات مسکن:</span> {{ $housing_description }}</div>
            @endif
        </div>
    </div>
    
    <!-- اطلاعات سرپرست خانوار -->
    <div class="mb-6 p-4 border border-gray-200 rounded-lg">
        <h4 class="font-semibold mb-3">اطلاعات سرپرست خانوار</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
            <div><span class="font-medium">نام و نام خانوادگی:</span> {{ $head['first_name'] }} {{ $head['last_name'] }}</div>
            <div><span class="font-medium">کد ملی:</span> {{ $head['national_code'] }}</div>
            <div><span class="font-medium">نام پدر:</span> {{ $head['father_name'] }}</div>
            <div><span class="font-medium">تاریخ تولد:</span> {{ $head['birth_date'] }}</div>
            <div>
                <span class="font-medium">جنسیت:</span>
                @if($head['gender'] == 'male') مرد @elseif($head['gender'] == 'female') زن @else - @endif
            </div>
            <div>
                <span class="font-medium">وضعیت تأهل:</span>
                @switch($head['marital_status'])
                    @case('single') مجرد @break
                    @case('married') متأهل @break
                    @case('divorced') مطلقه @break
                    @case('widowed') همسر فوت شده @break
                    @default -
                @endswitch
            </div>
            <div><span class="font-medium">شغل:</span> {{ $head['occupation'] ?? '-' }}</div>
            <div><span class="font-medium">موبایل:</span> {{ $head['mobile'] }}</div>
            <div>
                <span class="font-medium">شرایط خاص:</span>
                @if($head['has_disability'] || $head['has_chronic_disease'] || $head['has_insurance'])
                    {{ $head['has_disability'] ? 'معلولیت، ' : '' }}
                    {{ $head['has_chronic_disease'] ? 'بیماری خاص، ' : '' }}
                    {{ $head['has_insurance'] ? 'دارای بیمه ' . ($insuranceTypes[$head['insurance_type']] ?? '') : '' }}
                @else
                    -
                @endif
            </div>
        </div>
    </div>
    
    <!-- اطلاعات اعضای خانواده -->
    @if(count($members) > 0)
        <div class="mb-6 p-4 border border-gray-200 rounded-lg">
            <h4 class="font-semibold mb-3">اعضای خانواده ({{ count($members) }} نفر)</h4>
            
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white text-sm">
                    <thead>
                        <tr>
                            <th class="py-2 px-3 border-b text-right">#</th>
                            <th class="py-2 px-3 border-b text-right">نام و نام خانوادگی</th>
                            <th class="py-2 px-3 border-b text-right">کد ملی</th>
                            <th class="py-2 px-3 border-b text-right">نسبت</th>
                            <th class="py-2 px-3 border-b text-right">تاریخ تولد</th>
                            <th class="py-2 px-3 border-b text-right">شرایط خاص</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($members as $index => $member)
                            @if(!empty($member['first_name']) || !empty($member['last_name']) || !empty($member['national_code']))
                                <tr>
                                    <td class="py-2 px-3 border-b">{{ $index + 1 }}</td>
                                    <td class="py-2 px-3 border-b">{{ $member['first_name'] }} {{ $member['last_name'] }}</td>
                                    <td class="py-2 px-3 border-b">{{ $member['national_code'] }}</td>
                                    <td class="py-2 px-3 border-b">
                                        @switch($member['relationship'])
                                            @case('spouse') همسر @break
                                            @case('child') فرزند @break
                                            @case('parent') والدین @break
                                            @case('sibling') خواهر/برادر @break
                                            @case('other') سایر @break
                                            @default -
                                        @endswitch
                                    </td>
                                    <td class="py-2 px-3 border-b">{{ $member['birth_date'] ?? '-' }}</td>
                                    <td class="py-2 px-3 border-b">
                                        @if(($member['has_disability'] ?? false) || ($member['has_chronic_disease'] ?? false) || ($member['has_insurance'] ?? false))
                                            {{ ($member['has_disability'] ?? false) ? 'معلولیت، ' : '' }}
                                            {{ ($member['has_chronic_disease'] ?? false) ? 'بیماری خاص، ' : '' }}
                                            {{ ($member['has_insurance'] ?? false) ? 'دارای بیمه' : '' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    
    <!-- اطلاعات تکمیلی -->
    <div class="mb-6">
        <h4 class="font-semibold mb-3">اطلاعات تکمیلی</h4>
        <textarea wire:model="additional_info" rows="4" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="هر گونه اطلاعات تکمیلی درباره خانواده که لازم است ثبت شود"></textarea>
    </div>
    
    <!-- کارت پیش‌نمایش -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
        <div class="flex justify-between items-center mb-3">
            <h4 class="font-semibold">پیش‌نمایش کارت شناسایی</h4>
            <div class="text-sm text-blue-600 cursor-pointer" id="show-preview-final">نمایش کامل</div>
        </div>
        
        <div class="flex">
            <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center ml-4">
                @if($family_photo)
                    <img src="{{ $family_photo->temporaryUrl() }}" class="w-16 h-16 rounded-full object-cover">
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                @endif
            </div>
            <div>
                <div class="font-bold">{{ $head['first_name'] }} {{ $head['last_name'] }}</div>
                <div class="text-sm text-gray-600">کد خانواده: {{ $family_code }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ $regions[$region_id] ?? '' }}</div>
            </div>
        </div>
    </div>
    
    <!-- تأییدیه نهایی -->
    <div class="border border-gray-200 rounded-lg p-4 bg-yellow-50">
        <label class="inline-flex items-center">
            <input type="checkbox" wire:model="confirmSubmission" class="rounded text-blue-600">
            <span class="mr-2">تأیید می‌کنم که تمامی اطلاعات وارد شده صحیح است و مسئولیت آن را می‌پذیرم.</span>
        </label>
        @error('confirmSubmission') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
    </div>
</div> 