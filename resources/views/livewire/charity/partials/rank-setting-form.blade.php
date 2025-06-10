<form wire:submit.prevent="save" class="space-y-4 p-2">
    {{-- ردیف اول: نام و وزن معیار --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="rank_name_{{ $editingRankSettingId ?? 'new' }}" class="block text-sm font-medium text-gray-700">نام معیار</label>
            <input type="text" id="rank_name_{{ $editingRankSettingId ?? 'new' }}" wire:model="editingRankSetting.name" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            @error('editingRankSetting.name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="rank_weight_{{ $editingRankSettingId ?? 'new' }}" class="block text-sm font-medium text-gray-700">وزن (0-10)</label>
            <input type="number" id="rank_weight_{{ $editingRankSettingId ?? 'new' }}" wire:model="editingRankSetting.weight" min="0" max="10" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            @error('editingRankSetting.weight') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- ردیف دوم: شرح --}}
    <div>
        <label for="rank_description_{{ $editingRankSettingId ?? 'new' }}" class="block text-sm font-medium text-gray-700">شرح</label>
        <textarea id="rank_description_{{ $editingRankSettingId ?? 'new' }}" wire:model="editingRankSetting.description" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
        @error('editingRankSetting.description') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
    </div>
    
    {{-- ردیف سوم: نیاز به مدرک --}}
    <div class="flex items-center space-x-3 rtl:space-x-reverse">
        <div class="flex items-center">
            <input id="requires_document_{{ $editingRankSettingId ?? 'new' }}" type="checkbox" wire:model="editingRankSetting.requires_document" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <label for="requires_document_{{ $editingRankSettingId ?? 'new' }}" class="mr-2 block text-sm text-gray-900">نیاز به مدرک دارد؟</label>
        </div>
        
        {{-- فیلد رنگ با مخفی کردن به جای حذف --}}
        <div class="flex items-center hidden">
             <label for="rank_color_{{ $editingRankSettingId ?? 'new' }}" class="block text-sm font-medium text-gray-700 ml-2">رنگ:</label>
             <input type="color" id="rank_color_{{ $editingRankSettingId ?? 'new' }}" wire:model="editingRankSetting.color" class="w-8 h-8 p-0 border-gray-300 rounded-md cursor-pointer">
             @error('editingRankSetting.color') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- ردیف چهارم: دکمه‌ها --}}
    <div class="flex items-center justify-end pt-4 border-t border-gray-200">
        <div class="flex items-center gap-3">
            <button type="button" wire:click="cancel"  
                    class="inline-flex items-center justify-center px-6 py-2.5 bg-gray-50 text-gray-700 font-medium rounded-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-900 transition-all duration-200 group" 
                    wire:loading.attr="disabled"> 
                <svg class="w-4 h-4 ml-2 group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                انصراف 
            </button>
             
            <button type="submit"  
                    class="inline-flex items-center justify-center px-8 py-2.5 bg-green-50 text-green-700 font-medium rounded-lg border border-green-500 hover:bg-green-100 hover:text-green-800 transition-all duration-200 group" 
                    wire:loading.attr="disabled"> 
                <svg wire:loading wire:target="save" class="animate-spin h-5 w-5 ml-2 text-green-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"> 
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle> 
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path> 
                </svg> 
                <svg wire:loading.remove wire:target="save" class="w-4 h-4 ml-2 group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg> 
                <span wire:loading.remove wire:target="save">ذخیره</span> 
                <span wire:loading wire:target="save">در حال ذخیره...</span> 
            </button> 
        </div> 
    </div>
</form>