<!-- مودال تنظیمات رتبه --> 
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
            <!-- انتخاب الگو --> 
            <div class="mb-4"> 
                <label for="scheme_select">انتخاب الگو یا ایجاد الگوی جدید:</label> 
                <select id="scheme_select" wire:model.live="selectedSchemeId" class="form-select w-full"> 
                    <option value="">-- ایجاد الگوی جدید --</option> 
                    @foreach($rankingSchemes as $scheme) 
                        <option value="{{ $scheme->id }}">{{ $scheme->name }}</option> 
                    @endforeach 
                </select> 
            </div> 

            <!-- فرم نام و توضیحات الگو --> 
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4"> 
                <input wire:model="newSchemeName" type="text" placeholder="نام الگو (مثلا: اولویت زمستان)" class="form-input"> 
                <input wire:model="newSchemeDescription" type="text" placeholder="توضیحات مختصر" class="form-input"> 
            </div> 

            <!-- جدول معیارها و وزن‌ها --> 
            <table class="w-full"> 
                <thead>
                    <tr class="bg-gray-50 text-gray-700 border-b">
                        <th class="px-3 py-3 text-right">معیار پذیرش</th>
                        <th class="px-3 py-3 text-center">وزن (0-10)</th>
                    </tr>
                </thead> 
                <tbody> 
                    @foreach($availableCriteria as $criterion) 
                    <tr class="hover:bg-gray-50 border-b border-gray-200"> 
                        <td class="px-3 py-3">{{ $criterion->name }}</td> 
                        <td class="px-3 py-3 text-center"> 
                            <!-- این input وزن را برای هر معیار مدیریت می‌کند --> 
                            <input type="number" min="0" max="10" 
                                   wire:model="schemeWeights.{{ $criterion->id }}" 
                                   class="form-input w-20 text-center"> 
                        </td> 
                    </tr> 
                    @endforeach 
                </tbody> 
            </table> 
        </div> 

        <!-- فوتر مودال با دکمه‌ها --> 
        <div class="p-6 flex justify-between"> 
            <div> 
                <!-- دکمه ذخیره الگو --> 
                <button wire:click="saveScheme" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">ذخیره الگو</button> 
            </div> 
            <div class="flex space-x-2 rtl:space-x-reverse"> 
                 <!-- دکمه پاک کردن فیلتر --> 
                <button wire:click="clearRanking" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">حذف فیلتر رتبه</button> 
                <!-- دکمه اعمال الگو --> 
                <button wire:click="applyRankingScheme" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md">اعمال این الگو</button> 
            </div> 
        </div> 
    </div> 
</div>