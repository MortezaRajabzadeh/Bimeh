<div class="mb-8">
    <h3 class="text-lg font-bold mb-4 border-b pb-2">اطلاعات پایه خانواده</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label for="family_code" class="block mb-1 text-sm font-medium text-gray-700">کد خانواده</label>
            <input type="text" id="family_code" wire:model="family_code" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="کد خانواده بصورت خودکار تولید می‌شود" readonly>
        </div>
        
        <div>
            <label for="region_id" class="block mb-1 text-sm font-medium text-gray-700">منطقه <span class="text-red-500 mr-1">*</span></label>
            <select id="region_id" wire:model="region_id" class="border border-gray-300 rounded-md w-full py-2 px-3">
                <option value="">انتخاب منطقه</option>
                @foreach($regions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            @error('region_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            
            @if($suggestedRegion)
                <div class="mt-1 p-2 bg-blue-50 text-blue-700 text-sm rounded">
                    منطقه پیشنهادی: {{ $regions[$suggestedRegion] ?? 'نامشخص' }}
                </div>
            @endif
        </div>
        
        <div>
            <label for="postal_code" class="block mb-1 text-sm font-medium text-gray-700">کد پستی <span class="text-red-500 mr-1">*</span></label>
            <input type="text" id="postal_code" wire:model.debounce.500ms="postal_code" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="۱۰ رقم بدون خط تیره">
            @error('postal_code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
        
        <div>
            <label for="housing_status" class="block mb-1 text-sm font-medium text-gray-700">وضعیت مسکن <span class="text-red-500 mr-1">*</span></label>
            <select id="housing_status" wire:model="housing_status" class="border border-gray-300 rounded-md w-full py-2 px-3">
                <option value="">انتخاب وضعیت</option>
                <option value="owned">ملکی</option>
                <option value="rented">استیجاری</option>
                <option value="relative">منزل اقوام</option>
                <option value="organizational">سازمانی</option>
            </select>
            @error('housing_status') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
        
        <div>
            <label for="housing_description" class="block mb-1 text-sm font-medium text-gray-700">توضیحات مسکن</label>
            <input type="text" id="housing_description" wire:model="housing_description" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="مانند: ۵۰ متر، ۲ خوابه">
            @error('housing_description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>
    </div>
    
    <div class="mt-6">
        <label for="address" class="block mb-1 text-sm font-medium text-gray-700">آدرس <span class="text-red-500 mr-1">*</span></label>
        <textarea id="address" wire:model="address" rows="2" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="آدرس دقیق محل سکونت"></textarea>
        @error('address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
    </div>
    
    <!-- آپلود تصویر خانواده با drag & drop -->
    <div class="mt-6" x-data="{ 
        isDropping: false,
        handleDrop(e) {
            this.isDropping = false;
            e.preventDefault();
            // انتقال فایل به لیوایر
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
            class="dropzone"
            :class="{ 'dragover': isDropping }"
        >
            @if ($family_photo)
                <img src="{{ $family_photo->temporaryUrl() }}" class="mx-auto h-32 object-cover">
                <p class="mt-2 text-sm">برای تغییر تصویر، فایل جدید را بکشید و رها کنید</p>
            @else
                <div class="flex flex-col items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <p class="mt-1">عکس را اینجا بکشید و رها کنید یا</p>
                    <label for="family_photo_input" class="mt-2 cursor-pointer px-4 py-2 bg-gray-200 rounded-md hover:bg-gray-300">
                        انتخاب فایل
                    </label>
                    <input id="family_photo_input" wire:model="family_photo" type="file" class="hidden" accept="image/*">
                </div>
            @endif
        </div>
        <p class="text-xs text-gray-500 mt-1">فرمت‌های مجاز: JPG, PNG - حداکثر سایز: 2MB</p>
        @error('family_photo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
    </div>
</div> 