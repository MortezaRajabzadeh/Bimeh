{{-- بخش "در حال بررسی" --}}
<div>
    {{-- نمایش اطلاعات و دکمه‌های عملیاتی --}}
    <div class="bg-white rounded-xl shadow p-6 mb-8">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">لیست خانواده‌های در حال بررسی</h2>
                <p class="text-gray-600 mt-1">در این بخش خانواده‌هایی که اطلاعات هویتی آنها تایید شده و در مرحله تخصیص سهم بیمه هستند را مشاهده می‌کنید.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200 disabled:opacity-50"
                        x-on:click="showShareModal = true"
                        {{ count($selected) === 0 ? 'disabled' : '' }}>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    تخصیص سهم بیمه
                    <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                </button>

                <button type="button" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 disabled:opacity-50"
                        onclick="updateFamiliesStatus($wire.selected, 'approved', 'reviewing')"
                        {{ count($selected) === 0 ? 'disabled' : '' }}>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    تایید و انتقال به مرحله بعد
                    <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                </button>

                <button type="button" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors duration-200 disabled:opacity-50"
                        onclick="updateFamiliesStatus($wire.selected, 'pending', 'reviewing')"
                        {{ count($selected) === 0 ? 'disabled' : '' }}>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    بازگشت به مرحله قبل
                    <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                </button>
            </div>
        </div>

        {{-- نمایش فیلدهای انتخاب و تعداد انتخاب شده‌ها --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center">
                <input type="checkbox" id="select-all-reviewing" wire:model="selectAll" class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                <label for="select-all-reviewing" class="mr-3 text-gray-700">انتخاب همه</label>

                @if(count($selected) > 0)
                    <span class="mr-3 px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm">{{ count($selected) }} مورد انتخاب شده</span>
                    <button wire:click="clearSelected" class="mr-2 text-sm text-gray-600 hover:text-gray-900 hover:underline">پاک کردن انتخاب‌ها</button>
                @endif
            </div>

            {{-- نمایش صفحه‌بندی --}}
            <div>
                {{ $families->links() }}
            </div>
        </div>

        {{-- نمایش لیست خانواده‌ها --}}
        <div class="overflow-x-auto scrollbar-thin">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($families as $family)
                    <div class="family-card bg-white border border-gray-200 rounded-lg p-6 {{ in_array($family->id, $selected) ? 'ring-2 ring-indigo-500 bg-indigo-50' : '' }}">
                        {{-- هدر کارت --}}
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="family-reviewing-{{ $family->id }}" value="{{ $family->id }}" wire:model="selected" class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <h3 class="mr-3 text-lg font-bold text-gray-800">خانواده {{ $family->last_name }}</h3>
                            </div>

                            <div class="flex items-center">
                                @if($family->share_percentage)
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs ml-2">
                                        سهم تخصیص داده شده: {{ $family->share_percentage }}%
                                    </span>
                                @endif

                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                                    {{ $family->members_count ?? 0 }} نفر
                                </span>
                            </div>
                        </div>

                        {{-- اطلاعات خانواده --}}
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>{{ $family->first_name ?? '-' }} {{ $family->last_name ?? '-' }}</span>
                            </div>

                            <div class="flex items-center text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                </svg>
                                <span>{{ $family->national_code ?? '-' }}</span>
                            </div>

                            <div class="flex items-center text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span class="text-left dir-ltr">{{ $family->mobile ?? '-' }}</span>
                            </div>

                            @if($family->insurance_type)
                                <div class="flex items-center text-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <span>{{ $family->insurance_type->title ?? '-' }}</span>
                                </div>
                            @endif

                            @if($family->approved_at)
                                <div class="flex items-center text-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>تاریخ تایید: {{ verta($family->approved_at)->formatJalaliDate() }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- دکمه‌های عملیاتی --}}
                        <div class="flex justify-between pt-4 border-t border-gray-200">
                            <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 toggle-family-btn" data-family-id="{{ $family->id }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                                نمایش جزئیات
                            </button>

                            <div class="flex gap-2">
                                <button type="button" class="inline-flex items-center px-3 py-2 border border-blue-500 rounded text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200" onclick="openEditShareModal('{{ $family->id }}')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                    </svg>
                                    تخصیص سهم
                                </button>

                                <a href="{{ route('insurance.families.show', $family->id) }}" class="inline-flex items-center px-3 py-2 border border-green-500 rounded text-sm font-medium text-green-700 bg-white hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    ویرایش اطلاعات
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 py-12 text-center bg-gray-50 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <p class="mt-4 text-lg font-medium text-gray-600">موردی برای نمایش وجود ندارد</p>
                        <p class="mt-2 text-gray-500">در حال حاضر هیچ خانواده‌ای در مرحله بررسی نمی‌باشد.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- نمایش صفحه‌بندی در پایین صفحه --}}
        @if($families->hasPages())
            <div class="mt-6 pt-4 border-t border-gray-200">
                {{ $families->links() }}
            </div>
        @endif
    </div>
</div>

{{-- مودال تخصیص سهم --}}
<div x-data="{ showShareModal: false, familyShares: {} }" x-cloak>
    <div x-show="showShareModal" class="modal-container" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click.away="showShareModal = false" class="modal-content p-8 animate-fade-in" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-800">تخصیص سهم بیمه</h2>
                <button @click="showShareModal = false" class="text-gray-400 hover:text-gray-600 focus:outline-none text-2xl">&times;</button>
            </div>

            <div class="mb-6">
                <div class="text-blue-700 text-lg font-bold mb-3 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    تخصیص سهم برای <span x-text="Object.keys(familyShares).length"></span> خانواده
                </div>

                <div class="text-gray-600 text-base leading-7 bg-blue-50 p-4 rounded-lg">
                    لطفاً درصد سهم بیمه برای هر خانواده را مشخص کنید. مجموع سهم‌ها باید 100٪ باشد.
                </div>
            </div>

            <div class="space-y-4 max-h-80 overflow-y-auto px-2 py-1 mb-6">
                <template x-for="(family, index) in $wire.familySharesData" :key="family.id">
                    <div class="bg-white p-4 rounded-lg border border-gray-200 transition hover:shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <span class="rounded-full bg-blue-100 text-blue-800 p-2 ml-3 text-sm">
                                    <span x-text="index + 1"></span>
                                </span>
                                <div>
                                    <h4 class="font-bold text-gray-800" x-text="family.name"></h4>
                                    <p class="text-sm text-gray-600" x-text="family.national_code"></p>
                                </div>
                            </div>
                            <div class="text-indigo-800 text-sm px-2 py-1 bg-indigo-100 rounded-lg">
                                <span x-text="family.members_count"></span> نفر
                            </div>
                        </div>

                        <div class="flex items-center space-x-4 space-x-reverse">
                            <label class="w-full">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm text-gray-700">درصد سهم:</span>
                                    <span class="text-sm text-blue-700" x-text="familyShares[family.id] + '%'"></span>
                                </div>
                                <input type="range"
                                       class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                                       min="0"
                                       max="100"
                                       step="5"
                                       x-model="familyShares[family.id]">
                            </label>
                            <div class="w-24 flex-shrink-0">
                                <input type="number"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                       min="0"
                                       max="100"
                                       x-model="familyShares[family.id]">
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="border-t border-gray-200 pt-4 mt-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-lg font-bold">جمع کل سهم‌ها:</div>
                    <div class="text-lg" :class="getTotalShares() === 100 ? 'text-green-600 font-bold' : 'text-red-600 font-bold'">
                        <span x-text="getTotalShares() + '%'"></span>
                    </div>
                </div>

                <div x-show="getTotalShares() !== 100" class="text-sm text-red-500 mb-4">
                    توجه: مجموع سهم‌ها باید دقیقاً 100٪ باشد.
                </div>

                <div class="flex flex-row-reverse gap-3">
                    <button @click="saveShares()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-3 text-lg font-bold flex items-center justify-center gap-2 transition duration-200 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed" :disabled="getTotalShares() !== 100">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                        </svg>
                        ذخیره و تایید سهم بیمه
                    </button>
                    <button @click="equalizeShares()" class="flex-none bg-gray-600 hover:bg-gray-700 text-white rounded-lg px-4 py-3 text-lg font-bold transition duration-200 ease-in-out">
                        تخصیص مساوی
                    </button>
                    <button @click="showShareModal = false" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg py-3 text-lg font-bold transition duration-200 ease-in-out">
                        انصراف
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('shareModalData', () => ({
                showShareModal: false,
                familyShares: {},

                init() {
                    window.openEditShareModal = (familyId) => {
                        this.showShareModal = true;
                        this.familyShares = {};
                        this.familyShares[familyId] = 100;
                    };

                    window.addEventListener('openShareAllocationModal', (e) => {
                        this.showShareModal = true;
                        const familyIds = e.detail || [];

                        // ارسال درخواست به کنترلر برای دریافت اطلاعات خانواده‌ها
                        window.livewire.emit('getFamilySharesData', familyIds);

                        // تنظیم مقادیر اولیه
                        familyIds.forEach(id => {
                            this.familyShares[id] = Math.floor(100 / familyIds.length);
                        });

                        // توزیع باقی‌مانده
                        const remainder = 100 - (Math.floor(100 / familyIds.length) * familyIds.length);
                        if (remainder > 0 && familyIds.length > 0) {
                            this.familyShares[familyIds[0]] += remainder;
                        }
                    });
                },

                getTotalShares() {
                    return Object.values(this.familyShares).reduce((sum, share) => sum + parseInt(share || 0), 0);
                },

                equalizeShares() {
                    const count = Object.keys(this.familyShares).length;
                    if (count === 0) return;

                    const equalShare = Math.floor(100 / count);
                    const remainder = 100 - (equalShare * count);

                    Object.keys(this.familyShares).forEach((id, index) => {
                        this.familyShares[id] = equalShare + (index === 0 ? remainder : 0);
                    });
                },

                saveShares() {
                    if (this.getTotalShares() !== 100) {
                        alert('مجموع سهم‌ها باید دقیقاً 100٪ باشد.');
                        return;
                    }

                    // ارسال سهم‌ها به سرور
                    window.livewire.emit('saveShares', this.familyShares);
                    this.showShareModal = false;
                }
            }));
        });
    </script>
</div>
