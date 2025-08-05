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

        <!-- نمایش خطاهای validation کد ملی -->
        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-start text-red-600">
                    <svg class="w-5 h-5 ml-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <div class="font-medium mb-1">خطاهای موجود در اطلاعات:</div>
                        <ul class="text-sm space-y-1">
                            @foreach($errors->all() as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                        <div class="mt-2 text-sm">
                            لطفاً به مرحله قبل بازگردید و خطاها را اصلاح کنید.
                        </div>
                    </div>
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
                    <span class="font-medium flex items-center gap-1">
                        <span class="text-lg" title="خیریه معرف" aria-label="خیریه معرف">🏷️</span>
                        خیریه معرف:
                    </span>
                    {{ optional(auth()->user()->organization)->name ?? '' }}
                </div>
                <div class="md:col-span-2"><span class="font-medium">آدرس:</span> {{ $address }}</div>
                @if($housing_description)
                    <div class="md:col-span-2"><span class="font-medium">توضیحات مسکن:</span> {{ $housing_description }}</div>
                @endif
            </div>
        </div>
        
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
                @else
                <div><span class="font-medium">موبایل:</span> بدون شماره</div>
                @endif
                <div><span class="font-medium">شماره تماس:</span> {{ $head['phone'] ?? 'بدون شماره' }}</div>
                <div><span class="font-medium">شماره شبا:</span> {{ $head['sheba'] ?? 'بدون شماره شبا' }}</div>
                
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
                                <th class="py-2 px-3 border-b text-right">معیار پذیرش</th>
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
                                                @case('mother') مادر @break
                                                @case('father') پدر @break
                                                @case('son') پسر @break
                                                @case('daughter') دختر @break
                                                @case('grandmother') مادربزرگ @break
                                                @case('grandfather') پدربزرگ @break
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
                                                    <div class="space-y-1">
                                                        <div>{{ implode('، ', $member['problem_type']) }}</div>
                                                        @if(in_array('بیماری خاص', $member['problem_type']))
                                                            @if(isset($uploadedDocuments[$index]))
                                                                <div class="flex items-center text-xs text-green-600">
                                                                    <svg class="w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                    مدرک آپلود شده: {{ $uploadedDocuments[$index]['original_name'] }}
                                                                </div>
                                                            @else
                                                                <div class="flex items-center text-xs text-red-600">
                                                                    <svg class="w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                    مدرک آپلود نشده
                                                                </div>
                                                            @endif
                                                        @endif
                                                    </div>
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