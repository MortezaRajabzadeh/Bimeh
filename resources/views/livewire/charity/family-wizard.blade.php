<div>
    <div class="bg-white rounded-lg shadow p-6">
        <!-- نوار پیشرفت -->
        <div class="mb-8">
            <div class="flex justify-between">
                @for ($i = 1; $i <= $totalSteps; $i++)
                    <div class="flex flex-col items-center">
                        <button 
                            wire:click="goToStep({{ $i }})" 
                            class="w-10 h-10 rounded-full flex items-center justify-center 
                                {{ $currentStep >= $i ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-500' }}
                                {{ $currentStep == $i ? 'ring-2 ring-blue-300' : '' }}">
                            {{ $i }}
                        </button>
                        <span class="mt-2 text-sm font-medium {{ $currentStep >= $i ? 'text-blue-500' : 'text-gray-500' }}">
                            @switch($i)
                                @case(1)
                                    اطلاعات خانواده
                                    @break
                                @case(2)
                                    سرپرست خانوار
                                    @break
                                @case(3)
                                    اعضای خانواده
                                    @break
                                @case(4)
                                    تأیید نهایی
                                    @break
                            @endswitch
                        </span>
                    </div>
                    
                    @if ($i < $totalSteps)
                        <div class="flex-1 flex items-center">
                            <div class="h-1 w-full {{ $currentStep > $i ? 'bg-blue-500' : 'bg-gray-200' }}"></div>
                        </div>
                    @endif
                @endfor
            </div>
        </div>

        <form wire:submit.prevent="submit">
            <!-- مرحله ۱: اطلاعات خانواده -->
            @if ($currentStep == 1)
                @include('livewire.charity.family-wizard.step1')
            @endif

            <!-- مرحله ۲: سرپرست خانوار -->
            @if ($currentStep == 2)
                @include('livewire.charity.family-wizard.step2')
            @endif

            <!-- مرحله ۳: اعضای خانواده -->
            @if ($currentStep == 3)
                @include('livewire.charity.family-wizard.step3')
            @endif

            <!-- مرحله ۴: تأیید نهایی -->
            @if ($currentStep == 4)
                @include('livewire.charity.family-wizard.step4')
            @endif

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
    <div id="family-card-preview" class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50 hidden" x-data="{ open: false }" x-show="open" x-cloak>
        <div class="bg-white rounded-lg p-6 max-w-lg w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">پیش نمایش کارت شناسایی خانواده</h3>
                <button x-on:click="open = false" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div id="card-content" class="border rounded-lg p-4">
                <!-- محتوای کارت اینجا قرار می‌گیرد -->
            </div>
        </div>
    </div>

    <!-- اسکریپت‌های جاوااسکریپت -->
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        document.addEventListener('livewire:load', function () {
            // نمایش هشدارها و پیام‌ها
            window.addEventListener('show-toast', event => {
                alert(event.detail.message); // در حالت واقعی از یک کتابخانه toast استفاده می‌شود
            });
            
            // نمایش سن محاسبه شده
            window.addEventListener('show-age', event => {
                const age = event.detail.age;
                alert(`سن محاسبه شده: ${age} سال`); // در حالت واقعی در المان مناسب نمایش داده می‌شود
            });

            // پیش‌نمایش کارت شناسایی
            const previewButtons = document.querySelectorAll('#show-preview, #show-preview-final');
            previewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // با Alpine.js کنترل می‌شود
                    const previewEl = document.getElementById('family-card-preview').__x.$data;
                    previewEl.open = true;
                    
                    // محتوای کارت را آماده می‌کنیم
                    const cardContent = document.getElementById('card-content');
                    cardContent.innerHTML = `
                        <div class="flex items-center mb-4">
                            <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center ml-4">
                                ${@this.family_photo ? '<img src="' + @this.family_photo.temporaryUrl() + '" class="w-16 h-16 rounded-full object-cover">' : '<span class="text-gray-600">تصویر</span>'}
                            </div>
                            <div>
                                <h4 class="font-bold">${@this.head.first_name} ${@this.head.last_name}</h4>
                                <p class="text-sm text-gray-600">کد خانواده: ${@this.family_code}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div><span class="font-bold">آدرس:</span> ${@this.address}</div>
                            <div><span class="font-bold">کد پستی:</span> ${@this.postal_code}</div>
                            <div><span class="font-bold">تعداد اعضا:</span> ${@this.members.length + 1}</div>
                        </div>
                    `;
                });
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
                    
                    // ارسال اطلاعات به کامپوننت Livewire
                    @this.mapLocationSelected({
                        lat: e.latlng.lat,
                        lng: e.latlng.lng,
                        address: "آدرس انتخاب شده در نقشه" // در نسخه واقعی از Reverse Geocoding استفاده می‌شود
                    });
                });
            }
        });
    </script>
    @endpush

    @push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.7.1/dist/leaflet.css" />
    <style>
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
    </style>
    @endpush
</div> 