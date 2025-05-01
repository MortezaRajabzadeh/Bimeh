<!-- کارت اصلی -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- هدر -->
    <div class="border-b border-gray-100 p-6">
        <div class="flex justify-between items-center">
            <div class="text-lg font-bold text-gray-800">اطلاعات پایه خانواده</div>
        </div>
    </div>

    <div class="p-6">
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

        <!-- پیام موفقیت -->
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

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="space-y-1">
                <label for="family_code" class="block text-sm font-medium text-gray-700">کد خانواده</label>
                <input type="text" id="family_code" wire:model="family_code" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="کد خانواده بصورت خودکار تولید می‌شود" readonly>
            </div>
            
            <div class="space-y-1">
                <label for="region_id" class="block text-sm font-medium text-gray-700">منطقه <span class="text-red-500 mr-1">*</span></label>
                <input type="text" id="region_id" wire:model="region_id" 
                    class="border rounded-md w-full py-2 px-3 transition duration-150 ease-in-out {{ $errors->has('region_id') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-green-500 focus:ring-green-500' }}" 
                    placeholder="منطقه را وارد کنید">
                @error('region_id') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="space-y-1">
                <label for="postal_code" class="block text-sm font-medium text-gray-700">کد پستی <span class="text-red-500 mr-1">*</span></label>
                <input type="text" id="postal_code" wire:model.debounce.500ms="postal_code" 
                    class="border rounded-md w-full py-2 px-3 transition duration-150 ease-in-out {{ $errors->has('postal_code') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-green-500 focus:ring-green-500' }}" 
                    placeholder="۱۰ رقم بدون خط تیره">
                @error('postal_code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="space-y-1">
                <label for="housing_status" class="block text-sm font-medium text-gray-700">وضعیت مسکن <span class="text-red-500 mr-1">*</span></label>
                <select id="housing_status" wire:model="housing_status" 
                    class="border rounded-md w-full py-2 px-3 transition duration-150 ease-in-out {{ $errors->has('housing_status') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-green-500 focus:ring-green-500' }}">
                    <option value="">انتخاب وضعیت</option>
                    <option value="owned">ملکی</option>
                    <option value="rented">استیجاری</option>
                    <option value="relative">منزل اقوام</option>
                    <option value="organizational">سازمانی</option>
                </select>
                @error('housing_status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="space-y-1">
                <label for="housing_description" class="block text-sm font-medium text-gray-700">توضیحات مسکن</label>
                <input type="text" id="housing_description" wire:model="housing_description" 
                    class="border rounded-md w-full py-2 px-3 transition duration-150 ease-in-out {{ $errors->has('housing_description') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-green-500 focus:ring-green-500' }}" 
                    placeholder="مانند: ۵۰ متر، ۲ خوابه">
                @error('housing_description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <div class="mt-6 space-y-1">
            <label for="address" class="block text-sm font-medium text-gray-700">آدرس <span class="text-red-500 mr-1">*</span></label>
            <textarea id="address" wire:model="address" rows="2" 
                class="border rounded-md w-full py-2 px-3 transition duration-150 ease-in-out {{ $errors->has('address') ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-green-500 focus:ring-green-500' }}" 
                placeholder="آدرس دقیق محل سکونت"></textarea>
            @error('address')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        
        <!-- آپلود تصویر خانواده با drag & drop -->
        <div class="mt-6" x-data="{ 
            isDropping: false,
            handleDrop(e) {
                this.isDropping = false;
                e.preventDefault();
                if (e.dataTransfer.files.length) {
                    @this.upload('family_photo', e.dataTransfer.files[0]);
                }
            }
        }">
            <label class="block mb-1 text-sm font-medium text-gray-700">تصویر خانواده</label>
            <div
                x-on:dragover.prevent="isDropping = true"
                x-on:dragleave.prevent="isDropping = false"
                x-on:drop="handleDrop"
                class="mt-1 border-2 border-dashed rounded-lg p-6 text-center transition-all duration-150 ease-in-out {{ $errors->has('family_photo') ? 'border-red-300 bg-red-50' : 'border-gray-300 hover:border-green-500' }}"
                :class="{ 'border-green-500 bg-green-50': isDropping }"
            >
                @if ($family_photo)
                    <img src="{{ $family_photo->temporaryUrl() }}" class="mx-auto h-32 object-cover rounded">
                    <p class="mt-2 text-sm text-gray-600">برای تغییر تصویر، فایل جدید را بکشید و رها کنید</p>
                @else
                    <div class="flex flex-col items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <p class="mt-1 text-gray-600">عکس را اینجا بکشید و رها کنید یا</p>
                        <label for="family_photo_input" class="mt-2 cursor-pointer px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition duration-150 ease-in-out">
                            انتخاب فایل
                        </label>
                        <input id="family_photo_input" wire:model="family_photo" type="file" class="hidden" accept="image/*">
                    </div>
                @endif
            </div>
            <div class="mt-1 flex items-center justify-between">
                <p class="text-xs text-gray-500">فرمت‌های مجاز: JPG, PNG - حداکثر سایز: 2MB</p>
                @error('family_photo') 
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:load', function () {
        Livewire.on('show-message', event => {
            const type = event.type;
            const message = event.message;
            
            if (type === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'موفق',
                    text: message,
                    confirmButtonText: 'باشه',
                    timer: 3000,
                    timerProgressBar: true
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'خطا',
                    text: message,
                    confirmButtonText: 'باشه'
                });
            }
        });
    });
</script>
@endpush 