@props([
    'showModal' => false,
    'availableRankSettings' => null,
    'isInsuranceUser' => false
])

@if($isInsuranceUser)
<div x-show="showRankModal"
     @keydown.escape.window="showRankModal = false"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform scale-90"
     x-transition:enter-end="opacity-100 transform scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform scale-100"
     x-transition:leave-end="opacity-0 transform scale-90"
     x-cloak
     class="fixed inset-0 z-30 flex items-center justify-center p-4 bg-black bg-opacity-50">

    <div @click.away="showRankModal = false"
         class="w-full max-w-3xl max-h-[90vh] overflow-y-auto bg-white rounded-lg">

        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-2xl font-bold text-gray-800">تنظیمات رتبه</h3>
            <button @click="showRankModal = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="p-6">
            <p class="mb-6 text-center text-gray-700">
                لطفا برای <span class="font-bold">معیار پذیرش</span> لیست شده وزن انتخاب کنید تا پس از تایید در رتبه بندی ها اعمال شود
            </p>

            <!-- جدول معیارهای پذیرش -->
            <div class="overflow-x-auto mb-6">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-700 border-b">
                            <th class="px-3 py-3 text-center">انتخاب</th>
                            <th class="px-3 py-3 text-right">معیار پذیرش</th>
                            <th class="px-3 py-3 text-center">وزن (0-10)</th>
                            <th class="px-3 py-3 text-center">شرح</th>
                            <th class="px-3 py-3 text-center">نیاز به مدرک؟</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($availableRankSettings))
                            @foreach($availableRankSettings as $criterion)
                                <tr class="hover:bg-gray-50 border-b border-gray-200" wire:key="rank-setting-{{ $criterion->id }}">
                                    <td class="px-3 py-3 text-center">
                                        <input type="checkbox" wire:model.live="selectedCriteria.{{ $criterion->id }}" class="form-checkbox h-5 w-5 text-green-500">
                                    </td>
                                    <td class="px-3 py-3 flex justify-between items-center">
                                        <div class="flex space-x-2 rtl:space-x-reverse">
                                            <button wire:click="editRankSetting({{ $criterion->id }})" class="text-orange-500 hover:text-orange-700 ml-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="px-4 py-2 rounded-md text-center w-full" style="background-color: {{ $criterion->color ?? '#e5f7eb' }}">
                                            {{ $criterion->name }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-center">{{ $criterion->weight }}</td>
                                    <td class="px-3 py-3 text-center">
                                        <div class="relative group">
                                            <button type="button" class="text-gray-500 hover:text-gray-700">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </button>
                                            <div class="fixed z-20 hidden group-hover:block bg-white border border-gray-200 rounded-lg shadow-lg p-4 max-w-xs">
                                                <p class="text-sm text-gray-700">{{ $criterion->description }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        @if($criterion->requires_document)
                                            <span class="text-green-500">✓</span>
                                        @else
                                            <span class="text-red-500">✗</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">
                                    معیار رتبه‌بندی تعریف نشده است
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- باکس اضافه کردن معیار جدید -->
            <div x-data="{ showNewCriterionForm: false }" x-init="$watch('$wire.editingRankSettingId', value => { if(value) showNewCriterionForm = true; })" class="mb-6">
                <!-- دکمه اضافه کردن معیار جدید -->
                <div x-show="!showNewCriterionForm" @click="showNewCriterionForm = true" class="border border-green-500 rounded-lg p-4 flex flex-col items-center justify-center cursor-pointer hover:bg-green-50 transition-all duration-300">
                    <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center mb-2">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <span class="text-green-600 font-medium">افزودن معیار جدید</span>
                </div>

                <!-- فرم افزودن/ویرایش معیار -->
                <div x-show="showNewCriterionForm" class="border border-green-500 rounded-lg p-5 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900" x-text="$wire.editingRankSettingId ? 'ویرایش معیار' : 'افزودن معیار جدید'"></h3>
                    </div>

                    <!-- نمایش نام معیار به صورت فقط خواندنی در حالت ویرایش -->
                    <div x-show="$wire.editingRankSettingId" class="mb-4">
                        <label class="block text-gray-700 mb-2">نام معیار</label>
                        <div class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-100 text-gray-600" x-text="$wire.rankSettingName"></div>
                    </div>

                    <!-- فقط در حالت افزودن معیار جدید نمایش داده شود -->
                    <div x-show="!$wire.editingRankSettingId" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 mb-2">اسم معیار پذیرش</label>
                            <input type="text" wire:model="rankSettingName"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">نیاز به مدرک؟</label>
                            <div class="relative">
                                <select wire:model="rankSettingNeedsDoc"
                                        class="w-full px-3 py-2 pr-8 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 rtl text-right appearance-none">
                                    <option value="1">بله</option>
                                    <option value="0">خیر</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center px-2 text-gray-700">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- فیلد وزن که همیشه نمایش داده می‌شود -->
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">وزن معیار پذیرش</label>
                        <div class="relative w-32">
                            <select wire:model="rankSettingWeight"
                                    class="w-full px-3 py-2 pr-8 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 rtl text-right appearance-none">
                                @for($i = 0; $i <= 10; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- فیلد توضیحات فقط در حالت افزودن معیار جدید -->
                    <div x-show="!$wire.editingRankSettingId" class="mb-4">
                        <label class="block text-gray-700 mb-2">شرح معیار پذیرش در اینجا ذکر میشود و مدارک و نحوه پذیرش در اینجا تعیین میشود</label>
                        <textarea wire:model="rankSettingDescription" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                    </div>

                    <div class="flex justify-center space-x-4 rtl:space-x-reverse">
                        <button @click="showNewCriterionForm = false; $wire.resetRankSettingForm();" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                            انصراف
                        </button>
                        <button wire:click="saveRankSetting" @click="showNewCriterionForm = false" class="bg-green-500 text-white px-6 py-2 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            ذخیره
                        </button>
                    </div>
                </div>
            </div>

            <!-- بخش ذخیره سازی و بارگذاری فیلترها -->
            <div class="mt-8 p-4 border-t border-gray-200 bg-gray-50 rounded-lg"
                 x-data="{
                     showSaveForm: false,
                     filterName: '',
                     filterDescription: '',
                     filterVisibility: 'private',
                     showLoadOptions: false,
                     savedFilters: [],
                     loading: false,
                     async loadSavedFilters() {
                         this.loading = true;
                         try {
                             const data = await $wire.loadSavedFilters('rank_modal');
                             this.savedFilters = data || [];
                             console.log('فیلترهای رتبه‌بندی بارگذاری شده:', this.savedFilters);
                         } catch (error) {
                             console.error('خطا در بارگذاری فیلترها:', error);
                             this.savedFilters = [];
                             // نمایش پیام خطا
                             window.dispatchEvent(new CustomEvent('notify', {
                                 detail: {
                                     message: 'خطا در بارگذاری فیلترهای ذخیره شده',
                                     type: 'error'
                                 }
                             }));
                         } finally {
                             this.loading = false;
                         }
                     },
                     async saveCurrentFilter() {
                         if (!this.filterName.trim()) {
                             // نمایش پیام هشدار - مشابه filter-modal
                             window.dispatchEvent(new CustomEvent('notify', {
                                 detail: {
                                     message: 'لطفا نام فیلتر را وارد کنید',
                                     type: 'warning'
                                 }
                             }));
                             return;
                         }
                         this.loading = true;
                         try {
                             // ذخیره فیلتر با صدا زدن متد saveRankFilter در کامپوننت
                             await $wire.saveRankFilter(this.filterName, this.filterDescription);
                             
                             // پاک کردن فرم
                             this.filterName = '';
                             this.filterDescription = '';
                             this.showSaveForm = false;
                             
                             // بارگذاری مجدد لیست فیلترها
                             await this.loadSavedFilters();
                             
                             // نمایش پیام موفقیت
                             window.dispatchEvent(new CustomEvent('notify', {
                                 detail: {
                                     message: 'فیلتر با موفقیت ذخیره شد',
                                     type: 'success'
                                 }
                             }));
                             
                         } catch (error) {
                             console.error('خطا در ذخیره فیلتر:', error);
                             // نمایش پیام خطا
                             window.dispatchEvent(new CustomEvent('notify', {
                                 detail: {
                                     message: 'خطا در ذخیره فیلتر: ' + (error.message || 'خطای ناشناخته'),
                                     type: 'error'
                                 }
                             }));
                         } finally {
                             this.loading = false;
                         }
                     }
                 }">

                <div class="mb-4">
                    <h4 class="text-lg font-semibold text-gray-800 mb-2">مدیریت فیلترهای ذخیره شده</h4>
                    <p class="text-sm text-gray-600">می‌توانید تنظیمات فعلی را ذخیره کرده و بعداً بارگذاری کنید</p>
                </div>

                <!-- نوار ابزارهای کمپکت -->
                <div class="flex flex-wrap gap-2 mb-4">
                    <button @click="showLoadOptions = !showLoadOptions; showLoadOptions && loadSavedFilters()"
                            :disabled="loading"
                            class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-lg transition-all duration-200"
                            :class="showLoadOptions 
                                ? 'bg-blue-600 text-white shadow-md' 
                                : 'bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200'"> 
                        <svg class="w-4 h-4 ml-1" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        <span x-text="showLoadOptions ? 'بستن' : 'بارگذاری'"></span>
                    </button>

                    <button @click="showSaveForm = !showSaveForm"
                            class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-lg transition-all duration-200"
                            :class="showSaveForm 
                                ? 'bg-green-600 text-white shadow-md' 
                                : 'bg-green-50 text-green-700 hover:bg-green-100 border border-green-200'">
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <span x-text="showSaveForm ? 'لغو' : 'ذخیره'"></span>
                    </button>
                </div>

                <!-- فرم بارگذاری کمپکت -->
                <div x-show="showLoadOptions" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="mb-4 bg-white rounded-lg border border-blue-200 p-3">
                    
                    <div class="max-h-48 overflow-y-auto space-y-2">
                        <template x-for="filter in savedFilters" :key="filter.id">
                            <div class="group flex items-center justify-between p-2 rounded-md hover:bg-blue-50 cursor-pointer transition-colors"
                            @click="async () => { 
                                try {
                                    await $wire.loadRankFilter(filter.id);
                                    showLoadOptions = false;
                                } catch (error) {
                                    console.error('خطا در بارگذاری فیلتر:', error);
                                    window.dispatchEvent(new CustomEvent('notify', {
                                        detail: {
                                            message: 'خطا در بارگذاری فیلتر: ' + (error.message || 'خطای ناشناخته'),
                                            type: 'error'
                                        }
                                    }));
                                }
                            }"
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h6 class="text-sm font-medium text-gray-900 truncate" x-text="filter.name"></h6>
                                        <span class="inline-flex px-2 py-1 text-xs rounded-full"
                                              :class="{
                                                  'bg-green-100 text-green-700': filter.visibility === 'private',
                                                  'bg-blue-100 text-blue-700': filter.visibility === 'organization',
                                                  'bg-purple-100 text-purple-700': filter.visibility === 'public'
                                              }"
                                              x-text="{
                                                  'private': 'شخصی',
                                                  'organization': 'سازمانی', 
                                                  'public': 'عمومی'
                                              }[filter.visibility]"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 truncate mt-1" x-text="filter.description || 'بدون توضیح'"></p>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-400">
                                    <span x-text="filter.usage_count + ' بار'"></span>
                                    <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </template>
                        
                        <div x-show="savedFilters.length === 0 && !loading" class="text-center py-4 text-gray-500">
                            <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-sm">فیلتر ذخیره‌ای وجود ندارد</p>
                        </div>

                        <div x-show="loading" class="text-center py-4">
                            <div class="inline-flex items-center gap-2 text-blue-600">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span class="text-sm">در حال بارگذاری...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- فرم ذخیره کمپکت -->
                <div x-show="showSaveForm" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="bg-white rounded-lg border border-green-200 p-4 space-y-3">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">نام فیلتر *</label>
                            <input type="text" x-model="filterName" 
                                   placeholder="نام مناسب برای فیلتر"
                                   class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">توضیحات</label>
                        <textarea x-model="filterDescription" rows="2" 
                                  placeholder="توضیح کوتاه درباره کاربرد این فیلتر"
                                  class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button @click="showSaveForm = false; filterName = ''; filterDescription = ''"
                                class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200">
                            انصراف
                        </button>
                        <button @click="saveCurrentFilter()"
                                :disabled="loading"
                                class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700 disabled:opacity-50">
                            <span x-text="loading ? 'ذخیره...' : 'ذخیره فیلتر'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- دکمه های پایینی -->
            <div class="flex justify-between">
                <button wire:click="resetToDefaults" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-md">
                    بازگشت به تنظیمات پیشفرض
                </button>
                <button wire:click="applyCriteria" class="bg-green-500 text-white px-6 py-3 rounded-md flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    تایید و اعمال تنظیمات جدید
                </button>
            </div>
        </div>
    </div>
</div>
@endif
