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
            @if ($currentStep == 1)
            @include('livewire.charity.family-wizard.step1')

            @endif
            <!-- مرحله ۲: اعضای خانواده -->
            @if ($currentStep == 2)
                @include('livewire.charity.family-wizard.step2')
            @endif
            <!-- مرحله ۳: تأیید نهایی -->
            @if ($currentStep == 3)
                @include('livewire.charity.family-wizard.step3')
                <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
                    <strong>شناسه خانواده (کد یکتا):</strong>
                    <span dir="ltr" class="font-mono select-all text-blue-700">{{ $family_code ?? '---' }}</span>
                    <span class="text-xs text-gray-400">(این کد برای پیگیری و پشتیبانی استفاده می‌شود)</span>
                </div>
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

    <!-- اسکریپت‌های جاوااسکریپت -->
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.7.1/dist/leaflet.js"></script>
    <script type="text/javascript" src="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js"></script>
    <script>
        document.addEventListener('livewire:load', function () {
            jalaliDatepicker.startWatch();
        });
        document.addEventListener('livewire:navigated', function () {
            jalaliDatepicker.startWatch();
        });
        document.addEventListener('livewire:update', function () {
            jalaliDatepicker.startWatch();
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