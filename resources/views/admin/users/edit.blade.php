<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-semibold text-gray-700">ویرایش کاربر</h2>
                    <a href="{{ route('admin.users.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg flex items-center justify-center text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                        </svg>
                        بازگشت به لیست
                    </a>
                </div>

                <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- نام کامل -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                نام کامل
                                <span class="text-red-500 mr-1">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- موبایل -->
                        <div>
                            <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1">شماره موبایل</label>
                            <input type="text" name="mobile" id="mobile" value="{{ old('mobile', $user->mobile) }}" dir="ltr" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('mobile')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- ایمیل -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">ایمیل</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" dir="ltr" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('email')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- نام کاربری -->
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">نام کاربری</label>
                            <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" dir="ltr" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('username')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- سطح دسترسی -->
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">
                                سطح دسترسی
                                <span class="text-red-500 mr-1">*</span>
                            </label>
                            <select name="role" id="role" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-right appearance-none bg-no-repeat bg-[length:1.5em_1.5em] bg-[right_0.5rem_center] pr-10">
                                <option value="">انتخاب کنید</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ (old('role', $user->roles->first()->name ?? null) == $role->name) ? 'selected' : '' }}>
                                        @if($role->name === 'admin')
                                            مدیر کل
                                        @elseif($role->name === 'charity')
                                            خیریه
                                        @elseif($role->name === 'insurance')
                                            بیمه
                                        @else
                                            {{ $role->name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- کلمه عبور جدید (اختیاری) -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">کلمه عبور جدید (اختیاری)</label>
                            <input type="password" name="password" id="password" dir="ltr"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                            @error('password')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- تایید کلمه عبور جدید (اختیاری) -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">تایید کلمه عبور جدید</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" dir="ltr"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>

                    <!-- دکمه ثبت -->
                    <div class="flex justify-end">
                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            بروزرسانی کاربر
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>