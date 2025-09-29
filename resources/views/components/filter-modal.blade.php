@props([
    'showModal' => false,
    'availableCriteria' => null,
    'provinces' => null,
    'cities' => null,
    'organizations' => null,
    'showSpecialCriteria' => false,
    'availableRankSettings' => null
])

<div x-show="{{ $showModal }}"
    @keydown.escape.window="{{ $showModal }} = false"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4"
    style="display: none;">

    <div @click.away="
        // بررسی اینکه آیا کلیک روی JalaliDatePicker بوده یا نه
        const target = $event.target;
        const isDatePickerClick = target.closest('.jdp-container') || 
                                target.closest('[class*=\"jdp-\"]') ||
                                target.classList.contains('jdp-day') ||
                                target.classList.contains('jdp-month') ||
                                target.classList.contains('jdp-year') ||
                                target.classList.contains('jdp-btn-today') ||
                                target.classList.contains('jdp-btn-empty') ||
                                target.hasAttribute('data-jdp');
        
        if (!isDatePickerClick) {
            {{ $showModal }} = false;
        }
    "
         x-transition:enter="transition-all ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition-all ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden transform"
         style="isolation: isolate; will-change: transform, opacity;">

        <!-- هدر مودال -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">فیلتر جدول</h3>
                    <p class="text-sm text-gray-600">لطفاً فیلترهای مدنظر خود را اعمال کنید.</p>
                </div>
            </div>
            <button @click="{{ $showModal }} = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- محتوای مودال -->
        <div class="p-6 overflow-y-auto max-h-[70vh]">
            <!-- جدول فیلترها -->
            <div class="overflow-x-auto bg-white rounded-lg border border-gray-200">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100 text-sm text-gray-700">
                            <th class="px-6 py-4 text-right border-b border-gray-200 font-semibold min-w-[140px]">نوع فیلتر</th>
                            <th class="px-6 py-4 text-right border-b border-gray-200 font-semibold min-w-[200px]">جزئیات فیلتر</th>
                            <th class="px-6 py-4 text-right border-b border-gray-200 font-semibold min-w-[120px]">شرط</th>
                            <th class="px-6 py-4 text-center border-b border-gray-200 font-semibold w-20">حذف</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(filter, index) in filters" :key="index">
                            <tr class="hover:bg-blue-50 transition-colors duration-200" x-init="$nextTick(() => { if (!filter.type) { filter.type = 'province'; } if (!filter.operator) { filter.operator = 'and'; } setTimeout(() => updateFilterLabel(index), 50); });">
                                <!-- نوع فیلتر -->
                                <td class="px-6 py-5">
                                    <div class="relative">
                                        <select x-model="filter.type" @change="updateFilterLabel(index)"
                                                class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white py-2 pr-4 pl-8 appearance-none cursor-pointer transition-all duration-200"
                                                style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: url('data:image/svg+xml;charset=utf8,%3Csvg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"2\"%3E%3Cpath stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M19 9l-7 7-7-7\" /%3E%3C/svg%3E') !important; background-repeat: no-repeat !important; background-position: left 8px center !important; background-size: 16px !important;">
                                            <option value="province">استان</option>
                                            <option value="city">شهر</option>
                                            @if($organizations)
                                                <option value="charity">خیریه معرف</option>
                                            @endif
                                            <option value="members_count">تعداد اعضا</option>
                                            <option value="special_disease">معیار پذیرش</option>
                                            <option value="membership_date">تاریخ عضویت</option>
                                        </select>
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </td>

                                <!-- جزئیات فیلتر -->
                                <td class="px-6 py-5">
                                    <div x-show="filter.type === 'province'" class="relative">
                                        <select x-model="filter.value"
                                                class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 appearance-none cursor-pointer transition-all duration-200"
                                                style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                            <option value="">انتخاب استان...</option>
                                            @if($provinces)
                                                @foreach($provinces as $province)
                                                    <option value="{{ $province->id }}">{{ $province->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                    </div>

                                    <div x-show="filter.type === 'city'" class="relative">
                                        <select x-model="filter.value"
                                                class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 appearance-none cursor-pointer transition-all duration-200"
                                                style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                            <option value="">انتخاب شهر...</option>
                                            @if($cities)
                                                @foreach($cities as $city)
                                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                    </div>

                                    @if($organizations)
                                    <div x-show="filter.type === 'charity'" class="relative">
                                        <select x-model="filter.value"
                                                class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 appearance-none cursor-pointer transition-all duration-200"
                                                style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                            <option value="">انتخاب خیریه...</option>
                                            @foreach($organizations as $org)
                                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    @endif

                                    <!-- Special Disease Filter - Multi-select with checkboxes -->
                                    <div x-show="filter.type === 'special_disease'" x-data="{
                                        showDropdown: false,
                                        availableOptions: [
                                            'بیماری های خاص',
                                            'اعتیاد',
                                            'از کار افتادگی',
                                            'بیکاری'
                                        ],
                                        selectedOptions: [],
                                        
                                        init() {
                                            // مقداردهی اولیه از filter.value
                                            if (filter.value && typeof filter.value === 'string') {
                                                this.selectedOptions = filter.value.split(',').filter(v => v.trim());
                                            } else if (Array.isArray(filter.value)) {
                                                this.selectedOptions = [...filter.value];
                                            }
                                        },
                                        
                                        toggleOption(option) {
                                            const index = this.selectedOptions.indexOf(option);
                                            if (index > -1) {
                                                this.selectedOptions.splice(index, 1);
                                            } else {
                                                this.selectedOptions.push(option);
                                            }
                                            // بروزرسانی filter.value
                                            filter.value = this.selectedOptions.join(',');
                                        },
                                        
                                        isSelected(option) {
                                            return this.selectedOptions.includes(option);
                                        },
                                        
                                        getDisplayText() {
                                            if (this.selectedOptions.length === 0) {
                                                return 'انتخاب معیار پذیرش...';
                                            } else if (this.selectedOptions.length === 1) {
                                                return this.selectedOptions[0];
                                            } else {
                                                return this.selectedOptions.length + ' معیار انتخاب شده';
                                            }
                                        }
                                    }" class="relative">
                                        <!-- باکس اصلی نمایش -->
                                        <div @click="showDropdown = !showDropdown" 
                                             class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 cursor-pointer transition-all duration-200 flex items-center justify-between"
                                             :class="{'border-blue-500 ring-2 ring-blue-200': showDropdown}">
                                            <span class="text-gray-700" x-text="getDisplayText()"></span>
                                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" 
                                                 :class="{'rotate-180': showDropdown}" 
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                        
                                        <!-- دراپ داون با چک باکس ها -->
                                        <div x-show="showDropdown" 
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-150"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             @click.away="showDropdown = false"
                                             class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg z-50 max-h-48 overflow-y-auto">
                                            
                                            <!-- هدر با دکمه انتخاب همه / هیچ کدام -->
                                            <div class="p-2 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                                                <span class="text-xs font-medium text-gray-600">معیارهای پذیرش</span>
                                                <div class="flex gap-1">
                                                    <button @click="selectedOptions = [...availableOptions]; filter.value = selectedOptions.join(',')" 
                                                            class="text-xs px-2 py-1 text-blue-600 hover:bg-blue-50 rounded transition-colors">همه</button>
                                                    <button @click="selectedOptions = []; filter.value = ''" 
                                                            class="text-xs px-2 py-1 text-red-600 hover:bg-red-50 rounded transition-colors">هیچ کدام</button>
                                                </div>
                                            </div>
                                            
                                            <!-- لیست گزینه ها -->
                                            <template x-for="option in availableOptions" :key="option">
                                                <label class="flex items-center p-3 hover:bg-gray-50 cursor-pointer transition-colors border-b border-gray-50 last:border-b-0">
                                                    <input type="checkbox" 
                                                           :checked="isSelected(option)"
                                                           @change="toggleOption(option)"
                                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-colors">
                                                    <span class="mr-3 text-sm text-gray-700" x-text="option"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </div>

                                    <div x-show="filter.type === 'members_count'" x-data="{
                                        rangeMode: false,
                                        toggleRangeMode() {
                                            this.rangeMode = !this.rangeMode;
                                            if (this.rangeMode) {
                                                // حالت بازه: مقداردهی اولیه min و max
                                                filter.min_members = filter.value || '';
                                                filter.max_members = '';
                                                filter.value = '';
                                            } else {
                                                // حالت تک عدد: برگرداندن به value
                                                filter.value = filter.min_members || '';
                                                filter.min_members = '';
                                                filter.max_members = '';
                                            }
                                        }
                                    }" class="space-y-3">
                                        <!-- دکمه تغییر حالت -->
                                        <div class="flex justify-end">
                                            <button type="button" @click="toggleRangeMode()" 
                                                    class="text-xs px-2 py-1 rounded border transition-colors"
                                                    :class="rangeMode ? 'bg-blue-100 text-blue-700 border-blue-300' : 'bg-gray-100 text-gray-600 border-gray-300'">
                                                <span x-text="rangeMode ? 'تک عدد' : 'بازه'"></span>
                                            </button>
                                        </div>
                                        
                                        <!-- حالت تک عدد -->
                                        <div x-show="!rangeMode">
                                            <input type="number" x-model="filter.value" min="1" max="20"
                                                   class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 px-4 transition-all duration-200"
                                                   placeholder="تعداد اعضا">
                                        </div>
                                        
                                        <!-- حالت بازه -->
                                        <div x-show="rangeMode" class="flex space-x-2 rtl:space-x-reverse">
                                            <div class="w-1/2">
                                                <input type="number" x-model="filter.min_members" min="1" max="20"
                                                       class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 px-4 transition-all duration-200"
                                                       placeholder="حداقل">
                                            </div>
                                            <div class="flex items-center px-2 text-gray-500">تا</div>
                                            <div class="w-1/2">
                                                <input type="number" x-model="filter.max_members" min="1" max="20"
                                                       class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 px-4 transition-all duration-200"
                                                       placeholder="حداکثر">
                                            </div>
                                        </div>
                                    </div>

                                    <div x-show="filter.type === 'membership_date'" class="flex space-x-4 rtl:space-x-reverse">
                                        <div class="w-2/5">
                                            <input
                                                type="text"
                                                x-model="filter.start_date"
                                                data-jdp
                                                placeholder="از تاریخ"
                                                class="w-full border border-gray-300 rounded-lg px-2 py-1 text-center bg-white cursor-pointer"
                                            />
                                        </div>
                                        <div class="w-2/5">
                                            <input
                                                type="text"
                                                      data-jdp-only-date

                                                x-model="filter.end_date"
                                                data-jdp
                                                placeholder="تا تاریخ"
                                                class="w-full border border-gray-300 rounded-lg px-2 py-1 text-center bg-white cursor-pointer"
                                            />
                                        </div>
                                    </div>

                                    <div x-show="filter.type === 'weighted_score'" class="flex space-x-4 rtl:space-x-reverse">
                                        <div class="w-1/2">
                                            <input type="number" x-model="filter.min" placeholder="حداقل امتیاز" step="0.1"
                                                   class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 transition-all duration-200">
                                        </div>
                                        <div class="w-1/2">
                                            <input type="number" x-model="filter.max" placeholder="حداکثر امتیاز" step="0.1"
                                                   class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 transition-all duration-200">
                                        </div>
                                    </div>

                                    <div x-show="filter.type === 'insurance_end_date'">
                                        <input type="date" x-model="filter.value"
                                               class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 px-4 transition-all duration-200">
                                    </div>

                                    <div x-show="filter.type === 'created_at'">
                                        <input type="date" x-model="filter.value"
                                               class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 px-4 transition-all duration-200">
                                    </div>
                                </td>

                                <!-- شرط -->
                                <td class="px-6 py-5">
                                    <div class="space-y-2">
                                        <!-- شرط وجودی (باشد/نباشد) -->
                                        <div class="relative">
                                            <select x-model="filter.existence_operator" @change="updateFilterLabel(index)"
                                                    x-init="if (!filter.existence_operator) { filter.existence_operator = 'exists'; }"
                                                    class="w-full h-10 border-2 border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-3 appearance-none cursor-pointer transition-all duration-200"
                                                    style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                                <option value="exists">باشد</option>
                                                <option value="not_exists">نباشد</option>
                                            </select>
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- شرط منطقی (و/یا) -->
                                        <div class="relative">
                                            <select x-model="filter.logical_operator" @change="updateFilterLabel(index)"
                                                    x-init="if (!filter.logical_operator) { filter.logical_operator = 'and'; }"
                                                    class="w-full h-10 border-2 border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-3 appearance-none cursor-pointer transition-all duration-200"
                                                    style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                                <option value="and">و</option>
                                                <option value="or">یا</option>
                                            </select>
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- حذف -->
                                <td class="px-6 py-5 text-center">
                                    <button @click="removeFilter(index)"
                                            class="inline-flex items-center justify-center w-10 h-10 bg-red-50 hover:bg-red-100 text-red-500 hover:text-red-700 rounded-lg transition-all duration-200 group">
                                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <!-- خط اضافه کردن فیلتر جدید -->
                        <tr>
                            <td colspan="4" class="px-6 py-6">
                                <button @click="addFilter()"
                                        class="w-full flex items-center justify-center gap-3 p-4 text-green-700 hover:text-green-800 hover:bg-green-50 rounded-xl border-2 border-dashed border-green-300 hover:border-green-400 transition-all duration-200 group">
                                    <svg class="w-6 h-6 group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span class="font-medium">افزودن فیلتر جدید</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- بخش ذخیره سازی و بارگذاری فیلترها -->
        <div class="p-6 border-t border-gray-200 bg-gradient-to-r from-gray-50 to-blue-50" 
             x-data="{
                 showSaveForm: false,
                 filterName: '',
                 filterDescription: '',
                 showLoadOptions: false,
                 savedFilters: [],
                loadSavedFilters() {
                    // فراخوانی API برای دریافت فیلترهای ذخیره شده
                    $wire.loadSavedFilters('filter_modal').then(data => {
                        this.savedFilters = data;
                    });
                },
                saveCurrentFilter() {
                    if (!this.filterName.trim()) {
                        alert('لطفا نام فیلتر را وارد کنید');
                        return;
                    }
                    $wire.saveFilter(this.filterName, this.filterDescription)
                        .then((result) => {
                            console.log('Filter saved successfully:', result);
                            this.filterName = '';
                            this.filterDescription = '';
                            this.showSaveForm = false;
                            // بارگیری مجدد فیلترهای ذخیره شده
                            this.loadSavedFilters();
                        })
                        .catch((error) => {
                            console.error('Error saving filter:', error);
                            alert('خطا در ذخیره فیلتر: ' + (error.message || 'خطای ناشناخته'));
                        });
                },
                async deleteSavedFilter(filterId) {
                    if (!confirm('آیا مطمئن هستید که می‌خواهید این فیلتر را حذف کنید؟')) {
                        return;
                    }
                    try {
                        await $wire.deleteSavedFilter(filterId);
                        // بارگیری مجدد لیست فیلترها
                        this.loadSavedFilters();
                        alert('فیلتر با موفقیت حذف شد');
                    } catch (error) {
                        console.error('Error deleting filter:', error);
                        alert('خطا در حذف فیلتر: ' + (error.message || 'خطای ناشناخته'));
                    }
                }
             }">
            
            <!-- نوار ابزارهای ذخیره/بارگذاری -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex gap-2">
                    <!-- دکمه بارگذاری فیلترهای ذخیره شده -->
                    <button @click="showLoadOptions = !showLoadOptions; if(showLoadOptions) loadSavedFilters()"
                            class="inline-flex items-center px-3 py-2 bg-blue-100 border border-blue-300 rounded-lg text-sm font-medium text-blue-700 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                        </svg>
                        بارگذاری فیلتر
                    </button>
                    
                    <!-- دکمه ذخیره فیلتر جاری -->
                    <button @click="showSaveForm = !showSaveForm"
                            class="inline-flex items-center px-3 py-2 bg-green-100 border border-green-300 rounded-lg text-sm font-medium text-green-700 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        ذخیره فیلتر
                    </button>
                </div>
            </div>
            
            <!-- فرم بارگذاری فیلترها -->
            <div x-show="showLoadOptions" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 class="bg-white rounded-lg border-2 border-blue-200 p-4 mb-4">
                
                <div class="mb-4">
                    <h4 class="text-lg font-medium text-gray-900 mb-3">فیلترهای ذخیره شده</h4>
                </div>
                
                <div class="max-h-64 overflow-y-auto">
                    <template x-for="filter in savedFilters" :key="filter.id">
                        <div class="p-3 border border-gray-200 rounded-lg mb-2 transition-colors hover:bg-gray-50">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 cursor-pointer" @click="$wire.loadFilter(filter.id); showLoadOptions = false">
                                    <div class="flex items-center justify-between">
                                        <h5 class="text-sm font-medium text-gray-900" x-text="filter.name"></h5>
                                        <span class="px-2 py-1 text-xs rounded-full ml-2"
                                              :class="{
                                                  'bg-green-100 text-green-800': filter.visibility === 'private',
                                                  'bg-blue-100 text-blue-800': filter.visibility === 'organization',
                                                  'bg-purple-100 text-purple-800': filter.visibility === 'public'
                                              }"
                                              x-text="{
                                                  'private': 'خصوصی',
                                                  'organization': 'سازمانی',
                                                  'public': 'عمومی'
                                              }[filter.visibility]"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1" x-text="filter.description || 'بدون توضیح'"></p>
                                    <div class="flex items-center mt-2 text-xs text-gray-400">
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span x-text="'استفاده: ' + filter.usage_count + ' بار'"></span>
                                        <span class="mx-2">•</span>
                                        <span x-text="filter.created_at"></span>
                                    </div>
                                </div>
                                <!-- دکمه حذف - فقط برای فیلترهای متعلق به کاربر -->
                                <div class="flex items-center ml-2" x-show="filter.is_owner">
                                    <button @click.stop="deleteSavedFilter(filter.id)"
                                            class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-full transition-colors duration-200"
                                            title="حذف فیلتر">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                    
                    <div x-show="savedFilters.length === 0" class="p-4 text-center text-gray-500">
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-sm">هیچ فیلتر ذخیره‌ای وجود ندارد</p>
                    </div>
                </div>
                
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="showLoadOptions = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        بستن
                    </button>
                </div>
            </div>
            
            <!-- فرم ذخیره فیلتر -->
            <div x-show="showSaveForm" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 class="bg-white rounded-lg border-2 border-green-200 p-4 mb-4">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">نام فیلتر *</label>
                    <input type="text" x-model="filterName" placeholder="نام مناسبی برای فیلتر انتخاب کنید"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">توضیحات (اختیاری)</label>
                    <textarea x-model="filterDescription" rows="2" placeholder="توضیح کوتاهی درباره کاربرد این فیلتر"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
                </div>
                
                
                <div class="flex justify-end gap-2">
                    <button @click="showSaveForm = false; filterName = ''; filterDescription = ''"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        انصراف
                    </button>
                    <button @click="saveCurrentFilter()"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                        ذخیره فیلتر
                    </button>
                </div>
            </div>
        </div>

        <!-- فوتر مودال -->
        <div class="flex items-center justify-between p-6 border-t border-gray-200 bg-gray-50">
            <div class="flex gap-2">
                <button wire:click="resetToDefault" @click="{{ $showModal }} = false"
                        class="inline-flex items-center px-4 py-2.5 bg-gray-100 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    بازگشت به پیشفرض
                </button>
            </div>

            <button @click="setTimeout(() => { $wire.applyFilters(); {{ $showModal }} = false; }, 100)"
                    class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg text-sm font-medium hover:from-green-600 hover:to-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors">
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                تایید و اعمال فیلترها
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script src="/vendor/jalalidatepicker/jalalidatepicker.min.js"></script>
<script>
    function initJalaliDatepicker() {
        try {
            if (typeof jalaliDatepicker !== 'undefined') {
                jalaliDatepicker.startWatch({
                    minDate: "attr",
                    maxDate: "attr",
                    time: false
                });
            } else {
            }
        } catch (error) {
            console.error('Error initializing JalaliDatePicker:', error);
        }
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
