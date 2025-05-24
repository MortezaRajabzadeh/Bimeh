@php
    // استفاده از داده‌های واقعی از کامپوننت 
    $geoLabels = $this->provinceNames ?? []; 
    $geoDataMale = $this->provinceMaleCounts ?? [];   
    $geoDataFemale = $this->provinceFemaleCounts ?? [];  
    $geoDataDeprived = $this->provinceDeprivedCounts ?? [];
@endphp

<div>
    <!-- اضافه کردن فیلترهای سراسری در بالای صفحه -->
    <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">فیلترهای داشبورد</h3>
                <p class="text-sm text-gray-600">انتخاب سال و ماه برای نمایش داده‌های مربوطه در تمام نمودارها</p>
            </div>
            
            <div class="flex gap-4">
                <!-- فیلتر سال -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">سال:</label>
                    <div class="relative">
                        <select wire:model.live="selectedYear" class="border border-gray-300 rounded-md pr-8 pl-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white min-w-[120px]" style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
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
                        <select wire:model.live="selectedMonth" class="border border-gray-300 rounded-md pr-8 pl-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white min-w-[140px]" style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
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
            </div>
        </div>
    </div>

    <!-- Top Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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

        <!-- کارت بودجه باقیمانده -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="text-gray-600 text-sm font-medium mb-2">بودجه باقیمانده سازمان</div>
                    <div class="text-2xl font-bold text-gray-800">{{ number_format(($monthlyClaimsData['premiums'] ?? 0) - ($monthlyClaimsData['claims'] ?? 0)) }}</div>
                    <div class="text-sm text-gray-500 mt-1">ریال</div>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
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
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-center">
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
                    <span class="text-red-500 mr-2">• خط منحنی: افراد محروم بیمه‌شده</span>
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
                    <span class="text-red-500">• آمارها مربوط به افراد بیمه‌شده</span><br>
                    میله‌ها: بودجه اختصاص یافته و خسارت پرداختی ماهانه • خط: روند خسارت‌ها
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
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-center">
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
                    <p class="text-sm text-gray-600">نمایش خسارات پرداختی به تفکیک ماه‌ها (+43%) نسبت به سال گذشته</p>
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
                        <span>روند کلی</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 bg-orange-500 rounded"></span>
                        <span>میانگین ماهانه</span>
                    </div>
                </div>
            </div>

            <!-- پنل کنترل و نمودار دونات -->
            <div class="w-full lg:w-80">
                <!-- نمودار نسبت خسارت ماهانه -->
                <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
                    <div class="text-center mb-4">
                        <h4 class="font-semibold text-gray-800 mb-2">نسبت خسارت ماهانه</h4>
                        <p class="text-xs text-gray-600">انتخاب ماه برای نمایش جزئیات</p>
                    </div>

                    <!-- فیلترها -->
                    <div class="space-y-3 mb-4">
                        <!-- فیلتر سال -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">سال:</label>
                            <div class="relative">
                                <select wire:model.live="selectedYear" class="w-full border border-gray-300 rounded-md pr-8 pl-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                    @foreach($jalaliYears as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                    <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- فیلتر ماه -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">ماه:</label>
                            <div class="relative">
                                <select wire:model.live="selectedMonth" class="w-full border border-gray-300 rounded-md pr-8 pl-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                    @foreach($jalaliMonths as $monthNum => $monthName)
                                        <option value="{{ $monthNum }}">{{ $monthName }}</option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                    <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- نمودار دونات کوچک -->
                    <div class="relative mb-4">
                        <canvas id="monthlyClaimsChart" width="200" height="200"></canvas>
                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-center">
                            <div class="text-xs text-gray-500">{{ $jalaliMonths[$selectedMonth] ?? 'ماه' }}</div>
                            <div class="text-sm font-bold">{{ number_format($monthlyClaimsData['total'] ?? 0) }}</div>
                            <div class="text-xs text-gray-400">ریال</div>
                        </div>
                    </div>

                    <!-- آمار کلی -->
                    <div class="bg-gray-50 rounded-lg p-3 text-xs">
                        <div class="space-y-2">
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
                        معیارهایی که به کل خانواده اعمال می‌شوند مانند مادر سرپرست، پدر فوت شده و...
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
            <canvas id="criteriaBarChart" height="400"></canvas>
        </div>
        <div class="mt-4 text-xs text-gray-500 text-center">
            نمودار بالا نشان‌دهنده تعداد افراد تحت پوشش بر اساس هر معیار پذیرش است
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // متغیرهای سراسری برای نگهداری چارت‌ها
        let genderChart, geoChart, financialChart, monthlyChart, criteriaChart, doubleDonutChart;
        
        // تابع برای حفظ تنظیمات پیش‌فرض چارت‌ها
        function getDefaultChartOptions() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 750,
                    easing: 'easeInOutQuart'
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            font: {
                                family: 'IRANSans, Tahoma, Arial, sans-serif',
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#ddd',
                        borderWidth: 1,
                        cornerRadius: 6,
                        displayColors: true
                    }
                }
            };
        }
        
        // تابع برای ایجاد نمودار جنسیتی
        function createGenderChart() {
            const ctx = document.getElementById('genderDonut');
            if (!ctx) return;
            
            if (genderChart) {
                genderChart.destroy();
            }
            
            genderChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['مرد', 'زن'],
                    datasets: [{
                        data: [Number('{{ $maleCount }}'), Number('{{ $femaleCount }}')],
                        backgroundColor: ['#3b82f6', '#10b981'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    ...getDefaultChartOptions(),
                    cutout: '70%',
                    plugins: {
                        ...getDefaultChartOptions().plugins,
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        // تابع برای ایجاد نمودار جغرافیایی
        function createGeoChart() {
            const ctx = document.getElementById('geoBarLineChart');
            if (!ctx) return;
            
            if (geoChart) {
                geoChart.destroy();
            }
            
            // const geoLabels = @json($geoLabels);
            // const geoDataMale = @json($geoDataMale);
            // const geoDataFemale = @json($geoDataFemale);
            // const geoDataDeprived = @json($geoDataDeprived);
            
            geoChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: geoLabels,
                    datasets: [
                        {
                            label: 'مرد',
                            data: geoDataMale,
                            backgroundColor: '#3b82f6',
                            borderRadius: 4,
                            stack: 'combined'
                        },
                        {
                            label: 'زن',
                            data: geoDataFemale,
                            backgroundColor: '#10b981',
                            borderRadius: 4,
                            stack: 'combined'
                        },
                        {
                            label: 'افراد محروم',
                            data: geoDataDeprived,
                            type: 'line',
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            borderWidth: 3,
                            fill: false,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#ef4444',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    ...getDefaultChartOptions(),
                    scales: {
                        x: {
                            stacked: true,
                            title: { display: true, text: 'استان‌ها' }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            title: { display: true, text: 'تعداد افراد' }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            title: { display: true, text: 'افراد محروم' },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        }
        
        // تابع برای ایجاد سایر چارت‌ها
        function createFinancialChart() {
            // ... کد مشابه برای نمودار مالی ...
        }
        
        function createMonthlyChart() {
            // ... کد مشابه برای نمودار ماهانه ...
        }
        
        function createCriteriaChart() {
            // ... کد مشابه برای نمودار معیارها ...
        }
        
        function createDoubleDonutChart() {
            // ... کد مشابه برای نمودار دونات دوگانه ...
        }
        
        // تابع اصلی برای ایجاد تمام چارت‌ها
        function initializeAllCharts() {
            createGenderChart();
            createGeoChart();
            createFinancialChart();
            createMonthlyChart();
            createCriteriaChart();
            createDoubleDonutChart();
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            initializeAllCharts();
        });
        
        // Livewire event listeners
        document.addEventListener('livewire:initialized', function() {
            Livewire.on('refreshAllCharts', function() {
                setTimeout(() => {
                    initializeAllCharts();
                }, 100);
            });
        });
        
        // برای سازگاری با نسخه‌های قدیمی Livewire
        document.addEventListener('livewire:load', function() {
            Livewire.on('refreshAllCharts', function() {
                setTimeout(() => {
                    initializeAllCharts();
                }, 100);
            });
        });
        
        // رفرش چارت‌ها هنگام آپدیت کامپوننت
        document.addEventListener('livewire:updated', function() {
            setTimeout(() => {
                initializeAllCharts();
            }, 50);
        });
    </script>
</div>