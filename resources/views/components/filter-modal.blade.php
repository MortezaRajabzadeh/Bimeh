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

    <div @click.away="{{ $showModal }} = false"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">

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
                            <tr class="hover:bg-blue-50 transition-colors duration-200">
                                <!-- نوع فیلتر -->
                                <td class="px-6 py-5">
                                    <div class="relative">
                                        <select x-model="filter.type" @change="updateFilterLabel(index)"
                                                class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 appearance-none cursor-pointer transition-all duration-200"
                                                style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
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
                                    <div x-show="filter.type === 'status'" class="relative">
                                        <select x-model="filter.value"
                                                class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 appearance-none cursor-pointer transition-all duration-200"
                                                style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                            <option value="">انتخاب وضعیت...</option>
                                            <option value="insured">بیمه شده</option>
                                            <option value="uninsured">بدون بیمه</option>
                                            <option value="pending">در انتظار بررسی</option>
                                            <option value="approved">تایید شده</option>
                                            <option value="rejected">رد شده</option>
                                        </select>
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                    </div>

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

                                    <!-- Special Disease Filter -->
                                    <div x-show="filter.type === 'special_disease'" class="relative">
                                        <select x-model="filter.value"
                                                class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 appearance-none cursor-pointer transition-all duration-200"
                                                style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                            <option value="">انتخاب معیار پذیرش...</option>
                                            <option value="بیماری های خاص">بیماری های خاص</option>
                                            <option value="اعتیاد">اعتیاد</option>
                                            <option value="از کار افتادگی">از کار افتادگی</option>
                                            <option value="بیکاری">بیکاری</option>
                                        </select>
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                    </div>

                                    <div x-show="filter.type === 'members_count'">
                                        <input type="number" x-model="filter.value" min="1" max="20"
                                               class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 px-4 transition-all duration-200"
                                               placeholder="تعداد اعضا">
                                    </div>

                                    <div x-show="filter.type === 'membership_date'" class="flex space-x-4 rtl:space-x-reverse">
                                        <div class="w-1/2">
                                            <div class="relative">
                                                <input
                                                    type="text"
                                                    x-model="filter.start_date"
                                                    class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 transition-all duration-200 jalali-datepicker"
                                                    placeholder="از تاریخ"
                                                    autocomplete="off"
                                                    data-jdp
                                                    readonly
                                                >
                                                <div class="absolute inset-y-0 left-2 flex items-center text-gray-400 pointer-events-none">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-1/2">
                                            <div class="relative">
                                                <input
                                                    type="text"
                                                    x-model="filter.end_date"
                                                    class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 transition-all duration-200 jalali-datepicker"
                                                    placeholder="تا تاریخ"
                                                    autocomplete="off"
                                                    data-jdp
                                                    readonly
                                                >
                                                <div class="absolute inset-y-0 left-2 flex items-center text-gray-400 pointer-events-none">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            </div>
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
                                    <div class="relative">
                                        <select x-model="filter.operator" @change="updateFilterLabel(index)"
                                                class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 appearance-none cursor-pointer transition-all duration-200"
                                                style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                            <option value="and">و</option>
                                            <option value="or">یا</option>
                                        </select>
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
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
                 filterVisibility: 'private',
                 showLoadOptions: false,
                 savedFilters: [],
                 loadSavedFilters() {
                     // فراخوانی API برای دریافت فیلترهای ذخیره شده
                     $wire.loadSavedFilters().then(data => {
                         this.savedFilters = data;
                     });
                 },
                 saveCurrentFilter() {
                     if (!this.filterName.trim()) {
                         alert('لطفا نام فیلتر را وارد کنید');
                         return;
                     }
                     $wire.saveFilter(this.filterName, this.filterDescription, this.filterVisibility)
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
                        <div class="p-3 hover:bg-gray-50 border border-gray-200 rounded-lg cursor-pointer mb-2 transition-colors"
                             @click="$wire.loadFilter(filter.id); showLoadOptions = false">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h5 class="text-sm font-medium text-gray-900" x-text="filter.name"></h5>
                                    <p class="text-xs text-gray-500 mt-1" x-text="filter.description"></p>
                                    <div class="flex items-center mt-2 text-xs text-gray-400">
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span x-text="'استفاده: ' + filter.usage_count + ' بار'"></span>
                                        <span class="mx-2">•</span>
                                        <span x-text="filter.created_at"></span>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <span class="px-2 py-1 text-xs rounded-full"
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
