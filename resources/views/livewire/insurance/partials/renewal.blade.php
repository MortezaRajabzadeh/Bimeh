{{-- بخش "تمدید" --}}
<div>
    {{-- نمایش اطلاعات و دکمه‌های عملیاتی --}}
    <div class="bg-white rounded-xl shadow p-6 mb-8">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">بیمه‌های نیازمند تمدید</h2>
                <p class="text-gray-600 mt-1">در این بخش خانواده‌هایی که بیمه‌نامه آنها منقضی شده یا نزدیک به انقضا است را مشاهده می‌کنید.</p>
            </div>
            
            <div class="flex flex-wrap gap-3">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 disabled:opacity-50"
                        x-on:click="showRenewalModal = true" 
                        {{ count($selected) === 0 ? 'disabled' : '' }}>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    تمدید بیمه‌نامه
                    <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                </button>
                
                <button type="button" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors duration-200 disabled:opacity-50"
                        wire:click="returnToPreviousStage" 
                        {{ count($selected) === 0 ? 'disabled' : '' }}>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    بازگشت به مرحله قبل
                    <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                </button>
            </div>
        </div>
        
        {{-- نمایش نکات مهم تمدید بیمه‌نامه --}}
        <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
            <h3 class="text-lg font-bold text-red-800 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                نکات مهم تمدید بیمه‌نامه
            </h3>
            
            <ul class="list-disc mr-6 space-y-2 text-red-800">
                <li>بیمه‌هایی که تاریخ انقضای آنها گذشته است، با رنگ قرمز نمایش داده می‌شوند و نیاز به تمدید فوری دارند.</li>
                <li>بیمه‌هایی که کمتر از 30 روز به انقضای آنها باقی مانده است، با رنگ نارنجی نمایش داده می‌شوند.</li>
                <li>جهت تمدید بیمه‌نامه‌ها، خانواده‌های مورد نظر را انتخاب کرده و روی دکمه «تمدید بیمه‌نامه» کلیک کنید.</li>
                <li>در صورت عدم تمدید به موقع، ممکن است برخی مزایای بیمه‌ای از دست برود یا مشمول جریمه شود.</li>
            </ul>
        </div>
        
        {{-- نمایش فیلدهای انتخاب و تعداد انتخاب شده‌ها --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center">
                <input type="checkbox" id="select-all-renewal" wire:model="selectAll" class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                <label for="select-all-renewal" class="mr-3 text-gray-700">انتخاب همه</label>
                
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
                    @php
                        $daysLeft = $family->insurance_expiry_date ? now()->diffInDays($family->insurance_expiry_date, false) : null;
                        $cardClass = '';
                        
                        if ($daysLeft !== null) {
                            if ($daysLeft <= 0) {
                                $cardClass = 'border-red-300 bg-red-50';
                            } elseif ($daysLeft <= 30) {
                                $cardClass = 'border-orange-300 bg-orange-50';
                            }
                        }
                    @endphp
                    
                    <div class="family-card bg-white border border-gray-200 rounded-lg p-6 {{ $cardClass }} {{ in_array($family->id, $selected) ? 'ring-2 ring-indigo-500' : '' }}">
                        {{-- هدر کارت --}}
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="family-renewal-{{ $family->id }}" value="{{ $family->id }}" wire:model="selected" class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <h3 class="mr-3 text-lg font-bold text-gray-800">خانواده {{ $family->last_name }}</h3>
                            </div>
                            
                            <div class="flex items-center">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">
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
                            
                            @if($family->insurance_type)
                                <div class="flex items-center text-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <span>{{ $family->insurance_type->title ?? '-' }}</span>
                                </div>
                            @endif
                            
                            {{-- اطلاعات بیمه‌نامه --}}
                            <div class="grid grid-cols-2 gap-2 pt-3 mt-2 border-t border-gray-100">
                                @if($family->insurance_number)
                                    <div class="col-span-2">
                                        <span class="text-sm text-gray-500">شماره بیمه‌نامه:</span>
                                        <span class="text-sm font-bold text-gray-800 mr-1">{{ $family->insurance_number }}</span>
                                    </div>
                                @endif
                                
                                @if($family->insurance_issue_date)
                                    <div class="col-span-2">
                                        <span class="text-sm text-gray-500">تاریخ صدور:</span>
                                        <span class="text-sm text-gray-800 mr-1">{{ verta($family->insurance_issue_date)->formatJalaliDate() }}</span>
                                    </div>
                                @endif
                                
                                @if($family->insurance_expiry_date)
                                    <div class="col-span-2">
                                        <span class="text-sm text-gray-500">تاریخ انقضا:</span>
                                        <span class="text-sm text-gray-800 mr-1">{{ verta($family->insurance_expiry_date)->formatJalaliDate() }}</span>
                                        
                                        @if($daysLeft !== null)
                                            @if($daysLeft <= 0)
                                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">منقضی شده</span>
                                            @elseif($daysLeft <= 30)
                                                <span class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded-full">{{ $daysLeft }} روز مانده</span>
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        {{-- دکمه‌های عملیاتی --}}
                        <div class="flex justify-between pt-4 border-t border-gray-200">
                            <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 toggle-family-btn" data-family-id="{{ $family->id }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                                نمایش اعضای خانواده
                            </button>
                            
                            <div class="flex gap-2">
                                <button type="button" class="inline-flex items-center px-3 py-2 border border-indigo-500 rounded text-sm font-medium text-indigo-700 bg-white hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                                       wire:click="selectForRenewal({{ $family->id }})">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    تمدید بیمه
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <p class="mt-4 text-lg font-medium text-gray-600">موردی برای نمایش وجود ندارد</p>
                        <p class="mt-2 text-gray-500">در حال حاضر هیچ خانواده‌ای در مرحله تمدید نمی‌باشد.</p>
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
    
    {{-- مودال تمدید بیمه‌نامه --}}
    <div x-data="{ showRenewalModal: false }" x-cloak>
        <div x-show="showRenewalModal" class="modal-container" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
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
    </div>
    
    @push('scripts')
    <script>
        document.addEventListener('livewire:load', function () {
            window.addEventListener('openRenewalModal', () => {
                window.showRenewalModal = true;
            });
            
            window.addEventListener('closeRenewalModal', () => {
                window.showRenewalModal = false;
            });
            
            Livewire.on('renewalComplete', () => {
                window.showRenewalModal = false;
                // نمایش پیام موفقیت با استفاده از تابع toast
                if (typeof showToast === 'function') {
                    showToast('تمدید بیمه‌نامه با موفقیت انجام شد.', 'success');
                }
            });
        });
    </script>
    @endpush
</div> 