<div class="container mx-auto px-4 py-6">

    <!-- بخش هدر -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border border-gray-100">
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('insurance.paid-claims') }}" class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition duration-200">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    بازگشت
                </a>
                <h2 class="text-2xl font-bold text-gray-800">خلاصه خسارات پرداخت شده</h2>
            </div>
            <button wire:click="toggleFilters" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                <span>{{ $showFilters ? 'پنهان کردن فیلترها' : 'نمایش فیلترها' }}</span>
            </button>
        </div>

        <!-- بخش فیلترها -->
        @if($showFilters)
        <div class="border-t pt-4 mt-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                <!-- فیلتر نوع نمایش -->
                <div>
                    <label for="viewType" class="block text-sm font-medium text-gray-700 mb-2">نوع نمایش</label>
                    <div class="relative">
                        <select wire:model.live="viewType" id="viewType" class="w-full pr-10 pl-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white text-right">
                            <option value="summary">خلاصه عمومی</option>
                            <option value="monthly">نمای ماهانه</option>
                            <option value="top_families">خانواده‌های پرخسارت</option>
                            <option value="by_insurance_type">به تفکیک نوع بیمه</option>
                            <option value="by_status">به تفکیک وضعیت</option>
                        </select>
                        {{-- <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div> --}}
                    </div>
                </div>

                <!-- فیلتر تاریخ شروع -->
                @if($viewType !== 'monthly')
                <div>
                    <label for="startDate" class="block text-sm font-medium text-gray-700 mb-2">از تاریخ</label>
                    <div class="relative">
                        <input type="text"
                               wire:model.live="startDate"
                               id="startDate"
                               class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-right"
                               placeholder="انتخاب تاریخ"
                               ata-jdp-only-date
                               data-jdp>
                        <div class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- فیلتر تاریخ پایان -->
                <div>
                    <label for="endDate" class="block text-sm font-medium text-gray-700 mb-2">تا تاریخ</label>
                    <div class="relative">
                        <input type="text"
                               wire:model.live="endDate"
                               id="endDate"
                               class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-right"
                               placeholder="انتخاب تاریخ"
                               data-jdp-only-date
                               data-jdp>
                        <div class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>
                @endif

                <!-- فیلتر سال برای نمای ماهانه -->
                @if($viewType === 'monthly')
                <div>
                    <label for="selectedYear" class="block text-sm font-medium text-gray-700 mb-2">سال</label>
                    <div class="relative">
                        <select wire:model.live="selectedYear" id="selectedYear" class="w-full pr-10 pl-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white text-right">
                            @for($year = 1400; $year <= 1410; $year++)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                @endif

                <!-- فیلتر نوع بیمه -->
                <div>
                    <label for="selectedInsuranceType" class="block text-sm font-medium text-gray-700 mb-2">نوع بیمه</label>
                    <div class="relative">
                        <select wire:model.live="selectedInsuranceType" id="selectedInsuranceType" class="w-full pr-10 pl-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white text-right">
                            <option value="">همه انواع</option>
                            @foreach($availableInsuranceTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>

                    </div>
                </div>

                <!-- فیلتر کد خانواده -->
                <div>
                    <label for="familyCode" class="block text-sm font-medium text-gray-700 mb-2">کد خانواده</label>
                    <input type="text"
                           wire:model.lazy="familyCode"
                           id="familyCode"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-right"
                           placeholder="جستجوی کد خانواده">
                </div>

                <!-- فیلتر وضعیت پرداخت -->
                <div>
                    <label for="paymentStatus" class="block text-sm font-medium text-gray-700 mb-2">وضعیت پرداخت</label>
                    <div class="relative">
                        <select wire:model.live="paymentStatus" id="paymentStatus" class="w-full pr-10 pl-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white text-right">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="paid">پرداخت شده</option>
                            <option value="pending">در انتظار پرداخت</option>
                        </select>

                    </div>
                </div>

                <!-- فیلتر محدوده مبلغ -->
                <div>
                    <label for="minAmount" class="block text-sm font-medium text-gray-700 mb-2">حداقل مبلغ</label>
                    <input type="number"
                           wire:model.lazy="minAmount"
                           id="minAmount"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-right"
                           placeholder="حداقل مبلغ خسارت">
                </div>

                <div>
                    <label for="maxAmount" class="block text-sm font-medium text-gray-700 mb-2">حداکثر مبلغ</label>
                    <input type="number"
                           wire:model.lazy="maxAmount"
                           id="maxAmount"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-right"
                           placeholder="حداکثر مبلغ خسارت">
                </div>

                <!-- فیلتر تعداد نمایش در هر صفحه -->
                <div>
                    <label for="perPage" class="block text-sm font-medium text-gray-700 mb-2">تعداد در صفحه</label>
                    <div class="relative">
                        <select wire:model.live="perPage" id="perPage" class="w-full pr-10 pl-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white text-right">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="0">همه</option>
                        </select>

                    </div>
                </div>
            </div>

            <!-- دکمه‌های عملیات فیلتر -->
            <div class="flex justify-between items-center mt-4">
                <!-- دکمه‌های صادرات -->
                <div class="flex gap-2">
                    <button wire:click="exportExcel" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        خروجی Excel
                    </button>
                </div>

                <!-- دکمه‌های فیلتر -->
                <div class="flex gap-3">
                    <button wire:click="clearFilters" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        پاک کردن فیلترها
                    </button>
                    <button wire:click="applyFilters" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        اعمال فیلترها
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- نمایش پیام‌های موفقیت -->
    @if(session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 flex items-center">
            <svg class="w-5 h-5 ml-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif


    <!-- آمار کلی -->
    @if($overallStats)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">کل خانواده‌ها</p>
                        <p class="text-2xl font-bold text-blue-600">{{ number_format($overallStats->total_families) }}</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">کل خسارات</p>
                        <p class="text-2xl font-bold text-green-600">{{ number_format($overallStats->total_claims) }}</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">مجموع مبلغ</p>
                        <p class="text-2xl font-bold text-purple-600">{{ number_format($overallStats->total_amount) }}</p>
                        <p class="text-xs text-gray-500">تومان</p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">میانگین خسارت</p>
                        <p class="text-2xl font-bold text-orange-600">{{ number_format($overallStats->average_claim_amount) }}</p>
                        <p class="text-xs text-gray-500">تومان</p>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- جدول داده‌ها -->
    <div class="overflow-x-auto rounded-2xl shadow-lg bg-white border border-gray-100">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr class="text-center text-gray-700 text-base">
                    @if($viewType === 'summary')
                        <th class="py-3 px-4">تاریخ ثبت</th>
                        <th class="py-3 px-4">تاریخ صدور بیمه</th>
                        <th class="py-3 px-4">تاریخ پرداخت</th>
                        <th class="py-3 px-4">نوع بیمه</th>
                        <th class="py-3 px-4">تعداد خانواده</th>
                        <th class="py-3 px-4">تعداد خسارت</th>
                        <th class="py-3 px-4">مجموع مبلغ</th>
                        <th class="py-3 px-4">میانگین</th>
                    @elseif($viewType === 'monthly')
                        <th class="py-3 px-4">سال</th>
                        <th class="py-3 px-4">ماه</th>
                        <th class="py-3 px-4">نوع بیمه</th>
                        <th class="py-3 px-4">تعداد خانواده</th>
                        <th class="py-3 px-4">تعداد خسارت</th>
                        <th class="py-3 px-4">مجموع مبلغ</th>
                    @elseif($viewType === 'top_families')
                        <th class="py-3 px-4">کد خانواده</th>
                        <th class="py-3 px-4">سرپرست</th>
                        <th class="py-3 px-4">موبایل</th>
                        <th class="py-3 px-4">تعداد خسارت</th>
                        <th class="py-3 px-4">مجموع مبلغ</th>
                        <th class="py-3 px-4">میانگین خسارت</th>
                        <th class="py-3 px-4">آخرین خسارت</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($summaryData as $item)
                    <tr class="text-center hover:bg-green-50 transition">
                        @if($viewType === 'summary')
                            <td class="py-3 px-4 text-gray-700">
                                @if($item->allocation_date)
                                    {{ jdate($item->allocation_date)->format('Y/m/d') }}
                                    <div class="text-xs text-gray-500">{{ jdate($item->allocation_date)->format('l، d F Y') }}</div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-3 px-4 text-gray-700">
                                @if($item->issue_date)
                                    {{ jdate($item->issue_date)->format('Y/m/d') }}
                                    <div class="text-xs text-gray-500">{{ jdate($item->issue_date)->format('l، d F Y') }}</div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-3 px-4 text-gray-700">
                                @if($item->paid_date)
                                    {{ jdate($item->paid_date)->format('Y/m/d') }}
                                    <div class="text-xs text-gray-500">{{ jdate($item->paid_date)->format('l، d F Y') }}</div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-3 px-4 text-gray-700">
                                {{ $item->insurance_type ?: 'نامشخص' }}
                            </td>
                            <td class="py-3 px-4 font-bold text-blue-600">
                                {{ number_format($item->family_count) }}
                            </td>
                            <td class="py-3 px-4 font-bold text-green-600">
                                {{ number_format($item->total_claims) }}
                            </td>
                            <td class="py-3 px-4 font-bold text-purple-600">
                                {{ number_format($item->total_amount) }}
                                <span class="text-xs font-normal">تومان</span>
                            </td>
                            <td class="py-3 px-4 text-orange-600">
                                {{ number_format($item->average_amount) }}
                                <span class="text-xs font-normal">تومان</span>
                            </td>

                        @elseif($viewType === 'monthly')
                            <td class="py-3 px-4 text-gray-700">
                                @php
                                    $jalaliYear = $item->year + 621; // تبدیل سال میلادی به جلالی
                                @endphp
                                {{ $jalaliYear }} ({{ $item->year }})
                            </td>
                            <td class="py-3 px-4 text-gray-700">
                                @php
                                    $jalaliMonths = [
                                        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
                                        5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
                                        9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
                                    ];
                                    $jalaliMonth = (int) $item->month;
                                    if ($jalaliMonth > 12) $jalaliMonth = $jalaliMonth - 12;
                                    if ($jalaliMonth < 1) $jalaliMonth = $jalaliMonth + 12;
                                @endphp
                                {{ $jalaliMonths[$jalaliMonth] ?? $item->month }}
                            </td>
                            <td class="py-3 px-4 text-gray-700">{{ $item->insurance_type ?: 'نامشخص' }}</td>
                            <td class="py-3 px-4 font-bold text-blue-600">{{ number_format($item->family_count) }}</td>
                            <td class="py-3 px-4 font-bold text-green-600">{{ number_format($item->total_claims) }}</td>
                            <td class="py-3 px-4 font-bold text-purple-600">
                                {{ number_format($item->total_amount) }}
                                <span class="text-xs font-normal">تومان</span>
                            </td>

                        @elseif($viewType === 'top_families')
                            <td class="py-3 px-4 font-bold text-blue-600">{{ $item->family_code }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $item->head_name ?: '-' }}</td>
                            <td class="py-3 px-4 text-gray-700">{{ $item->mobile ?: '-' }}</td>
                            <td class="py-3 px-4 font-bold text-green-600">{{ number_format($item->claims_count) }}</td>
                            <td class="py-3 px-4 font-bold text-purple-600">
                                {{ number_format($item->total_claims_amount) }}
                                <span class="text-xs font-normal">تومان</span>
                            </td>
                            <td class="py-3 px-4 text-orange-600">
                                {{ number_format($item->average_claim_amount) }}
                                <span class="text-xs font-normal">تومان</span>
                            </td>
                            <td class="py-3 px-4 text-gray-700">
                                @if($item->last_claim_date)
                                    {{ jdate($item->last_claim_date)->format('Y/m/d') }}
                                    <div class="text-xs text-gray-500">{{ jdate($item->last_claim_date)->format('l، d F Y') }}</div>
                                @else
                                    -
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-6 text-center text-gray-400">
                            در این بازه تاریخی هیچ خسارتی یافت نشد.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($summaryData->count() > 0)
        <div class="mt-6 text-center text-sm text-gray-600">
            مجموعاً {{ number_format($summaryData->count()) }} ردیف نمایش داده شده است
        </div>
    @endif
</div>

<!-- اسکریپت‌های تقویم جلالی -->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/custom-select.css') }}">
<link rel="stylesheet" href="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css">
@endpush

@push('scripts')
<script src="/vendor/jalalidatepicker/jalalidatepicker.min.js"></script>
<script>
    function initJalaliDatepicker() {
        jalaliDatepicker.startWatch({
  minDate: "attr",
  maxDate: "attr",
  time: true,
});

    }

    document.addEventListener('livewire:load', function () {
        initJalaliDatepicker();
    });

    document.addEventListener('DOMContentLoaded', function () {
        initJalaliDatepicker();
    });

    // برای بروزرسانی‌های Livewire
    document.addEventListener('livewire:init', function () {
        setTimeout(initJalaliDatepicker, 100);
    });

    window.addEventListener('livewire:navigated', function () {
        setTimeout(initJalaliDatepicker, 200);
    });

    document.addEventListener('livewire:update', function () {
        setTimeout(initJalaliDatepicker, 300);
    });

    window.addEventListener('refreshJalali', function () {
        initJalaliDatepicker();
    });
</script>
@endpush
