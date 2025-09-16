<!-- کارت اصلی -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-visible" x-data="{
    init() {
        $watch('$wire.head_member_index', value => {
            console.log('Head member index changed:', value);
        });
    }
}">
    <!-- هدر -->
    <div class="border-b border-gray-100 p-6">
        <div class="flex justify-between items-center">
            <div class="text-lg font-bold text-gray-800">اطلاعات اعضای خانوار</div>
            <div class="text-sm text-gray-500">تعداد اعضا: {{ count($members) }}</div>
        </div>
    </div>

    <div class="p-6 relative overflow-visible">
        <!-- راهنمای فرم -->
        <div class="bg-blue-50 p-4 rounded-lg mb-6 border border-blue-100">
            <div class="flex items-start gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <circle cx="12" cy="12" r="10" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01" />
                </svg>
                <div>
                    <div class="text-sm font-medium text-blue-800 mb-1">راهنمای تکمیل فرم</div>
                    <ul class="text-xs text-blue-700 space-y-1">
                        <li>• لطفاً یکی از اعضا را به عنوان سرپرست خانوار انتخاب کنید (با انتخاب دکمه رادیویی)</li>
                        <li>• فقط برای سرپرست خانوار، فیلدهای «شماره تماس» و «شماره شبا» نمایش داده می‌شود</li>
                        <li>• موارد ستاره‌دار (<span class="text-red-500">*</span>) الزامی هستند</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- راهنمای فیلدها -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6 overflow-visible relative z-10">
            <div class="flex items-center gap-4 text-sm text-gray-600">
                <div class="w-8 text-center">سرپرست</div>
                <div class="w-32 text-center">نسبت <span class="text-red-500">*</span></div>
                <div class="flex-1 text-center">نام <span class="text-red-500">*</span></div>
                <div class="flex-1 text-center">نام خانوادگی <span class="text-red-500">*</span></div>
                <div class="w-32 text-center">تاریخ تولد</div>
                <div class="w-32 text-center">کد ملی <span class="text-red-500">*</span></div>
                <div class="w-32 text-center">وضعیت شغلی</div>
                <div class="w-32 text-center">معیار پذیرش</div>
                <div class="w-8 text-center">حذف</div>
            </div>
        </div>

        <!-- پیام خطای کلی -->
        @if(session()->has('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg overflow-visible">
                <div class="flex items-center text-red-600">
                    <svg class="w-5 h-5 ml-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- فرم ورودی -->
        <div class="space-y-4 overflow-visible relative z-20">
            @foreach($members as $index => $member)
                <div class="flex items-center gap-4 bg-gray-50/50 p-4 rounded-lg hover:bg-gray-50 transition-colors duration-150 relative overflow-visible group {{ (string)$head_member_index === (string)$index ? 'border-r-4 border-green-500 bg-green-50/40' : '' }}">
                    @if((string)$head_member_index === (string)$index)
                        <div class="absolute -right-2 top-1/2 -translate-y-1/2 bg-green-500 text-white text-xs px-2 py-1 rounded-full hidden">
                            سرپرست
                        </div>
                    @endif
                    <div class="w-8">
                        <input type="radio"
                               wire:model="head_member_index"
                               wire:change="$refresh"
                               name="head_member_index"
                               value="{{ $index }}"
                               class="w-4 h-4 {{ (string)$head_member_index === (string)$index ? 'text-green-600 ring-2 ring-offset-2 ring-green-500' : 'text-gray-600' }} border-gray-300 focus:ring-green-500">
                        @if((string)$head_member_index === (string)$index)
                            <div class="mt-1 text-xs text-green-600 absolute hidden">سرپرست</div>
                        @else
                            <div class="mt-1 text-xs text-gray-400 absolute opacity-0 group-hover:opacity-100 transition-opacity duration-200">انتخاب</div>
                        @endif
                    </div>
                    <div class="w-32 relative">
                        <select wire:model="members.{{ $index }}.relationship"
                                class="w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500 bg-white custom-select @error('members.'.$index.'.relationship') border-red-300 @enderror">
                            <option value="">عضو خانواده</option>
                            <option value="مادر">مادر</option>
                            <option value="پدر">پدر</option>
                            <option value="زن">زن</option>
                            <option value="مرد">مرد</option>
                            <option value="پسر">پسر</option>
                            <option value="دختر">دختر</option>
                            <option value="مادربزرگ">مادربزرگ</option>
                            <option value="پدربزرگ">پدربزرگ</option>
                            <option value="سایر">سایر</option>
                        </select>
                        @error('members.'.$index.'.relationship') 
                            <div class="absolute mt-1 text-red-500 text-xs bg-white p-1 rounded shadow-sm border border-red-100">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="flex-1">
                        <input type="text" wire:model.debounce.500ms="members.{{ $index }}.first_name" 
                               class="w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500 bg-white @error('members.'.$index.'.first_name') border-red-300 @enderror" 
                               placeholder="نام">
                        @error('members.'.$index.'.first_name')
                            <div class="absolute mt-1 text-red-500 text-xs bg-white p-1 rounded shadow-sm border border-red-100">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="flex-1">
                        <input type="text" wire:model.debounce.500ms="members.{{ $index }}.last_name" 
                               class="w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500 bg-white @error('members.'.$index.'.last_name') border-red-300 @enderror" 
                               placeholder="نام خانوادگی">
                        @error('members.'.$index.'.last_name')
                            <div class="absolute mt-1 text-red-500 text-xs bg-white p-1 rounded shadow-sm border border-red-100">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <div class="w-32">
                        <div class="relative">
                            <input
                                type="text"
                                wire:model.defer="members.{{ $index }}.birth_date"
                                id="birthDate_{{ $index }}"
                                class="w-full border border-gray-300 rounded-lg px-2 py-1 text-center bg-white cursor-pointer jalali-datepicker"
                                placeholder="انتخاب تاریخ"
                                autocomplete="off"
                                data-jdp
                                readonly
                            >
                            <div class="absolute inset-y-0 left-2 flex items-center text-gray-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="w-32">
                        <input type="text" 
                               wire:model.live.debounce.500ms="members.{{ $index }}.national_code" 
                               wire:blur="checkNationalCodeOnChange({{ $index }}, $event.target.value)"
                               class="w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500 text-center bg-white @error('members.'.$index.'.national_code') border-red-300 bg-red-50 @enderror" 
                               placeholder="0123456789"
                               maxlength="10">
                        @error('members.'.$index.'.national_code')
                            <div class="absolute mt-1 text-red-500 text-xs bg-white p-2 rounded shadow-lg border border-red-200 z-50 max-w-xs">
                                <div class="flex items-center">
                                    <svg class="w-3 h-3 ml-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-right">{{ $message }}</span>
                                </div>
                                <button type="button" 
                                        wire:click="clearNationalCodeError({{ $index }})"
                                        class="mt-1 text-xs text-blue-600 hover:text-blue-800 underline">
                                    پاک کردن خطا
                                </button>
                            </div>
                        @enderror
                    </div>
                    <div class="w-32">
                        <div x-data="{
                                selectedStatus: '',
                                specificJob: '',
                                memberIndex: {{$index}},
                                statusOptions: ['شاغل', 'بیکار', 'محصل', 'دانشجو', 'از کار افتاده', 'ترک تحصیل', 'خانه دار'],
                                init() {
                                    // مقداردهی اولیه از Livewire
                                    this.$nextTick(() => {
                                        const currentValue = this.$wire.get('members.'+this.memberIndex+'.occupation');
                                        if (currentValue) {
                                            // بررسی اینکه آیا مقدار فعلی یکی از گزینه‌های اصلی است
                                            if (this.statusOptions.includes(currentValue)) {
                                                this.selectedStatus = currentValue;
                                            } else {
                                                // اگر مقدار خاصی است، آن را در فیلد شغل خاص قرار می‌دهیم
                                                this.selectedStatus = 'شاغل';
                                                this.specificJob = currentValue;
                                            }
                                        }
                                    });

                                    // آپدیت مقادیر وقتی از Livewire تغییر می‌کند
                                    this.$wire.on('updatedOccupation', (data) => {
                                        if (data.index === this.memberIndex) {
                                            const value = data.value;
                                            if (this.statusOptions.includes(value)) {
                                                this.selectedStatus = value;
                                                this.specificJob = '';
                                            } else {
                                                this.selectedStatus = 'شاغل';
                                                this.specificJob = value;
                                            }
                                        }
                                    });
                                },
                                updateLivewire() {
                                    let finalValue = this.selectedStatus;
                                    if (this.selectedStatus === 'شاغل' && this.specificJob.trim()) {
                                        finalValue = this.specificJob.trim();
                                    }
                                    this.$wire.set('members.'+this.memberIndex+'.occupation', finalValue);
                                },
                                onStatusChange() {
                                    if (this.selectedStatus !== 'شاغل') {
                                        this.specificJob = '';
                                    }
                                    this.updateLivewire();
                                },
                                onJobInput() {
                                    this.updateLivewire();
                                }
                            }"
                            class="relative">
                            
                            <!-- دراپ‌داون وضعیت شغلی -->
                            <select x-model="selectedStatus" 
                                    @change="onStatusChange()"
                                    class="w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500 bg-white custom-select mb-1">
                                <option value="">انتخاب کنید</option>
                                <template x-for="option in statusOptions" :key="option">
                                    <option :value="option" x-text="option"></option>
                                </template>
                            </select>
                            
                            <!-- فیلد شغل خاص (فقط برای شاغل) -->
                            <div x-show="selectedStatus === 'شاغل'" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95">
                                <input type="text" 
                                       x-model="specificJob"
                                       @input.debounce.500ms="onJobInput()"
                                       class="w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500 bg-white" 
                                       placeholder="مشخص کنید">
                            </div>
                        </div>
                    </div>
                    <div class="w-32">
                        <div x-data="{
                                problems: [],
                                options: ['بیماری خاص', 'اعتیاد', 'از کار افتادگی', 'بیکاری'],
                                open: false,
                                dropUp: false,
                                memberIndex: {{$index}},
                                init() {
                                    // مقداردهی اولیه از Livewire
                                    this.$nextTick(() => {
                                        const initialValue = this.$wire.get('members.'+this.memberIndex+'.problem_type');
                                        if (Array.isArray(initialValue)) {
                                            this.problems = [...initialValue];
                                        } else {
                                            this.problems = [];
                                            this.$wire.set('members.'+this.memberIndex+'.problem_type', []);
                                        }
                                    });

                                    // آپدیت مقادیر وقتی از Livewire تغییر می‌کند
                                    this.$wire.on('updatedProblems', (data) => {
                                        if (data.index === this.memberIndex) {
                                            this.problems = Array.isArray(data.value) ? [...data.value] : [];
                                        }
                                    });

                                    document.addEventListener('click', (e) => {
                                        if (!this.$el.contains(e.target)) {
                                            this.close();
                                        }
                                    });
                                },
                                updateLivewire() {
                                    this.$wire.set('members.'+this.memberIndex+'.problem_type', this.problems);
                                },
                                updateDropdownPosition() {
                                    const dropdown = this.$refs.dropdown;
                                    if (!dropdown || !this.open) return;

                                    const containerRect = this.$el.getBoundingClientRect();
                                    const spaceBelow = window.innerHeight - containerRect.bottom;
                                    const spaceAbove = containerRect.top;
                                    
                                    if (spaceBelow < 200 && spaceAbove > 200) {
                                        this.dropUp = true;
                                    } else {
                                        this.dropUp = false;
                                    }
                                },
                                toggle() {
                                    this.open = !this.open;
                                    
                                    if (this.open) {
                                        this.$nextTick(() => {
                                            this.updateDropdownPosition();
                                            window.addEventListener('scroll', () => this.updateDropdownPosition(), { passive: true });
                                            window.addEventListener('resize', () => this.updateDropdownPosition(), { passive: true });
                                        });
                                    } else {
                                        window.removeEventListener('scroll', () => this.updateDropdownPosition());
                                        window.removeEventListener('resize', () => this.updateDropdownPosition());
                                    }
                                },
                                close() {
                                    if (this.open) {
                                        this.open = false;
                                        window.removeEventListener('scroll', () => this.updateDropdownPosition());
                                        window.removeEventListener('resize', () => this.updateDropdownPosition());
                                    }
                                },
                                isSelected(value) { 
                                    return Array.isArray(this.problems) && this.problems.includes(value);
                                },
                                toggleProblem(value) {
                                    if (this.isSelected(value)) {
                                        this.problems = this.problems.filter(p => p !== value);
                                    } else {
                                        this.problems.push(value);
                                    }
                                    this.updateLivewire();
                                    
                                    // refresh Livewire برای به‌روزرسانی Alpine.js
                                    this.$wire.$refresh();
                                },
                                removeProblem(value) {
                                    this.problems = this.problems.filter(p => p !== value);
                                    this.updateLivewire();
                                    this.$nextTick(() => this.updateDropdownPosition());
                                }
                            }"
                            class="relative dropdown-problem">
                            
                            <div @click="toggle()" 
                                 class="w-full border border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500 bg-white cursor-pointer">
                                <div class="h-[36px] overflow-y-auto problem-tags">
                                    <div class="p-2">
                                        <template x-if="Array.isArray(problems) && problems.length">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <template x-for="problem in problems" :key="problem">
                                                    <span x-transition.opacity.duration.150ms
                                                          class="inline-flex items-center bg-green-100 text-green-800 text-xs leading-none px-1.5 py-1 rounded whitespace-nowrap">
                                                        <span x-text="problem"></span>
                                                        <button @click.stop="removeProblem(problem)" class="mr-1 text-green-600 hover:text-green-900 ml-1">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                            </svg>
                                                        </button>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                        <div x-show="!Array.isArray(problems) || !problems.length" 
                                             class="text-gray-400 text-sm">
                                            معیار پذیرش
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div x-ref="dropdown"
                                 x-show="open" 
                                 @click.away="close()"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 :class="{ 'bottom-full mb-1': dropUp, 'top-full mt-1': !dropUp }"
                                 class="absolute left-0 right-0 bg-white border border-gray-300 rounded-md shadow-xl p-2"
                                 style="min-width: 100%; z-index: 99999;">
                                <div class="max-h-[200px] overflow-y-auto">
                                    <ul class="space-y-1">
                                        <template x-for="option in options" :key="option">
                                            <li @click.stop="toggleProblem(option)"
                                                class="px-3 py-2 cursor-pointer hover:bg-gray-100 rounded-md text-sm"
                                                :class="{ 'bg-green-50': isSelected(option) }">
                                                <span x-text="option"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="w-8">
                        <button type="button" wire:click="removeMember({{ $index }})" 
                                class="text-red-500 hover:text-red-700 transition-colors duration-150"
                                title="حذف عضو">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>

                </div>
                <!-- فیلدهای مخصوص سرپرست با نمایش بهتر -->
                <div id="head-fields-{{ $index }}" 
                     class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2 w-full animate-fade-in bg-green-50 p-3 rounded-lg border border-green-200" 
                     x-data="{}"
                     x-show="$wire.head_member_index == {{ $index }}">
                    <div class="relative">
                        <div class="flex items-center mb-1">
                            <label class="block text-sm text-gray-800 font-medium">شماره موبایل</label>
                            <span class="mr-1 text-red-500">*</span>
                            <div class="mr-2 group relative">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600 cursor-help" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01" />
                                </svg>
                                <div class="absolute left-0 bottom-6 w-48 bg-white shadow-lg rounded-lg p-2 text-xs text-gray-600 hidden group-hover:block z-50 border border-gray-200">
                                    شماره موبایل برای ارسال پیامک‌های اطلاع‌رسانی استفاده می‌شود.
                                </div>
                            </div>
                        </div>
                        <input type="text"
                               wire:model="members.{{ $index }}.mobile"
                               class="w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500 bg-white"
                               placeholder="مثلاً 0912xxxxxxx">
                    </div>
                    <!-- Removed fixed phone number field as requested -->
                    <div class="relative">
                        <div class="flex items-center mb-1">
                            <label class="block text-sm text-gray-800 font-medium">شماره شبا</label>
                            <span class="mr-1 text-red-500">*</span>
                            <div class="mr-2 group relative">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600 cursor-help" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <circle cx="12" cy="12" r="10" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-4m0-4h.01" />
                                </svg>
                                <div class="absolute left-0 bottom-6 w-48 bg-white shadow-lg rounded-lg p-2 text-xs text-gray-600 hidden group-hover:block z-50 border border-gray-200">
                                    شماره شبا برای واریز کمک‌های مالی و پرداخت خسارت بیمه استفاده می‌شود.
                                </div>
                            </div>
                        </div>
                        <input type="text"
                               wire:model="members.{{ $index }}.sheba"
                               class="w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500 bg-white ltr text-left"
                               placeholder="IRxxxxxxxxxxxxxx"
                               dir="ltr">
                        <div class="mt-1 text-xs text-green-600">
                            شماره شبا باید با IR شروع شود و بدون خط تیره یا فاصله وارد شود
                        </div>
                    </div>
                </div>

                {{-- فیلد آپلود مدرک بیماری خاص فقط اگر معیار پذیرش انتخاب شده باشد --}}
                @if(is_array($member['problem_type'] ?? null) && in_array('بیماری خاص', $member['problem_type']))
                    <div class="mt-2 w-full bg-red-50 p-3 rounded-lg border border-red-200 file-upload-section">
                        <div class="flex items-center mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <label class="block text-sm text-red-800 font-medium">مدرک بیماری خاص</label>
                            <span class="mr-1 text-red-500">*</span>
                        </div>
                        
                        {{-- نمایش فایل آپلود شده (در صورت وجود) --}}
                        @if(isset($uploadedDocuments[$index]))
                            <div class="mb-3 flex items-center p-2 bg-green-100 rounded-lg uploaded-file-info">
                                <svg class="h-5 w-5 text-green-500 ml-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-green-800 truncate">
                                        فایل آپلود شده: {{ $uploadedDocuments[$index]['original_name'] }}
                                    </div>
                                    <div class="text-xs text-green-600">
                                        حجم: {{ round($uploadedDocuments[$index]['size'] / 1024) }} کیلوبایت
                                    </div>
                                </div>
                                <button type="button" wire:click="removeDocument({{ $index }})" 
                                        class="text-red-500 hover:text-red-700 transition-colors duration-150 ml-2 p-1 rounded hover:bg-red-200"
                                        title="حذف فایل">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        @endif
                        
                        {{-- فیلد آپلود (همیشه نمایش داده می‌شود) --}}
                        <div class="space-y-2">
                            @if(isset($uploadedDocuments[$index]))
                                <div class="text-xs text-blue-600 font-medium">
                                    آپلود فایل جدید (جایگزین فایل فعلی):
                                </div>
                            @endif
                            
                            <input type="file"
                                   wire:model="specialDiseaseDocuments.{{ $index }}"
                                   accept=".pdf,.jpg,.jpeg,.png"
                                   class="w-full border border-gray-300 rounded-md text-sm focus:ring-red-500 focus:border-red-500 bg-white @error('specialDiseaseDocuments.'.$index) border-red-300 @enderror">
                            
                            <div class="mt-1 text-xs text-red-600">
                                فرمت‌های مجاز: PDF، JPG، PNG (حداکثر 5 مگابایت)
                            </div>
                            
                            @error('specialDiseaseDocuments.'.$index)
                                <div class="mt-1 text-xs text-red-500 bg-red-50 p-2 rounded border border-red-200">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                @endif
            @endforeach
            <!-- دکمه اضافه کردن -->
            <button type="button" wire:click="addMember" 
                    class="w-full flex items-center justify-center gap-2 p-4 text-green-700 hover:text-green-800 hover:bg-green-50 rounded-lg transition-colors duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M12 4v16M4 12h16" />
                </svg>
                <span>افزودن عضو جدید</span>
            </button>
        </div>
        @if(!isset($head_member_index) && count($members) > 0)
            <div class="mt-4 p-4 bg-yellow-50 text-yellow-800 rounded-lg border border-yellow-200">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 19a7 7 0 100-14 7 7 0 000 14z" />
                    </svg>
                    <span>لطفاً سرپرست خانوار را مشخص کنید</span>
                </div>
            </div>
        @endif
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/custom-select.css') }}">
@endpush

