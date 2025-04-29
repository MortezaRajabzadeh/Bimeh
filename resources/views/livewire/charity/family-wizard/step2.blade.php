<div class="mb-8">
    <h3 class="text-lg font-bold mb-4 border-b pb-2">اطلاعات سرپرست خانوار</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label for="head_first_name" class="block mb-1 text-sm font-medium text-gray-700">نام</label>
            <input type="text" id="head_first_name" wire:model.debounce.500ms="head.first_name" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="نام سرپرست خانوار">
            @error('head.first_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
        
        <div>
            <label for="head_last_name" class="block mb-1 text-sm font-medium text-gray-700">نام خانوادگی</label>
            <input type="text" id="head_last_name" wire:model.debounce.500ms="head.last_name" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="نام خانوادگی سرپرست">
            @error('head.last_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
        
        <div>
            <label for="head_national_code" class="block mb-1 text-sm font-medium text-gray-700">کد ملی</label>
            <input type="text" id="head_national_code" wire:model.debounce.500ms="head.national_code" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="۱۰ رقم بدون خط تیره">
            @error('head.national_code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            
            @if($nationalCodeExists)
                <div class="mt-1 p-2 bg-red-50 text-red-700 text-xs rounded">
                    این کد ملی قبلاً در سیستم ثبت شده است!
                </div>
            @endif
        </div>
        
        <div>
            <label for="head_father_name" class="block mb-1 text-sm font-medium text-gray-700">نام پدر</label>
            <input type="text" id="head_father_name" wire:model.debounce.500ms="head.father_name" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="نام پدر">
            @error('head.father_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
        
        <div>
            <label for="head_birth_date" class="block mb-1 text-sm font-medium text-gray-700">تاریخ تولد</label>
            <input type="text" id="head_birth_date" wire:model.debounce.500ms="head.birth_date" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="۱۳۶۰/۰۱/۰۱">
            @error('head.birth_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            
            @if($ageCalculated)
                <div class="mt-1 p-2 bg-blue-50 text-blue-700 text-xs rounded">
                    سن محاسبه شده: {{ $ageCalculated }} سال
                </div>
            @endif
        </div>
        
        <div>
            <label for="head_gender" class="block mb-1 text-sm font-medium text-gray-700">جنسیت</label>
            <select id="head_gender" wire:model="head.gender" class="border border-gray-300 rounded-md w-full py-2 px-3">
                <option value="">انتخاب جنسیت</option>
                <option value="male">مرد</option>
                <option value="female">زن</option>
            </select>
            @error('head.gender') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
        
        <div>
            <label for="head_marital_status" class="block mb-1 text-sm font-medium text-gray-700">وضعیت تاهل</label>
            <select id="head_marital_status" wire:model="head.marital_status" class="border border-gray-300 rounded-md w-full py-2 px-3">
                <option value="">انتخاب وضعیت</option>
                <option value="single">مجرد</option>
                <option value="married">متاهل</option>
                <option value="divorced">مطلقه</option>
                <option value="widowed">همسر فوت شده</option>
            </select>
            @error('head.marital_status') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
        
        <div>
            <label for="head_education" class="block mb-1 text-sm font-medium text-gray-700">تحصیلات</label>
            <select id="head_education" wire:model="head.education" class="border border-gray-300 rounded-md w-full py-2 px-3">
                <option value="">انتخاب تحصیلات</option>
                <option value="illiterate">بی‌سواد</option>
                <option value="elementary">ابتدایی</option>
                <option value="middle_school">راهنمایی</option>
                <option value="high_school">دبیرستان</option>
                <option value="diploma">دیپلم</option>
                <option value="associate">فوق دیپلم</option>
                <option value="bachelor">لیسانس</option>
                <option value="master">فوق لیسانس</option>
                <option value="phd">دکترا</option>
            </select>
            @error('head.education') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
        
        <div>
            <label for="head_occupation" class="block mb-1 text-sm font-medium text-gray-700">شغل</label>
            <input type="text" id="head_occupation" wire:model.debounce.500ms="head.occupation" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="شغل فعلی">
            @error('head.occupation') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
        
        <div>
            <label for="head_mobile" class="block mb-1 text-sm font-medium text-gray-700">موبایل</label>
            <input type="text" id="head_mobile" wire:model.debounce.500ms="head.mobile" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="۰۹۱۲۳۴۵۶۷۸۹">
            @error('head.mobile') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            
            @if($mobileExists)
                <div class="mt-1 p-2 bg-red-50 text-red-700 text-xs rounded">
                    این شماره موبایل قبلاً در سیستم ثبت شده است!
                </div>
            @endif
        </div>
    </div>
    
    <div class="mt-6">
        <label class="block mb-1 text-sm font-medium text-gray-700">شرایط خاص</label>
        <div class="flex flex-wrap gap-4 mt-2 p-4 border border-gray-200 rounded-lg">
            <label class="inline-flex items-center">
                <input type="checkbox" wire:model="head.has_disability" class="rounded text-blue-600">
                <span class="mr-2 text-sm">معلولیت</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" wire:model="head.has_chronic_disease" class="rounded text-blue-600">
                <span class="mr-2 text-sm">بیماری خاص</span>
            </label>
            <label class="inline-flex items-center">
                <input type="checkbox" wire:model="head.has_insurance" class="rounded text-blue-600">
                <span class="mr-2 text-sm">دارای بیمه</span>
            </label>
            
            @if($head['has_insurance'])
                <div class="w-full mt-2">
                    <select wire:model="head.insurance_type" class="mt-1 border border-gray-300 rounded-md w-full py-2 px-3">
                        <option value="">انتخاب نوع بیمه</option>
                        @foreach($insuranceTypes as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                    
                    @if($suggestedInsurance)
                        <div class="mt-1 p-2 bg-blue-50 text-blue-700 text-xs rounded">
                            بیمه پیشنهادی: {{ $insuranceTypes[$suggestedInsurance] ?? 'نامشخص' }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
    
    <!-- پیش‌نمایش کارت شناسایی -->
    <div class="mt-6">
        <button type="button" id="show-preview" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            پیش‌نمایش کارت شناسایی
        </button>
    </div>
</div> 