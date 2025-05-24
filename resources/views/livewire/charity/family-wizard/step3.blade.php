<!-- کارت اصلی -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- هدر -->
    <div class="border-b border-gray-100 p-6">
        <div class="flex justify-between items-center">
            <div class="text-lg font-bold text-gray-800">بررسی نهایی و ثبت اطلاعات</div>
            @if($family_code)
                <div class="text-sm bg-blue-50 text-blue-700 py-1 px-3 rounded-full font-medium">
                    شناسه: {{ $family_code }}
                </div>
            @endif
        </div>
    </div>

    <div class="p-6">
        <!-- پیام‌های سیستمی -->
        @if(session()->has('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center text-red-600">
                    <svg class="w-5 h-5 ml-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if(session()->has('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center text-green-600">
                    <svg class="w-5 h-5 ml-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif
        
        <!-- شناسه خانواده (به صورت مخفی) -->
        <input type="hidden" id="family_code" wire:model="family_code" value="{{ $family_code }}">
        
        <!-- خلاصه اطلاعات خانواده -->
        <div class="mb-6 p-4 border border-gray-200 rounded-lg bg-gray-50">
            <h4 class="font-semibold mb-3 text-gray-700">اطلاعات خانواده</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="font-medium">وضعیت مسکن:</span>
                    @switch($housing_status)
                        @case('owned') ملکی @break
                        @case('rented') استیجاری @break
                        @case('relative') منزل اقوام @break
                        @case('organizational') سازمانی @break
                        @case('owner') ملک شخصی @break
                        @case('tenant') استیجاری @break
                        @case('other') سایر @break
                        @default -
                    @endswitch
                </div>
                <div>
                    <span class="font-medium">خیریه معرف:</span>
                    {{ optional(auth()->user()->organization)->name ?? '' }}
                </div>
                <div class="md:col-span-2"><span class="font-medium">آدرس:</span> {{ $address }}</div>
                @if($housing_description)
                    <div class="md:col-span-2"><span class="font-medium">توضیحات مسکن:</span> {{ $housing_description }}</div>
                @endif
            </div>
        </div>
        
        <!-- پیش‌نمایش عکس خانواده -->
        @if($family_photo)
            <div class="mb-6 flex flex-col items-center justify-center">
                <div class="w-40 h-40 rounded-xl overflow-hidden border-2 border-gray-200 bg-gray-50 flex items-center justify-center shadow-sm">
                    <img src="{{ $family_photo->temporaryUrl() }}" alt="عکس خانواده" class="object-cover w-full h-full">
                </div>
                <div class="mt-2 text-xs text-gray-500">عکس خانواده (پیش‌نمایش)</div>
                <div class="text-xs text-gray-600 mt-1">{{ $family_photo->getClientOriginalName() }}</div>
            </div>
        @else
            <div class="mb-6 flex flex-col items-center justify-center">
                <div class="w-40 h-40 rounded-xl overflow-hidden border-2 border-gray-200 bg-gray-50 flex items-center justify-center shadow-sm">
                    <span class="text-gray-400 text-sm">عکسی انتخاب نشده است</span>
                </div>
            </div>
        @endif
        
        <!-- اطلاعات سرپرست خانوار -->
        <div class="mb-6 p-4 border border-gray-200 rounded-lg bg-gray-50">
            <h4 class="font-semibold mb-3 text-gray-700">اطلاعات سرپرست خانوار</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
                <div><span class="font-medium">نام و نام خانوادگی:</span> {{ $head['first_name'] }} {{ $head['last_name'] }}</div>
                <div><span class="font-medium">کد ملی:</span> {{ $head['national_code'] }}</div>
                @if(!empty($head['birth_date']))
                <div>
                    <span class="font-medium">تاریخ تولد:</span>
                    {{ $head['birth_date'] }}
                </div>
                @endif
                @if(isset($head['gender']) && !empty($head['gender']))
                <div>
                    <span class="font-medium">جنسیت:</span>
                    @if($head['gender'] == 'male') مرد @elseif($head['gender'] == 'female') زن @endif
                </div>
                @endif
                @if(isset($head['marital_status']) && !empty($head['marital_status']))
                <div>
                    <span class="font-medium">وضعیت تأهل:</span>
                    @switch($head['marital_status'])
                        @case('single') مجرد @break
                        @case('married') متأهل @break
                        @case('divorced') مطلقه @break
                        @case('widowed') همسر فوت شده @break
                    @endswitch
                </div>
                @endif
                @if(!empty($head['occupation']))
                <div><span class="font-medium">شغل:</span> {{ $head['occupation'] }}</div>
                @endif
                @if(!empty($head['mobile']))
                <div><span class="font-medium">موبایل:</span> {{ $head['mobile'] }}</div>
                @endif
                <div><span class="font-medium">شماره تماس:</span> {{ $head['phone'] ?? '-' }}</div>
                <div><span class="font-medium">شماره شبا:</span> {{ $head['sheba'] ?? '-' }}</div>
                
                @php
                    $hasSpecialConditions = false;
                    $specialConditions = [];
                    
                    if (isset($head['has_disability']) && $head['has_disability']) {
                        $hasSpecialConditions = true;
                        $specialConditions[] = 'معلولیت';
                    }
                    
                    if (isset($head['has_chronic_disease']) && $head['has_chronic_disease']) {
                        $hasSpecialConditions = true;
                        $specialConditions[] = 'بیماری خاص';
                    }
                    
                    if (isset($head['has_insurance']) && $head['has_insurance']) {
                        $hasSpecialConditions = true;
                        $specialConditions[] = isset($head['insurance_type']) && isset($insuranceTypes[$head['insurance_type']]) 
                            ? 'بیمه ' . $insuranceTypes[$head['insurance_type']] 
                            : 'دارای بیمه';
                    }
                @endphp
                
                @if($hasSpecialConditions)
                <div>
                    <span class="font-medium">شرایط خاص:</span>
                    {{ implode('، ', $specialConditions) }}
                </div>
                @endif
            </div>
        </div>
        
        <!-- اطلاعات اعضای خانواده -->
        @if(count($members) > 0)
            <div class="mb-6 p-4 border border-gray-200 rounded-lg bg-gray-50">
                <h4 class="font-semibold mb-3 text-gray-700">اعضای خانواده ({{ count($members) }} نفر)</h4>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white text-sm rounded-md border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="py-2 px-3 border-b text-right">#</th>
                                <th class="py-2 px-3 border-b text-right">نام و نام خانوادگی</th>
                                <th class="py-2 px-3 border-b text-right">کد ملی</th>
                                <th class="py-2 px-3 border-b text-right">نسبت</th>
                                <th class="py-2 px-3 border-b text-right">تاریخ تولد</th>
                                <th class="py-2 px-3 border-b text-right">نوع مشکل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($members as $index => $member)
                                @if(!empty($member['first_name']) || !empty($member['last_name']) || !empty($member['national_code']))
                                    <tr class="hover:bg-gray-50">
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
                                        <td class="py-2 px-3 border-b">
                                            @if(!empty($member['birth_date']) && preg_match('/^1[34][0-9]{2}\/(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])$/', $member['birth_date']))
                                                {{ $member['birth_date'] }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-2 px-3 border-b">
                                            @if(isset($member['problem_type']) && !empty($member['problem_type']))
                                                @if(is_array($member['problem_type']))
                                                    {{ implode('، ', $member['problem_type']) }}
                                                @else
                                                    {{ $member['problem_type'] }}
                                                @endif
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
        <div class="mb-6 space-y-1">
            <label class="block text-sm font-medium text-gray-700">اطلاعات تکمیلی</label>
            <textarea wire:model="additional_info" rows="4" class="border border-gray-300 rounded-md w-full py-2 px-3 focus:border-green-500 focus:ring-green-500" placeholder="هر گونه اطلاعات تکمیلی درباره خانواده که لازم است ثبت شود"></textarea>
        </div>
        
        <!-- تأییدیه نهایی -->
        <div class="border border-gray-200 rounded-lg p-4 bg-yellow-50">
            <label class="inline-flex items-center">
                <input type="checkbox" wire:model="confirmSubmission" class="rounded text-green-600 focus:ring-green-500">
                <span class="mr-2">تأیید می‌کنم که تمامی اطلاعات وارد شده صحیح است و مسئولیت آن را می‌پذیرم.</span>
            </label>
            @error('confirmSubmission') <div class="text-red-500 text-sm mt-1">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', function () {
        Livewire.on('show-message', event => {
            Swal.fire({
                icon: event.type === 'success' ? 'success' : 'error',
                title: event.type === 'success' ? 'موفق' : 'خطا',
                text: event.message,
                confirmButtonText: 'باشه',
                timer: event.type === 'success' ? 3000 : undefined,
                timerProgressBar: event.type === 'success' ? true : false
            });
        });
    });
</script>
@endpush 