@push('scripts')
<script src="/vendor/jalalidatepicker/jalalidatepicker.min.js"></script>
<script>
    function initJalaliDatepicker() {
        jalaliDatepicker.startWatch({
            selector: '.jalali-datepicker',
            minDate: '1380/01/01',
            maxDate: '1420/12/29',
            autoClose: true,
            format: 'YYYY/MM/DD',
            theme: 'green',
            showTodayBtn: true,
            showEmptyBtn: true,
            todayBtnText: 'امروز',
            emptyBtnText: 'خالی',
            locale: 'fa',
            enableTimePicker: false,
            calendarType: 'persian'
        });
    }
    
    document.addEventListener('livewire:load', function () {
        initJalaliDatepicker();
        
        // مدیریت انتخاب سرپرست
        Livewire.on('headMemberChanged', function(index) {
            console.log('Head member changed to:', index);
            
            // به‌روزرسانی Alpine.js
            Livewire.first().updateHeadMemberIndex(index);
        });
    });
    
    document.addEventListener('DOMContentLoaded', function () {
        initJalaliDatepicker();
        
        // اطمینان از نمایش صحیح فیلدهای سرپرست در بارگذاری اولیه
        setTimeout(function() {
            const currentHead = document.querySelector('input[name="head_member_index"]:checked');
            if (currentHead) {
                const index = currentHead.value;
                const headFields = document.getElementById('head-fields-' + index);
                if (headFields) {
                    headFields.style.display = 'grid';
                }
            }
        }, 200);
    });
    
    // برای بروزرسانی‌های Livewire
    document.addEventListener('livewire:init', function () {
        setTimeout(initJalaliDatepicker, 100);
    });
    
    window.addEventListener('livewire:navigated', function () {
        setTimeout(initJalaliDatepicker, 200);
    });
    
    document.addEventListener('livewire:update', function () {
        setTimeout(initJalaliDatepicker, 300);
    });
    
    window.addEventListener('refreshJalali', function () {
        initJalaliDatepicker();
    });
</script>
@endpush

@push('styles')
<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-0.25rem); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

/* اصلاح آیکون‌های SVG در محیط RTL */
svg {
    direction: ltr;
    transform-origin: center;
}

/* استایل‌های بهبود یافته برای آپلود فایل */
.file-upload-section {
    transition: all 0.3s ease;
}

.uploaded-file-info {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border: 1px solid #10b981;
}

.uploaded-file-info:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
}

.file-input-replacement {
    border: 2px dashed #d1d5db;
    transition: all 0.2s ease;
}

.file-input-replacement:hover {
    border-color: #6b7280;
    background-color: #f9fafb;
}

.file-input-replacement input[type="file"]:focus + label,
.file-input-replacement:focus-within {
    border-color: #dc2626;
    background-color: #fef2f2;
}
</style>
@endpush
