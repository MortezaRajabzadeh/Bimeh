@php
    $problemTypeTranslations = [
        'اعتیاد' => 'اعتیاد',
        'بیماری خاص' => 'بیماری خاص',
        'بیماری های خاص' => 'بیماری خاص',
        'از کار افتادگی' => 'از کار افتادگی',
        'بیکاری' => 'بیکاری',
        // برای سازگاری با مقادیر قدیمی
        'addiction' => 'اعتیاد',
        'special_disease' => 'بیماری خاص',
        'work_disability' => 'از کار افتادگی',
        'unemployment' => 'بیکاری',
        'old_age' => 'کهولت سن',
        'disability' => 'معلولیت',
        'single_parent' => 'سرپرست خانوار'
    ];
@endphp

<div x-data="{
        showFilterModal: false,
        showRankModal: @entangle('showRankModal'),
        filters: @entangle('tempFilters'),
        addFilter() {
            if (!this.filters) {
                this.filters = [];
            }
            this.filters.push({
                type: 'status',
                operator: 'equals',
                value: '',
                label: ''
            });
        },
        removeFilter(index) {
            // فراخوانی متد Livewire برای حذف فیلتر
            $wire.removeFilter(index);
        },
        updateFilterLabel(index) {
            if (!this.filters[index]) return;

            let label = '';

            switch(this.filters[index].type) {
                case 'status':
                    label = 'وضعیت';
                    break;
                case 'province':
                    label = 'استان';
                    break;
                case 'city':
                    label = 'شهر';
                    break;
                case 'deprivation_rank':
                    label = 'رتبه';
                    break;
                case 'charity':
                    label = 'خیریه معرف';
                    break;
                case 'members_count':
                    label = 'تعداد اعضا';
                    break;
                case 'created_at':
                    if (this.filters && this.filters.find(f => f.type === 'status' && f.value === 'insured')) {
                        label = 'تاریخ پایان بیمه';
                    } else {
                        label = 'تاریخ عضویت';
                    }
                    break;
            }

            if (this.filters[index].operator === 'equals' || this.filters[index].operator === 'and') label += ' برابر با';
            else if (this.filters[index].operator === 'not_equals') label += ' مخالف';
            else if (this.filters[index].operator === 'greater_than') label += ' بیشتر از';
            else if (this.filters[index].operator === 'less_than') label += ' کمتر از';
            else if (this.filters[index].operator === 'contains') label += ' شامل';
            else if (this.filters[index].operator === 'exists') label += ' باشد';
            else if (this.filters[index].operator === 'not_exists') label += ' نباشد';
            else if (this.filters[index].operator === 'or') label += ' یا';

            this.filters[index].label = label;
        }
    }">
    {{-- Knowing others is intelligence; knowing yourself is true wisdom. --}}


    <!-- نوار جستجو و فیلتر -->
    <div class="mb-8">
        <div class="flex gap-3 items-center">
            <!-- جستجو -->
            <div class="relative flex-grow">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input wire:model.live="search" type="text" placeholder="جستجو در: نام، کد ملی، شناسه خانواده، نسبت، شغل، استان، شهر، خیریه..."
                       class="border border-gray-300 rounded-lg pl-3 pr-10 py-2.5 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
            </div>

            <!-- دکمه فیلتر جدول -->
            <button @click="showFilterModal = true"
                    class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                </svg>
                فیلتر جدول
                @if($this->hasActiveFilters())
                    <span class="mr-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full">
                        {{ $this->getActiveFiltersCount() }}
                    </span>
                @endif
            </button>

            <!-- دکمه تنظیمات رتبه - فقط برای کاربران بیمه -->
            @if(auth()->user()->isInsurance())
                <button wire:click="openRankModal"
                        class="inline-flex items-center px-4 py-2.5 bg-blue-600 border border-blue-600 rounded-lg text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    تنظیمات رتبه
                </button>
            @endif

        </div>

        <!-- نمایش فیلترهای فعال -->
        @if($this->hasActiveFilters())
            <div class="mt-3 flex flex-wrap gap-2">
                @if($status)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        وضعیت: {{ $status === 'insured' ? 'بیمه شده' : 'بدون بیمه' }}
                        <button wire:click="$set('status', '')" class="mr-1 text-blue-600 hover:text-blue-800">×</button>
                    </span>
                @endif

                @if($province)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        استان: {{ $provinces->find($province)->name ?? 'نامشخص' }}
                        <button wire:click="$set('province', '')" class="mr-1 text-green-600 hover:text-green-800">×</button>
                    </span>
                @endif

                @if($city)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        شهر: {{ $cities->find($city)->name ?? 'نامشخص' }}
                        <button wire:click="$set('city', '')" class="mr-1 text-purple-600 hover:text-purple-800">×</button>
                    </span>
                @endif

                @if($deprivation_rank)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                        محرومیت: {{ $deprivation_rank === 'high' ? 'بالا' : ($deprivation_rank === 'medium' ? 'متوسط' : 'پایین') }}
                        <button wire:click="$set('deprivation_rank', '')" class="mr-1 text-orange-600 hover:text-orange-800">×</button>
                    </span>
                @endif

                @if($charity)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-pink-100 text-pink-800">
                        خیریه: {{ $organizations->find($charity)->name ?? 'نامشخص' }}
                        <button wire:click="$set('charity', '')" class="mr-1 text-pink-600 hover:text-pink-800">×</button>
                    </span>
                @endif

                <!-- فیلترهای Modal -->
                @if(!empty($tempFilters))
                    @foreach($tempFilters as $index => $filter)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            @php
                                $displayValue = '';
                                if (!empty($filter['min_members']) && !empty($filter['max_members'])) {
                                    $displayValue = $filter['min_members'] . ' تا ' . $filter['max_members'];
                                } elseif (!empty($filter['min_members'])) {
                                    $displayValue = 'حداقل ' . $filter['min_members'];
                                } elseif (!empty($filter['max_members'])) {
                                    $displayValue = 'حداکثر ' . $filter['max_members'];
                                } else {
                                    $displayValue = $filter['value'] ?? '';
                                }
                            @endphp
                            {{ $filter['label'] ?? '' }} {{ $displayValue }}
                            <button @click="removeFilter({{ $index }})" class="mr-1 text-yellow-600 hover:text-yellow-800">×</button>
                        </span>
                    @endforeach
                @endif

                @if($specific_criteria && isset($availableRankSettings))
                    @php $criteria = $availableRankSettings->find($specific_criteria); @endphp
                    @if($criteria)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            معیار: {{ $criteria->name }}
                            <button wire:click="$set('specific_criteria', '')" class="mr-1 text-indigo-600 hover:text-indigo-800">×</button>
                        </span>
                    @endif
                @endif

                <!-- دکمه پاک کردن همه فیلترها -->
                <button wire:click="clearAllFilters" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200 transition-colors">
                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    پاک کردن همه
                </button>
            </div>
        @endif
    </div>

    <!-- جدول خانواده‌ها -->
    <div class="w-full overflow-hidden shadow-sm border border-gray-200 rounded-lg sticky top-0">
        <!-- عنوان جدول با دکمه دانلود -->
        <div class="flex items-center justify-between p-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">لیست خانواده‌ها</h3>
            @if(isset($families) && $families->count() > 0)
                <button type="button"
                       wire:click="downloadPageExcel"
                       wire:loading.attr="disabled"
                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-600 bg-white border border-green-600 rounded-md hover:bg-green-50 transition disabled:opacity-50 disabled:cursor-not-allowed">

                    <!-- آیکون لودینگ -->
                    <svg wire:loading wire:target="downloadPageExcel" class="animate-spin -ml-1 mr-2 h-4 w-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 0.879 5.824 2.339 8.021l2.66-1.73z"></path>
                    </svg>

                    <!-- آیکون دانلود -->
                    <svg wire:loading.remove wire:target="downloadPageExcel" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>

                    <span>دانلود اکسل</span>
                </button>
            @endif
        </div>

        <div class="w-full overflow-x-auto overflow-y-auto max-h-[70vh]">
            <table class="w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50 text-xs text-gray-700">
                        <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">
                            <button wire:click="sortBy('family_code')" class="flex items-center justify-center w-full">
                                شناسه خانواده
                                @php $sf = $sortField ?? ''; $sd = $sortDirection ?? ''; @endphp
                                @if($sf === 'family_code')
                                    <span class="mr-1 text-[0.5rem]">
                                        @if($sd === 'asc')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </span>
                                @else
                                    <span class="mr-1 text-[0.5rem]">▼</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">
                            <button wire:click="sortBy('province_id')" class="flex items-center justify-center w-full">
                                استان
                                @if($sf === 'province_id')
                                    <span class="mr-1 text-[0.5rem]">
                                        @if($sd === 'asc')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </span>
                                @else
                                    <span class="mr-1 text-[0.5rem]">▼</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">
                            <button wire:click="sortBy('city_id')" class="flex items-center justify-center w-full">
                                شهر
                                @if($sf === 'city_id')
                                    <span class="mr-1 text-[0.5rem]">
                                        @if($sd === 'asc')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </span>
                                @else
                                    <span class="mr-1 text-[0.5rem]">▼</span>
                                @endif
                            </button>
                        </th>

                        <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium min-w-[180px]">
                            <div class="flex items-center justify-center">
                                <span>سرپرست خانوار</span>
                            </div>
                        </th>

                        <!-- تعداد اعضا - برای کاربران خیریه و بیمه -->
                        @if(auth()->user()->isCharity() || auth()->user()->isInsurance())
                        <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium w-24">
                            <div class="flex items-center justify-center space-s-1">
                                <span>تعداد اعضا</span>
                                @if($sf === 'members_count')
                                    <button wire:click="sortBy('members_count')" class="text-green-600">
                                        @if($sd === 'asc')
                                            <i class="fas fa-sort-up"></i>
                                        @else
                                            <i class="fas fa-sort-down"></i>
                                        @endif
                                    </button>
                                @else
                                    <button wire:click="sortBy('members_count')" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-sort"></i>
                                    </button>
                                @endif
                            </div>
                        </th>
                        @endif

                        <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium w-32">
                            <div class="flex items-center justify-center">
                                <span>معیار پذیرش</span>
                            </div>
                        </th>

                        @if($status === 'insured' && auth()->user()->isInsurance())

                        <!-- تعداد بیمه‌ها -->
                        <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium w-24">
                            <div class="flex items-center justify-center">
                                <span>تعداد بیمه‌ها</span>
                            </div>
                        </th>

                        <!-- نوع بیمه -->
                        <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">
                            <div class="flex items-center justify-center">
                                <span>نوع بیمه</span>
                            </div>
                        </th>

                        <!-- تاریخ شروع و پایان بیمه -->
                        <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">
                            <div class="flex items-center justify-center">
                                <span>تاریخ شروع و پایان بیمه</span>
                            </div>
                        </th>

                        <!-- تاریخ عضویت -->
                        <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">
                            <div class="flex items-center justify-center">
                                <span>تاریخ عضویت</span>
                            </div>
                        </th>

                        <!-- پرداخت کننده حق بیمه -->
                        <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">
                            <div class="flex items-center justify-center">
                                <span>پرداخت کننده حق بیمه</span>
                            </div>
                        </th>
                        @elseif($status === 'insured')
                        <!-- نوع بیمه برای کاربران غیر بیمه -->
                        <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">
                            <div class="flex items-center justify-center">
                                <span>نوع بیمه</span>
                            </div>
                        </th>

                        <!-- تاریخ شروع -->
                        <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">
                            <div class="flex items-center justify-center">
                                <span>تاریخ شروع</span>

                        
                            </div>
                        </th>

                        <!-- پرداخت کننده حق بیمه -->
                        <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">
                            <div class="flex items-center justify-center">
                                <span>پرداخت کننده حق بیمه</span>
                            </div>
                        </th>
                        @endif


                        @if(auth()->user()->isActiveAs('admin'))
                            <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">
                                <button wire:click="sortBy('total_paid_premium')" class="flex items-center justify-center w-full">
                                    مجموع حق بیمه پرداختی
                                    @if($sf === 'total_paid_premium')
                                        <span class="mr-1 text-[0.5rem]">
                                            @if($sd === 'asc')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        </span>
                                    @else
                                        <span class="mr-1 text-[0.5rem]">▼</span>
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">
                                <button wire:click="sortBy('total_paid_claims')" class="flex items-center justify-center w-full">
                                    مجموع خسارات پرداخت شده
                                    @if($sf === 'total_paid_claims')
                                        <span class="mr-1 text-[0.5rem]">
                                            @if($sd === 'asc')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        </span>
                                    @else
                                        <span class="mr-1 text-[0.5rem]">▼</span>
                                    @endif
                                </button>
                            </th>
                        @endif
                        @if(!auth()->user()->isActiveAs('admin'))

                        <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">
                            اعتبارسنجی
                        </th>
                        <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">
                            جزئیات
                        </th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse(($families ?? collect([])) as $family)
                    <tr class="{{ $expandedFamily === $family->id ? 'bg-green-200' : 'hover:bg-blue-50' }}" data-family-id="{{ $family->id }}">
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                            <div class="flex items-center justify-center">
                                @if($family->family_code)
                                    <div class="group relative">
                                        <button
                                            type="button"
                                            class="inline-flex items-center px-2 py-1 rounded-md text-xs font-mono bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors cursor-pointer"
                                            onclick="this.classList.toggle('expanded'); const full = this.querySelector('.full-code'); const short = this.querySelector('.short-code'); if (this.classList.contains('expanded')) { full.classList.remove('hidden'); short.classList.add('hidden'); } else { full.classList.add('hidden'); short.classList.remove('hidden'); }"
                                            title="کلیک کنید تا کد کامل نمایش داده شود"
                                        >
                                            <span class="short-code">{{ Str::limit($family->family_code, 8, '...') }}</span>
                                            <span class="full-code hidden">{{ $family->family_code }}</span>
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">بدون شناسه</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                            {{ $family->province->name ?? 'نامشخص' }}
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                            @if($family->city)
                                {{ $family->city->name }}
                                @if($family->district)
                                    <span class="text-gray-500">/ {{ $family->district->name }}</span>
                                @endif
                            @else
                                نامشخص
                            @endif
                        </td>


                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                            @php
                                $head = $family->members?->where('is_head', true)->first();
                            @endphp
                            @if($head)
                                <div class="flex items-center justify-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1 text-blue-600" fill="none" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        {{ $head->first_name }} {{ $head->last_name }}
                                    </span>
                                </div>
                                @if($head->national_code)
                                    <div class="text-center mt-1">
                                        <span class="text-xs text-gray-500">کد ملی: {{ $head->national_code }}</span>
                                    </div>
                                    <div class="text-center mt-1">
                                        <span class="text-xs text-gray-500">نسبت: {{ $head->relationship_fa ?? 'سرپرست' }}</span>
                                    </div>
                                @endif
                            @else
                                <div class="flex items-center justify-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                        ⚠️ بدون سرپرست
                                    </span>
                                </div>
                            @endif
                        </td>

                        <!-- تعداد اعضا - برای کاربران خیریه و بیمه -->
                        @if(auth()->user()->isCharity() || auth()->user()->isInsurance())
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                            {{ $family->members->count() ?? 0 }}
                        </td>
                        @endif

                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                            @php
                                // نمایش معیارهای پذیرش خانواده
                                $acceptanceCriteria = $family->acceptance_criteria ?? [];
                                if (is_string($acceptanceCriteria)) {
                                    $acceptanceCriteria = json_decode($acceptanceCriteria, true) ?? [];
                                }
                            @endphp

                            <div class="flex flex-wrap gap-1 justify-center">
                                @if(!empty($acceptanceCriteria) && is_array($acceptanceCriteria))
                                    @foreach($acceptanceCriteria as $criteria)
                                        <span class="px-2 py-0.5 rounded-md text-xs bg-blue-100 text-blue-800">
                                            {{ $criteria }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-xs bg-gray-100 text-gray-800">
                                        بدون معیار
                                    </span>
                                @endif
                            </div>
                        </td>

                        @if($status === 'insured' && auth()->user()->isInsurance())

                        <!-- تعداد بیمه‌ها -->
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                            @php
                                $insuranceCount = $family->finalInsurances ? $family->finalInsurances->count() : 0;
                            @endphp
                            <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-800 rounded-full text-sm font-bold">
                                {{ $insuranceCount }}
                            </span>
                        </td>

                        <!-- نوع بیمه -->
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                            @php
                                $insuranceTypes = $family->insuranceTypes();
                                $insuranceTypeLabels = [
                                    'health' => 'سلامت',
                                    'life' => 'عمر',
                                    'accident' => 'حوادث',
                                    'disability' => 'معلولیت',
                                    'unemployment' => 'بیکاری',
                                    'old_age' => 'کهولت سن',
                                    'single_parent' => 'سرپرست خانوار'
                                ];
                            @endphp
                            <div class="flex flex-wrap gap-1 justify-center">
                                @if($insuranceTypes->count() > 0)
                                    @foreach($insuranceTypes as $type)
                                        <span class="px-2 py-0.5 rounded-md text-xs bg-blue-100 text-blue-800">
                                            {{ $insuranceTypeLabels[$type] ?? $type }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-xs bg-gray-100 text-gray-800">
                                        -
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- تاریخ شروع و پایان بیمه -->
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                            @php
                                // گرفتن اولین تاریخ شروع و آخرین تاریخ پایان از تمام بیمه‌های نهایی
                                $allInsurances = $family->finalInsurances;
                                $earliestStart = $allInsurances->min('start_date');
                                $latestEnd = $allInsurances->max('end_date');
                            @endphp
                            <div class="flex flex-col items-center gap-1">
                                @if($earliestStart)
                                    <div class="text-green-700 font-medium">
                                        <span class="text-xs text-gray-500">شروع:</span>
                                        @php
                                            try {
                                                echo jdate($earliestStart)->format('Y/m/d');
                                            } catch (\Exception $e) {
                                                echo \Carbon\Carbon::parse($earliestStart)->format('Y/m/d');
                                            }
                                        @endphp
                                    </div>
                                @else
                                    <div class="text-gray-400">-</div>
                                @endif

                                @if($latestEnd)
                                    <div class="text-red-600 font-medium">
                                        <span class="text-xs text-gray-500">پایان:</span>
                                        @php
                                            try {
                                                echo jdate($latestEnd)->format('Y/m/d');
                                            } catch (\Exception $e) {
                                                echo \Carbon\Carbon::parse($latestEnd)->format('Y/m/d');
                                            }
                                        @endphp
                                    </div>
                                @else
                                    <div class="text-gray-400">-</div>
                                @endif
                            </div>
                        </td>

                        <!-- تاریخ عضویت -->
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                            @if($family->created_at)
                                <div class="text-blue-700 font-medium">
                                    @php
                                        try {
                                            echo jdate($family->created_at)->format('Y/m/d');
                                        } catch (\Exception $e) {
                                            echo $family->created_at->format('Y/m/d');
                                        }
                                    @endphp
                                </div>
                            @else
                                -
                            @endif
                        </td>

                        <!-- پرداخت کننده حق بیمه -->
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                            @php
                                $latestInsurance = $family->finalInsurances()->with('shares.fundingSource')->latest('start_date')->first();
                                $fundingSummary = collect([]);
                                if ($latestInsurance && $latestInsurance->shares && $latestInsurance->shares->count() > 0) {
                                    $fundingSummary = $latestInsurance->shares
                                        ->where('funding_source_id', '!=', null)
                                        ->groupBy('funding_source_id')
                                        ->map(function($group) {
                                            return [
                                                'source' => $group->first()->fundingSource,
                                                'percentage' => $group->sum('percentage')
                                            ];
                                        });
                                }
                            @endphp
                            @if($latestInsurance && $fundingSummary->count() > 0)
                                <div class="flex flex-col gap-1 justify-center items-center">
                                    @foreach($fundingSummary as $summary)
                                        <div class="flex items-center gap-2">
                                            {{-- For now, we don't have logo field in funding_sources table --}}
                                            {{-- @if($summary['source'] && $summary['source']->logo)
                                                <img src="{{ asset('storage/' . $summary['source']->logo) }}" 
                                                     alt="{{ $summary['source']->name }}" 
                                                     class="w-6 h-6 rounded-full object-cover">
                                            @endif --}}
                                            <span class="px-2 py-0.5 rounded-md text-xs bg-green-100 text-green-800 font-medium">
                                                {{ $summary['source']->name ?? 'نامشخص' }}
                                            </span>
                                            <span class="px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-800 font-bold">
                                                {{ number_format($summary['percentage'], 1) }}%
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($latestInsurance && $latestInsurance->insurance_payer)
                                <span class="px-2 py-0.5 rounded-md text-xs bg-green-100 text-green-800">
                                    {{ $latestInsurance->insurance_payer }}
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-md text-xs bg-gray-100 text-gray-800">
                                    -
                                </span>
                            @endif
                        </td>
                        @elseif($status === 'insured')
                        <!-- نوع بیمه برای کاربران غیر بیمه -->
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                            @php
                                $insuranceTypes = $family->insuranceTypes();
                                $insuranceTypeLabels = [
                                    'health' => 'سلامت',
                                    'life' => 'عمر',
                                    'accident' => 'حوادث',
                                    'disability' => 'معلولیت',
                                    'unemployment' => 'بیکاری',
                                    'old_age' => 'کهولت سن',
                                    'single_parent' => 'سرپرست خانوار'
                                ];
                            @endphp
                            <div class="flex flex-wrap gap-1 justify-center">
                                @if($insuranceTypes->count() > 0)
                                    @foreach($insuranceTypes as $type)
                                        <span class="px-2 py-0.5 rounded-md text-xs bg-blue-100 text-blue-800">
                                            {{ $insuranceTypeLabels[$type] ?? $type }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-xs bg-gray-100 text-gray-800">
                                        -
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- تاریخ شروع -->
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                            @php
                                $latestInsurance = $family->finalInsurances()->latest('start_date')->first();
                            @endphp
                            @if($latestInsurance && $latestInsurance->start_date)
                                @php
                                    try {
                                        echo jdate($latestInsurance->start_date)->format('Y/m/d');
                                    } catch (\Exception $e) {
                                        echo \Carbon\Carbon::parse($latestInsurance->start_date)->format('Y/m/d');
                                    }
                                @endphp
                            @else
                                -
                            @endif
                        </td>

                        <!-- پرداخت کننده حق بیمه -->
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                            @php
                                $latestInsurance = $family->finalInsurances()->with('shares.fundingSource')->latest('start_date')->first();
                                $fundingSummary = collect([]);
                                if ($latestInsurance && $latestInsurance->shares && $latestInsurance->shares->count() > 0) {
                                    $fundingSummary = $latestInsurance->shares
                                        ->where('funding_source_id', '!=', null)
                                        ->groupBy('funding_source_id')
                                        ->map(function($group) {
                                            return [
                                                'source' => $group->first()->fundingSource,
                                                'percentage' => $group->sum('percentage')
                                            ];
                                        });
                                }
                            @endphp
                            @if($latestInsurance && $fundingSummary->count() > 0)
                                <div class="flex flex-col gap-1 justify-center items-center">
                                    @foreach($fundingSummary as $summary)
                                        <div class="flex items-center gap-2">
                                            {{-- For now, we don't have logo field in funding_sources table --}}
                                            {{-- @if($summary['source'] && $summary['source']->logo)
                                                <img src="{{ asset('storage/' . $summary['source']->logo) }}" 
                                                     alt="{{ $summary['source']->name }}" 
                                                     class="w-6 h-6 rounded-full object-cover">
                                            @endif --}}
                                            <span class="px-2 py-0.5 rounded-md text-xs bg-green-100 text-green-800 font-medium">
                                                {{ $summary['source']->name ?? 'نامشخص' }}
                                            </span>
                                            <span class="px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-800 font-bold">
                                                {{ number_format($summary['percentage'], 1) }}%
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($latestInsurance && $latestInsurance->insurance_payer)
                                <span class="px-2 py-0.5 rounded-md text-xs bg-green-100 text-green-800">
                                    {{ $latestInsurance->insurance_payer }}
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-md text-xs bg-gray-100 text-gray-800">
                                    -
                                </span>
                            @endif
                        </td>
                        @endif


                        @if(auth()->user()->isActiveAs('admin'))
                            <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                                {{ number_format($family->total_paid_premium ?? 0) }} تومان
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                                {{ number_format($family->total_paid_claims ?? 0) }} تومان
                            </td>
                        @endif


                        @if(!auth()->user()->isActiveAs('admin'))
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                            <div class="flex items-center justify-center">
                                <x-family-validation-icons :family="$family" size="sm" />
                            </div>
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                            <div class="flex items-center justify-center">
                                <button wire:click="toggleFamily({{ $family->id }})"
                                        class="inline-flex items-center justify-center w-8 h-8 bg-blue-50 hover:bg-blue-100 text-blue-600 hover:text-blue-800 rounded-lg transition-all duration-200 group toggle-family-btn"
                                        data-family-id="{{ $family->id }}"
                                        title="مشاهده جزئیات">
                                    <svg class="w-5 h-5 transition-transform duration-200 {{ $expandedFamily === $family->id ? 'rotate-180' : '' }} group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                        @endif
                    </tr>

                    @if($expandedFamily === $family->id && !auth()->user()->isActiveAs('admin'))
                    <tr class="bg-green-50">
                        <td colspan="{{ auth()->user()->isActiveAs('admin') ? ($status === 'insured' ? 17 : 14) : ($status === 'insured' ? 20 : 17) }}" class="p-0">
                            <div class="overflow-hidden shadow-inner rounded-lg bg-green-50 p-2">
                                <div class="overflow-x-auto w-full max-h-96 scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100">
                                    <table class="min-w-full table-auto bg-green-50 border border-green-100 rounded-lg family-members-table" wire:key="family-{{ $family->id }}">
                                    <thead>
                                        <tr class="bg-green-100 border-b border-green-200">
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-center sticky left-0 bg-green-100">سرپرست؟</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-center">نسبت</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-center">نام و نام خانوادگی</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-center">کد ملی</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-center">تاریخ تولد</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-center">شغل</th>
                                            @if($status === 'insured')
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-center">نوع بیمه</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-center">پرداخت کننده حق بیمه</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-center">تاریخ شروع بیمه</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-center">تاریخ پایان بیمه</th>
                                            @endif
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-center">معیار پذیرش</th>
                                            @if(!auth()->user()->isActiveAs('admin'))
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-center">اعتبارسنجی</th>
                                            @endif
                                            @if(!$family->verified_at && $status !== 'insured')
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-center">عملیات</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($familyMembers as $member)
                                        <tr class="bg-green-100 border-b border-green-200 hover:bg-green-200" wire:key="member-{{ $member->id }}">
                                            <td class="px-3 py-3 text-sm text-gray-800 text-center sticky left-0 bg-green-100">
                                                @if($family->verified_at || $status === 'insured')
                                                    {{-- خانواده تایید شده یا بیمه شده - فقط نمایش --}}
                                                    @if($member->is_head)
                                                        <span class="text-blue-500 font-bold inline-flex items-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                            سرپرست
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400">-</span>
                                                    @endif
                                                @else
                                                    {{-- خانواده تایید نشده - امکان تغییر سرپرست --}}
                                                    <input
                                                        type="radio"
                                                        name="family_head_{{ $family->id }}"
                                                        value="{{ $member->id }}"
                                                        wire:model="selectedHead"
                                                        {{ $member->is_head ? 'checked' : '' }}
                                                        wire:change="setFamilyHead({{ $family->id }}, {{ $member->id }})"
                                                        class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer hover:scale-110 transition-transform"
                                                        title="{{ $member->is_head ? 'سرپرست فعلی' : 'انتخاب به عنوان سرپرست' }}"
                                                    >
                                                @endif
                                            </td>
                                            {{-- نسبت --}}
                                            <td class="px-3 py-3 text-sm text-gray-800 text-center">
                                                @if($editingMemberId === $member->id)
                                                    <select wire:model="editingMemberData.relationship" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">
                                                        <option value="">انتخاب کنید...</option>
                                                        @foreach($this->getRelationshipOptions() as $value => $label)
                                                            <option value="{{ $value }}">{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    {{ $member->relationship_fa ?? '-' }}
                                                @endif
                                            </td>

                                            {{-- نام و نام خانوادگی --}}
                                            <td class="px-3 py-3 text-sm text-gray-800 text-center">
                                                {{ $member->first_name }} {{ $member->last_name }}
                                            </td>

                                            {{-- کد ملی --}}
                                            <td class="px-3 py-3 text-sm text-gray-800 text-center">{{ $member->national_code ?? '-' }}</td>

                                            {{-- تاریخ تولد --}}
                                            <td class="px-3 py-3 text-sm text-gray-800 text-center">
                                                @if($member->birth_date)
                                                    @php
                                                        try {
                                                            $date = \Carbon\Carbon::parse($member->birth_date)->startOfDay();
                                                            $jalaliDate = jdate($date)->format('Y/m/d');
                                                            // حذف ساعت از انتهای رشته
                                                            $dateOnly = preg_replace('/\s+\d{2}:\d{2}(:\d{2})?$/', '', $jalaliDate);
                                                            echo $dateOnly;
                                                        } catch (\Exception $e) {
                                                            echo \Carbon\Carbon::parse($member->birth_date)->startOfDay()->format('Y/m/d');
                                                        }
                                                    @endphp
                                                @else
                                                    -
                                                @endif
                                            </td>

                                            {{-- شغل --}}
                                            <td class="px-3 py-3 text-sm text-gray-800 text-center">
                                                @if($editingMemberId === $member->id)
                                                    <div class="space-y-1">
                                                        <select wire:model="editingMemberData.occupation" class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">
                                                            <option value="">انتخاب کنید...</option>
                                                            @foreach($this->getOccupationOptions() as $value => $label)
                                                                <option value="{{ $value }}">{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        @if($editingMemberData['occupation'] === 'شاغل')
                                                            <input type="text" wire:model="editingMemberData.job_type"
                                                                   placeholder="نوع شغل را وارد کنید"
                                                                   class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">
                                                        @endif
                                                    </div>
                                                @else
                                                    <div>
                                                        @if($member->occupation === 'شاغل' && !empty($member->job_type))
                                                            {{ $member->job_type }}
                                                        @else
                                                            {{ $member->occupation ?? 'بیکار' }}
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>

                                            @if($status === 'insured')
                                            @php
                                                // گرفتن آخرین بیمه نهایی شده خانواده با مدیریت خطا
                                                try {
                                                    $latestInsurance = $family->finalInsurances->sortByDesc('created_at')->first();

                                                    // لاگ برای ردیابی بیمه خانواده
                                                    if (!$latestInsurance) {
                                                        \Log::info("FamilySearch: No final insurance found for family ID: {$family->id}, Code: {$family->family_code}");
                                                    } else {
                                                        \Log::debug("FamilySearch: Insurance loaded for family ID: {$family->id}, Insurance Type: {$latestInsurance->insurance_type}");
                                                    }
                                                } catch (\Exception $e) {
                                                    \Log::error("FamilySearch: Error loading insurance for family ID: {$family->id}", [
                                                        'error' => $e->getMessage(),
                                                        'family_code' => $family->family_code ?? 'N/A'
                                                    ]);
                                                    $latestInsurance = null;
                                                }
                                            @endphp
                                            {{-- نوع بیمه --}}
                                            <td class="px-3 py-3 text-sm text-gray-800 text-center">
                                                <div>{{ $latestInsurance->insurance_type ?? '-' }}</div>
                                            </td>

                                            {{-- پرداخت کننده حق بیمه --}}
                                            <td class="px-3 py-3 text-sm text-gray-800 text-center">
                                                @php
                                                    $memberLatestInsurance = $family->finalInsurances()->with('shares.fundingSource')->latest('start_date')->first();
                                                    $memberFundingSummary = collect([]);
                                                    if ($memberLatestInsurance && $memberLatestInsurance->shares && $memberLatestInsurance->shares->count() > 0) {
                                                        $memberFundingSummary = $memberLatestInsurance->shares
                                                            ->where('funding_source_id', '!=', null)
                                                            ->groupBy('funding_source_id')
                                                            ->map(function($group) {
                                                                return [
                                                                    'source' => $group->first()->fundingSource,
                                                                    'percentage' => $group->sum('percentage')
                                                                ];
                                                            });
                                                    }
                                                @endphp
                                                @if($memberFundingSummary->count() > 0)
                                                    <div class="flex flex-col gap-1 items-center">
                                                        @foreach($memberFundingSummary as $summary)
                                                            <div class="flex items-center gap-1 text-xs">
                                                                {{-- For now, we don't have logo field in funding_sources table --}}
                                                                {{-- @if($summary['source'] && $summary['source']->logo)
                                                                    <img src="{{ asset('storage/' . $summary['source']->logo) }}" 
                                                                         alt="{{ $summary['source']->name }}" 
                                                                         class="w-4 h-4 rounded-full object-cover">
                                                                @endif --}}
                                                                <span class="font-medium text-green-700">
                                                                    {{ $summary['source']->name ?? 'نامشخص' }}
                                                                </span>
                                                                <span class="text-blue-600 font-bold">
                                                                    {{ number_format($summary['percentage'], 1) }}%
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div>{{ $memberLatestInsurance->fundingSource->name ?? ($memberLatestInsurance->insurance_payer ?? '-') }}</div>
                                                @endif
                                            </td>

                                            {{-- تاریخ شروع بیمه --}}
                                            <td class="px-3 py-3 text-sm text-gray-800 text-center">
                                                @if($latestInsurance && $latestInsurance->start_date)
                                                    @php
                                                        try {
                                                            echo jdate($latestInsurance->start_date)->format('Y/m/d');
                                                        } catch (\Exception $e) {
                                                            echo \Carbon\Carbon::parse($latestInsurance->start_date)->format('Y/m/d');
                                                        }
                                                    @endphp
                                                @else
                                                    -
                                                @endif
                                            </td>

                                            {{-- تاریخ پایان بیمه --}}
                                            <td class="px-3 py-3 text-sm text-gray-800 text-center">
                                                @if($latestInsurance && $latestInsurance->end_date)
                                                    @php
                                                        try {
                                                            echo jdate($latestInsurance->end_date)->format('Y/m/d');
                                                        } catch (\Exception $e) {
                                                            echo \Carbon\Carbon::parse($latestInsurance->end_date)->format('Y/m/d');
                                                        }
                                                    @endphp
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            @endif

                                            {{-- معیار پذیرش --}}
                                            <td class="px-3 py-3 text-sm text-gray-800 text-center">
                                                @if($editingMemberId === $member->id)
                                                    <div class="space-y-1">
                                                        <input type="text" wire:model="editingMemberData.problem_type"
                                                               placeholder="معیارهای پذیرش را وارد کنید (با کاما جدا کنید)"
                                                               class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:border-blue-500 focus:ring-blue-500">
                                                        <div class="text-xs text-gray-500">مثال: بیماری خاص، از کار افتادگی</div>
                                                    </div>
                                                @else
                                                    @php
                                                        // نمایش معیارهای پذیرش خانواده (نه مشکلات فردی عضو)
                                                        $familyAcceptanceCriteria = $family->acceptance_criteria ?? [];
                                                        if (is_string($familyAcceptanceCriteria)) {
                                                            $familyAcceptanceCriteria = json_decode($familyAcceptanceCriteria, true) ?? [];
                                                        }
                                                    @endphp

                                                    <div class="flex flex-wrap gap-1 justify-center">
                                                        @if(!empty($familyAcceptanceCriteria) && is_array($familyAcceptanceCriteria))
                                                            @foreach($familyAcceptanceCriteria as $criteria)
                                                                <span class="px-2 py-0.5 rounded-md text-xs bg-blue-100 text-blue-800">
                                                                    {{ $criteria }}
                                                                </span>
                                                            @endforeach
                                                        @else
                                                            <span class="px-2 py-0.5 rounded-md text-xs bg-gray-100 text-gray-800">
                                                                بدون معیار
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>

                                            @if(!auth()->user()->isActiveAs('admin'))
                                            <td class="px-3 py-3 text-sm text-gray-800 text-center">
                                                @php
                                                    // چک کنیم آیا این عضو نیاز به مدرک دارد
                                                    $needsDocument = isset($member->needs_document) && $member->needs_document;
                                                @endphp

                                                @if($needsDocument)
                                                    <a href="{{ route('charity.family.members.documents.upload', ['family' => $family->id, 'member' => $member->id]) }}"
                                                       class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full hover:bg-yellow-200 transition-colors">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                        آپلود مدرک
                                                    </a>
                                                @else
                                                    <x-member-validation-icons :member="$member" size="sm" />
                                                @endif
                                            </td>
                                            @endif

                                            {{-- عملیات - فقط برای خانواده‌های تأیید نشده و غیر بیمه --}}
                                            @if(!$family->verified_at && $status !== 'insured')
                                            <td class="px-3 py-3 text-sm text-gray-800 text-center">
                                                @if($editingMemberId === $member->id)
                                                    {{-- دکمه‌های ذخیره و لغو --}}
                                                    <div class="flex items-center justify-center space-x-1 space-x-reverse">
                                                        <button wire:click="saveMember"
                                                                class="inline-flex items-center px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                            ذخیره
                                                        </button>
                                                        <button wire:click="cancelMemberEdit"
                                                                class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                            لغو
                                                        </button>
                                                    </div>
                                                @else
                                                    {{-- دکمه ویرایش --}}
                                                    <button wire:click="editMember({{ $member->id }})"
                                                            class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3a1 1 0 100 2h2a1 1 0 100-2h-2zM11 21a1 1 0 100-2h2a1 1 0 100 2h-2zM12 8V4m0 16v-4m9-8h-4m-14 0H4" />
                                                        </svg>
                                                        ویرایش
                                                    </button>
                                                @endif
                                            </td>
                                            @endif
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="{{ (!$family->verified_at && $status !== 'insured') ? (auth()->user()->isActiveAs('admin') ? 8 : 9) : (auth()->user()->isActiveAs('admin') ? 7 : 8) }}" class="px-3 py-3 text-sm text-gray-500 text-center border-b border-gray-100">
                                                عضوی برای این خانواده ثبت نشده است.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                <div class="bg-green-100 py-4 px-4 rounded-b border-r border-l border-b border-green-100 flex flex-wrap justify-between items-center gap-4">
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 ml-2">شماره موبایل سرپرست:</span>
                                        <div class="bg-white rounded px-3 py-2 flex items-center">
                                            @php
                                                $headMember = $family->head()->first();
                                                $headMobile = $headMember?->mobile;
                                            @endphp
                                            <span class="text-sm text-gray-800">{{ $headMobile ?: 'بدون شماره' }}</span>
                                            @if($headMobile)
                                                <button type="button" wire:click="copyText('{{ $headMobile }}')" class="text-blue-500 mr-2 cursor-pointer">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 ml-2">شماره شبا جهت پرداخت خسارت:</span>
                                        <div class="bg-white rounded px-3 py-2 flex items-center">
                                            @php
                                                $headSheba = $headMember?->sheba;
                                            @endphp
                                            <span class="text-sm text-gray-800 ltr">{{ $headSheba ?: 'بدون شبا' }}</span>
                                            @if($headSheba)
                                                <button type="button" wire:click="copyText('{{ $headSheba }}')" class="text-blue-500 mr-2 cursor-pointer">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endif

                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isActiveAs('admin') ? 11 : 14 }}" class="px-5 py-4 text-sm text-gray-500 border-b border-gray-200 text-center">
                            هیچ خانواده‌ای یافت نشد.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- اعلان کپی -->
    <div id="copy-notification" class="hidden fixed top-4 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-md shadow-lg z-50 flex items-center">
        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span id="copy-notification-text">متن با موفقیت کپی شد</span>
    </div>

    <!-- پیجینیشن -->
    @if(($families ?? null) && ($families->hasPages() ?? false))
    <div class="mt-6 border-t border-gray-200 pt-4" id="pagination-section">
        <div class="flex flex-wrap items-center justify-between">
            <!-- تعداد نمایش - سمت راست -->
            <div class="flex items-center order-1">
                <span class="text-sm text-gray-600 ml-2">تعداد نمایش:</span>
                <select wire:model.live="perPage"
                        class="h-9 w-16 border border-gray-300 rounded-md px-2 py-1 text-sm bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors"
                        style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>

            <!-- شماره صفحات - وسط -->
            <div class="flex items-center justify-center order-2 flex-grow mx-4">
                <!-- دکمه صفحه قبل -->
                <button type="button" wire:click="previousPage" wire:loading.attr="disabled" wire:target="previousPage" @if($families->onFirstPage()) disabled @endif class="{{ !$families->onFirstPage() ? 'text-green-600 hover:bg-green-50 cursor-pointer' : 'text-gray-400 opacity-50 cursor-not-allowed' }} bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm mr-1 transition-colors duration-200">

                    <!-- آیکون لودینگ -->
                    <svg wire:loading wire:target="previousPage" class="animate-spin -ml-1 mr-2 h-4 w-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 0.879 5.824 2.339 8.021l2.66-1.73z"></path>
                    </svg>

                    <!-- آیکون -->
                    <svg wire:loading.remove wire:target="previousPage" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L10.586 10 7.293 6.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>

                <!-- شماره صفحات -->
                <div class="flex h-9 border border-gray-300 rounded-md overflow-hidden shadow-sm divide-x divide-gray-300">
                    @php
                        $start = isset($families) ? max($families->currentPage() - 2, 1) : 1;
                        $end = isset($families) ? min($start + 4, $families->lastPage()) : 1;
                        if (isset($families) && $end - $start < 4 && $start > 1) {
                            $start = max(1, $end - 4);
                        }
                    @endphp

                    @if(isset($families) && $start > 1)
                        <button type="button" wire:click="gotoPage(1)" class="bg-white text-gray-600 hover:bg-gray-50 h-full px-3 inline-flex items-center justify-center text-sm">1</button>
                        @if(isset($families) && $start > 2)
                            <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                        @endif
                    @endif

                    @for($i = $start; $i <= $end; $i++)
                        <button type="button"
                                wire:click="gotoPage({{ $i }})"
                                wire:key="page-{{ $i }}"
                                wire:loading.attr="disabled"
                                wire:target="gotoPage"
                                class="{{ ($families->currentPage() == $i) ? 'bg-green-100 text-green-800 font-medium' : 'bg-white text-gray-600 hover:bg-gray-50' }} h-full px-3 inline-flex items-center justify-center text-sm transition-colors duration-200">
                            <span wire:loading.remove wire:target="gotoPage">{{ $i }}</span>
                            <span wire:loading wire:target="gotoPage" class="inline-block">
                                <svg class="animate-spin h-4 w-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 0.879 5.824 2.339 8.021l2.66-1.73z"></path>
                                </svg>
                            </span>
                        </button>
                    @endfor

                    @if(isset($families) && $end < $families->lastPage())
                        @if(isset($families) && $end < $families->lastPage() - 1)
                            <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                        @endif
                        <button type="button" wire:click="gotoPage({{ isset($families) ? $families->lastPage() : 1 }})" class="bg-white text-gray-600 hover:bg-gray-50 h-full px-3 inline-flex items-center justify-center text-sm">{{ isset($families) ? $families->lastPage() : 1 }}</button>
                    @endif
                </div>

                <!-- دکمه صفحه بعد -->
                <button type="button" wire:click="nextPage" wire:loading.attr="disabled" wire:target="nextPage" @if(!$families->hasMorePages()) disabled @endif class="{{ $families->hasMorePages() ? 'text-green-600 hover:bg-green-50 cursor-pointer' : 'text-gray-400 opacity-50 cursor-not-allowed' }} bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm ml-1 transition-colors duration-200">
                    <svg wire:loading.remove wire:target="nextPage" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span wire:loading wire:target="nextPage" class="inline-block">
                        <svg class="animate-spin h-4 w-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 0.879 5.824 2.339 8.021l2.66-1.73z"></path>
                        </svg>
                    </span>
                </button>
            </div>

            <!-- شمارنده - سمت چپ -->
            <div class="text-sm text-gray-600 order-3">
                نمایش {{ $families->firstItem() ?? 0 }} تا {{ $families->lastItem() ?? 0 }} از {{ $families->total() ?? 0 }} خانواده
            </div>
        </div>
    </div>
    @endif

    <!-- اعلان toast -->
    <div id="toast-notification" class="hidden fixed top-4 left-1/2 transform -translate-x-1/2 px-6 py-3 rounded-md shadow-lg z-50 flex items-center">
        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span id="toast-notification-text"></span>
    </div>

    <script>
    document.addEventListener('livewire:initialized', function () {
        let notificationTimeout = null;

        // تابع اسکرول به محتوای باز شده
        function scrollToExpandedContent(familyId, delay = 300) {
            setTimeout(() => {
                const familyRow = document.querySelector(`tr[data-family-id="${familyId}"]`);
                const expandedContent = document.querySelector(`tr[data-family-id="${familyId}"] + tr`);

                if (expandedContent && familyRow) {
                    const rect = expandedContent.getBoundingClientRect();
                    const isInViewport = (
                        rect.top >= 0 &&
                        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight)
                    );

                    if (!isInViewport) {
                        familyRow.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            }, delay);
        }

        // مدیریت کلیک روی دکمه‌های توگل
        document.addEventListener('click', function(e) {
            const toggleBtn = e.target.closest('.toggle-family-btn');
            if (toggleBtn) {
                const familyId = toggleBtn.getAttribute('data-family-id');
                if (familyId) {
                    scrollToExpandedContent(familyId, 500);
                }
            }
        });

        // نمایش toast notification
        Livewire.on('notify', params => {
            const toast = document.getElementById('toast-notification');
            const toastText = document.getElementById('toast-notification-text');

            if (!toast || !toastText) return;

            // تعیین نوع آیکون بر اساس نوع اعلان
            let iconSvg = '';
            if (params.type === 'success') {
                iconSvg = '<svg class="w-6 h-6 ml-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>';
            } else if (params.type === 'error') {
                iconSvg = '<svg class="w-6 h-6 ml-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>';
            } else if (params.type === 'info') {
                iconSvg = '<svg class="w-6 h-6 ml-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
            } else {
                iconSvg = '<svg class="w-6 h-6 ml-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
            }

            // قرار دادن آیکون و متن
            toastText.innerHTML = `${iconSvg}<span class="mr-1">${params.message}</span>`;

            // تنظیم کلاس‌ها و استایل‌ها
            toast.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 flex items-center p-4 rounded-lg shadow-xl z-50 min-w-[300px] max-w-[600px] animate-fade-in';

            // رنگ‌بندی متناسب با نوع پیام
            if (params.type === 'success') {
                toast.classList.add('bg-green-600', 'text-white', 'border-r-4', 'border-green-800');
            } else if (params.type === 'error') {
                toast.classList.add('bg-red-600', 'text-white', 'border-r-4', 'border-red-800');
            } else if (params.type === 'info') {
                toast.classList.add('bg-blue-600', 'text-white', 'border-r-4', 'border-blue-800');
            } else {
                toast.classList.add('bg-gray-700', 'text-white', 'border-r-4', 'border-gray-900');
            }

            clearTimeout(notificationTimeout);

            toast.classList.remove('hidden');

            // اضافه کردن دکمه بستن
            const closeButton = document.createElement('button');
            closeButton.className = 'ml-2 text-white hover:text-gray-200 focus:outline-none transition-colors duration-200';
            closeButton.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>';
            closeButton.addEventListener('click', () => {
                toast.classList.add('hidden');
            });

            // حذف دکمه بستن قبلی اگر وجود داشته باشد
            const existingCloseButton = toast.querySelector('button');
            if (existingCloseButton) {
                toast.removeChild(existingCloseButton);
            }

            toast.appendChild(closeButton);

            // نمایش اعلان برای 8 ثانیه (زمان طولانی‌تر برای خواندن پیام‌های مهم)
            notificationTimeout = setTimeout(() => {
                toast.classList.add('hidden');
            }, 8000);
        });

        // همچنین برای سازگاری با سیستم قبلی
        Livewire.on('show-toast', params => {
            Livewire.dispatch('notify', params);
        });

        // اسکرول به خانواده باز شده
        Livewire.on('family-expanded', familyId => {
            scrollToExpandedContent(familyId);
        });

        // کپی متن
        Livewire.on('copy-text', params => {
            const text = typeof params === 'object' ? (params.text || String(params)) : String(params);

            if (navigator.clipboard) {
                navigator.clipboard.writeText(text)
                    .then(() => showCopyNotification(text))
                    .catch(() => fallbackCopyTextToClipboard(text));
            } else {
                fallbackCopyTextToClipboard(text);
            }
        });

        function fallbackCopyTextToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.cssText = 'position:fixed;opacity:0';
            document.body.appendChild(textarea);

            textarea.focus();
            textarea.select();

            try {
                if (document.execCommand('copy')) {
                    showCopyNotification(text);
                }
            } catch (err) {}

            document.body.removeChild(textarea);
        }

        function showCopyNotification(text) {
            const notification = document.getElementById('copy-notification');
            const notificationText = document.getElementById('copy-notification-text');

            if (!notification || !notificationText) return;

            notificationText.textContent = 'متن با موفقیت کپی شد: ' + text;

            clearTimeout(notificationTimeout);

            notification.classList.remove('hidden');

            // نمایش اعلان برای 3 ثانیه
            notificationTimeout = setTimeout(() => {
                notification.classList.add('hidden');
            }, 3000);
        }
    });
    </script>

    <!-- مودال آپلود اکسل خانواده‌ها -->
    <div id="uploadModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 hidden" onclick="closeUploadModalOnBackdrop(event)">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative" onclick="event.stopPropagation()">
                <!-- هدر مودال -->
                <div class="border-b border-gray-200 p-6 text-center relative">
                    <button type="button" onclick="closeUploadModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">وارد کردن با فایل اکسل</h3>
                    <p class="text-sm text-gray-600">برای وارد کردن اطلاعات خانواده‌ها به صورت دسته جمعی، ابتدا فایل نمونه را طبق فایل نمونه آماده کرده و آن را آپلود نمایید.</p>
                </div>

                <!-- محتوای مودال -->
                <div class="p-6">
                    <!-- منطقه Drag & Drop -->
                    <div id="dropZone" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center mb-6 hover:border-green-400 transition-colors cursor-pointer">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <p id="dropZoneText" class="text-gray-600 mb-2 font-medium">فایل آماده شده را در اینجا قرار دهید</p>
                        <p class="text-xs text-gray-500">یا برای انتخاب فایل کلیک کنید</p>
                        <input type="file" id="excelFile" accept=".xlsx,.xls,.csv" class="hidden">
                    </div>

                    <!-- دکمه‌های عملیات -->
                    <div class="flex gap-3">
                        <button type="button" onclick="downloadTemplate()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 px-4 rounded-lg text-sm font-medium transition-colors flex items-center justify-center">
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            دانلود فایل نمونه
                        </button>

                        <button type="button" onclick="uploadFile()" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg text-sm font-medium transition-colors flex items-center justify-center">
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            آپلود فایل
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- فرم مخفی برای آپلود -->
    <form id="uploadForm" action="{{ route('charity.import.store') }}" method="POST" enctype="multipart/form-data" class="hidden">
        @csrf
        <input type="hidden" name="import_type" value="families">
        <input type="hidden" name="district_id" id="districtSelect" value="1">
        <input type="file" name="file" id="hiddenFileInput">
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('excelFile');
        const dropZoneText = document.getElementById('dropZoneText');

        if (!dropZone || !fileInput) {
            return;
        }

        // کلیک برای انتخاب فایل
        dropZone.addEventListener('click', function() {
            fileInput.click();
        });

        // تغییر فایل انتخاب شده
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const fileName = this.files[0].name;
                if (dropZoneText) {
                    dropZoneText.textContent = fileName;
                }
                dropZone.classList.add('border-green-400', 'bg-green-50');
            }
        });

        // Drag & Drop events
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('border-green-400', 'bg-green-50');
        });

        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('border-green-400', 'bg-green-50');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('border-green-400', 'bg-green-50');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const fileName = files[0].name;
                fileInput.files = files;
                if (dropZoneText) {
                    dropZoneText.textContent = fileName;
                }
                this.classList.add('border-green-400', 'bg-green-50');
            }
        });

        // بستن مودال با کلید ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('uploadModal');
                if (modal && !modal.classList.contains('hidden')) {
                    closeUploadModal();
                }
            }
        });
    });
    </script>
        <script>
        // باز کردن مودال
        function openUploadModal() {
            const modal = document.getElementById('uploadModal');
            if (modal) {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                // حذف این خط تا فایل انتخاب شده پاک نشود
                // resetModalContent();
            }
        }

        // بستن مودال
        function closeUploadModal() {
            const modal = document.getElementById('uploadModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                // ریست کردن محتوای مودال فقط هنگام بستن
                resetModalContent();
            }
        }

        // بستن مودال با کلیک روی پس‌زمینه
        function closeUploadModalOnBackdrop(event) {
            if (event.target === event.currentTarget) {
                closeUploadModal();
            }
        }

        // ریست کردن محتوای مودال
        function resetModalContent() {
            const fileInput = document.getElementById('excelFile');
            const dropZoneText = document.getElementById('dropZoneText');
            const dropZone = document.getElementById('dropZone');

            if (fileInput) {
                fileInput.value = '';
            }
            if (dropZoneText) {
                dropZoneText.textContent = 'فایل آماده شده را در اینجا قرار دهید';
            }
            if (dropZone) {
                dropZone.classList.remove('border-green-400', 'bg-green-50');
            }
        }

        // دانلود فایل نمونه
        function downloadTemplate() {
            // تست Ajax برای نمایش خطای دقیق
            fetch('{{ route("charity.import.template.families") }}', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(response => {
                if (response.ok) {
                    // اگر موفقیت‌آمیز بود، دانلود فایل
                    window.open('{{ route("charity.import.template.families") }}', '_blank');
                } else {
                    // نمایش خطا
                    response.text().then(text => {
                        console.error('خطا:', response.status, text);
                        if (response.status === 401) {
                            alert('ابتدا وارد سیستم شوید.');
                        } else if (response.status === 403) {
                            alert('شما مجوز دانلود فایل نمونه را ندارید.');
                        } else {
                            alert('خطا در دانلود فایل: ' + response.status);
                        }
                    });
                }
            })
            .catch(error => {
                console.error('خطا در درخواست:', error);
                alert('خطا در ارتباط با سرور.');
            });
        }

        // آپلود فایل
        function uploadFile() {
            const fileInput = document.getElementById('excelFile');
            const hiddenInput = document.getElementById('hiddenFileInput');
            const uploadButton = document.querySelector('button[onclick="uploadFile()"]');

            if (!fileInput || fileInput.files.length === 0) {
                alert('لطفا ابتدا فایل را انتخاب کنید.');
                return;
            }

            if (!hiddenInput) {
                alert('خطا در سیستم. لطفا صفحه را بازخوانی کنید.');
                return;
            }

            try {
                // نمایش loading state
                if (uploadButton) {
                    uploadButton.disabled = true;
                    uploadButton.innerHTML = `
                        <svg class="animate-spin w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        در حال آپلود...
                    `;
                }

                // کپی فایل انتخاب شده به فرم مخفی
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(fileInput.files[0]);
                hiddenInput.files = dataTransfer.files;

                // ارسال فرم
                const form = document.getElementById('uploadForm');
                if (form) {
                    form.submit();
                } else {
                    alert('خطا در سیستم. لطفا صفحه را بازخوانی کنید.');
                    // بازگشت به حالت عادی
                    if (uploadButton) {
                        uploadButton.disabled = false;
                        uploadButton.innerHTML = `
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            آپلود فایل
                        `;
                    }
                }
            } catch (error) {
                console.error('Error uploading file:', error);
                alert('خطا در آپلود فایل. لطفا مجدد تلاش کنید.');

                // بازگشت به حالت عادی
                if (uploadButton) {
                    uploadButton.disabled = false;
                    uploadButton.innerHTML = `
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        آپلود فایل
                    `;
                }
            }
        }

        // Event listeners برای drag & drop
        document.addEventListener('DOMContentLoaded', function() {
            const dropZone = document.getElementById('dropZone');
            const fileInput = document.getElementById('excelFile');
            const dropZoneText = document.getElementById('dropZoneText');

            if (!dropZone || !fileInput) {
                return;
            }

            // کلیک برای انتخاب فایل
            dropZone.addEventListener('click', function() {
                fileInput.click();
            });

            // تغییر فایل انتخاب شده
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const fileName = this.files[0].name;
                    if (dropZoneText) {
                        dropZoneText.textContent = fileName;
                    }
                    dropZone.classList.add('border-green-400', 'bg-green-50');
                }
            });

            // Drag & Drop events
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('border-green-400', 'bg-green-50');
            });

            dropZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('border-green-400', 'bg-green-50');
            });

            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('border-green-400', 'bg-green-50');

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    const fileName = files[0].name;
                    fileInput.files = files;
                    if (dropZoneText) {
                        dropZoneText.textContent = fileName;
                    }
                    this.classList.add('border-green-400', 'bg-green-50');
                }
            });

            // بستن مودال با کلید ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('uploadModal');
                    if (modal && !modal.classList.contains('hidden')) {
                        closeUploadModal();
                    }
                }
            });
        });
    </script>

    <!-- کامپوننت تنظیمات رتبه -->
    <x-rank-settings-modal
        :showModal="'showRankModal'"
        :availableRankSettings="$availableRankSettings ?? null"
        :isInsuranceUser="auth()->user()->isInsurance()"
    />


    <!-- مودال فیلتر با قابلیت ذخیره و بارگذاری -->
    <x-filter-modal
        :showModal="'showFilterModal'"
        :provinces="$provinces ?? null"
        :cities="$cities ?? null"
        :organizations="auth()->user()->isInsurance() ? \App\Models\Organization::all() : null"
    />

</div>
