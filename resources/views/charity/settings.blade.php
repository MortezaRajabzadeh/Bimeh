<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('تنظیمات خیریه') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex flex-col md:flex-row">
                        <!-- بخش لوگو و تصویر -->
                        <div class="w-full md:w-1/3 lg:w-1/4 mb-8 md:mb-0">
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <div class="text-center mb-6">
                                    <div class="w-32 h-32 mx-auto mb-4 bg-white p-2 rounded-full border border-gray-200">
                                        <img src="{{ asset('images/image.png') }}" alt="لوگو خیریه" class="w-full h-full object-contain rounded-full">
                                    </div>
                                    <h3 class="text-lg font-bold">خیریه نمونه</h3>
                                    <p class="text-gray-500 text-sm">عضویت از تاریخ: ۱۴۰۲/۰۱/۰۱</p>
                                </div>
                                
                                <div class="mt-4">
                                    <form action="#" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-4">
                                            <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">تغییر لوگو</label>
                                            <input type="file" id="logo" name="logo" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                        </div>
                                        <button type="submit" class="w-full py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                                            آپلود لوگو
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- بخش اطلاعات حساب -->
                        <div class="w-full md:w-2/3 lg:w-3/4 md:pr-6">
                            <div class="mb-6">
                                <h3 class="text-lg font-bold mb-4 pb-2 border-b">اطلاعات خیریه</h3>
                                
                                <form action="#" method="POST">
                                    @csrf
                                    @method('PUT')
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                        <div>
                                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">نام خیریه</label>
                                            <input type="text" id="name" name="name" value="خیریه نمونه" class="border border-gray-300 rounded-md w-full py-2 px-3">
                                        </div>
                                        
                                        <div>
                                            <label for="registration_number" class="block text-sm font-medium text-gray-700 mb-1">شماره ثبت</label>
                                            <input type="text" id="registration_number" name="registration_number" value="۱۲۳۴۵" class="border border-gray-300 rounded-md w-full py-2 px-3">
                                        </div>
                                        
                                        <div>
                                            <label for="website" class="block text-sm font-medium text-gray-700 mb-1">وب‌سایت</label>
                                            <input type="url" id="website" name="website" value="https://example.com" class="border border-gray-300 rounded-md w-full py-2 px-3">
                                        </div>
                                        
                                        <div>
                                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">شماره تماس</label>
                                            <input type="text" id="phone" name="phone" value="۰۲۱۱۲۳۴۵۶۷۸" class="border border-gray-300 rounded-md w-full py-2 px-3">
                                        </div>
                                        
                                        <div class="md:col-span-2">
                                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">آدرس</label>
                                            <input type="text" id="address" name="address" value="تهران، خیابان ولیعصر، کوچه شماره ۱۰" class="border border-gray-300 rounded-md w-full py-2 px-3">
                                        </div>
                                        
                                        <div class="md:col-span-2">
                                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">درباره خیریه</label>
                                            <textarea id="description" name="description" rows="4" class="border border-gray-300 rounded-md w-full py-2 px-3">این خیریه با هدف کمک به اقشار نیازمند جامعه تاسیس شده است.</textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-end">
                                        <button type="submit" class="px-6 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                                            ذخیره تغییرات
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- بخش تغییر رمز عبور -->
                            <div>
                                <h3 class="text-lg font-bold mb-4 pb-2 border-b">تغییر رمز عبور</h3>
                                
                                <form action="#" method="POST">
                                    @csrf
                                    @method('PUT')
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                        <div>
                                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">رمز عبور فعلی</label>
                                            <input type="password" id="current_password" name="current_password" class="border border-gray-300 rounded-md w-full py-2 px-3">
                                        </div>
                                        
                                        <div class="md:col-span-2 border-t pt-3 mt-2"></div>
                                        
                                        <div>
                                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">رمز عبور جدید</label>
                                            <input type="password" id="password" name="password" class="border border-gray-300 rounded-md w-full py-2 px-3">
                                        </div>
                                        
                                        <div>
                                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">تکرار رمز عبور جدید</label>
                                            <input type="password" id="password_confirmation" name="password_confirmation" class="border border-gray-300 rounded-md w-full py-2 px-3">
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-end">
                                        <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                            تغییر رمز عبور
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 