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
                    
                    <!-- جدول اطلاعات خانواده -->
                    <div class="mb-8 overflow-x-auto">
                        <div class="grid grid-cols-8 bg-gray-100 p-2 rounded-t-lg font-medium text-sm">
                            <div class="col-span-1 text-center">عملیات</div>
                            <div class="col-span-1 text-center">شناسه</div>
                            <div class="col-span-1 text-center">استان</div>
                            <div class="col-span-1 text-center">شهر/روستا</div>
                            <div class="col-span-1 text-center">معیار پذیرش</div>
                            <div class="col-span-1 text-center">تعداد اعضا</div>
                            <div class="col-span-1 text-center">سرپرست خانوار</div>
                            <div class="col-span-1 text-center">تاریخ عضویت</div>
                        </div>
                        
                        <div class="grid grid-cols-8 bg-green-100 p-2 rounded-b-lg text-sm">
                            <div class="col-span-1 text-center">سرپرست؟</div>
                            <div class="col-span-1 text-center">اعضای خانواده</div>
                            <div class="col-span-1 text-center">نام</div>
                            <div class="col-span-1 text-center">نام خانوادگی</div>
                            <div class="col-span-1 text-center">تاریخ تولد</div>
                            <div class="col-span-1 text-center">کد ملی</div>
                            <div class="col-span-1 text-center">شغل</div>
                            <div class="col-span-1 text-center">نوع مشکل</div>
                            <div class="col-span-1 text-center">مدارک الحاقی</div>
                            <div class="col-span-1 text-center">نوع بیمه</div>
                        </div>
                    </div>
                    
                    <!-- اطلاعات اعضای خانواده -->
                    <div class="mb-4">
                        <!-- ردیف سرپرست -->
                        <div class="grid grid-cols-10 gap-2 items-center mb-2 bg-green-50 p-2 rounded-lg">
                            <div class="col-span-1 text-center">
                                <input type="radio" name="is_head" value="1" class="head-of-family-radio" checked>
                            </div>
                            <div class="col-span-1">
                                <select class="border border-gray-300 rounded-md w-full py-1 px-2 text-sm">
                                    <option value="son">پسر</option>
                                    <option value="daughter">دختر</option>
                                    <option value="spouse">همسر</option>
                                    <option value="father">پدر</option>
                                    <option value="mother">مادر</option>
                                </select>
                            </div>
                            <div class="col-span-1">
                                <input type="text" name="members[0][first_name]" placeholder="نام" class="border border-gray-300 rounded-md w-full py-1 px-2 text-sm">
                            </div>
                            <div class="col-span-1">
                                <input type="text" name="members[0][last_name]" placeholder="نام خانوادگی" class="border border-gray-300 rounded-md w-full py-1 px-2 text-sm">
                            </div>
                            <div class="col-span-1">
                                <input type="text" name="members[0][birth_date]" placeholder="تاریخ تولد" class="border border-gray-300 rounded-md w-full py-1 px-2 text-sm datepicker" dir="ltr">
                            </div>
                            <div class="col-span-1">
                                <input type="text" name="members[0][national_code]" placeholder="کد ملی" class="border border-gray-300 rounded-md w-full py-1 px-2 text-sm">
                            </div>
                            <div class="col-span-1">
                                <select name="members[0][job]" class="border border-gray-300 rounded-md w-full py-1 px-2 text-sm">
                                    <option value="">بیکار</option>
                                    <option value="employee">کارمند</option>
                                    <option value="worker">کارگر</option>
                                    <option value="self_employed">آزاد</option>
                                    <option value="disabled">از کار افتاده</option>
                                </select>
                            </div>
                            <div class="col-span-1">
                                <select name="members[0][problem_type]" class="border border-gray-300 rounded-md w-full py-1 px-2 text-sm">
                                    <option value="">انتخاب کنید</option>
                                    <option value="physical">از کار افتادگی</option>
                                    <option value="mental">معلولیت</option>
                                    <option value="orphan">یتیم</option>
                                </select>
                            </div>
                            <div class="col-span-1">
                                <button type="button" class="bg-green-500 text-white rounded-full w-6 h-6 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                            <div class="col-span-1">
                                <select name="members[0][insurance_type]" class="border border-gray-300 rounded-md w-full py-1 px-2 text-sm">
                                    <option value="">انتخاب کنید</option>
                                    <option value="social_security">تأمین اجتماعی</option>
                                    <option value="health">سلامت</option>
                                    <option value="military">نیروهای مسلح</option>
                                    <option value="none">ندارد</option>
                                </select>
                            </div>
                        </div>

                        <!-- ردیف عضو دیگر خانواده - نمونه -->
                        <div class="grid grid-cols-10 gap-2 items-center mb-2 bg-gray-50 p-2 rounded-lg member-row">
                            <div class="col-span-1 text-center">
                                <input type="radio" name="is_head" value="0" class="head-of-family-radio">
                            </div>
                            <div class="col-span-1">
                                <select class="border border-gray-300 rounded-md w-full py-1 px-2 text-sm">
                                    <option value="family_member">عضو خانواده</option>
                                    <option value="son">پسر</option>
                                    <option value="daughter">دختر</option>
                                    <option value="spouse">همسر</option>
                                </select>
                            </div>
                            <div class="col-span-1">
                                <input type="text" name="members[1][first_name]" placeholder="نام" class="border border-gray-300 rounded-md w-full py-1 px-2 text-sm">
                            </div>
                            <div class="col-span-1">
                                <input type="text" name="members[1][last_name]" placeholder="نام خانوادگی" class="border border-gray-300 rounded-md w-full py-1 px-2 text-sm">
                            </div>
                            <div class="col-span-1">
                                <input type="text" name="members[1][birth_date]" placeholder="تاریخ تولد" class="border border-gray-300 rounded-md w-full py-1 px-2 text-sm datepicker" dir="ltr">
                            </div>
                            <div class="col-span-1">
                                <input type="text" name="members[1][national_code]" placeholder="کد ملی" class="border border-gray-300 rounded-md w-full py-1 px-2 text-sm">
                            </div>
                            <div class="col-span-1">
                                <select name="members[1][job]" class="border border-gray-300 rounded-md w-full py-1 px-2 text-sm">
                                    <option value="">بیکار</option>
                                    <option value="employee">کارمند</option>
                                    <option value="worker">کارگر</option>
                                    <option value="self_employed">آزاد</option>
                                    <option value="disabled">از کار افتاده</option>
                                </select>
                            </div>
                            <div class="col-span-1">
                                <select name="members[1][problem_type]" class="border border-gray-300 rounded-md w-full py-1 px-2 text-sm">
                                    <option value="">انتخاب کنید</option>
                                    <option value="physical">از کار افتادگی</option>
                                    <option value="mental">معلولیت</option>
                                    <option value="orphan">یتیم</option>
                                </select>
                            </div>
                            <div class="col-span-1">
                                <button type="button" class="bg-green-500 text-white rounded-full w-6 h-6 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                            <div class="col-span-1">
                                <select name="members[1][insurance_type]" class="border border-gray-300 rounded-md w-full py-1 px-2 text-sm">
                                    <option value="">انتخاب کنید</option>
                                    <option value="social_security">تأمین اجتماعی</option>
                                    <option value="health">سلامت</option>
                                    <option value="military">نیروهای مسلح</option>
                                    <option value="none">ندارد</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- دکمه افزودن عضو جدید -->
                        <div class="flex justify-center mt-4 mb-6">
                            <button type="button" id="add-member" class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                افزودن عضو خانواده
                            </button>
                        </div>
                    </div>
                    
                    <!-- اطلاعات تکمیلی سرپرست - این بخش در ابتدا نمایش داده می‌شود -->
                    <div id="head-info" class="mb-8 grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">شماره موبایل سرپرست</label>
                            <input type="text" name="head_mobile" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="مانند: ۰۹۱۲۱۲۳۴۵۶۷">
                        </div>
                        
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">شماره شبا جهت پرداخت خسارت</label>
                            <input type="text" name="head_sheba" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="بدون IR و بدون فاصله" dir="ltr">
                        </div>
                        
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">استان</label>
                            <input type="text" name="province" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="استان محل سکونت">
                        </div>
                        
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">شهر/روستا</label>
                            <input type="text" name="city" class="border border-gray-300 rounded-md w-full py-2 px-3" placeholder="شهر یا روستای محل سکونت">
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
            let memberCount = 2; // شروع از ۲ چون سرپرست و یک عضو دیگر از قبل وجود دارند
            
            // رویداد برای دکمه افزودن عضو جدید
            document.getElementById('add-member').addEventListener('click', function() {
                const lastMemberRow = document.querySelector('.member-row:last-child');
                const newRow = lastMemberRow.cloneNode(true);
                
                // تغییر نام فیلدها به index جدید
                const inputs = newRow.querySelectorAll('input[name^="members"]');
                const selects = newRow.querySelectorAll('select[name^="members"]');
                
                inputs.forEach(input => {
                    const name = input.getAttribute('name');
                    input.setAttribute('name', name.replace(/\[\d+\]/, `[${memberCount}]`));
                    input.value = '';
                });
                
                selects.forEach(select => {
                    const name = select.getAttribute('name');
                    if (name) {
                        select.setAttribute('name', name.replace(/\[\d+\]/, `[${memberCount}]`));
                        select.selectedIndex = 0;
                    }
                });
                
                // اضافه کردن رویداد رادیو برای سرپرست
                const radioBtn = newRow.querySelector('.head-of-family-radio');
                radioBtn.addEventListener('change', handleHeadOfFamilyChange);
                
                // اضافه کردن ردیف جدید به صفحه
                lastMemberRow.after(newRow);
                memberCount++;
            });
            
            // رویداد برای تغییر سرپرست خانواده
            const headRadios = document.querySelectorAll('.head-of-family-radio');
            headRadios.forEach(radio => {
                radio.addEventListener('change', handleHeadOfFamilyChange);
            });
            
            function handleHeadOfFamilyChange(e) {
                // اگر این رادیو انتخاب شده، اطلاعات سرپرست را نمایش بده
                if (e.target.checked && e.target.value === "1") {
                    document.getElementById('head-info').style.display = 'grid';
                } else {
                    document.getElementById('head-info').style.display = 'none';
                }
            }
            
            // نمایش اطلاعات سرپرست در ابتدا (چون سرپرست به صورت پیش‌فرض انتخاب شده)
            document.getElementById('head-info').style.display = 'grid';
        });
    </script>
</x-app-layout> 