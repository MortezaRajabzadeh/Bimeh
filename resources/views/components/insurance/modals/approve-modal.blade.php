@props(['selectedCount', 'totalMembers'])

<div x-show="showApproveModal" x-cloak class="modal-container" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div @click.away="showApproveModal = false" class="modal-content p-8 animate-fade-in" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">تایید و ادامه</h2>
            <button @click="showApproveModal = false" class="text-gray-400 hover:text-gray-600 focus:outline-none text-2xl">&times;</button>
        </div>
        
        <div class="mb-6">
            <div class="text-green-700 text-lg font-bold mb-3 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                اطلاعات هویتی تعداد <span x-text="$wire.selected.length"></span> خانواده معادل <span>{{ $totalMembers }}</span> نفر مورد تایید است
            </div>
            
            <div class="text-gray-600 text-base leading-7 bg-green-50 p-4 rounded-lg">
                تایید این خانواده‌ها به منزله بررسی و تایید اطلاعات هویتی و مدارک مورد نیاز این افراد از نظر شما می‌باشد. پس از تایید، این افراد در قسمت "در انتظار حمایت" قرار می‌گیرند تا در زمان مقتضی فرایند تایید جهت صدور بیمه‌نامه برای آنها انجام گردد.
            </div>
        </div>
        
        <div class="flex flex-row-reverse gap-3">
            <button wire:click="approveSelected" @click="showApproveModal = false" class="flex-1 bg-green-600 hover:bg-green-700 text-white rounded-lg py-3 text-lg font-bold flex items-center justify-center gap-2 transition duration-200 ease-in-out">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                تایید نهایی و ادامه
            </button>
            <button @click="showApproveModal = false" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg py-3 text-lg font-bold transition duration-200 ease-in-out">
                بازگشت و ایجاد تغییر
            </button>
        </div>
    </div>
</div> 