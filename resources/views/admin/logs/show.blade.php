@php
    $eventLabels = [
        'created' => 'ایجاد',
        'updated' => 'ویرایش',
        'deleted' => 'حذف',
        'login' => 'ورود',
        'logout' => 'خروج',
        'restored' => 'بازیابی',
    ];
    
    $modelLabels = [
        // مدل‌های اصلی
        'User' => 'کاربر',
        'Organization' => 'سازمان',
        'Region' => 'منطقه',
        'Province' => 'استان',
        'City' => 'شهر',
        'District' => 'منطقه شهری',
        'Family' => 'خانواده',
        'Member' => 'عضو خانواده',
        'FamilyInsurance' => 'بیمه خانواده',
        'FundingSource' => 'منبع تامین',
        
        // مدل‌های مالی
        'Payment' => 'پرداخت',
        'Transaction' => 'تراکنش',
        'Invoice' => 'فاکتور',
        'Receipt' => 'رسید',
        'FundingTransaction' => 'تراکنش بودجه',
        'FundingSource' => 'منبع بودجه',
        'InsuranceAllocation' => 'تخصیص بیمه',
        'InsuranceImportLog' => 'ایمپورت بیمه',
        
        // مدل‌های سیستمی
        'Role' => 'نقش کاربری',
        'Permission' => 'دسترسی',
        'ActivityLog' => 'لاگ فعالیت',
        'Media' => 'فایل',
        'Notification' => 'اعلان',
        
        // مدل‌های بیمه
        'InsurancePolicy' => 'پالیس بیمه',
        'Claim' => 'ادعای بیمه',
        'Premium' => 'حق بیمه',
        'Coverage' => 'پوشش بیمه',
    ];
    
    $attributeLabels = [
        // فیلدهای عمومی
        'id' => 'شناسه',
        'name' => 'نام',
        'title' => 'عنوان',
        'description' => 'توضیحات',
        'type' => 'نوع',
        'status' => 'وضعیت',
        'is_active' => 'فعال/غیرفعال',
        'is_verified' => 'تایید شده',
        'is_approved' => 'تصویب شده',
        'created_at' => 'تاریخ ایجاد',
        'updated_at' => 'تاریخ بروزرسانی',
        'deleted_at' => 'تاریخ حذف',
        
        // فیلدهای کاربر
        'username' => 'نام کاربری',
        'email' => 'ایمیل',
        'password' => 'رمز عبور',
        'user_type' => 'نوع کاربر',
        'mobile' => 'موبایل',
        'phone' => 'تلفن',
        'avatar' => 'تصویر پروفایل',
        'email_verified_at' => 'تاریخ تایید ایمیل',
        'last_login_at' => 'آخرین ورود',
        'organization_id' => 'سازمان',
        
        // فیلدهای سازمان
        'code' => 'کد',
        'license_number' => 'شماره مجوز',
        'manager_name' => 'نام مدیر',
        'address' => 'آدرس',
        'postal_code' => 'کد پستی',
        'website' => 'وب‌سایت',
        'established_at' => 'تاریخ تاسیس',
        
        // فیلدهای خانواده
        'family_code' => 'کد خانواده',
        'head_national_id' => 'کد ملی سرپرست',
        'head_name' => 'نام سرپرست',
        'head_birth_date' => 'تاریخ تولد سرپرست',
        'head_gender' => 'جنسیت سرپرست',
        'member_count' => 'تعداد اعضا',
        'total_income' => 'درآمد کل',
        'monthly_income' => 'درآمد ماهانه',
        'is_insured' => 'وضعیت بیمه',
        'insurance_start_date' => 'تاریخ شروع بیمه',
        'end_date' => 'تاریخ پایان بیمه',
        'region_id' => 'منطقه',
        'province_id' => 'استان',
        'city_id' => 'شهر',
        'district_id' => 'منطقه شهری',
        
        // فیلدهای عضو خانواده
        'national_id' => 'کد ملی',
        'birth_date' => 'تاریخ تولد',
        'gender' => 'جنسیت',
        'relation' => 'نسبت',
        'is_head' => 'سرپرست خانواده',
        'education_level' => 'سطح تحصیلات',
        'job' => 'شغل',
        'health_condition' => 'وضعیت سلامت',
        'family_id' => 'خانواده',
        
        // فیلدهای مالی
        'amount' => 'مبلغ',
        'currency' => 'واحد پول',
        'payment_method' => 'روش پرداخت',
        'payment_date' => 'تاریخ پرداخت',
        'due_date' => 'سررسید',
        'reference_id' => 'شماره مرجع',
        'transaction_id' => 'شماره تراکنش',
        
        // فیلدهای بیمه
        'policy_number' => 'شماره پالیس',
        'coverage_amount' => 'مبلغ پوشش',
        'premium_amount' => 'مبلغ حق بیمه',
        'deductible' => 'فرانشیز',
        'claim_amount' => 'مبلغ ادعا',
        'approval_date' => 'تاریخ تایید',
        'rejection_reason' => 'دلیل رد',
        
        // فیلدهای تایید و وضعیت خانواده
        'verified_at' => 'تاریخ تایید',
        'verified_by' => 'تایید شده توسط',
        'poverty_confirmed' => 'تایید فقر',
        'poverty_confirmed_at' => 'تاریخ تایید فقر',
        'poverty_confirmed_by' => 'تایید فقر توسط',
        'financial_aid_eligible' => 'واجد شرایط کمک مالی',
        'priority_level' => 'سطح اولویت',
        'assistance_type' => 'نوع کمک',
        'notes' => 'یادداشت‌ها',
        'remarks' => 'توضیحات',
        'comment' => 'نظر',
        'review_status' => 'وضعیت بررسی',
        'approval_status' => 'وضعیت تایید',
        'follow_up_date' => 'تاریخ پیگیری',
        'last_contact_date' => 'تاریخ آخرین تماس',
        'contact_method' => 'روش تماس',
        'emergency_contact' => 'تماس اضطراری',
        'emergency_phone' => 'تلفن اضطراری',
        'social_worker_id' => 'مددکار اجتماعی',
        'case_worker_id' => 'مسئول پرونده',
    ];
    
    $typeLabels = [
        // نوع کاربران
        'admin' => 'ادمین',
        'charity' => 'خیریه', 
        'insurance' => 'بیمه',
        
        // نوع سازمان‌ها
        'government' => 'دولتی',
        'private' => 'خصوصی',
        'ngo' => 'غیرانتفاعی',
        
        // جنسیت
        'male' => 'مرد',
        'female' => 'زن',
        
        // نسبت خانوادگی
        'father' => 'پدر',
        'mother' => 'مادر',
        'son' => 'پسر',
        'daughter' => 'دختر',
        'spouse' => 'همسر',
        'grandfather' => 'پدربزرگ',
        'grandmother' => 'مادربزرگ',
        'brother' => 'برادر',
        'sister' => 'خواهر',
        'other' => 'سایر',
        
        // وضعیت
        'active' => 'فعال',
        'inactive' => 'غیرفعال',
        'pending' => 'در انتظار',
        'approved' => 'تایید شده',
        'rejected' => 'رد شده',
        'suspended' => 'تعلیق شده',
        
        // روش پرداخت
        'cash' => 'نقدی',
        'card' => 'کارتی',
        'bank_transfer' => 'انتقال بانکی',
        'online' => 'آنلاین',
        
        // مقادیر بولین
        '1' => 'فعال',
        '0' => 'غیرفعال',
        'true' => 'بله',
        'false' => 'خیر',
        
        // وضعیت‌های تکمیلی
        'verified' => 'تایید شده',
        'unverified' => 'تایید نشده',
        'under_review' => 'در حال بررسی',
        'completed' => 'تکمیل شده',
        'cancelled' => 'لغو شده',
        'draft' => 'پیش‌نویس',
        'published' => 'منتشر شده',
        
        // سطح اولویت
        'high' => 'بالا',
        'medium' => 'متوسط', 
        'low' => 'پایین',
        'urgent' => 'فوری',
        'normal' => 'عادی',
        'critical' => 'بحرانی',
        
        // نوع کمک
        'financial' => 'مالی',
        'medical' => 'پزشکی',
        'educational' => 'آموزشی',
        'food' => 'غذایی',
        'clothing' => 'پوشاک',
        'housing' => 'مسکن',
        'emergency' => 'اضطراری',
        
        // روش تماس
        'phone' => 'تلفن',
        'sms' => 'پیامک',
        'email' => 'ایمیل',
        'visit' => 'حضوری',
        'letter' => 'نامه',
        'whatsapp' => 'واتساپ',
        'telegram' => 'تلگرام',
    ];
    
    function readableDescription($log) {
        $modelName = $modelLabels[class_basename($log->subject_type) ?? ''] ?? class_basename($log->subject_type);
        $userName = $log->causer ? ($log->causer->name ?? $log->causer->username ?? $log->causer->mobile ?? 'سیستم') : 'سیستم';
        
        // نام موضوع (اگر موجود باشد)
        $subjectName = '';
        if ($log->subject) {
            if (class_basename($log->subject_type) === 'User') {
                $subjectName = $log->subject->name ?? $log->subject->username ?? $log->subject->mobile ?? '';
            } elseif (class_basename($log->subject_type) === 'Organization') {
                $subjectName = $log->subject->name ?? '';
            } elseif (class_basename($log->subject_type) === 'Family') {
                $subjectName = $log->subject->head_name ?? '';
            } elseif (class_basename($log->subject_type) === 'Member') {
                $subjectName = $log->subject->name ?? '';
            } else {
                $subjectName = $log->subject->name ?? $log->subject->title ?? $log->subject->display_name ?? '';
            }
        }
        
        if ($log->event === 'created') {
            return $modelName . ($subjectName ? " «{$subjectName}» " : ' ') . 'ایجاد شد توسط ' . $userName;
        }
        if ($log->event === 'updated') {
            return $modelName . ($subjectName ? " «{$subjectName}» " : ' ') . 'ویرایش شد توسط ' . $userName;
        }
        if ($log->event === 'deleted') {
            return $modelName . ($subjectName ? " «{$subjectName}» " : ' ') . 'حذف شد توسط ' . $userName;
        }
        if ($log->event === 'login') {
            return $userName . ' وارد سیستم شد';
        }
        if ($log->event === 'logout') {
            return $userName . ' از سیستم خارج شد';
        }
        if ($log->event === 'restored') {
            return $modelName . ($subjectName ? " «{$subjectName}» " : ' ') . 'بازیابی شد توسط ' . $userName;
        }
        
        return $log->description ?? ($modelName . ' تغییر کرد');
    }
    
    function formatValue($value, $key, $typeLabels, $attributeLabels) {
        // اگر مقدار null یا خالی است
        if (is_null($value) || $value === '') {
            return '-';
        }
        
        // بررسی فیلدهای خاص
        if ($key === 'is_active' || $key === 'is_verified' || $key === 'is_approved' || $key === 'is_head' || $key === 'is_insured') {
            return $value ? 'فعال' : 'غیرفعال';
        }
        
        if ($key === 'type' || $key === 'user_type' || $key === 'gender' || $key === 'relation' || 
            $key === 'status' || $key === 'payment_method') {
            return $typeLabels[$value] ?? $value;
        }
        
        // فرمت کردن تاریخ‌ها
        if (str_contains($key, 'date') || str_contains($key, '_at')) {
            try {
                return jdate($value)->format('Y/m/d H:i');
            } catch (Exception $e) {
                return $value;
            }
        }
        
        // فرمت کردن مبالغ
        if (str_contains($key, 'amount') || str_contains($key, 'income') || $key === 'amount') {
            return number_format($value) . ' تومان';
        }
        
        return $value;
    }
