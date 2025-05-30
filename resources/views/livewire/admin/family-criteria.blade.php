<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">مدیریت معیارهای خانواده‌ها</h1>
            <p class="text-gray-600 mt-1">تعیین و مدیریت معیارهای رتبه‌بندی محرومیت برای هر خانواده</p>
        </div>
        <button wire:click="recalculateAllRanks" 
                onclick="return confirm('آیا از محاسبه مجدد رتبه تمام خانواده‌ها اطمینان دارید؟')"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            محاسبه مجدد همه رتبه‌ها
        </button>
    </div>

    <!-- فیلترها -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">جستجو</label>
                <input wire:model.live="search" type="text" placeholder="جستجو در کد خانواده، آدرس یا اعضا..." 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">استان</label>
                <select wire:model.live="filterProvince" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">همه استان‌ها</option>
                    @foreach($provinces as $province)
                        <option value="{{ $province->id }}">{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">شهر</label>
                <select wire:model.live="filterCity" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">همه شهرها</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}">{{ $city->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">بازه رتبه</label>
                <select wire:model.live="filterRankRange" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">همه رتبه‌ها</option>
                    <option value="very_high">خیلی بالا (80-100)</option>
                    <option value="high">بالا (60-79)</option>
                    <option value="medium">متوسط (40-59)</option>
                    <option value="low">پایین (20-39)</option>
                    <option value="very_low">خیلی پایین (0-19)</option>
                    <option value="unranked">بدون رتبه</option>
                </select>
            </div>
        </div>
    </div>

    <!-- جدول خانواده‌ها -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button wire:click="sortBy('family_code')" class="flex items-center">
                                کد خانواده
                                @if($sortField === 'family_code')
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"></path>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">سرپرست</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">استان/شهر</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <button wire:click="sortBy('calculated_rank')" class="flex items-center">
                                رتبه محاسبه شده
                                @if($sortField === 'calculated_rank')
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"></path>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">معیارهای فعال</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($families as $family)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $family->family_code }}</div>
                                <div class="text-sm text-gray-500">{{ $family->created_at?->format('Y/m/d') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php $head = $family->members->first(); @endphp
                                @if($head)
                                    <div class="text-sm font-medium text-gray-900">{{ $head->first_name }} {{ $head->last_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $head->national_code }}</div>
                                @else
                                    <span class="text-red-500 text-sm">بدون سرپرست</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $family->province?->name }}</div>
                                <div class="text-sm text-gray-500">{{ $family->city?->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($family->calculated_rank !== null)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($family->calculated_rank >= 80) bg-red-100 text-red-800
                                        @elseif($family->calculated_rank >= 60) bg-orange-100 text-orange-800
                                        @elseif($family->calculated_rank >= 40) bg-yellow-100 text-yellow-800
                                        @elseif($family->calculated_rank >= 20) bg-blue-100 text-blue-800
                                        @else bg-green-100 text-green-800
                                        @endif">
                                        {{ $family->calculated_rank }}
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $family->rank_calculated_at?->format('Y/m/d H:i') }}
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        محاسبه نشده
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($family->familyCriteria as $criterion)
                                        @if($criterion->has_criteria && $criterion->rankSetting)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                @switch($criterion->rankSetting->category)
                                                    @case('addiction') bg-green-100 text-green-800 @break
                                                    @case('disability') bg-orange-100 text-orange-800 @break
                                                    @case('disease') bg-pink-100 text-pink-800 @break
                                                    @case('economic') bg-blue-100 text-blue-800 @break
                                                    @case('social') bg-purple-100 text-purple-800 @break
                                                    @default bg-gray-100 text-gray-800
                                                @endswitch">
                                                {{ $criterion->rankSetting->name }}
                                                <span class="mr-1 text-xs">({{ $criterion->rankSetting->weight }})</span>
                                            </span>
                                        @endif
                                    @empty
                                        <span class="text-gray-400 text-sm">بدون معیار</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <button wire:click="openCriteriaModal({{ $family->id }})" 
                                            class="text-blue-600 hover:text-blue-900 p-1 rounded hover:bg-blue-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="recalculateRank({{ $family->id }})" 
                                            class="text-green-600 hover:text-green-900 p-1 rounded hover:bg-green-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                هیچ خانواده‌ای یافت نشد
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($families->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">
                {{ $families->links() }}
            </div>
        @endif
    </div>

    <!-- مودال تنظیمات رتبه -->
    @if($showCriteriaModal && $selectedFamily)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeCriteriaModal"></div>

                <div class="inline-block align-middle bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-4xl sm:w-full">
                    <!-- هدر مودال -->
                    <div class="bg-white px-6 pt-6 pb-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">تنظیمات رتبه</h3>
                                <p class="text-sm text-gray-600">
                                    لطفا برای <strong>معیار پذیرش</strong> لیست شده وزن انتخاب کنید تا پس از تایید در رتبه بندی ها اعمال شود
                                </p>
                                <div class="mt-2 text-sm text-gray-500">
                                    خانواده: {{ $selectedFamily->family_code }} - 
                                    رتبه فعلی: 
                                    @if($selectedFamily->calculated_rank)
                                        <span class="font-medium">{{ $selectedFamily->calculated_rank }}</span>
                                    @else
                                        <span class="text-red-500">محاسبه نشده</span>
                                    @endif
                                </div>
                            </div>
                            <button wire:click="closeCriteriaModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- محتوای مودال -->
                    <div class="bg-white px-6 py-4 max-h-96 overflow-y-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-right py-3 text-sm font-medium text-gray-700">معیار پذیرش</th>
                                    <th class="text-center py-3 text-sm font-medium text-gray-700">نیاز به مدرک؟</th>
                                    <th class="text-center py-3 text-sm font-medium text-gray-700">شرح</th>
                                    <th class="text-center py-3 text-sm font-medium text-gray-700">افزودن مدرک</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($rankSettings as $setting)
                                    <tr class="hover:bg-gray-50">
                                        <!-- معیار پذیرش -->
                                        <td class="py-4">
                                            <div class="flex items-center">
                                                <input type="checkbox" 
                                                       wire:model.live="familyCriteria.{{ $setting->id }}"
                                                       wire:change="toggleCriteria({{ $setting->id }}, $event.target.checked)"
                                                       class="h-5 w-5 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                                <div class="mr-3">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                                        @switch($setting->category)
                                                            @case('addiction') bg-green-100 text-green-800 @break
                                                            @case('disability') bg-orange-100 text-orange-800 @break
                                                            @case('disease') bg-pink-100 text-pink-800 @break
                                                            @case('economic') bg-red-100 text-red-800 @break
                                                            @case('social') bg-purple-100 text-purple-800 @break
                                                            @default bg-gray-100 text-gray-800
                                                        @endswitch">
                                                        {{ $setting->name }}
                                                    </span>
                                                    <div class="text-xs text-gray-500 mt-1">وزن: {{ $setting->weight }}</div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- نیاز به مدرک -->
                                        <td class="py-4 text-center">
                                            @if(in_array($setting->category, ['disability', 'disease', 'addiction']))
                                                <svg class="w-6 h-6 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            @else
                                                <svg class="w-6 h-6 text-red-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            @endif
                                        </td>

                                        <!-- شرح -->
                                        <td class="py-4 text-center">
                                            <button class="text-gray-400 hover:text-gray-600" title="{{ $setting->description }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </button>
                                        </td>

                                        <!-- افزودن مدرک -->
                                        <td class="py-4 text-center">
                                            @if(in_array($setting->category, ['disability', 'disease', 'addiction']))
                                                <button class="text-blue-500 hover:text-blue-700">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                    </svg>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- فوتر مودال -->
                    <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-gray-200">
                        <button wire:click="saveCriteria" 
                                class="w-full inline-flex justify-center items-center rounded-lg border border-transparent shadow-sm px-6 py-3 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            تایید و اعمال تنظیمات جدید
                        </button>
                        <button wire:click="closeCriteriaModal"
                                class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-6 py-3 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            بازگشت به تنظیمات پیشفرض
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="fixed top-4 left-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="fixed top-4 left-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif
</div>

<script>
    // Auto-hide flash messages
    setTimeout(function() {
        const alerts = document.querySelectorAll('[role="alert"]');
        alerts.forEach(alert => {
            alert.style.display = 'none';
        });
    }, 5000);
</script> 