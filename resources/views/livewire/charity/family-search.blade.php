<div>
    {{-- Knowing others is intelligence; knowing yourself is true wisdom. --}}
    <!-- نشانگر بارگذاری صفحه -->
    <div wire:loading.delay.longer class="fixed inset-0 bg-black bg-opacity-30 z-50 flex items-center justify-center">
        <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full">
            <div class="flex items-center justify-center">
                <svg class="animate-spin h-8 w-8 text-blue-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <h3 class="text-lg font-semibold">در حال بارگذاری اطلاعات...</h3>
            </div>
            <p class="mt-2 text-sm text-gray-600 text-center">این عملیات ممکن است چند لحظه طول بکشد.</p>
        </div>
    </div>
    
    <!-- جستجو و فیلتر -->
    <div class="mb-6 flex gap-2">
        <div class="w-full flex flex-wrap items-center gap-2">
            <div class="relative flex-grow">
                <input wire:model.debounce.300ms="search" type="text" placeholder="جستجو..." class="border border-gray-300 rounded p-2 w-full">
                <div wire:loading wire:target="search" class="absolute left-3 top-1/2 transform -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
            
            <div class="relative">
                <select wire:model.debounce.300ms="statusFilter" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="insured">بیمه شده</option>
                    <option value="uninsured">بدون بیمه</option>
                </select>
                <div wire:loading wire:target="statusFilter" class="absolute left-3 top-1/2 transform -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
            
            <div class="relative">
                <select wire:model.debounce.300ms="regionFilter" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="">همه مناطق</option>
                    @foreach($regions as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                    @endforeach
                </select>
                <div wire:loading wire:target="regionFilter" class="absolute left-3 top-1/2 transform -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>


    <!-- جدول خانواده‌ها -->
    <div class="w-full overflow-x-auto">
        <div wire:loading wire:target="search, statusFilter, regionFilter, gotoPage, previousPage, nextPage, sortBy" class="w-full flex justify-center items-center py-4">
            <div class="flex items-center justify-center space-x-2 rtl:space-x-reverse">
                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-700">در حال بارگذاری...</span>
            </div>
        </div>
        <table class="w-full border border-gray-200" wire:loading.class="opacity-50">
            <thead>
                <tr class="bg-gray-50 text-xs text-gray-700">
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('id')" class="flex items-center justify-end w-full">
                            رتبه
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('province')" class="flex items-center justify-end w-full">
                            استان
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('city')" class="flex items-center justify-end w-full">
                            شهر/روستا
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('is_insured')" class="flex items-center justify-end w-full">
                            تعداد بیمه ها
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('acceptance_criteria')" class="flex items-center justify-end w-full">
                            معیار پذیرش
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('members_count')" class="flex items-center justify-end w-full">
                            تعداد اعضا
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('head_name')" class="flex items-center justify-end w-full">
                            سرپرست خانوار
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('consumption_coefficient')" class="flex items-center justify-end w-full">
                            ضریبه مصرف
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('created_at')" class="flex items-center justify-end w-full">
                            تاریخ عضویت
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('payer')" class="flex items-center justify-end w-full">
                            پرداخت کننده حق بیمه
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('participation_percentage')" class="flex items-center justify-end w-full">
                            درصد مشارکت
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button wire:click="sortBy('verified_at')" class="flex items-center justify-end w-full">
                            تاییدیه
                            <span class="mr-1 text-[0.5rem]">▼</span>
                        </button>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($families as $family)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $loop->iteration }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->region->province ?? 'نامشخص' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->region->name ?? 'نامشخص' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->is_insured ? $family->members->count() : 0 }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        @if(is_array($family->acceptance_criteria) || $family->acceptance_criteria instanceof \Illuminate\Support\Collection)
                            @foreach($family->acceptance_criteria as $criteria)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1 mb-1">
                                    {{ $criteria }}
                                </span>
                            @endforeach
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1">
                                {{ $family->acceptance_criteria ?? 'از کار افتادگی' }}
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->members->count() ?? 0 }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->head()?->full_name ?? 'نامشخص' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        ۵۰٪
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->created_at ? jdate($family->created_at)->format('Y/m/d') : '-' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        <div class="flex items-center">
                            <span>{{ $family->payer ?? 'خیریه' }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        <div class="flex items-center">
                            <span class="ml-2">{{ $family->participation_percentage ?? '۵۰٪' }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        <button class="bg-blue-100 hover:bg-blue-200 text-blue-800 text-xs py-1 px-2 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="px-5 py-4 text-sm text-gray-500 border-b border-gray-200 text-center">
                        هیچ خانواده‌ای یافت نشد.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- پیجینیشن -->
    @if($families->hasPages())
    <div class="mt-4">
        {{ $families->links() }}
    </div>
    @endif
</div>
