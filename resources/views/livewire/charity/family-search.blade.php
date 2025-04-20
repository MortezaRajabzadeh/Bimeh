<div>
    {{-- Knowing others is intelligence; knowing yourself is true wisdom. --}}
    <!-- جستجو و فیلتر -->
    <div class="mb-6 flex gap-2">
        <div class="w-full flex flex-wrap items-center gap-2">
            <div class="relative flex-grow">
                <input wire:model.debounce.300ms="search" type="text" placeholder="جستجو..." class="border border-gray-300 rounded p-2 w-full">
            </div>
            
            <div class="relative">
                <select wire:model="statusFilter" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="insured">بیمه شده</option>
                    <option value="uninsured">بدون بیمه</option>
                </select>
            </div>
            
            <div class="relative">
                <select wire:model="regionFilter" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="">همه مناطق</option>
                    @foreach($regions as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- آمار کلی -->
    <div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
            <h3 class="text-lg font-semibold text-green-700">خانواده‌های بیمه شده</h3>
            <div class="text-2xl font-bold">{{ $insuredFamilies }} خانواده</div>
        </div>
        <div class="bg-red-50 p-4 rounded-lg border border-red-200">
            <h3 class="text-lg font-semibold text-red-700">خانواده‌های بدون بیمه</h3>
            <div class="text-2xl font-bold">{{ $uninsuredFamilies }} خانواده</div>
        </div>
    </div>

    <!-- جدول خانواده‌ها -->
    <div class="w-full overflow-x-auto">
        <table class="w-full border border-gray-200">
            <thead>
                <tr class="bg-gray-50 text-xs text-gray-700">
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">کد خانواده</th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">منطقه</th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">سرپرست خانوار</th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">تعداد اعضا</th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">وضعیت بیمه</th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">وضعیت مسکن</th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">تاریخ ثبت</th>
                    <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">عملیات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($families as $family)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->family_code ?? 'کد-' . $family->id }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->region->name ?? 'نامشخص' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->head()?->full_name ?? 'نامشخص' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->members->count() ?? 0 }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        @if($family->is_insured)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mr-1">
                                بیمه شده
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 mr-1">
                                بدون بیمه
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        @switch($family->housing_status)
                            @case('owner')
                                <span>مالک</span>
                                @break
                            @case('tenant')
                                <span>مستأجر</span>
                                @break
                            @case('relative')
                                <span>منزل اقوام</span>
                                @break
                            @default
                                <span>سایر</span>
                        @endswitch
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        {{ $family->created_at ? jdate($family->created_at)->format('Y/m/d') : '-' }}
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <a href="#" class="text-blue-600 hover:text-blue-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </a>
                            <a href="#" class="text-yellow-600 hover:text-yellow-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-4 text-sm text-gray-500 border-b border-gray-200 text-center">
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
