<div>
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                <div>
                    <div class="text-3xl font-bold">{{ number_format($insuredMembers + $uninsuredMembers) }}</div>
                    <div class="text-blue-100 text-sm">کل افراد تحت نظارت</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">{{ number_format($insuredFamilies + $uninsuredFamilies) }}</div>
                    <div class="text-blue-100 text-sm">کل خانواده‌ها</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">{{ number_format($insuredMembers) }}</div>
                    <div class="text-blue-100 text-sm">افراد تحت پوشش</div>
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
            </div>
        </div>
    </div>
        <!-- کارت‌های اصلی -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- کارت خانواده‌های بیمه شده -->
            <div class="bg-green-50 p-6 rounded-lg shadow-sm border border-green-100">
                <div class="flex items-center mb-4">
                    <div class="bg-green-500 p-3 rounded-full mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">خانواده‌های بیمه شده</h3>
                        <p class="text-sm text-gray-600">{{ $insuredFamilies }} خانواده - {{ $insuredMembers }} نفر</p>
                    </div>
                </div>
                <a href="{{ route('charity.insured-families') }}" class="w-full block text-center py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">مشاهده</a>
            </div>

            <!-- کارت خانواده‌های بدون پوشش -->
            <div class="bg-red-50 p-6 rounded-lg shadow-sm border border-red-100">
                <div class="flex items-center mb-4">
                    <div class="bg-red-500 p-3 rounded-full mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">خانواده‌های بدون پوشش</h3>
                        <p class="text-sm text-gray-600">{{ $uninsuredFamilies }} خانواده - {{ $uninsuredMembers }} نفر</p>
                    </div>
                </div>
                <a href="{{ route('charity.uninsured-families') }}" class="w-full block text-center py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">مشاهده</a>
            </div>

            <!-- کارت افزودن خانواده جدید -->
            <div class="bg-blue-50 p-6 rounded-lg shadow-sm border border-blue-100">
                <div class="flex items-center mb-4">
                    <div class="bg-blue-500 p-3 rounded-full mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">افزودن خانواده جدید</h3>
                        <p class="text-sm text-gray-600">ثبت خانواده نیازمند جدید</p>
                    </div>
                </div>
                <a href="{{ route('charity.add-family') }}" class="w-full block text-center py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">افزودن</a>
            </div>
        </div>

        <!-- آمار تفصیلی -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- کارت افراد تحت پوشش -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="text-gray-600 text-sm font-medium mb-2">افراد تحت پوشش</div>
                        <div class="text-2xl font-bold text-gray-800">{{ number_format($insuredMembers) }}</div>
                        <div class="text-sm text-gray-500 mt-1">نفر</div>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- کارت افراد بدون پوشش -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="text-gray-600 text-sm font-medium mb-2">افراد بدون پوشش</div>
                        <div class="text-2xl font-bold text-gray-800">{{ number_format($uninsuredMembers) }}</div>
                        <div class="text-sm text-gray-500 mt-1">نفر</div>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- کارت خانواده‌های بیمه شده -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="text-gray-600 text-sm font-medium mb-2">خانواده‌های بیمه شده</div>
                        <div class="text-2xl font-bold text-gray-800">{{ number_format($insuredFamilies) }}</div>
                        <div class="text-sm text-gray-500 mt-1">خانواده</div>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- کارت خانواده‌های بدون پوشش -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="text-gray-600 text-sm font-medium mb-2">خانواده‌های بدون پوشش</div>
                        <div class="text-2xl font-bold text-gray-800">{{ number_format($uninsuredFamilies) }}</div>
                        <div class="text-sm text-gray-500 mt-1">خانواده</div>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- نمودارهای جنسیتی و جغرافیایی -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
            <!-- نمودار جنسیتی -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="text-lg font-semibold text-center mb-1">تفکیک جنسیتی</div>
                <div class="text-center text-gray-600 text-sm mb-4">افراد تحت نظارت</div>
                <div class="relative flex flex-col items-center justify-center">
                    <canvas id="genderDonut" width="200" height="200"></canvas>
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-center">
                        <div class="text-gray-500 text-xs">مجموع</div>
                        <div class="text-xl font-bold">{{ number_format($insuredMembers + $uninsuredMembers) }}</div>
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
                    <div class="text-lg font-semibold text-gray-800 mb-1">تفکیک جغرافیایی افراد تحت نظارت</div>
                    <div class="text-gray-600 text-sm">
                        میله‌ها: تعداد افراد در هر استان (تجمیعی زن و مرد)
                        <span class="text-red-500 mr-2">• خط منحنی: افراد محروم</span>
                    </div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <canvas id="geoBarLineChart" height="280"></canvas>
                </div>
            </div>
        </div>

        <!-- نمودار وضعیت پوشش بیمه‌ای -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
            <div class="mb-4">
                <div class="text-lg font-semibold text-gray-800 mb-1">وضعیت پوشش بیمه‌ای خانواده‌ها</div>
                <div class="text-gray-600 text-sm">نمایش نسبت خانواده‌های تحت پوشش و بدون پوشش بیمه</div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- نمودار دونات -->
                <div class="flex flex-col items-center">
                    <div class="relative">
                        <canvas id="insuranceCoverageChart" width="300" height="300"></canvas>
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-center">
                            <div class="text-gray-500 text-xs">کل خانواده‌ها</div>
                            <div class="text-2xl font-bold">{{ number_format($insuredFamilies + $uninsuredFamilies) }}</div>
                            <div class="text-xs text-gray-400">خانواده</div>
                        </div>
                    </div>
                    <div class="flex justify-center gap-6 mt-4 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-4 h-4 rounded-full bg-green-500"></span>
                            <span>تحت پوشش ({{ number_format($insuredFamilies) }})</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-4 h-4 rounded-full bg-red-500"></span>
                            <span>بدون پوشش ({{ number_format($uninsuredFamilies) }})</span>
                        </div>
                    </div>
                </div>

                <!-- آمار تفصیلی -->
                <div class="space-y-4">
                    <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-semibold text-green-800">خانواده‌های تحت پوشش</span>
                            <span class="text-2xl font-bold text-green-600">{{ number_format($insuredFamilies) }}</span>
                        </div>
                        <div class="text-sm text-green-700">
                            {{ $insuredFamilies > 0 ? round(($insuredFamilies / ($insuredFamilies + $uninsuredFamilies)) * 100, 1) : 0 }}% از کل خانواده‌ها
                        </div>
                        <div class="text-xs text-green-600 mt-1">
                            {{ number_format($insuredMembers) }} نفر تحت پوشش
                        </div>
                    </div>

                    <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-semibold text-red-800">خانواده‌های بدون پوشش</span>
                            <span class="text-2xl font-bold text-red-600">{{ number_format($uninsuredFamilies) }}</span>
                        </div>
                        <div class="text-sm text-red-700">
                            {{ $uninsuredFamilies > 0 ? round(($uninsuredFamilies / ($insuredFamilies + $uninsuredFamilies)) * 100, 1) : 0 }}% از کل خانواده‌ها
                        </div>
                        <div class="text-xs text-red-600 mt-1">
                            {{ number_format($uninsuredMembers) }} نفر بدون پوشش
                        </div>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <div class="text-center">
                            <div class="text-sm font-medium text-blue-800 mb-1">نیاز به اقدام فوری</div>
                            <div class="text-lg font-bold text-blue-600">
                                {{ $uninsuredFamilies > 0 ? number_format($uninsuredFamilies) : 'هیچ' }} خانواده
                            </div>
                            <div class="text-xs text-blue-600 mt-1">
                                برای دریافت پوشش بیمه‌ای
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- نمودار معیارهای پذیرش -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
            <div class="mb-6">
                <div class="text-lg font-semibold text-gray-800 mb-2">معیارهای پذیرش افراد تحت نظارت</div>
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
                @if(isset($criteriaData))
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
                @endif
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
                'maleCount' => $maleCount ?? 0,
                'femaleCount' => $femaleCount ?? 0,
                'insuredFamilies' => $insuredFamilies,
                'uninsuredFamilies' => $uninsuredFamilies,
                'insuredMembers' => $insuredMembers,
                'uninsuredMembers' => $uninsuredMembers
            ]) !!}
        </script>
    </div>
