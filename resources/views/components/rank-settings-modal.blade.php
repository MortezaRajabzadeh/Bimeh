@props([
    'showModal' => false,
    'availableRankSettings' => null,
    'isInsuranceUser' => false
])

@if($isInsuranceUser)
<div x-show="{{ $showModal }}"
     @keydown.escape.window="{{ $showModal }} = false"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform scale-90"
     x-transition:enter-end="opacity-100 transform scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform scale-100"
     x-transition:leave-end="opacity-0 transform scale-90"
     x-cloak
     class="fixed inset-0 z-30 flex items-center justify-center p-4 bg-black bg-opacity-50">

    <div @click.away="{{ $showModal }} = false"
         class="w-full max-w-3xl max-h-[90vh] overflow-y-auto bg-white rounded-lg">

        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-2xl font-bold text-gray-800">تنظیمات رتبه</h3>
            <button @click="{{ $showModal }} = false" class="text-gray-400 hover:text-gray-600">
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
