<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-semibold text-gray-700">افزودن کاربر جدید</h2>
                    <a href="{{ route('admin.users.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg flex items-center justify-center text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                        </svg>
                        بازگشت به لیست
                    </a>
                </div>

                <!-- استایل‌های سفارشی برای غیرفعال کردن بوردر قرمز اینپوت‌ها -->
                <style>
                    input:invalid,
                    select:invalid,
                    textarea:invalid,
                    input:invalid:focus,
                    select:invalid:focus,
                    textarea:invalid:focus {
                        border-color: #d1d5db !important;
                        box-shadow: none !important;
                        outline: none !important;
                    }
                    input:focus:invalid,
                    select:focus:invalid,
                    textarea:focus:invalid {
                        border-color: #10b981 !important;
                        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
                    }
                    input:required:invalid,
                    select:required:invalid,
                    textarea:required:invalid {
                        border-color: #d1d5db !important;
                        box-shadow: none !important;
                    }
                </style>

                <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" novalidate>
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- نام -->
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                                نام
                                <span class="text-red-500 mr-1">*</span>
                            </label>
                            <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            @error('first_name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- نام خانوادگی -->
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                                نام خانوادگی
                                <span class="text-red-500 mr-1">*</span>
                            </label>
                            <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            @error('last_name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>


                        <!-- موبایل -->
                        <div>
                            <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1">
                                شماره موبایل
                                <span class="text-red-500 mr-1">*</span>
                            </label>
                            <input type="text" name="mobile" id="mobile" value="{{ old('mobile') }}" dir="ltr" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            @error('mobile')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- ایمیل -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">ایمیل</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" dir="ltr" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            @error('email')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- نام کاربری -->
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                                نام کاربری
                                <span class="text-red-500 mr-1">*</span>
                            </label>
                            <input type="text" name="username" id="username" value="{{ old('username') }}" dir="ltr" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
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
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-right appearance-none bg-white bg-no-repeat bg-[length:1.2em_1.2em] bg-[left_0.5rem_center] pl-8" style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 4 5\"><path fill=\"%23666\" d=\"M2 0L0 2h4zm0 5L0 3h4z\"/></svg>')">
                                <option value="">انتخاب کنید</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
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

                        <!-- انتخاب سازمان موجود -->
                        <div id="organization_section" class="col-span-2">
                            <div class="flex items-center mb-3">
                                <input type="checkbox" id="create_organization" name="create_organization" value="1" 
                                       {{ old('create_organization') ? 'checked' : '' }}
                                       onchange="toggleOrganizationForm(this.checked)" 
                                       class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                <label for="create_organization" class="mr-2 block text-sm text-gray-700">
                                    سازمان جدید ایجاد کنم
                                </label>
                            </div>
                            
                            <!-- انتخاب سازمان موجود -->
                            <div id="existing_organization">
                                <label for="organization_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    انتخاب سازمان
                                    <span class="text-red-500 mr-1">*</span>
                                </label>
                                <select name="organization_id" id="organization_id" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-right appearance-none bg-white bg-no-repeat bg-[length:1.2em_1.2em] bg-[left_0.5rem_center] pl-8" style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 4 5\"><path fill=\"%23666\" d=\"M2 0L0 2h4zm0 5L0 3h4z\"/></svg>')">
                                    <option value="">انتخاب کنید</option>
                                    @foreach($organizations as $org)
                                        <option value="{{ $org->id }}" {{ old('organization_id') == $org->id ? 'selected' : '' }}>
                                            {{ $org->name }} - 
                                            @if($org->type === 'charity')
                                                خیریه
                                            @elseif($org->type === 'insurance')
                                                بیمه
                                            @else
                                                {{ $org->type }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('organization_id')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <!-- فرم ایجاد سازمان جدید -->
                            <div id="organization_form" class="{{ old('create_organization') ? '' : 'hidden' }} mt-4 p-4 border border-gray-200 rounded-lg bg-gray-50">
                                <h4 class="text-sm font-medium text-gray-700 mb-4">اطلاعات سازمان جدید</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- نام سازمان -->
                                    <div>
                                        <label for="org_name" class="block text-sm font-medium text-gray-700 mb-1">
                                            نام سازمان
                                            <span class="text-red-500 mr-1">*</span>
                                        </label>
                                        <input type="text" name="org_name" id="org_name" value="{{ old('org_name') }}"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                        @error('org_name')
                                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    
                                    <!-- نوع سازمان -->
                                    <div>
                                        <label for="org_type" class="block text-sm font-medium text-gray-700 mb-1">
                                            نوع سازمان
                                            <span class="text-red-500 mr-1">*</span>
                                        </label>
                                        <select name="org_type" id="org_type"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-right appearance-none bg-white bg-no-repeat bg-[length:1.2em_1.2em] bg-[left_0.5rem_center] pl-8" style="background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 4 5\"><path fill=\"%23666\" d=\"M2 0L0 2h4zm0 5L0 3h4z\"/></svg>')">
                                            <option value="">انتخاب کنید</option>
                                            <option value="charity" {{ old('org_type') === 'charity' ? 'selected' : '' }}>خیریه</option>
                                            <option value="insurance" {{ old('org_type') === 'insurance' ? 'selected' : '' }}>بیمه</option>
                                        </select>
                                        @error('org_type')
                                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    
                                    <!-- کد سازمان -->
                                    <div>
                                        <label for="org_code" class="block text-sm font-medium text-gray-700 mb-1">
                                            کد سازمان
                                        </label>
                                        <input type="text" name="org_code" id="org_code" value="{{ old('org_code') }}"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                        @error('org_code')
                                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    
                                    <!-- تلفن سازمان -->
                                    <div>
                                        <label for="org_phone" class="block text-sm font-medium text-gray-700 mb-1">
                                            تلفن سازمان
                                        </label>
                                        <input type="text" name="org_phone" id="org_phone" value="{{ old('org_phone') }}" dir="ltr"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                        @error('org_phone')
                                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    
                                    <!-- ایمیل سازمان -->
                                    <div class="md:col-span-2">
                                        <label for="org_email" class="block text-sm font-medium text-gray-700 mb-1">
                                            ایمیل سازمان
                                        </label>
                                        <input type="email" name="org_email" id="org_email" value="{{ old('org_email') }}" dir="ltr"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                        @error('org_email')
                                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    
                                    <!-- آدرس سازمان -->
                                    <div class="md:col-span-2">
                                        <label for="org_address" class="block text-sm font-medium text-gray-700 mb-1">
                                            آدرس سازمان
                                        </label>
                                        <textarea name="org_address" id="org_address" rows="2"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">{{ old('org_address') }}</textarea>
                                        @error('org_address')
                                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- کلمه عبور -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                کلمه عبور
                                <span class="text-red-500 mr-1">*</span>
                            </label>
                            <input type="password" name="password" id="password" dir="ltr" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            @error('password')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- تایید کلمه عبور -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                تایید کلمه عبور
                                <span class="text-red-500 mr-1">*</span>
                            </label>
                            <input type="password" name="password_confirmation" id="password_confirmation" dir="ltr" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>

                    <!-- دکمه ثبت -->
                    <div class="flex justify-end">
                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            ثبت کاربر جدید
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleOrganizationForm(show) {
            const organizationForm = document.getElementById('organization_form');
            const organizationSelect = document.getElementById('organization_id');
            
            if (show) {
                organizationForm.classList.remove('hidden');
                organizationSelect.disabled = true;
            } else {
                organizationForm.classList.add('hidden');
                organizationSelect.disabled = false;
            }
        }

        // اجرای اولیه برای حفظ وضعیت فرم بعد از ارسال با خطا
        document.addEventListener('DOMContentLoaded', function() {
            const createOrgCheckbox = document.getElementById('create_organization');
            if (createOrgCheckbox.checked) {
                toggleOrganizationForm(true);
            }
        });
    </script>
</x-app-layout> 