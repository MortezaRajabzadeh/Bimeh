<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-semibold text-gray-700">ویرایش سازمان: {{ $organization->name }}</h2>
                    <a href="{{ route('admin.organizations.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg flex items-center justify-center text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        بازگشت به لیست
                    </a>
                </div>

                @php
                    $type = old('type', $organization->type);
                    if ($type === 'خیریه') $type = 'charity';
                    if ($type === 'بیمه') $type = 'insurance';
                @endphp

                <form action="{{ route('admin.organizations.update', $organization) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- نام سازمان -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">نام سازمان</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $organization->name) }}" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- نوع سازمان -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">نوع سازمان</label>
                            <select name="type" id="type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-right appearance-none bg-no-repeat bg-[length:1.5em_1.5em] bg-[right_0.5rem_center] pr-10">
                                <option value="">انتخاب کنید</option>
                                <option value="charity" {{ $type === 'charity' ? 'selected' : '' }}>خیریه</option>
                                <option value="insurance" {{ $type === 'insurance' ? 'selected' : '' }}>بیمه</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- آدرس -->
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">آدرس</label>
                            <textarea name="address" id="address" rows="3" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">{{ old('address', $organization->address) }}</textarea>
                            @error('address')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- توضیحات -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">توضیحات</label>
                            <textarea name="description" id="description" rows="3" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">{{ old('description', $organization->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- لوگو -->
                    <div>
                        <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">لوگو</label>
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center">
                                <img id="logo-preview" src="{{ $organization->logo_url }}" alt="Logo Preview" class="max-h-full max-w-full p-1">
                            </div>
                            <div class="flex-1">
                                <input type="file" name="logo" id="logo" accept="image/*"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                    onchange="document.getElementById('logo-preview').src = window.URL.createObjectURL(this.files[0])">
                                <p class="mt-1 text-xs text-gray-500">فایل‌های مجاز: JPG، PNG با حداکثر حجم ۱ مگابایت</p>
                                <p class="mt-1 text-xs text-gray-500">برای حفظ لوگوی فعلی، این فیلد را خالی بگذارید</p>
                            </div>
                        </div>
                        @error('logo')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- دکمه ذخیره تغییرات -->
                    <div class="flex justify-end">
                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            ذخیره تغییرات
                        </button>
                    </div>
                </form>

                <!-- فرم حذف به صورت جداگانه -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-md font-medium text-gray-700">حذف سازمان</h3>
                        <form method="POST" action="{{ route('admin.organizations.destroy', $organization) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('آیا از حذف این سازمان اطمینان دارید؟')" 
                                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg flex items-center text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                حذف سازمان
                            </button>
                        </form>
                    </div>
                    <p class="mt-2 text-sm text-red-500">توجه: حذف سازمان غیر قابل بازگشت است. این عملیات باعث حذف سازمان و اطلاعات مرتبط با آن خواهد شد.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>