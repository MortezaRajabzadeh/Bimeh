<!-- کارت اصلی -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- هدر -->
    <div class="border-b border-gray-100 p-6">
        <div class="flex justify-between items-center">
            <div class="text-lg font-bold text-gray-800">اطلاعات اعضای خانوار</div>
            <div class="text-sm text-gray-500">تعداد اعضا: {{ count($members) }}</div>
        </div>
    </div>

    <div class="p-6">
        <!-- راهنمای فیلدها -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <div class="flex items-center gap-4 text-sm text-gray-600">
                <div class="w-8 text-center">سرپرست</div>
                <div class="w-32 text-center">نسبت <span class="text-red-500">*</span></div>
                <div class="flex-1 text-center">نام <span class="text-red-500">*</span></div>
                <div class="flex-1 text-center">نام خانوادگی <span class="text-red-500">*</span></div>
                <div class="w-32 text-center">تاریخ تولد</div>
                <div class="w-32 text-center">کد ملی <span class="text-red-500">*</span></div>
                <div class="w-32 text-center">شغل</div>
                <div class="w-32 text-center">نوع مشکل</div>
                <div class="w-8 text-center">حذف</div>
            </div>
        </div>

        <!-- پیام خطای کلی -->
        @if(session()->has('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center text-red-600">
                    <svg class="w-5 h-5 ml-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- فرم ورودی -->
        <div class="space-y-4">
            @foreach($members as $index => $member)
                <div class="flex items-center gap-4 bg-gray-50/50 p-4 rounded-lg hover:bg-gray-50 transition-colors duration-150 relative">
                    <!-- نشانگر سرپرست -->
                    @if($head_member_index === $index)
                        <div class="absolute -right-2 top-1/2 -translate-y-1/2 bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                            سرپرست
                        </div>
                    @endif

                    <div class="w-8">
                        <input type="radio" wire:model="head_member_index" name="head_member_index" value="{{ $index }}" 
                               class="w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500">
                    </div>
                    
                    <div class="w-32">
                        <select wire:model="members.{{ $index }}.relationship"
                                class="w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500 bg-white @error('members.'.$index.'.relationship') border-red-300 @enderror">
                            <option value="">عضو خانواده</option>
                            <option value="spouse">همسر</option>
                            <option value="child">فرزند</option>
                            <option value="parent">والدین</option>
                            <option value="sibling">خواهر/برادر</option>
                            <option value="other">سایر</option>
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
                            <input type="text" 
                                   wire:model.debounce.500ms="members.{{ $index }}.birth_date" 
                                   class="w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500 text-center bg-white cursor-pointer" 
                                   placeholder="انتخاب تاریخ"
                                   data-jdp
                                   data-jdp-only-date
                                   autocomplete="off"
                                   readonly>
                            <div class="absolute inset-y-0 left-2 flex items-center text-gray-400 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="w-32">
                        <input type="text" wire:model.debounce.500ms="members.{{ $index }}.national_code" 
                               class="w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500 text-center bg-white @error('members.'.$index.'.national_code') border-red-300 @enderror" 
                               placeholder="0123456789">
                        @error('members.'.$index.'.national_code')
                            <div class="absolute mt-1 text-red-500 text-xs bg-white p-1 rounded shadow-sm border border-red-100">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="w-32">
                        <input type="text" wire:model.debounce.500ms="members.{{ $index }}.occupation" 
                               class="w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500 bg-white" 
                               placeholder="شغل">
                    </div>

                    <div class="w-32">
                        <div x-data="{
                                problems: null,
                                options: ['اعتیاد', 'بیماری خاص', 'از کار افتادگی', 'معلولیت'],
                                search: '',
                                open: false,
                                dropUp: false,
                                dropdownId: 'dropdown-' + {{ $index }},
                                init() {
                                    if (!Array.isArray(this.problems)) {
                                        this.$nextTick(() => {
                                            this.problems = [];
                                            this.$wire.set('members.' + {{$index}} + '.problem_type', []);
                                        });
                                    }

                                    this.$watch('problems', (value) => {
                                        if (!Array.isArray(value)) {
                                            this.problems = [];
                                            this.$wire.set('members.' + {{$index}} + '.problem_type', []);
                                        }
                                    });

                                    // اضافه کردن event listener برای بستن dropdown با کلیک خارج از آن
                                    document.addEventListener('click', (e) => {
                                        if (!this.$el.contains(e.target)) {
                                            this.close();
                                        }
                                    });
                                },
                                toggle($el) {
                                    // بستن سایر dropdownها
                                    this.$root.querySelectorAll('[x-data]').forEach(el => {
                                        if (el !== this.$el && el.__x) {
                                            el.__x.getUnobservedData().open = false;
                                        }
                                    });
                                    
                                    this.open = !this.open;
                                    
                                    if (this.open) {
                                        this.search = '';
                                        this.$nextTick(() => {
                                            const rect = $el.getBoundingClientRect();
                                            const spaceBelow = window.innerHeight - rect.bottom;
                                            const spaceAbove = rect.top;
                                            this.dropUp = spaceBelow < 200 && spaceAbove > 200;
                                            
                                            // انتقال dropdown به body
                                            const dropdown = this.$refs.dropdown;
                                            document.body.appendChild(dropdown);
                                            
                                            // تنظیم موقعیت
                                            dropdown.style.position = 'fixed';
                                            dropdown.style.width = rect.width + 'px';
                                            dropdown.style.left = rect.left + 'px';
                                            
                                            if (this.dropUp) {
                                                dropdown.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
                                                dropdown.style.top = 'auto';
                                            } else {
                                                dropdown.style.top = (rect.bottom + 4) + 'px';
                                                dropdown.style.bottom = 'auto';
                                            }
                                        });
                                    }
                                },
                                close() {
                                    if (this.open) {
                                        const dropdown = this.$refs.dropdown;
                                        if (dropdown && dropdown.parentElement === document.body) {
                                            this.$el.appendChild(dropdown);
                                        }
                                        this.open = false;
                                        this.search = '';
                                    }
                                },
                                isSelected(value) { 
                                    return Array.isArray(this.problems) && this.problems.includes(value);
                                },
                                addProblem() {
                                    if (!this.search.trim()) return;
                                    
                                    if (!Array.isArray(this.problems)) {
                                        this.problems = [];
                                    }
                                    
                                    const newValue = this.search.trim();
                                    if (!this.problems.includes(newValue)) {
                                        this.problems.push(newValue);
                                        this.$wire.set('members.'+{{$index}}+'.problem_type', this.problems);
                                    }
                                    
                                    this.search = '';
                                },
                                removeProblem(value) {
                                    if (!Array.isArray(this.problems)) return;
                                    
                                    this.problems = this.problems.filter(p => p !== value);
                                    this.$wire.set('members.'+{{$index}}+'.problem_type', this.problems);
                                }
                            }"
                            class="relative">
                            
                            <div @click="toggle($el)" 
                                 class="w-full border border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500 bg-white cursor-pointer">
                                <div class="max-h-[70px] overflow-y-auto p-1">
                                    <div x-show="Array.isArray(problems) && problems.length > 0" class="flex flex-wrap gap-1">
                                        <template x-for="problem in problems" :key="problem">
                                            <span class="inline-flex items-center bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded whitespace-nowrap">
                                                <span x-text="problem"></span>
                                                <button @click.stop="removeProblem(problem)" class="mr-1 text-green-600 hover:text-green-900">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>
                                    </div>
                                    <div x-show="!Array.isArray(problems) || problems.length === 0" class="text-gray-400 py-1 px-2">
                                        انتخاب کنید
                                    </div>
                                </div>
                            </div>
                            
                            <div x-ref="dropdown"
                                 x-show="open" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 class="absolute left-0 w-full bg-white border border-gray-300 rounded-md shadow-lg"
                                 style="z-index: 9999;">
                                <div class="p-2 border-b">
                                    <input type="text" 
                                           x-model="search" 
                                           @keydown.enter.prevent="addProblem()"
                                           @click.stop
                                           class="w-full border-gray-300 rounded-md text-sm focus:ring-green-500 focus:border-green-500"
                                           placeholder="جستجو یا افزودن مورد جدید...">
                                </div>
                                <div class="max-h-[200px] overflow-y-auto">
                                    <ul class="py-1">
                                        <template x-for="option in options.filter(opt => opt.toLowerCase().includes(search.toLowerCase()))" :key="option">
                                            <li @click.stop="
                                                if(!isSelected(option)) {
                                                    if(!Array.isArray(problems)) problems = [];
                                                    problems.push(option);
                                                    this.$wire.set('members.'+{{$index}}+'.problem_type', problems);
                                                } else {
                                                    removeProblem(option);
                                                }"
                                                class="px-3 py-2 cursor-pointer hover:bg-gray-100"
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach

            <!-- دکمه اضافه کردن -->
            <button type="button" wire:click="addMember" 
                    class="w-full flex items-center justify-center gap-2 p-4 text-green-700 hover:text-green-800 hover:bg-green-50 rounded-lg transition-colors duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>افزودن عضو جدید</span>
            </button>
        </div>

        @if(!isset($head_member_index) && count($members) > 0)
            <div class="mt-4 p-4 bg-yellow-50 text-yellow-800 rounded-lg border border-yellow-200">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-yellow-400 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>لطفاً سرپرست خانوار را مشخص کنید</span>
                </div>
            </div>
        @endif

    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:load', function () {
        // تنظیمات تقویم جلالی
        const initJalaliDatepicker = () => {
            const dateInputs = document.querySelectorAll('[data-jdp]');
            dateInputs.forEach(input => {
                if (!input.hasAttribute('data-jdp-initialized')) {
                    jalaliDatepicker.startWatch({
                        minDate: "attr",
                        maxDate: "attr",
                        selector: input,
                        time: false,
                        onSelect: function(selected) {
                            input.dispatchEvent(new Event('input'));
                        }
                    });
                    input.setAttribute('data-jdp-initialized', 'true');
                }
            });
        };

        // اجرای اولیه
        initJalaliDatepicker();

        // اجرای مجدد بعد از آپدیت Livewire
        Livewire.hook('message.processed', (message, component) => {
            initJalaliDatepicker();
        });
    });
</script>
@endpush 