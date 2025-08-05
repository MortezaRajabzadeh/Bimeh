                <!-- کارت اصلی -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- هدر -->
                    <div class="border-b border-gray-100 p-6">
                        <div class="flex justify-between items-center">
                            <div class="text-lg font-bold text-gray-800">اطلاعات پایه خانواده</div>
                            @if($family_code)
                                <div class="text-sm bg-blue-50 text-blue-700 py-1 px-3 rounded-full font-medium">
                                    شناسه: {{ $family_code }}
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="p-6">
                        <!-- پیام‌های سیستمی -->
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
                        @if(session()->has('success'))
                            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center text-green-600">
                                    <svg class="w-5 h-5 ml-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>{{ session('success') }}</span>
                                </div>
                            </div>
                        @endif
                        <!-- شناسه خانواده (به صورت مخفی) -->
                        <input type="hidden" id="family_code" wire:model="family_code" value="{{ $family_code }}">
                        <!-- موقعیت جغرافیایی -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div class="space-y-1">
                                <label for="province_id" class="block text-sm font-medium text-gray-700">استان <span class="text-red-500 mr-1">*</span></label>
                                                <div class="relative">
                    <select id="province_id" wire:model.live="province_id"
                        style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;"
                        class="border rounded-md w-full py-2 px-3 pr-8 bg-white transition duration-150 ease-in-out {{ $errors->has('province_id') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-green-500 focus:ring-green-500' }}">
                        <option value="">انتخاب استان</option>
                        @foreach($provinces as $prov)
                            <option value="{{ $prov->id }}">{{ $prov->name }}</option>
                        @endforeach
                    </select>
                    <!-- آیکون کشویی -->
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                    <div wire:loading wire:target="province_id" class="absolute left-3 top-2">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                                @error('province_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="space-y-1">
                                <label for="city_id" class="block text-sm font-medium text-gray-700">شهرستان <span class="text-red-500 mr-1">*</span></label>
                                                <div class="relative">
                    <select id="city_id" wire:model.live="city_id"
                        style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;"
                        class="border rounded-md w-full py-2 px-3 pr-8 bg-white transition duration-150 ease-in-out {{ $errors->has('city_id') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-green-500 focus:ring-green-500' }}"
                        {{ is_null($province_id) ? 'disabled' : '' }}>
                        <option value="">انتخاب شهرستان</option>
                        @if(!is_null($province_id) && $cities && $cities->isNotEmpty())
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}">{{ $city->name }}</option>
                            @endforeach
                        @endif
                    </select>
                    <!-- آیکون کشویی -->
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                    <div wire:loading wire:target="city_id, province_id" class="absolute left-3 top-2">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                                @error('city_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="space-y-1">
                                <label for="district_id" class="block text-sm font-medium text-gray-700">دهستان <span class="text-red-500 mr-1">*</span></label>
                                                <div class="relative">
                    <select id="district_id" wire:model.live="district_id"
                        style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;"
                        class="border rounded-md w-full py-2 px-3 pr-8 bg-white transition duration-150 ease-in-out {{ $errors->has('district_id') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-green-500 focus:ring-green-500' }}"
                        {{ is_null($city_id) ? 'disabled' : '' }}>
                        <option value="">انتخاب دهستان</option>
                        @if(!is_null($city_id) && $districts && $districts->isNotEmpty())
                            @foreach($districts as $district)
                                <option value="{{ $district->id }}">{{ $district->name }}</option>
                            @endforeach
                        @endif
                    </select>
                    <!-- آیکون کشویی -->
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                    <div wire:loading wire:target="district_id, city_id" class="absolute left-3 top-2">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                                @error('district_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                            
                        <!-- آدرس -->
                        <div class="mb-6 space-y-1">
                            <label for="address" class="block text-sm font-medium text-gray-700">آدرس <span class="text-red-500 mr-1">*</span></label>
                            <textarea id="address" wire:model="address" rows="2" 
                                class="border rounded-md w-full py-2 px-3 transition duration-150 ease-in-out {{ $errors->has('address') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-green-500 focus:ring-green-500' }}" 
                                placeholder="آدرس دقیق محل سکونت"></textarea>
                            @error('address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>