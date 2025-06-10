@props(['selectedCount', 'totalMembers'])

<div x-show="showRenewalModal" x-cloak class="modal-container" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div @click.away="showRenewalModal = false" class="modal-content p-8 animate-fade-in" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">تمدید بیمه‌نامه</h2>
            <button @click="showRenewalModal = false" class="text-gray-400 hover:text-gray-600 focus:outline-none text-2xl">&times;</button>
        </div>
        
        <div class="mb-6">
            <div class="text-indigo-700 text-lg font-bold mb-3 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                تمدید بیمه‌نامه برای <span x-text="$wire.selected.length"></span> خانواده
            </div>
            
            <div class="text-gray-600 text-base leading-7 bg-indigo-50 p-4 rounded-lg">
                لطفاً اطلاعات تمدید بیمه‌نامه را وارد کنید. پس از تمدید، تاریخ انقضای جدید برای بیمه‌نامه‌های انتخاب شده ثبت خواهد شد.
            </div>
        </div>
        
        <div class="space-y-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="renewal-period" class="block text-sm font-medium text-gray-700 mb-1">مدت زمان تمدید</label>
                    <select id="renewal-period" wire:model="renewalPeriod" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="1">یک ماه</option>
                        <option value="3">سه ماه</option>
                        <option value="6">شش ماه</option>
                        <option value="12">یک سال</option>
                    </select>
                </div>
                
                <div>
                    <label for="renewal-date" class="block text-sm font-medium text-gray-700 mb-1">تاریخ شروع تمدید</label>
                    <input type="date" id="renewal-date" wire:model="renewalDate" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>
            
            <div>
                <label for="renewal-note" class="block text-sm font-medium text-gray-700 mb-1">توضیحات تمدید</label>
                <textarea id="renewal-note" rows="3" wire:model="renewalNote" class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="توضیحات تکمیلی برای تمدید بیمه‌نامه..."></textarea>
            </div>
        </div>
        
        <div class="border-t border-gray-200 pt-4 mt-4">
            <div class="flex flex-row-reverse gap-3">
                <button wire:click="renewInsurance" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg py-3 text-lg font-bold flex items-center justify-center gap-2 transition duration-200 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    ثبت تمدید بیمه‌نامه
                </button>
                <button @click="showRenewalModal = false" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg py-3 text-lg font-bold transition duration-200 ease-in-out">
                    انصراف
                </button>
            </div>
        </div>
    </div>
</div> 