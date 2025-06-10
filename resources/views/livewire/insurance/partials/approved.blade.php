{{-- بخش "تایید شده" --}}
<div>
    {{-- نمایش اطلاعات و دکمه‌های عملیاتی --}}
    <div class="bg-white rounded-xl shadow p-6 mb-8">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">لیست خانواده‌های تایید شده</h2>
                <p class="text-gray-600 mt-1">در این بخش خانواده‌هایی که نهایی شده و آماده صدور بیمه‌نامه هستند را مشاهده می‌کنید.</p>
            </div>
            
            <div class="flex flex-wrap gap-3">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 disabled:opacity-50"
                        onclick="updateFamiliesStatus($wire.selected, 'excel', 'approved')" 
                        {{ count($selected) === 0 ? 'disabled' : '' }}>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    انتقال به مرحله آپلود اکسل
                    <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                </button>
                
                <button type="button" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors duration-200 disabled:opacity-50"
                        onclick="updateFamiliesStatus($wire.selected, 'reviewing', 'approved')" 
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
                <input type="checkbox" id="select-all-approved" wire:model="selectAll" class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                <label for="select-all-approved" class="mr-3 text-gray-700">انتخاب همه</label>
                
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
                                <input type="checkbox" id="family-approved-{{ $family->id }}" value="{{ $family->id }}" wire:model="selected" class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <h3 class="mr-3 text-lg font-bold text-gray-800">خانواده {{ $family->last_name }}</h3>
                            </div>
                            
                            <div class="flex items-center">
                                @if($family->share_percentage)
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs ml-2">
                                        {{ $family->share_percentage }}%
                                    </span>
                                @endif
                                
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
                            
                            {{-- نمایش اطلاعات اضافی --}}
                            <div class="grid grid-cols-2 gap-2 pt-3 mt-2 border-t border-gray-100">
                                <div class="col-span-2">
                                    <span class="text-sm text-gray-500">تاریخ تایید:</span>
                                    <span class="text-sm text-gray-800">
                                        {{ $family->approved_at ? verta($family->approved_at)->formatJalaliDate() : '-' }}
                                    </span>
                                </div>
                                
                                <div class="col-span-2">
                                    <span class="text-sm text-gray-500">تاییدکننده:</span>
                                    <span class="text-sm text-gray-800">
                                        {{ $family->approved_by ? $family->approver->name ?? '-' : '-' }}
                                    </span>
                                </div>
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
                                <button type="button" 
                                      class="inline-flex items-center px-3 py-2 border border-purple-500 rounded text-sm font-medium text-purple-700 bg-white hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors duration-200"
                                      wire:click="updateFamiliesStatus([{{ $family->id }}], 'excel', 'approved')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    آپلود اکسل
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-4 text-lg font-medium text-gray-600">موردی برای نمایش وجود ندارد</p>
                        <p class="mt-2 text-gray-500">در حال حاضر هیچ خانواده‌ای در مرحله تایید شده نمی‌باشد.</p>
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

    <script>
        function excelUpload(familyId) {
            let familyIds = [familyId];
            updateFamiliesStatus(familyIds, 'excel', 'approved');
        }
    </script>
</div> 