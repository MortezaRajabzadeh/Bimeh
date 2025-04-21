<div>
    {{-- Knowing others is intelligence; knowing yourself is true wisdom. --}}
    
    <!-- جستجو و فیلتر -->
    <div class="mb-6 flex gap-2">
        <div class="w-full flex flex-wrap items-center gap-2">
            <div class="relative flex-grow">
                <input wire:model.live="search" type="text" placeholder="جستجو در تمام فیلدها..." class="border border-gray-300 rounded p-2 w-full">
            </div>
            
            <div class="relative">
                <select wire:model.live="statusFilter" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="insured">بیمه شده</option>
                    <option value="uninsured">بدون بیمه</option>
                </select>
            </div>
            
            <div class="relative">
                <select wire:model.live="regionFilter" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="">همه مناطق</option>
                    @foreach($regions as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="relative">
                <select wire:model.live="sortField" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="created_at">مرتب‌سازی بر اساس...</option>
                    <option value="id">رتبه</option>
                    <option value="province">استان</option>
                    <option value="city">شهر/روستا</option>
                    <option value="is_insured">تعداد بیمه ها</option>
                    <option value="acceptance_criteria">معیار پذیرش</option>
                    <option value="members_count">تعداد اعضا</option>
                    <option value="head_name">سرپرست خانوار</option>
                    <option value="consumption_coefficient">ضریبه مصرف</option>
                    <option value="created_at">تاریخ عضویت</option>
                    <option value="payer">پرداخت کننده حق بیمه</option>
                    <option value="participation_percentage">درصد مشارکت</option>
                    <option value="verified_at">تاییدیه</option>
                </select>
            </div>
            
            <div class="relative">
                <select wire:model.live="sortDirection" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="asc">صعودی</option>
                    <option value="desc">نزولی</option>
                </select>
            </div>
        </div>
    </div>

    <!-- آمار تجمیعی -->
    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded shadow">
            <div class="text-sm font-medium text-gray-500">خانواده‌های بیمه شده</div>
            <div class="mt-1 text-3xl font-semibold text-gray-800">{{ $insuredFamilies }}</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-sm font-medium text-gray-500">خانواده‌های بدون بیمه</div>
            <div class="mt-1 text-3xl font-semibold text-gray-800">{{ $uninsuredFamilies }}</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-sm font-medium text-gray-500">اعضای بیمه شده</div>
            <div class="mt-1 text-3xl font-semibold text-gray-800">{{ $insuredMembers }}</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-sm font-medium text-gray-500">اعضای بدون بیمه</div>
            <div class="mt-1 text-3xl font-semibold text-gray-800">{{ $uninsuredMembers }}</div>
        </div>
    </div>

    <!-- جدول خانواده‌ها -->
    <div class="w-full overflow-x-auto">
        <table class="w-full border border-gray-200">
            <thead>
                <tr class="bg-gray-50 text-xs text-gray-700">
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('id')" class="flex items-center justify-end w-full">
                            رتبه
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('province')" class="flex items-center justify-end w-full">
                            استان
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('city')" class="flex items-center justify-end w-full">
                            شهر/روستا
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('is_insured')" class="flex items-center justify-end w-full">
                            تعداد بیمه ها
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('acceptance_criteria')" class="flex items-center justify-end w-full">
                            معیار پذیرش
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('members_count')" class="flex items-center justify-end w-full">
                            تعداد اعضا
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('head_name')" class="flex items-center justify-end w-full">
                            سرپرست خانوار
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('consumption_coefficient')" class="flex items-center justify-end w-full">
                            ضریبه مصرف
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('created_at')" class="flex items-center justify-end w-full">
                            تاریخ عضویت
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('payer')" class="flex items-center justify-end w-full">
                            پرداخت کننده حق بیمه
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('participation_percentage')" class="flex items-center justify-end w-full">
                            درصد مشارکت
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('verified_at')" class="flex items-center justify-end w-full">
                            تاییدیه / اعضا
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($families as $family)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $loop->iteration }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->region->province ?? 'نامشخص' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->region->name ?? 'نامشخص' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->is_insured ? $family->members->count() : 0 }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        @if(is_array($family->acceptance_criteria) || $family->acceptance_criteria instanceof \Illuminate\Support\Collection)
                            @foreach($family->acceptance_criteria as $criteria)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1 mb-1">
                                    {{ $criteria }}
                                </span>
                            @endforeach
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1">
                                {{ $family->acceptance_criteria ?? 'از کار افتادگی' }}
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->members->count() ?? 0 }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->head()?->full_name ?? 'نامشخص' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        ۵۰٪
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->created_at ? jdate($family->created_at)->format('Y/m/d') : '-' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        <div class="flex items-center">
                            <span>{{ $family->payer ?? 'خیریه' }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        <div class="flex items-center">
                            <span class="ml-2">{{ $family->participation_percentage ?? '۵۰٪' }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        <div class="flex space-x-2 space-x-reverse">
                            <span class="bg-blue-100 text-blue-800 text-xs py-1 px-2 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                            
                            <button wire:click="toggleFamily({{ $family->id }})" class="bg-green-200 hover:bg-green-300 text-green-800 text-xs py-1 px-2 rounded-full transition-colors duration-150 ease-in-out">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block transform {{ $expandedFamily === $family->id ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                
                @if($expandedFamily === $family->id)
                <tr class="bg-transparent">
                    <td colspan="12" class="px-0 py-0">
                        <div class="overflow-x-auto">
                            <table class="w-full bg-green-50 border border-green-100">
                                <thead>
                                    <tr class="bg-green-100 border-b border-green-200">
                                        <th class="px-4 py-3 text-sm font-medium text-gray-700 text-right">سرپرست؟</th>
                                        <th class="px-4 py-3 text-sm font-medium text-gray-700 text-right">نسبت</th>
                                        <th class="px-4 py-3 text-sm font-medium text-gray-700 text-right">نام</th>
                                        <th class="px-4 py-3 text-sm font-medium text-gray-700 text-right">نام خانوادگی</th>
                                        <th class="px-4 py-3 text-sm font-medium text-gray-700 text-right">کد ملی</th>
                                        <th class="px-4 py-3 text-sm font-medium text-gray-700 text-right">تاریخ تولد</th>
                                        <th class="px-4 py-3 text-sm font-medium text-gray-700 text-right">شغل</th>
                                        <th class="px-4 py-3 text-sm font-medium text-gray-700 text-right">نوع مشکل</th>
                                        <th class="px-4 py-3 text-sm font-medium text-gray-700 text-right">نوع بیمه</th>
                                        <th class="px-4 py-3 text-sm font-medium text-gray-700 text-right">پرداخت کننده حق بیمه</th>
                                        <th class="px-4 py-3 text-sm font-medium text-gray-700 text-right">درصد مشارکت</th>
                                        <th class="px-4 py-3 text-sm font-medium text-gray-700 text-right">تاییدیه</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($familyMembers as $member)
                                    <tr class="bg-green-100 border-b border-green-200 hover:bg-green-200">
                                        <td class="px-4 py-3 text-sm text-gray-800 text-center">
                                            @if($member->is_head)
                                                <span class="text-blue-500 inline-block">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800">
                                            {{ $member->is_head ? 'پدر' : ($member->gender == 'male' ? 'پسر' : 'مادر') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800">{{ $member->first_name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800">{{ $member->last_name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800">{{ $member->national_code ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800">{{ $member->birth_date ? jdate($member->birth_date)->format('Y/m/d') : '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800">{{ $member->occupation ?? 'بیکار' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800">
                                            @if($member->has_disability)
                                                <span class="px-2 py-0.5 rounded-md text-xs bg-orange-100 text-orange-800">
                                                    از کار افتادگی
                                                </span>
                                            @elseif($member->has_chronic_disease)
                                                <span class="px-2 py-0.5 rounded-md text-xs bg-pink-100 text-red-800">
                                                    بیماری خاص
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-md text-xs bg-gray-100 text-gray-800">
                                                    اعتیاد
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800">
                                            درمان تکمیلی
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800">
                                            درمان تکمیلی
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800">۱۰۰٪</td>
                                        <td class="px-4 py-3 text-sm text-gray-800 text-center">
                                            ✓
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="12" class="px-4 py-3 text-sm text-gray-500 text-center border-b border-gray-100">
                                            عضوی برای این خانواده ثبت نشده است.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            
                            <div class="bg-green-100 py-4 px-4 rounded-b border-r border-l border-b border-green-100 flex justify-between items-center">
                                <div class="flex items-center">
                                    <span class="text-sm text-gray-600 ml-2">شماره موبایل سرپرست:</span>
                                    <div class="bg-white rounded px-3 py-2 flex items-center">
                                        <span class="text-sm text-gray-800">{{ $family->head()?->mobile ?? '09347964873' }}</span>
                                        <button type="button" wire:click="copyText('09347964873')" class="text-blue-500 mr-2 cursor-pointer">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="flex items-center">
                                    <span class="text-sm text-gray-600 ml-2">شماره شبا جهت پرداخت خسارت:</span>
                                    <div class="bg-white rounded px-3 py-2 flex items-center">
                                        <span class="text-sm text-gray-800 ltr">{{ $family->head()?->sheba ?? 'IR056216845813188' }}</span>
                                        <button type="button" wire:click="copyText('IR056216845813188')" class="text-blue-500 mr-2 cursor-pointer">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @endif
                
                @empty
                <tr>
                    <td colspan="12" class="px-5 py-4 text-sm text-gray-500 border-b border-gray-200 text-center">
                        هیچ خانواده‌ای یافت نشد.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- اعلان کپی -->
    <div id="copy-notification" class="fixed top-4 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-md shadow-lg z-50 flex items-center opacity-0 transition-opacity duration-300">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span id="copy-notification-text">متن با موفقیت کپی شد</span>
    </div>
    
    <!-- پیجینیشن -->
    @if($families->hasPages())
    <div class="mt-4">
        {{ $families->links() }}
    </div>
    @endif
    
    <script>
    document.addEventListener('livewire:initialized', function () {
        Livewire.on('copy-text', params => {
            console.log('دریافت متن برای کپی:', params);
            
            // استخراج متن از پارامتر دریافتی - آبجکت است نه رشته
            let textToCopy = '';
            
            if (typeof params === 'object' && params !== null) {
                // اگر دارای ویژگی text است، از آن استفاده کن
                if (params.hasOwnProperty('text')) {
                    textToCopy = params.text;
                } else {
                    // استفاده از تبدیل رشته‌ای استاندارد
                    textToCopy = String(params);
                }
            } else {
                // اگر پارامتر ارسالی مستقیماً رشته یا عدد یا اینهاست، از آن استفاده می‌کنیم
                textToCopy = String(params);
            }
            
            console.log('متن نهایی برای کپی:', textToCopy);
            
            // روش 1: استفاده از Clipboard API
            if (navigator.clipboard) {
                navigator.clipboard.writeText(textToCopy)
                    .then(() => {
                        console.log('متن با موفقیت کپی شد:', textToCopy);
                        showCopyNotification(textToCopy);
                    })
                    .catch(err => {
                        console.error('خطا در کپی متن: ', err);
                        // استفاده از روش دوم در صورت خطا
                        fallbackCopyTextToClipboard(textToCopy);
                    });
            } else {
                // استفاده از روش دوم اگر Clipboard API پشتیبانی نشود
                fallbackCopyTextToClipboard(textToCopy);
            }
        });
        
        // روش جایگزین برای مرورگرهایی که از Clipboard API پشتیبانی نمی‌کنند
        function fallbackCopyTextToClipboard(text) {
            // ایجاد یک عنصر textarea موقت
            const textarea = document.createElement('textarea');
            textarea.value = text;
            
            // تنظیم موقعیت خارج از دید
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            
            // اضافه کردن به DOM
            document.body.appendChild(textarea);
            
            // انتخاب متن
            textarea.focus();
            textarea.select();
            
            // کپی متن
            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    console.log('متن با موفقیت کپی شد (روش جایگزین):', text);
                    showCopyNotification(text);
                } else {
                    console.error('کپی متن با شکست مواجه شد.');
                }
            } catch (err) {
                console.error('خطا در کپی متن: ', err);
            }
            
            // حذف عنصر موقت
            document.body.removeChild(textarea);
        }
        
        // نمایش نوتیفیکیشن کپی
        function showCopyNotification(text) {
            const notification = document.getElementById('copy-notification');
            const notificationText = document.getElementById('copy-notification-text');
            
            // تنظیم متن نوتیفیکیشن
            notificationText.textContent = 'متن با موفقیت کپی شد: ' + text;
            
            // نمایش نوتیفیکیشن
            notification.classList.add('opacity-100');
            notification.classList.remove('opacity-0');
            
            // مخفی کردن نوتیفیکیشن بعد از ۲ ثانیه
            setTimeout(() => {
                notification.classList.remove('opacity-100');
                notification.classList.add('opacity-0');
            }, 2000);
        }
    });
    </script>
    
    <style>
    @keyframes slideInDown {
        from {
            transform: translate(-50%, -20px);
            opacity: 0;
        }
        to {
            transform: translate(-50%, 0);
            opacity: 1;
        }
    }
    
    #copy-notification {
        animation: slideInDown 0.3s ease-out forwards;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
    }
    </style>
</div>
