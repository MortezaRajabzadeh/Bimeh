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
                            
                            <button wire:click="toggleFamily({{ $family->id }})" class="bg-green-100 hover:bg-green-200 text-green-800 text-xs py-1 px-2 rounded-full transition-colors duration-150 ease-in-out">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block transform {{ $expandedFamily === $family->id ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                
                @if($expandedFamily === $family->id)
                <tr class="bg-transparent">
                    <td colspan="12" class="px-0 py-4">
                        <div class="bg-green-50 rounded-lg overflow-x-auto p-4 border border-green-200">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-green-200">
                                        <th class="px-4 py-3 text-sm font-medium text-gray-700 text-right">سرپرست؟</th>
                                        <th class="px-4 py-3 text-sm font-medium text-gray-700 text-right">اعضای خانواده</th>
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
                                    <tr class="border-b border-green-100 hover:bg-green-100 transition-colors">
                                        <td class="px-4 py-3 text-sm text-gray-800 ">
                                            @if($member->is_head)
                                                <div class="flex items-center justify-center">
                                                    <div class="w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800">
                                            <div class="flex items-center space-x-1 space-x-reverse">
                                                <span class="inline-block w-8 h-8 overflow-hidden rounded-full bg-gray-200">
                                                    <svg class="h-full w-full text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path>
                                                    </svg>
                                                </span>
                                                <div class="mx-2">
                                                    {{ $member->is_head ? 'پدر' : ($member->gender == 'male' ? 'پسر' : 'مادر') }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800">{{ $member->first_name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800">{{ $member->last_name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800">{{ $member->national_code ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800">{{ $member->birth_date ? jdate($member->birth_date)->format('Y/m/d') : '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800">{{ $member->occupation ?? 'بیکار' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800">
                                            @if($member->has_disability)
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    از کار افتادگی
                                                </span>
                                            @elseif($member->has_chronic_disease)
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-rose-100 text-rose-800">
                                                    بیماری خاص
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                    اعتیاد
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800">
                                            @if($member->has_insurance)
                                                <span>درمان تکمیلی</span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800">
                                            <div class="flex items-center space-x-1 space-x-reverse">
                                                <span class="inline-block p-1 rounded-full {{ $member->has_insurance ? 'bg-blue-100' : 'bg-gray-100' }}">
                                                    @if($member->has_insurance)
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                    </svg>
                                                    @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                    </svg>
                                                    @endif
                                                </span>
                                                <span>{{ $member->has_insurance ? 'درمان تکمیلی' : '-' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800">{{ $member->has_insurance ? '۱۰۰٪' : '۵۰٪' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800">
                                            <div class="flex items-center space-x-1 space-x-reverse">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="12" class="px-4 py-3 text-sm text-gray-500 text-center border-b border-green-100">
                                            عضوی برای این خانواده ثبت نشده است.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            
                            @if(count($familyMembers) > 0 && $familyMembers->first()?->is_head)
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-white rounded-lg p-3 border border-green-100 flex items-center justify-between">
                                    <span class="text-sm text-gray-600">شماره موبایل سرپرست</span>
                                    <span class="text-sm font-medium flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                        </svg>
                                        {{ $familyMembers->first(fn($m) => $m->is_head)?->mobile ?? '۰۹۱۲۳۴۵۶۷۸۹' }}
                                    </span>
                                </div>
                                <div class="bg-white rounded-lg p-3 border border-green-100 flex items-center justify-between">
                                    <span class="text-sm text-gray-600">شماره شبا جهت پرداخت خسارت</span>
                                    <span class="text-sm font-medium flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" />
                                            <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" />
                                        </svg>
                                        IR۰۵۶۲۱۶۸۴۵۸۱۳۱۸۸۴
                                    </span>
                                </div>
                            </div>
                            @endif
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
    
    <!-- پیجینیشن -->
    @if($families->hasPages())
    <div class="mt-4">
        {{ $families->links() }}
    </div>
    @endif
</div>
