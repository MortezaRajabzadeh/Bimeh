<div>
    <div class="bg-white rounded-lg shadow p-8">
        <!-- نوار پیشرفت -->
        <div class="mb-8">
            <div class="relative flex justify-between">
                @for ($i = 1; $i <= $totalSteps; $i++)
                    <div class="flex flex-col items-center relative z-10">
                        <button 
                            wire:click="goToStep({{ $i }})" 
                            class="w-14 h-14 rounded-full flex items-center justify-center transition-all duration-500 transform hover:scale-105
                                {{ $currentStep > $i ? 'bg-emerald-500 shadow-lg shadow-emerald-100' : ($currentStep == $i ? 'bg-blue-600 shadow-lg shadow-blue-100' : 'bg-gray-100') }}
                                {{ $currentStep == $i ? 'ring-4 ring-blue-100' : '' }}
                                {{ $i > $currentStep && !$this->canProceedToStep($i) ? 'opacity-50 cursor-not-allowed' : '' }}"
                            @if($i > $currentStep && !$this->canProceedToStep($i)) disabled @endif
                            >
                            @if($currentStep > $i)
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @else
                                <span class="text-{{ $currentStep == $i ? 'white' : 'gray-600' }}">
                                    @switch($i)
                                        @case(1)
                                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            @break
                                        @case(2)
                                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            @break
                                        @case(3)
                                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            @break
                                    @endswitch
                                </span>
                            @endif
                        </button>
                        <span class="mt-3 text-sm font-medium {{ $currentStep == $i ? 'text-blue-600' : ($currentStep > $i ? 'text-emerald-600' : 'text-gray-500') }}">
                            @switch($i)
                                @case(1)
                                    اطلاعات پایه
                                    @break
                                @case(2)
                                    اطلاعات شخصی
                                    @break
                                @case(3)
                                    تأیید نهایی
                                    @break
                            @endswitch
                        </span>
                    </div>
                    
                    @if ($i < $totalSteps)
                        <div class="flex-1 flex items-center">
                            <div class="h-2 w-full rounded-full transition-all duration-500 relative overflow-hidden
                                {{ $currentStep > $i ? 'bg-emerald-500' : ($currentStep == $i ? 'bg-blue-600' : 'bg-gray-200') }}">
                                @if($currentStep == $i)
                                    <div class="absolute inset-0 bg-blue-200 animate-pulse"></div>
                                @endif
                            </div>
                        </div>
                    @endif
                @endfor
            </div>
        </div>
        <br>
        <form wire:submit.prevent="submit">
            <!-- مرحله ۱: اطلاعات خانواده -->
            @if ($currentStep == 1)
                @include('livewire.charity.family-wizard.step1')
            @endif

            <!-- مرحله ۲: اعضای خانواده -->
            @if ($currentStep == 2)
                @include('livewire.charity.family-wizard.step2')
            @endif

            <!-- مرحله ۳: تأیید نهایی -->
            @if ($currentStep == 3)
                @include('livewire.charity.family-wizard.step4')
            @endif


            <br>
            <!-- دکمه‌های پیمایش -->
            <div class="flex justify-between mt-8">
                <div>
                    @if ($currentStep > 1)
                        <button type="button" wire:click="previousStep" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            مرحله قبل
                        </button>
                    @endif
                </div>
                
                <div>
                    @if ($currentStep < $totalSteps)
                        <button type="button" wire:click="nextStep" class="px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                            مرحله بعد
                        </button>
                    @else
                        <button type="submit" class="px-6 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                            ثبت خانواده
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- پیش نمایش کارت شناسایی -->
    <div id="family-card-preview" class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50" 
        x-data="{
            isOpen: false,
            data: null,
            show(data) {
                this.data = data;
                this.isOpen = true;
            },
            hide() {
                this.isOpen = false;
                setTimeout(() => {
                    this.data = null;
                }, 300);
            }
        }" 
        x-show="isOpen" 
        x-cloak
        @preview-card.window="show($event.detail)"
        @click.away="hide"
        @keydown.escape.window="hide">
        <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4" @click.stop>
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">پیش نمایش کارت شناسایی خانواده</h3>
                <button @click="hide" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div id="card-content" class="border rounded-lg p-4">
                <template x-if="data">
                    <div>
                        <div class="flex items-center mb-4">
                            <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center ml-4">
                                <template x-if="data.photo">
                                    <img :src="data.photo" class="w-16 h-16 rounded-full object-cover">
                                </template>
                                <template x-if="!data.photo">
                                    <span class="text-gray-600">تصویر</span>
                                </template>
                            </div>
                            <div>
                                <h4 class="font-bold" x-text="data.head_name || 'بدون نام'"></h4>
                                <p class="text-sm text-gray-600">کد خانواده: <span x-text="data.code || '---'"></span></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <span class="font-bold">آدرس:</span>
                                <span x-text="data.address || '---'"></span>
                            </div>
                            <div>
                                <span class="font-bold">کد پستی:</span>
                                <span x-text="data.postal_code || '---'"></span>
                            </div>
                            <div>
                                <span class="font-bold">تعداد اعضا:</span>
                                <span x-text="data.members_count || 1"></span>
                            </div>
                        </div>
                    </div>
                </template>
                <template x-if="!data">
                    <div class="text-center py-4 text-gray-500">
                        اطلاعات خانواده در دسترس نیست
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- اسکریپت‌های جاوااسکریپت -->
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script type="text/javascript" src="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js"></script>
    <script>
        // نمایش هشدارها و پیام‌ها
        document.addEventListener('livewire:load', function () {
            // تنظیم تقویم برای همه فیلدهای تاریخ
            jalaliDatepicker.startWatch({
                persianDigits: true,
                minDate: "attr",
                maxDate: "attr",
                initDate: null,
                autoHide: true,
                showTodayBtn: true,
                showEmptyBtn: true,
                topSpace: 10,
                bottomSpace: 10,
                zIndex: 1050,
                dayRendering: function(dayOptions, input) {
                    return {
                        isHollyDay: dayOptions.month == 1 && dayOptions.day <= 4
                    }
                }
            });

            // اجرای مجدد بعد از هر آپدیت Livewire
            Livewire.hook('message.processed', (message, component) => {
                setTimeout(() => {
                    jalaliDatepicker.updateOptions({
                        persianDigits: true,
                        minDate: "attr",
                        maxDate: "attr"
                    });
                }, 300);
            });

            window.addEventListener('show-toast', event => {
                alert(event.detail.message);
            });
            
            // نمایش سن محاسبه شده
            window.addEventListener('show-age', event => {
                const age = event.detail.age;
                alert(`سن محاسبه شده: ${age} سال`);
            });

            // اختصاصی مرحله ۱: نقشه
            if (document.getElementById('map')) {
                const map = L.map('map').setView([35.6892, 51.3890], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
                
                let marker;
                
                map.on('click', function(e) {
                    if (marker) {
                        map.removeLayer(marker);
                    }
                    
                    marker = L.marker(e.latlng).addTo(map);
                    
                    // ارسال اطلاعات به کامپوننت Livewire با استفاده از emit
                    Livewire.emit('mapLocationSelected', {
                        lat: e.latlng.lat,
                        lng: e.latlng.lng,
                        address: "آدرس انتخاب شده در نقشه"
                    });
                });
            }
        });
    </script>
    @endpush

    @push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css">
    <style>
        /* سفارشی‌سازی ظاهر تقویم */
        .jdp-container {
            font-family: inherit !important;
            z-index: 1050 !important;
        }
        .jdp-days-container {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 0.5rem;
        }
        .jdp-days td span {
            width: 2.5rem;
            height: 2.5rem;
            line-height: 2.5rem;
            font-size: 0.875rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }
        .jdp-days td span:hover {
            background-color: #10B981 !important;
            color: white;
        }
        .jdp-days td.selected span {
            background-color: #10B981 !important;
            color: white;
        }
        .jdp-days td.holly span {
            color: #EF4444 !important;
        }
        .jdp-months-container,
        .jdp-years-container {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        #map {
            height: 300px;
            width: 100%;
        }
        
        /* استایل برای drag & drop تصاویر */
        .dropzone {
            border: 2px dashed #ddd;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .dropzone.dragover {
            border-color: #3490dc;
            background-color: rgba(52, 144, 220, 0.1);
        }

        [x-cloak] {
            display: none !important;
        }

        /* Animation for step transitions */
        .step-enter-active, .step-leave-active {
            transition: all 0.3s ease-out;
        }
        .step-enter-from {
            opacity: 0;
            transform: translateX(30px);
        }
        .step-leave-to {
            opacity: 0;
            transform: translateX(-30px);
        }
    </style>
    @endpush
</div> 