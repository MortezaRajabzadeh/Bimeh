<div>
    {{-- Knowing others is intelligence; knowing yourself is true wisdom. --}}
    <!-- جستجو و فیلتر -->
    <div class="mb-6 flex gap-2">
        <div class="w-full flex flex-wrap items-center gap-2">
            <div class="relative flex-grow">
                <input wire:model.debounce.300ms="search" type="text" placeholder="جستجو..." class="border border-gray-300 rounded p-2 w-full">
            </div>
            
            <div class="relative">
                <select wire:model="status" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="insured">بیمه شده</option>
                    <option value="uninsured">بدون بیمه</option>
                </select>
            </div>
            
            <div class="relative">
                <select wire:model="region" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="">همه مناطق</option>
                    @foreach($regions as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- جدول خانواده‌ها -->
    <div class="w-full overflow-x-auto">
        <table class="w-full border border-gray-200">
            <thead>
                <tr class="bg-gray-50 text-xs text-gray-700">
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button class="flex items-center justify-end w-full">
                            شناسه
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button class="flex items-center justify-end w-full">
                            رتبه
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button class="flex items-center justify-end w-full">
                            استان
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button class="flex items-center justify-end w-full">
                            شهر/روستا
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button class="flex items-center justify-end w-full">
                            تعداد بیمه ها
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button class="flex items-center justify-end w-full">
                            معیار پذیرش
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button class="flex items-center justify-end w-full">
                            تعداد اعضا
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button class="flex items-center justify-end w-full">
                            سرپرست خانوار
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button class="flex items-center justify-end w-full">
                            ضریبه مصرف
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button class="flex items-center justify-end w-full">
                            تاریخ عضویت
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button class="flex items-center justify-end w-full">
                            پرداخت کننده حق بیمه
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button class="flex items-center justify-end w-full">
                            درصد مشارکت
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                        <button class="flex items-center justify-end w-full">
                            تاییدیه
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($families as $index => $family)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->id }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $index + 1 }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->region->province ?? 'تهران' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->region->name ?? 'پاکدشت' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->is_insured ? $family->members->count() : 0 }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1">
                            از کار افتادگی
                        </span>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->members->count() }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->head()?->full_name ?? 'نامشخص' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        ۵۰٪
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->created_at ? jdate($family->created_at)->format('Y/m/d') : '۱۴۰۳/۰۷/۰۱' }}
                    </td>
                    <td class="px-5 py-4 text-sm border-b border-gray-200">
                        <div class="flex items-center">
                            <span>خیریه</span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        <div class="flex items-center">
                            <span class="ml-2">۵۰٪</span>
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
                    <td colspan="13" class="px-5 py-4 text-sm text-gray-500 border-b border-gray-200 text-center">
                        هیچ رکوردی یافت نشد.
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
