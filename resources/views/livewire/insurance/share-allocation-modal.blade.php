<div x-data="{
    showShareModal: @entangle('showModal').live,
    successMessage: @entangle('successMessage').live,
}" 
    x-show="showShareModal" 
    x-cloak
    @closeModalAfterDelay.window="setTimeout(() => { showShareModal = false }, 3000)"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30">
    
    <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full p-8 relative overflow-y-auto max-h-[90vh]">
        <button @click="$wire.closeModal()" class="absolute left-4 top-4 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold mb-2">سهم‌بندی حق بیمه</h2>
            <p class="text-gray-600">لطفاً منابع مالی و درصد مشارکت آنها را مشخص کنید.</p>
            <p class="text-sm text-gray-500">مجموع درصدها باید دقیقاً ۱۰۰٪ باشد.</p>
        </div>

        @if($errorMessage)
            <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-lg">
                <span class="font-bold">خطا:</span> {{ $errorMessage }}
            </div>
        @endif

        @if($successMessage)
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-lg animate-pulse">
                <span class="font-bold">موفقیت:</span> {{ $successMessage }}
            </div>
        @endif

        <div class="mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">تعیین سهم منابع مالی</h3>
                <div class="text-gray-600">
                    مجموع: <span class="{{ abs($totalPercentage - 100) < 0.01 ? 'text-green-600' : 'text-red-600' }} font-bold">{{ $totalPercentage }}%</span>
                </div>
            </div>

            <div class="space-y-4">
                @foreach($shares as $index => $share)
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-bold text-gray-700">منبع مالی {{ $index + 1 }}</h4>
                            @if(count($shares) > 1)
                                <button type="button" wire:click="removeShare({{ $index }})" class="text-red-600 hover:text-red-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="funding_source_{{ $index }}" class="block text-sm font-medium text-gray-700 mb-1">منبع مالی</label>
                                <select 
                                    id="funding_source_{{ $index }}" 
                                    wire:model.live="shares.{{ $index }}.funding_source_id" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200">
                                    <option value="">انتخاب منبع مالی</option>
                                    @foreach($fundingSources as $source)
                                        <option value="{{ $source->id }}">{{ $source->name }}</option>
                                    @endforeach
                                </select>
                                @error('shares.'.$index.'.funding_source_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="percentage_{{ $index }}" class="block text-sm font-medium text-gray-700 mb-1">درصد مشارکت</label>
                                <div class="relative">
                                    <input 
                                        type="number" 
                                        id="percentage_{{ $index }}" 
                                        wire:model.live="shares.{{ $index }}.percentage" 
                                        step="0.01" 
                                        min="0.01" 
                                        max="100" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 pr-8"
                                        placeholder="مثال: 25">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500">%</span>
                                    </div>
                                </div>
                                @error('shares.'.$index.'.percentage')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="mt-4">
                            <label for="description_{{ $index }}" class="block text-sm font-medium text-gray-700 mb-1">توضیحات (اختیاری)</label>
                            <textarea 
                                id="description_{{ $index }}" 
                                wire:model.blur="shares.{{ $index }}.description" 
                                rows="2" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200"
                                placeholder="توضیحات اضافی برای این سهم"></textarea>
                            @error('shares.'.$index.'.description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" wire:click="addShare" class="mt-4 flex items-center text-blue-600 hover:text-blue-800 font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                افزودن منبع مالی دیگر
            </button>
        </div>

        <div class="flex flex-row-reverse gap-3 border-t border-gray-200 pt-5">
            <button 
                wire:click="allocateShares" 
                wire:loading.attr="disabled"
                class="bg-green-600 hover:bg-green-700 text-white rounded-lg py-2 px-6 text-lg font-bold flex items-center justify-center gap-2 transition disabled:opacity-70 disabled:cursor-not-allowed"
                {{ $isProcessing ? 'disabled' : '' }}>
                <span wire:loading.remove wire:target="allocateShares">ذخیره و ادامه</span>
                <span wire:loading wire:target="allocateShares" class="flex items-center">
                    <svg class="animate-spin h-5 w-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    در حال پردازش...
                </span>
            </button>
            <button @click="$wire.closeModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg py-2 px-6 text-lg font-bold">
                انصراف
            </button>
        </div>
    </div>
</div> 