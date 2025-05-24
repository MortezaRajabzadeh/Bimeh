<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8 text-center">
        <h2 class="text-3xl font-bold text-gray-800 mb-4">🗺️ نقشه مناطق محروم کشور</h2>
        <div class="text-gray-600 text-sm max-w-4xl mx-auto leading-7 bg-blue-50 p-6 rounded-xl border-r-4 border-blue-400">
            <p class="mb-3">
                <strong>📍 راهنما:</strong> 
                این صفحه تمام مناطق کشور را نشان می‌دهد. مناطق محروم با رنگ قرمز و مناطق عادی با رنگ سبز مشخص شده‌اند.
            </p>
            <p class="mb-3">
                🎯 <strong>هدف:</strong> خانواده‌های ساکن در مناطق محروم (قرمز) اولویت بالاتری برای دریافت بیمه دارند.
            </p>
        </div>
    </div>

    <!-- فیلترها و جستجو -->
    <div class="mb-8 flex flex-col md:flex-row gap-4 items-center justify-between">
        <!-- جستجوی پیشرفته -->
        <div class="relative w-full md:w-96">
            <div class="relative">
                <input 
                    type="text" 
                    wire:model.live.debounce.200ms="search" 
                    placeholder="جستجوی هوشمند: استان، شهرستان، دهستان..." 
                    class="w-full pr-12 pl-12 py-3 border-2 border-gray-200 rounded-xl text-right focus:border-green-400 focus:ring-4 focus:ring-green-100 transition-all duration-300 bg-white shadow-sm" 
                />
                
                <!-- آیکون جستجو -->
                <div class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400">
                    <div wire:loading.remove wire:target="search">🔍</div>
                    <div wire:loading wire:target="search" class="animate-spin">⚡</div>
                </div>
                
                <!-- دکمه پاک کردن -->
                @if($search)
                    <button 
                        wire:click="clearSearch"
                        class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-red-500 transition-colors duration-200 p-1"
                        title="پاک کردن جستجو"
                    >
                        ❌
                    </button>
                @endif
            </div>
            
            <!-- راهنمای جستجو -->
            @if($search)
                <div class="mt-2 text-xs text-gray-500 bg-yellow-50 border border-yellow-200 rounded-lg p-2">
                    💡 <strong>نکته:</strong> نتایج بر اساس میزان مطابقت مرتب شده‌اند
                </div>
            @endif
        </div>

        <!-- فیلتر محروم فقط -->
        <div class="flex items-center gap-3">
            <button 
                wire:click="toggleFilter"
                class="flex items-center px-4 py-2 rounded-xl transition-all duration-300 {{ $showOnlyDeprived ? 'bg-red-500 text-white shadow-lg' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
            >
                <span class="ml-2">{{ $showOnlyDeprived ? '🔴' : '⚪' }}</span>
                فقط مناطق محروم
            </button>
            
            <!-- دکمه‌های کنترل -->
            @if(!$search)
            <div class="flex items-center gap-2">
                <button 
                    wire:click="expandAll"
                    class="flex items-center px-3 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-all duration-200 text-sm"
                    title="باز کردن همه استان‌ها"
                >
                    <span class="ml-1">📂</span>
                    باز کردن همه
                </button>
                <button 
                    wire:click="collapseAll"
                    class="flex items-center px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all duration-200 text-sm"
                    title="بستن همه استان‌ها"
                >
                    <span class="ml-1">📁</span>
                    بستن همه
                </button>
            </div>
            @endif
            
            <!-- نشان‌دهنده تعداد نتایج -->
            <div class="hidden md:block bg-blue-100 text-blue-700 px-3 py-2 rounded-lg text-sm">
                📄 {{ $provinces->total() }} استان
            </div>
        </div>
    </div>

    <!-- نتایج جستجو -->
    @if($search)
        <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div class="text-green-700">
                    🔍 <strong>نتایج جستجو برای:</strong> "{{ $search }}"
                    <span class="text-sm text-green-600">(مرتب شده بر اساس مطابقت)</span>
                </div>
                <div class="text-sm text-green-600">
                    {{ $provinces->total() }} استان پیدا شد
                </div>
            </div>
        </div>
    @endif



    <!-- آمار -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        @php
            $totalDeprived = 0;
            $totalNonDeprived = 0;
            foreach($provinces as $province) {
                foreach($province->cities as $city) {
                    foreach($city->districts as $district) {
                        if($district->is_deprived) {
                            $totalDeprived++;
                        } else {
                            $totalNonDeprived++;
                        }
                    }
                }
            }
        @endphp
        
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center transform hover:scale-105 transition-transform duration-200">
            <div class="text-2xl font-bold text-green-600">{{ $provinces->total() }}</div>
            <div class="text-sm text-green-700">استان {{ $search ? 'یافت شده' : 'موجود' }}</div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center transform hover:scale-105 transition-transform duration-200">
            <div class="text-2xl font-bold text-red-600">{{ $totalDeprived }}</div>
            <div class="text-sm text-red-700">منطقه محروم</div>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center transform hover:scale-105 transition-transform duration-200">
            <div class="text-2xl font-bold text-blue-600">{{ $totalNonDeprived }}</div>
            <div class="text-sm text-blue-700">منطقه عادی</div>
        </div>
    </div>

        <!-- جدول اصلی با Collapsible -->
    <div class="space-y-4">
        @if($provinces->count() > 0)
            @foreach($provinces as $index => $province)
                @php
                    $isExpanded = isset($expandedProvinces[$province->id]);
                    $totalDeprived = 0;
                    $totalNonDeprived = 0;
                    foreach($province->cities as $city) {
                        $totalDeprived += $city->districts->where('is_deprived', true)->count();
                        $totalNonDeprived += $city->districts->where('is_deprived', false)->count();
                    }
                @endphp
                
                <div class="province-card bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 transition-all duration-300 hover:shadow-xl">
                    <!-- Header استان - کلیک برای باز/بسته کردن -->
                    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white cursor-pointer transition-all duration-300 hover:from-green-600 hover:to-green-700" 
                         wire:click="toggleProvince({{ $province->id }})">
                        <div class="p-6 flex items-center justify-between">
                            <div class="flex items-center">
                                <!-- آیکون Expand/Collapse -->
                                <div class="ml-4 w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center text-lg font-bold transition-transform duration-300 {{ $isExpanded ? 'rotate-90' : '' }}">
                                    {{ $isExpanded ? '−' : '+' }}
                                </div>
                                
                                <!-- شماره ترتیب -->
                                <span class="bg-white bg-opacity-20 px-2 py-1 rounded-full text-sm ml-3">
                                    {{ ($provinces->currentPage() - 1) * $provinces->perPage() + $index + 1 }}
                                </span>
                                
                                <!-- نام استان -->
                                <div class="flex items-center">
                                    🗺️ استان {{ $province->name }}
                                    <span class="mr-4 text-sm bg-white bg-opacity-20 px-3 py-1 rounded-full">
                                        {{ $province->cities->count() }} شهرستان
                                    </span>
                                </div>
                                
                                <!-- نشان‌دهنده امتیاز مطابقت -->
                                @if($search && isset($province->search_score) && $province->search_score > 0)
                                    <div class="mr-3 flex items-center bg-yellow-400 bg-opacity-30 px-3 py-1 rounded-full text-sm">
                                        @if($province->search_score >= 800)
                                            ⭐⭐⭐ بهترین مطابقت
                                        @elseif($province->search_score >= 400)
                                            ⭐⭐ مطابقت خوب
                                        @else
                                            ⭐ مطابقت جزئی
                                        @endif
                                    </div>
                                @endif
                            </div>
                            
                            <div class="flex items-center gap-3">
                                <!-- آمار سریع -->
                                <div class="flex items-center gap-2 text-sm bg-white bg-opacity-20 px-3 py-1 rounded-full">
                                    @if($totalDeprived > 0)
                                        <span class="text-red-200">🔴 {{ $totalDeprived }}</span>
                                    @endif
                                    @if($totalNonDeprived > 0)
                                        <span class="text-green-200">🟢 {{ $totalNonDeprived }}</span>
                                    @endif
                                </div>
                                
                                @if($search)
                                    <div class="text-sm bg-yellow-400 bg-opacity-20 px-3 py-1 rounded-full">
                                        🎯 یافت شده
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- محتوای قابل باز شدن -->
                    @if($isExpanded)
                        <div class="border-t border-gray-200 bg-gray-50 animate-fadeIn">
                            <div class="p-6">
                                @foreach($province->cities as $city)
                                    @if($city->districts->count() > 0)
                                        <div class="mb-6 last:mb-0 bg-white rounded-xl p-4 shadow-sm {{ $search ? 'ring-2 ring-blue-200' : '' }}">
                                            <!-- Header شهرستان -->
                                            <div class="mb-4 flex items-center justify-between border-b border-gray-200 pb-3">
                                                <h4 class="text-lg font-semibold text-blue-700 flex items-center">
                                                    🏙️ شهرستان {{ $city->name }}
                                                    @if($search && (stripos($city->name, $search) !== false))
                                                        <span class="mr-2 text-xs bg-yellow-200 text-yellow-800 px-2 py-1 rounded-full">
                                                            🎯 مطابقت مستقیم
                                                        </span>
                                                    @endif
                                                </h4>
                                                <div class="flex gap-2 text-sm">
                                                    @php
                                                        $cityDeprivedCount = $city->districts->where('is_deprived', true)->count();
                                                        $cityNonDeprivedCount = $city->districts->where('is_deprived', false)->count();
                                                    @endphp
                                                    @if($cityDeprivedCount > 0)
                                                        <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full">
                                                            {{ $cityDeprivedCount }} محروم
                                                        </span>
                                                    @endif
                                                    @if($cityNonDeprivedCount > 0)
                                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full">
                                                            {{ $cityNonDeprivedCount }} عادی
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- دهستان‌ها -->
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                                @foreach($city->districts as $district)
                                                    <div class="flex items-center justify-between p-3 rounded-lg border transition-all duration-200 hover:shadow-md
                                                        {{ $district->is_deprived 
                                                           ? 'bg-red-50 border-red-200 hover:bg-red-100' 
                                                           : 'bg-green-50 border-green-200 hover:bg-green-100' }}
                                                        {{ $search && (stripos($district->name, $search) !== false) ? 'ring-2 ring-yellow-300' : '' }}">
                                                        <span class="font-medium text-gray-800">
                                                            {{ $district->name }}
                                                            @if($search && (stripos($district->name, $search) !== false))
                                                                <span class="text-xs text-yellow-600">🎯</span>
                                                            @endif
                                                        </span>
                                                        <div class="flex items-center">
                                                            @if($district->is_deprived)
                                                                <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                                                                <span class="text-xs text-red-600 font-medium">محروم</span>
                                                            @else
                                                                <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                                                                <span class="text-xs text-green-600 font-medium">عادی</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        @else
            <!-- پیام خالی -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                <div class="text-center py-16">
                    <div class="text-gray-400 text-lg">
                        <div class="mb-4 text-6xl">{{ $search ? '🔍' : '📍' }}</div>
                        <div class="text-2xl font-medium mb-2">
                            {{ $search ? 'نتیجه‌ای یافت نشد' : 'موردی یافت نشد' }}
                        </div>
                        @if($search)
                            <div class="text-sm max-w-md mx-auto">
                                نتیجه‌ای برای "<strong class="text-gray-600">{{ $search }}</strong>" پیدا نشد
                                <br>
                                <button wire:click="clearSearch" class="mt-2 text-blue-500 hover:text-blue-700 underline">
                                    🗑️ پاک کردن جستجو
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Pagination پایین صفحه -->
    @if($provinces->hasPages())
    <div class="mt-8 border-t border-gray-200 pt-6 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-wrap items-center justify-between">
            <!-- تعداد نمایش - سمت راست -->
            <div class="flex items-center order-1 mr-auto">
                <span class="text-sm text-gray-600 ml-2">تعداد نمایش:</span>
                <div class="relative">
                    <select wire:model.live="perPage" class="h-9 w-20 border border-gray-300 rounded-md pr-8 pl-3 py-1 text-sm bg-white shadow-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 transition-colors duration-200 text-center appearance-none" style="-webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: none;">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                    </select>
                    <!-- آیکون dropdown -->
                    <div class="absolute inset-y-0 right-2 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- شماره صفحات - وسط (چپ به راست) -->
            <div class="flex items-center justify-center order-2 flex-grow mx-4" dir="ltr">
                <button type="button" wire:click="{{ !$provinces->onFirstPage() ? 'previousPage' : '' }}" 
                   class="{{ !$provinces->onFirstPage() ? 'text-green-600 hover:bg-green-50 cursor-pointer' : 'text-gray-400 opacity-50 cursor-not-allowed' }} bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm text-lg font-bold">
                    ‹
                </button>
                
                <div class="flex h-9 border border-gray-300 rounded-md overflow-hidden shadow-sm divide-x divide-gray-300 mx-1">
                    @php
                        $start = max($provinces->currentPage() - 2, 1);
                        $end = min($start + 4, $provinces->lastPage());
                        if ($end - $start < 4 && $start > 1) {
                            $start = max(1, $end - 4);
                        }
                    @endphp
                    
                    @if($start > 1)
                        <button type="button" wire:click="gotoPage(1)" class="bg-white text-gray-600 hover:bg-green-50 hover:text-green-700 h-full px-3 inline-flex items-center justify-center text-sm transition-colors duration-200">1</button>
                        @if($start > 2)
                            <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                        @endif
                    @endif
                    
                    @for($i = $start; $i <= $end; $i++)
                        <button type="button" wire:click="gotoPage({{ $i }})" 
                           class="{{ $provinces->currentPage() == $i ? 'bg-green-100 text-green-800 font-medium' : 'bg-white text-gray-600 hover:bg-green-50 hover:text-green-700' }} h-full px-3 inline-flex items-center justify-center text-sm transition-colors duration-200">
                            {{ $i }}
                        </button>
                    @endfor
                    
                    @if($end < $provinces->lastPage())
                        @if($end < $provinces->lastPage() - 1)
                            <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                        @endif
                        <button type="button" wire:click="gotoPage({{ $provinces->lastPage() }})" class="bg-white text-gray-600 hover:bg-green-50 hover:text-green-700 h-full px-3 inline-flex items-center justify-center text-sm transition-colors duration-200">{{ $provinces->lastPage() }}</button>
                    @endif
                </div>
                
                <button type="button" wire:click="{{ $provinces->hasMorePages() ? 'nextPage' : '' }}" 
                   class="{{ $provinces->hasMorePages() ? 'text-green-600 hover:bg-green-50 cursor-pointer' : 'text-gray-400 opacity-50 cursor-not-allowed' }} bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm text-lg font-bold">
                    ›
                </button>
            </div>

            <!-- شمارنده - سمت چپ -->
            <div class="text-sm text-gray-600 order-3 ml-auto">
                نمایش {{ $provinces->firstItem() }} تا {{ $provinces->lastItem() }} از {{ $provinces->total() }} استان
            </div>
        </div>
    </div>
    @endif

    <!-- راهنمای رنگ‌ها -->
    <div class="mt-8 bg-gray-50 rounded-xl p-6">
        <h3 class="font-bold text-gray-700 mb-4 flex items-center">
            📋 راهنمای جستجو و رنگ‌ها
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div class="flex items-center">
                <div class="w-4 h-4 bg-red-500 rounded-full mr-3"></div>
                <span><strong>قرمز:</strong> مناطق محروم</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 bg-green-500 rounded-full mr-3"></div>
                <span><strong>سبز:</strong> مناطق عادی</span>
            </div>
            <div class="flex items-center">
                <span class="text-lg mr-1">🎯</span>
                <span><strong>هدف:</strong> نتیجه جستجو</span>
            </div>
            <div class="flex items-center">
                <span class="text-lg mr-1">⭐</span>
                <span><strong>ستاره:</strong> میزان مطابقت</span>
            </div>
        </div>
        
        <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
            <strong class="text-blue-700">💡 راهنمای استفاده:</strong>
            <ul class="text-blue-600 text-xs mt-2 space-y-1">
                <li>• روی هر استان کلیک کنید تا شهرستان‌ها و دهستان‌ها نمایش داده شوند</li>
                <li>• از دکمه‌های "باز کردن همه" و "بستن همه" برای کنترل سریع استفاده کنید</li>
                <li>• هنگام جستجو، استان‌های مرتبط به صورت خودکار باز می‌شوند</li>
                <li>• آمار سریع هر استان در header آن نمایش داده می‌شود</li>
                <li>• نتایج بر اساس میزان مطابقت مرتب می‌شوند</li>
            </ul>
        </div>
    </div>

    <!-- Floating Pagination برای اسکرول -->
    @if($provinces->hasPages())
    <div id="floating-pagination" class="fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-white rounded-xl shadow-2xl border border-gray-200 p-4 z-50 transition-all duration-300 opacity-0 translate-y-10 pointer-events-none" style="min-width: 500px;">
        <div class="flex flex-wrap items-center justify-between">
            <!-- آیکون صفحه - سمت راست -->
            <div class="flex items-center order-1 mr-auto text-xs text-gray-400">
                📄
            </div>

            <!-- شماره صفحات - وسط (چپ به راست) -->
            <div class="flex items-center justify-center order-2 flex-grow mx-3" dir="ltr">
                <button type="button" wire:click="{{ !$provinces->onFirstPage() ? 'previousPage' : '' }}" 
                   class="{{ !$provinces->onFirstPage() ? 'text-green-600 hover:bg-green-50 cursor-pointer' : 'text-gray-400 opacity-50 cursor-not-allowed' }} bg-white rounded-md h-8 w-8 flex items-center justify-center border border-gray-300 shadow-sm text-sm font-bold">
                    ‹
                </button>
                
                <div class="flex h-8 border border-gray-300 rounded-md overflow-hidden shadow-sm divide-x divide-gray-300 mx-1">
                    @php
                        $start = max($provinces->currentPage() - 1, 1);
                        $end = min($start + 2, $provinces->lastPage());
                        if ($end - $start < 2 && $start > 1) {
                            $start = max(1, $end - 2);
                        }
                    @endphp
                    
                    @if($start > 1)
                        <button type="button" wire:click="gotoPage(1)" class="bg-white text-gray-600 hover:bg-green-50 hover:text-green-700 h-full px-2 inline-flex items-center justify-center text-xs transition-colors duration-200">1</button>
                        @if($start > 2)
                            <span class="bg-white text-gray-600 h-full px-1 inline-flex items-center justify-center text-xs">...</span>
                        @endif
                    @endif
                    
                    @for($i = $start; $i <= $end; $i++)
                        <button type="button" wire:click="gotoPage({{ $i }})" 
                           class="{{ $provinces->currentPage() == $i ? 'bg-green-100 text-green-800 font-medium' : 'bg-white text-gray-600 hover:bg-green-50 hover:text-green-700' }} h-full px-2 inline-flex items-center justify-center text-xs transition-colors duration-200">
                            {{ $i }}
                        </button>
                    @endfor
                    
                    @if($end < $provinces->lastPage())
                        @if($end < $provinces->lastPage() - 1)
                            <span class="bg-white text-gray-600 h-full px-1 inline-flex items-center justify-center text-xs">...</span>
                        @endif
                        <button type="button" wire:click="gotoPage({{ $provinces->lastPage() }})" class="bg-white text-gray-600 hover:bg-green-50 hover:text-green-700 h-full px-2 inline-flex items-center justify-center text-xs transition-colors duration-200">{{ $provinces->lastPage() }}</button>
                    @endif
                </div>
                
                <button type="button" wire:click="{{ $provinces->hasMorePages() ? 'nextPage' : '' }}" 
                   class="{{ $provinces->hasMorePages() ? 'text-green-600 hover:bg-green-50 cursor-pointer' : 'text-gray-400 opacity-50 cursor-not-allowed' }} bg-white rounded-md h-8 w-8 flex items-center justify-center border border-gray-300 shadow-sm text-sm font-bold">
                    ›
                </button>
            </div>

            <!-- شمارنده کوچک - سمت چپ -->
            <div class="text-xs text-gray-500 order-3 ml-auto">
                {{ $provinces->currentPage() }}/{{ $provinces->lastPage() }}
            </div>
        </div>
    </div>
    @endif

    <!-- استایل‌ها و JavaScript -->
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out;
        }
        
        /* انیمیشن hover برای کارت‌های استان */
        .province-card:hover {
            transform: translateY(-2px);
        }
    </style>

    <!-- JavaScript برای نمایش Floating Pagination -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const floatingPagination = document.getElementById('floating-pagination');
            const bottomPagination = document.querySelector('.mt-8.border-t.border-gray-200');
            
            if (!floatingPagination || !bottomPagination) return;
            
            let isFloatingVisible = false;
            let scrollTimeout;
            
            function checkScroll() {
                const bottomPaginationRect = bottomPagination.getBoundingClientRect();
                const windowHeight = window.innerHeight;
                const scrollY = window.scrollY;
                
                // فقط وقتی که اسکرول کرده و pagination پایین از دید خارج شده
                const shouldShow = scrollY > 200 && bottomPaginationRect.top > windowHeight;
                
                if (shouldShow && !isFloatingVisible) {
                    // نمایش floating pagination
                    floatingPagination.classList.remove('opacity-0', 'translate-y-10', 'pointer-events-none');
                    floatingPagination.classList.add('opacity-100', 'translate-y-0', 'pointer-events-auto');
                    isFloatingVisible = true;
                } else if (!shouldShow && isFloatingVisible) {
                    // مخفی کردن floating pagination
                    floatingPagination.classList.add('opacity-0', 'translate-y-10', 'pointer-events-none');
                    floatingPagination.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto');
                    isFloatingVisible = false;
                }
            }
            
            // بررسی هنگام اسکرول با throttle
            window.addEventListener('scroll', function() {
                if (scrollTimeout) {
                    clearTimeout(scrollTimeout);
                }
                scrollTimeout = setTimeout(checkScroll, 10);
            });
            
            // بررسی هنگام تغییر اندازه پنجره
            window.addEventListener('resize', checkScroll);
        });
    </script>
</div> 