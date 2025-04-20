<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('افزودن خانواده جدید') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-white rounded-lg shadow p-6">
                <form action="#" method="POST">
                    @csrf
                    
                    <!-- اطلاعات خانواده -->
                    <div class="mb-8">
                        <h3 class="text-lg font-bold mb-4 border-b pb-2">اطلاعات خانواده</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label for="family_code" class="block mb-1 text-sm font-medium text-gray-700">کد خانواده</label>
                                <input type="text" id="family_code" name="family_code" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="کد خانواده بصورت خودکار تولید می‌شود" readonly>
                            </div>
                            
                            <div>
                                <label for="region_id" class="block mb-1 text-sm font-medium text-gray-700">منطقه</label>
                                <select id="region_id" name="region_id" class="border border-gray-300 rounded-md w-full py-2 px-3">
                                    <option value="">انتخاب منطقه</option>
                                    <option value="1">تهران - منطقه ۱</option>
                                    <option value="2">تهران - منطقه ۲</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="address" class="block mb-1 text-sm font-medium text-gray-700">آدرس</label>
                                <input type="text" id="address" name="address" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="آدرس دقیق محل سکونت">
                            </div>
                            
                            <div>
                                <label for="postal_code" class="block mb-1 text-sm font-medium text-gray-700">کد پستی</label>
                                <input type="text" id="postal_code" name="postal_code" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="۱۰ رقم بدون خط تیره">
                            </div>
                            
                            <div>
                                <label for="housing_status" class="block mb-1 text-sm font-medium text-gray-700">وضعیت مسکن</label>
                                <select id="housing_status" name="housing_status" class="border border-gray-300 rounded-md w-full py-2 px-3">
                                    <option value="">انتخاب وضعیت</option>
                                    <option value="owned">ملکی</option>
                                    <option value="rented">استیجاری</option>
                                    <option value="relative">منزل اقوام</option>
                                    <option value="organizational">سازمانی</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="housing_description" class="block mb-1 text-sm font-medium text-gray-700">توضیحات مسکن</label>
                                <input type="text" id="housing_description" name="housing_description" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="مانند: ۵۰ متر، ۲ خوابه">
                            </div>
                        </div>
                    </div>
                    
                    <!-- اطلاعات سرپرست خانوار -->
                    <div class="mb-8">
                        <h3 class="text-lg font-bold mb-4 border-b pb-2">اطلاعات سرپرست خانوار</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label for="head_first_name" class="block mb-1 text-sm font-medium text-gray-700">نام</label>
                                <input type="text" id="head_first_name" name="members[0][first_name]" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="نام سرپرست خانوار">
                                <input type="hidden" name="members[0][is_head]" value="1">
                            </div>
                            
                            <div>
                                <label for="head_last_name" class="block mb-1 text-sm font-medium text-gray-700">نام خانوادگی</label>
                                <input type="text" id="head_last_name" name="members[0][last_name]" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="نام خانوادگی سرپرست">
                            </div>
                            
                            <div>
                                <label for="head_national_code" class="block mb-1 text-sm font-medium text-gray-700">کد ملی</label>
                                <input type="text" id="head_national_code" name="members[0][national_code]" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="۱۰ رقم بدون خط تیره">
                            </div>
                            
                            <div>
                                <label for="head_father_name" class="block mb-1 text-sm font-medium text-gray-700">نام پدر</label>
                                <input type="text" id="head_father_name" name="members[0][father_name]" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="نام پدر">
                            </div>
                            
                            <div>
                                <label for="head_birth_date" class="block mb-1 text-sm font-medium text-gray-700">تاریخ تولد</label>
                                <input type="text" id="head_birth_date" name="members[0][birth_date]" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="۱۳۶۰/۰۱/۰۱">
                            </div>
                            
                            <div>
                                <label for="head_gender" class="block mb-1 text-sm font-medium text-gray-700">جنسیت</label>
                                <select id="head_gender" name="members[0][gender]" class="border border-gray-300 rounded-md w-full py-2 px-3">
                                    <option value="">انتخاب جنسیت</option>
                                    <option value="male">مرد</option>
                                    <option value="female">زن</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="head_marital_status" class="block mb-1 text-sm font-medium text-gray-700">وضعیت تاهل</label>
                                <select id="head_marital_status" name="members[0][marital_status]" class="border border-gray-300 rounded-md w-full py-2 px-3">
                                    <option value="">انتخاب وضعیت</option>
                                    <option value="single">مجرد</option>
                                    <option value="married">متاهل</option>
                                    <option value="divorced">مطلقه</option>
                                    <option value="widowed">همسر فوت شده</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="head_education" class="block mb-1 text-sm font-medium text-gray-700">تحصیلات</label>
                                <select id="head_education" name="members[0][education]" class="border border-gray-300 rounded-md w-full py-2 px-3">
                                    <option value="">انتخاب تحصیلات</option>
                                    <option value="illiterate">بی‌سواد</option>
                                    <option value="elementary">ابتدایی</option>
                                    <option value="middle_school">راهنمایی</option>
                                    <option value="high_school">دبیرستان</option>
                                    <option value="diploma">دیپلم</option>
                                    <option value="associate">فوق دیپلم</option>
                                    <option value="bachelor">لیسانس</option>
                                    <option value="master">فوق لیسانس</option>
                                    <option value="phd">دکترا</option>
                                </select>
                            </div>
                            
                            <div class="flex flex-col">
                                <label class="block mb-1 text-sm font-medium text-gray-700">شرایط خاص</label>
                                <div class="flex flex-wrap gap-4 mt-2">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="members[0][has_disability]" value="1" class="rounded text-blue-600">
                                        <span class="mr-2 text-sm">معلولیت</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="members[0][has_chronic_disease]" value="1" class="rounded text-blue-600">
                                        <span class="mr-2 text-sm">بیماری خاص</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="members[0][has_insurance]" value="1" class="rounded text-blue-600">
                                        <span class="mr-2 text-sm">دارای بیمه</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div>
                                <label for="head_occupation" class="block mb-1 text-sm font-medium text-gray-700">شغل</label>
                                <input type="text" id="head_occupation" name="members[0][occupation]" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="شغل فعلی">
                            </div>
                            
                            <div>
                                <label for="head_mobile" class="block mb-1 text-sm font-medium text-gray-700">موبایل</label>
                                <input type="text" id="head_mobile" name="members[0][mobile]" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="شماره موبایل">
                            </div>
                        </div>
                    </div>
                    
                    <!-- دکمه افزودن اعضای خانواده -->
                    <div class="mb-8">
                        <button type="button" id="add-member" class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            افزودن عضو خانواده
                        </button>
                    </div>
                    
                    <!-- بخش اعضای خانواده (اینجا با جاوااسکریپت تکرار می‌شود) -->
                    <div id="family-members-container">
                        <!-- اعضای جدید اینجا اضافه می‌شوند -->
                    </div>
                    
                    <!-- اطلاعات تکمیلی -->
                    <div class="mb-8">
                        <h3 class="text-lg font-bold mb-4 border-b pb-2">اطلاعات تکمیلی</h3>
                        
                        <div>
                            <label for="additional_info" class="block mb-1 text-sm font-medium text-gray-700">توضیحات اضافی</label>
                            <textarea id="additional_info" name="additional_info" rows="4" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="هر گونه اطلاعات تکمیلی درباره خانواده که لازم است ثبت شود"></textarea>
                        </div>
                    </div>
                    
                    <!-- دکمه‌های ثبت و انصراف -->
                    <div class="flex justify-end space-x-4 space-x-reverse">
                        <button type="button" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            انصراف
                        </button>
                        <button type="submit" class="px-6 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                            ثبت خانواده
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let memberCount = 1;
            
            document.getElementById('add-member').addEventListener('click', function() {
                const container = document.getElementById('family-members-container');
                const memberHtml = `
                    <div class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50 member-container">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold">عضو خانواده ${memberCount}</h3>
                            <button type="button" class="remove-member text-red-500 hover:text-red-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">نام</label>
                                <input type="text" name="members[${memberCount}][first_name]" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="نام">
                                <input type="hidden" name="members[${memberCount}][is_head]" value="0">
                            </div>
                            
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">نام خانوادگی</label>
                                <input type="text" name="members[${memberCount}][last_name]" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="نام خانوادگی">
                            </div>
                            
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">کد ملی</label>
                                <input type="text" name="members[${memberCount}][national_code]" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="۱۰ رقم بدون خط تیره">
                            </div>
                            
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">نام پدر</label>
                                <input type="text" name="members[${memberCount}][father_name]" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="نام پدر">
                            </div>
                            
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">تاریخ تولد</label>
                                <input type="text" name="members[${memberCount}][birth_date]" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="۱۳۸۰/۰۱/۰۱">
                            </div>
                            
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">جنسیت</label>
                                <select name="members[${memberCount}][gender]" class="border border-gray-300 rounded-md w-full py-2 px-3">
                                    <option value="">انتخاب جنسیت</option>
                                    <option value="male">مرد</option>
                                    <option value="female">زن</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">نسبت</label>
                                <select name="members[${memberCount}][relationship]" class="border border-gray-300 rounded-md w-full py-2 px-3">
                                    <option value="">انتخاب نسبت</option>
                                    <option value="spouse">همسر</option>
                                    <option value="child">فرزند</option>
                                    <option value="parent">والدین</option>
                                    <option value="sibling">خواهر/برادر</option>
                                    <option value="other">سایر</option>
                                </select>
                            </div>
                            
                            <div class="flex flex-col">
                                <label class="block mb-1 text-sm font-medium text-gray-700">شرایط خاص</label>
                                <div class="flex flex-wrap gap-4 mt-2">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="members[${memberCount}][has_disability]" value="1" class="rounded text-blue-600">
                                        <span class="mr-2 text-sm">معلولیت</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="members[${memberCount}][has_chronic_disease]" value="1" class="rounded text-blue-600">
                                        <span class="mr-2 text-sm">بیماری خاص</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="members[${memberCount}][has_insurance]" value="1" class="rounded text-blue-600">
                                        <span class="mr-2 text-sm">دارای بیمه</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // افزودن HTML به صفحه
                container.insertAdjacentHTML('beforeend', memberHtml);
                memberCount++;
                
                // اضافه کردن رویداد کلیک به دکمه حذف
                const removeButtons = document.querySelectorAll('.remove-member');
                removeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        this.closest('.member-container').remove();
                    });
                });
            });
        });
    </script>
</x-app-layout> 