@endphp

<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-semibold text-gray-700">جزئیات لاگ فعالیت #{{ $activity->id }}</h2>
                    <a href="{{ route('admin.logs.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg flex items-center justify-center text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        بازگشت به لیست
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- اطلاعات اصلی -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-md font-medium text-gray-700 mb-4">اطلاعات اصلی</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-600">کاربر:</span>
                                <span class="text-gray-900">
                                    {{ $activity->causer ? ($activity->causer->name ?? $activity->causer->username ?? $activity->causer->mobile ?? '-') : 'سیستم' }}
                                    @if($activity->causer && $activity->causer->user_type)
                                        <span class="text-sm text-gray-500">
                                            ({{ $typeLabels[$activity->causer->user_type] ?? $activity->causer->user_type }})
                                        </span>
                                    @endif
                                </span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-600">عملیات:</span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    @if($activity->event == 'created') bg-green-100 text-green-800
                                    @elseif($activity->event == 'updated') bg-blue-100 text-blue-800
                                    @elseif($activity->event == 'deleted') bg-red-100 text-red-800
                                    @elseif($activity->event == 'login') bg-purple-100 text-purple-800
                                    @elseif($activity->event == 'logout') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $eventLabels[$activity->event] ?? $activity->event }}
                                </span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-600">مورد تغییر:</span>
                                <span class="text-gray-900">
                                    {{ $modelLabels[class_basename($activity->subject_type) ?? ''] ?? class_basename($activity->subject_type) }}
                                    @if($activity->subject_id)
                                        <span class="text-gray-500">(شناسه: {{ $activity->subject_id }})</span>
                                    @endif
                                    
                                    {{-- نمایش اطلاعات اضافی --}}
                                    @if($activity->subject)
                                        <div class="text-xs text-gray-600 mt-1">
                                            @if(class_basename($activity->subject_type) === 'User')
                                                {{ $activity->subject->name ?? $activity->subject->username ?? $activity->subject->mobile ?? 'بدون نام' }}
                                                @if($activity->subject->user_type)
                                                    <span class="text-gray-400">({{ $typeLabels[$activity->subject->user_type] ?? $activity->subject->user_type }})</span>
                                                @endif
                                            @elseif(class_basename($activity->subject_type) === 'Organization')
                                                {{ $activity->subject->name ?? 'بدون نام' }}
                                                @if($activity->subject->type)
                                                    <span class="text-gray-400">({{ $typeLabels[$activity->subject->type] ?? $activity->subject->type }})</span>
                                                @endif
                                            @elseif(class_basename($activity->subject_type) === 'Family')
                                                {{ $activity->subject->head_name ?? 'خانواده' }}
                                                @if($activity->subject->family_code)
                                                    <span class="text-gray-400">(کد: {{ $activity->subject->family_code }})</span>
                                                @endif
                                            @elseif(class_basename($activity->subject_type) === 'Member')
                                                {{ $activity->subject->name ?? 'عضو' }}
                                                @if($activity->subject->national_id)
                                                    <span class="text-gray-400">({{ $activity->subject->national_id }})</span>
                                                @endif
                                            @elseif(class_basename($activity->subject_type) === 'Region')
                                                {{ $activity->subject->name ?? 'منطقه' }}
                                            @else
                                                {{ $activity->subject->name ?? $activity->subject->title ?? $activity->subject->display_name ?? '' }}
                                            @endif
                                        </div>
                                    @endif
                                </span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-600">تاریخ:</span>
                                <span class="text-gray-900">{{ jdate($activity->created_at)->format('Y/m/d H:i:s') }}</span>
                            </div>
                            
                            @if($activity->description)
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-600">توضیحات:</span>
                                <span class="text-gray-900">{{ $activity->description }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- اطلاعات تغییرات -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-md font-medium text-gray-700 mb-4">جزئیات تغییرات</h3>
                        
                        @if(isset($activity->properties['attributes']) && is_array($activity->properties['attributes']))
                            <div class="space-y-2">
                                <h4 class="text-sm font-medium text-gray-600">مقادیر جدید:</h4>
                                <div class="bg-white rounded border p-3">
                                    @foreach($activity->properties['attributes'] as $key => $value)
                                        <div class="flex justify-between py-1 border-b border-gray-100 last:border-b-0">
                                            <span class="text-sm font-medium text-gray-600">
                                                {{ $attributeLabels[$key] ?? $key }}:
                                            </span>
                                            <span class="text-sm text-gray-900">
                                                {{ formatValue($value, $key, $typeLabels, $attributeLabels) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(isset($activity->properties['old']) && is_array($activity->properties['old']))
                            <div class="space-y-2 mt-4">
                                <h4 class="text-sm font-medium text-gray-600">مقادیر قبلی:</h4>
                                <div class="bg-white rounded border p-3">
                                    @foreach($activity->properties['old'] as $key => $value)
                                        <div class="flex justify-between py-1 border-b border-gray-100 last:border-b-0">
                                            <span class="text-sm font-medium text-gray-600">
                                                {{ $attributeLabels[$key] ?? $key }}:
                                            </span>
                                            <span class="text-sm text-gray-900">
                                                {{ formatValue($value, $key, $typeLabels, $attributeLabels) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(!isset($activity->properties['attributes']) && !isset($activity->properties['old']))
                            <div class="bg-white rounded border p-3">
                                <p class="text-sm text-gray-600 mb-2">اطلاعات خام:</p>
                                <pre class="text-xs text-gray-600 overflow-x-auto bg-gray-50 p-2 rounded">{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 