<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-semibold text-gray-700">افزودن سازمان جدید</h2>
                    <a href="{{ route('admin.organizations.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg flex items-center justify-center text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        بازگشت به لیست
                    </a>
                </div>

                <form action="{{ route('admin.organizations.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- نام سازمان -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">نام سازمان</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- نوع سازمان -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">نوع سازمان</label>
                            <select name="type" id="type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">انتخاب کنید</option>
                                <option value="خیریه" {{ old('type') === 'خیریه' ? 'selected' : '' }}>خیریه</option>
                                <option value="بیمه" {{ old('type') === 'بیمه' ? 'selected' : '' }}>بیمه</option>
                                <option value="دولتی" {{ old('type') === 'دولتی' ? 'selected' : '' }}>دولتی</option>
                                <option value="خصوصی" {{ old('type') === 'خصوصی' ? 'selected' : '' }}>خصوصی</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- آدرس -->
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">آدرس</label>
                            <textarea name="address" id="address" rows="3" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">{{ old('address') }}</textarea>
                            @error('address')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- توضیحات -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">توضیحات</label>
                            <textarea name="description" id="description" rows="3" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">{{ old('description') }}</textarea>
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
                                <img id="logo-preview" src="{{ asset('images/default-organization.png') }}" alt="Logo Preview" class="max-h-full max-w-full p-1">
                            </div>
                            <div class="flex-1">
                                <input type="file" name="logo" id="logo" accept="image/*"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                    onchange="document.getElementById('logo-preview').src = window.URL.createObjectURL(this.files[0])">
                                <p class="mt-1 text-xs text-gray-500">فایل‌های مجاز: JPG، PNG با حداکثر حجم ۱ مگابایت</p>
                            </div>
                        </div>
                        @error('logo')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- دکمه ثبت -->
                    <div class="flex justify-end">
                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            ثبت سازمان
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout> 