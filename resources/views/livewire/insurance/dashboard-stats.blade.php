@php
    // استفاده از داده‌های واقعی از کامپوننت
    $geoLabels = $provinceNames ?? [];
    $geoDataMale = $provinceMaleCounts ?? [];
    $geoDataFemale = $provinceFemaleCounts ?? [];
    $geoDataDeprived = $provinceDeprivedCounts ?? [];
@endphp

<div>
    <!-- خلاصه آمار کلی -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white p-6 rounded-xl shadow-lg mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 text-center">
            <div>
                <div class="text-3xl font-bold">{{ number_format($totalInsured) }}</div>
                <div class="text-blue-100 text-sm">افراد تحت پوشش</div>
            </div>
            <div>
                <div class="text-3xl font-bold">{{ $selectedMonth ? $jalaliMonths[$selectedMonth] : 'کل سال' }} {{ $selectedYear }}</div>
                <div class="text-blue-100 text-sm">دوره انتخاب شده</div>
            </div>
            <div>
                <div class="text-3xl font-bold">{{ number_format($totalOrganizations) }}</div>
                <div class="text-blue-100 text-sm">سازمان فعال</div>
            </div>
            <div>
                <div class="text-3xl font-bold">{{ $financialRatio['totalDisplay'] ?? '0' }}</div>
                <div class="text-blue-100 text-sm">{{ $financialRatio['unit'] ?? 'میلیون تومان' }}</div>
            </div>
        </div>
    </div>

    <!-- فیلترهای داشبورد -->
    <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
        <div class="flex flex-col space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">فیلترهای داشبورد</h3>
                <p class="text-sm text-gray-600">انتخاب دوره زمانی و سازمان برای نمایش داده‌های مربوطه</p>
            </div>

            <!-- فیلترها -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- فیلتر سال -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">سال:</label>
                    <div class="relative">
                        <select wire:model.live="selectedYear"
                                style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;"
                                class="w-full border border-gray-300 rounded-md pr-8 pl-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                            @foreach($jalaliYears as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- فیلتر ماه -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ماه:</label>
                    <div class="relative">
                        <select wire:model.live="selectedMonth"
                                style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;"
                                class="w-full border border-gray-300 rounded-md pr-8 pl-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                            <option value="">کل سال</option>
                            @foreach($jalaliMonths as $monthNum => $monthName)
                                <option value="{{ $monthNum }}">{{ $monthName }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- فیلتر سازمان -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">سازمان:</label>
                    <div class="relative">
                        <select wire:model.live="selectedOrganization"
                                style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;"
                                class="w-full border border-gray-300 rounded-md pr-8 pl-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                            <option value="">همه سازمان‌ها</option>
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }} ({{ $org->type === 'charity' ? 'خیریه' : 'بیمه' }})</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- دکمه ریست -->
                <div class="flex items-end">
                    <button wire:click="resetFilters" class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-gray-500 transition-colors duration-200">
                        <svg class="w-4 h-4 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        ریست فیلترها
                    </button>
                </div>
            </div>

            <!-- نمایش فیلترهای فعال -->
            <div class="flex flex-wrap gap-2 mt-2">
                @if($selectedMonth)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $jalaliMonths[$selectedMonth] ?? 'ماه انتخابی' }}
                        <button wire:click="$set('selectedMonth', '')" class="mr-1 text-blue-600 hover:text-blue-800">×</button>
                    </span>
                @endif

                @if($selectedOrganization)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        {{ $organizations->find($selectedOrganization)->name ?? 'سازمان انتخابی' }}
                        <button wire:click="$set('selectedOrganization', '')" class="mr-1 text-purple-600 hover:text-purple-800">×</button>
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Top Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- کارت افراد تحت پوشش -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="text-gray-600 text-sm font-medium mb-2">افراد تحت پوشش</div>
                    <div class="text-2xl font-bold text-gray-800">{{ number_format($totalInsured) }}</div>
                    <div class="text-sm text-gray-500 mt-1">نفر</div>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- کارت خسارات پرداخت شده -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="text-gray-600 text-sm font-medium mb-2">خسارات پرداخت شده</div>
                    <div class="text-2xl font-bold text-gray-800">{{ number_format($monthlyClaimsData['claims'] ?? 0) }}</div>
                    <div class="text-sm text-gray-500 mt-1">ریال</div>
                </div>
                <div class="bg-orange-100 p-3 rounded-full">
                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- کارت بودجه کل -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="text-gray-600 text-sm font-medium mb-2">بودجه تخصیص یافته</div>
                    <div class="text-2xl font-bold text-gray-800">{{ number_format($financialRatio['budget'] ?? 0) }}</div>
                    <div class="text-sm text-gray-500 mt-1">ریال</div>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- کارت بودجه باقیمانده -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 text-gray-600 text-sm font-medium mb-2">
                        <span>بودجه باقیمانده</span>
                        <a href="{{ route('insurance.funding-manager') }}" class="text-blue-500 hover:text-blue-700 transition-colors" title="افزودن بودجه" aria-label="افزودن بودجه">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>
                    </div>
                    <div class="text-2xl font-bold text-gray-800">{{ number_format(($financialRatio['budget'] ?? 0) - ($monthlyClaimsData['premiums'] ?? 0) - ($monthlyClaimsData['claims'] ?? 0)) }}</div>
                    <div class="text-sm text-gray-500 mt-1">ریال</div>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Gender & Geographic Charts -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
        <!-- نمودار جنسیتی -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <div class="text-lg font-semibold text-center mb-1">تفکیک جنسیتی</div>
            <div class="text-center text-gray-600 text-sm mb-4">افراد تحت پوشش</div>
            <div class="relative flex flex-col items-center justify-center">
                <canvas id="genderDonut" width="200" height="200"></canvas>
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-center">
                    <div class="text-gray-500 text-xs">مجموع</div>
                    <div class="text-xl font-bold">{{ number_format($totalInsured) }}</div>
                    <div class="text-xs text-gray-400">نفر</div>
                </div>
            </div>
            <div class="flex justify-center gap-4 mt-4 text-sm">
                <div class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-blue-500"></span>مرد
                </div>
                <div class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-green-500"></span>زن
                </div>
            </div>
        </div>

        <!-- نمودار جغرافیایی -->
        <div class="xl:col-span-2 bg-white p-6 rounded-xl shadow-lg">
            <div class="mb-4">
                <div class="text-lg font-semibold text-gray-800 mb-1">تفکیک جغرافیایی افراد تحت پوشش</div>
                <div class="text-gray-600 text-sm">
                    میله‌ها: تعداد تحت پوشش در هر استان (تجمیعی زن و مرد)
                    <span class="text-red-500 mr-2">• خط منحنی: افراد محروم</span>
                </div>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <canvas id="geoBarLineChart" height="280"></canvas>
            </div>
        </div>
    </div>

    <!-- نمودار جریان مالی -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
        <div class="lg:col-span-3 bg-white p-6 rounded-xl shadow-lg">
            <div class="mb-4">
                <div class="text-lg font-semibold text-gray-800 mb-1">نمودار جریان مالی ساﻻنه</div>
                <div class="text-gray-600 text-sm">
                    <span class="text-red-500">• آمارها مربوط به افراد انتخاب شده</span><br>
                    میله‌ها: بودجه اختصاص یافته و خسارت پرداختی ماهانه • خط: روند کلی
                </div>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <canvas id="financialFlowChart" height="240"></canvas>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-lg">
            <div class="text-center mb-4">
                <div class="text-lg font-semibold text-gray-800">نسبت مالی کلی</div>
                <div class="text-sm text-gray-600">تحلیل حق بیمه و خسارات</div>
            </div>

            <!-- آمار کلی -->
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="bg-green-50 p-3 rounded-lg text-center">
                    <div class="text-xs text-green-600 mb-1">حق بیمه</div>
                    <div class="text-lg font-bold text-green-800">{{ $financialRatio['premiumsDisplay'] }}</div>
                    <div class="text-xs text-gray-500">{{ $financialRatio['unit'] }}</div>
                </div>
                <div class="bg-orange-50 p-3 rounded-lg text-center">
                    <div class="text-xs text-orange-600 mb-1">خسارات</div>
                    <div class="text-lg font-bold text-orange-800">{{ $financialRatio['claimsDisplay'] }}</div>
                    <div class="text-xs text-gray-500">{{ $financialRatio['unit'] }}</div>
                </div>
            </div>

            <!-- نمودار دونات -->
            <div class="relative mb-4">
                <canvas id="doubleDonut" width="200" height="200"></canvas>
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-center">
                    <div class="text-gray-500 text-xs">مجموع</div>
                    <div class="text-xl font-bold">{{ $financialRatio['totalDisplay'] }}</div>
                    <div class="text-xs text-gray-400">{{ $financialRatio['unit'] }}</div>
                </div>
            </div>

            <!-- درصدها -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        <span class="text-sm font-medium">حق بیمه پرداختی</span>
                    </div>
                    <span class="text-sm font-bold text-green-700">{{ $financialRatio['premiumsPercentage'] }}%</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                        <span class="text-sm font-medium">خسارت پرداختی</span>
                    </div>
                    <span class="text-sm font-bold text-orange-700">{{ $financialRatio['claimsPercentage'] }}%</span>
                </div>

                <!-- نسبت خسارت -->
                <div class="border-t pt-2 mt-3">
                    <div class="flex items-center justify-between text-xs text-gray-600">
                        <span>نسبت خسارت:</span>
                        <span class="font-bold {{ $financialRatio['claimsPercentage'] > 40 ? 'text-red-600' : 'text-green-600' }}">
                            {{ round($financialRatio['claimsPercentage'], 1) }}%
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        @if($financialRatio['claimsPercentage'] <= 30)
                            <span class="text-green-600">• وضعیت مالی مطلوب</span>
                        @elseif($financialRatio['claimsPercentage'] <= 40)
                            <span class="text-yellow-600">• وضعیت مالی متوسط</span>
                        @else
                            <span class="text-red-600">• نیاز به بازنگری</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- نمودار جریان خسارات ماهانه -->
    <div class="bg-white p-6 rounded-xl shadow-lg mb-8 max-w-7xl mx-auto">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- نمودار اصلی -->
            <div class="flex-1">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-gray-800 mb-2">جریان خسارت‌های پرداختی ماهانه</h3>
                    <p class="text-sm text-gray-600">نمایش خسارات پرداختی به تفکیک ماه‌ها بر اساس فیلترهای انتخابی</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <canvas id="monthlyClaimsFlowChart" height="300"></canvas>
                </div>
                <!-- Legend -->
                <div class="flex justify-center gap-6 mt-4 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 bg-green-500 rounded"></span>
                        <span>خسارات پرداختی</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 bg-blue-500 rounded"></span>
                        <span>حق بیمه</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 bg-purple-500 rounded"></span>
                        <span>بودجه</span>
                    </div>
                </div>
            </div>

            <!-- پنل کنترل -->
            <div class="w-full lg:w-80">
                <!-- نمودار نسبت خسارت ماهانه -->
                <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div class="text-center mb-4">
                        <h4 class="font-semibold text-gray-800 mb-2">نسبت خسارت انتخابی</h4>
                        <p class="text-xs text-gray-600">بر اساس فیلترهای فعال</p>
                    </div>

                    <!-- نمودار دونات کوچک -->
                    <div class="relative mb-4">
                        <canvas id="monthlyClaimsChart" width="200" height="200"></canvas>
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-center">
                            <div class="text-xs text-gray-500">
                                {{ $selectedMonth ? $jalaliMonths[$selectedMonth] : 'کل سال' }} {{ $selectedYear }}
                            </div>
                            <div class="text-sm font-bold">{{ number_format($monthlyClaimsData['total'] ?? 0) }}</div>
                            <div class="text-xs text-gray-400">ریال</div>
                        </div>
                    </div>

                    <!-- آمار کلی -->
                    <div class="bg-gray-50 rounded-lg p-3 text-xs">
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span>بودجه:</span>
                                <span class="font-bold text-purple-600">{{ number_format($monthlyClaimsData['budget'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>حق بیمه:</span>
                                <span class="font-bold text-green-600">{{ number_format($monthlyClaimsData['premiums'] ?? 0) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>خسارت:</span>
                                <span class="font-bold text-orange-600">{{ number_format($monthlyClaimsData['claims'] ?? 0) }}</span>
                            </div>
                            <div class="border-t pt-2 flex justify-between font-bold">
                                <span>مجموع:</span>
                                <span>{{ number_format($monthlyClaimsData['total'] ?? 0) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- نمودار معیارهای پذیرش -->
    <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
        <div class="mb-6">
            <div class="text-lg font-semibold text-gray-800 mb-2">معیارهای پذیرش افراد تحت پوشش</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="bg-green-50 p-3 rounded-lg border border-green-200">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        <span class="font-semibold text-green-800">معیارهای خانوادگی</span>
                    </div>
                    <div class="text-green-700 text-xs">
                        معیارهایی که به کل خانواده اعمال می‌شوند مانند محرومیت، سرپرستی زن و...
                    </div>
                </div>
                <div class="bg-blue-50 p-3 rounded-lg border border-blue-200">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                        <span class="font-semibold text-blue-800">معیارهای فردی</span>
                    </div>
                    <div class="text-blue-700 text-xs">
                        معیارهایی که به هر فرد اعمال می‌شوند مانند معلولیت، بیماری خاص و...
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 p-4 rounded-lg">
            <canvas id="criteriaBarChart" height="300"></canvas>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
            @foreach($criteriaData as $criteria)
                <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
                    <div class="flex items-center justify-center gap-2 mb-2">
                        <span class="w-3 h-3 rounded-full {{ $criteria['color'] === '#ef4444' ? 'bg-red-500' : ($criteria['color'] === '#3b82f6' ? 'bg-blue-500' : ($criteria['color'] === '#10b981' ? 'bg-green-500' : 'bg-purple-500')) }}"></span>
                        <span class="font-semibold text-gray-800 text-sm">{{ $criteria['percentage'] }}%</span>
                    </div>
                    <div class="text-xs text-gray-600 mb-1">{{ $criteria['name'] }}</div>
                    <div class="text-lg font-bold text-gray-800">{{ number_format($criteria['count']) }}</div>
                    <div class="text-xs text-gray-500">{{ $criteria['type'] === 'family' ? 'خانوار' : 'نفر' }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Loading indicator -->
    <div wire:loading class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <div class="text-gray-600">در حال بارگذاری...</div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('js/dashboard-charts.js') }}"></script>

    <!-- داده‌های چارت برای JavaScript -->
    <script type="application/json" id="chart-data">
        {!! json_encode([
            'geoLabels' => $provinceNames ?? [],
            'geoDataMale' => $provinceMaleCounts ?? [],
            'geoDataFemale' => $provinceFemaleCounts ?? [],
            'geoDataDeprived' => $provinceDeprivedCounts ?? [],
            'criteriaData' => $criteriaData ?? [],
            'monthlyData' => $monthlyClaimsData ?? [],
            'yearlyData' => $yearlyClaimsFlow ?? [],
            'financialData' => $financialRatio ?? [],
            'maleCount' => $maleCount ?? 0,
            'femaleCount' => $femaleCount ?? 0
        ]) !!}
    </script>
</div>
