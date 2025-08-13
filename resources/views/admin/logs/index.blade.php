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
@endphp

<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-semibold text-gray-700">لاگ فعالیت‌های سیستم</h2>
                    <a href="{{ route('admin.users.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg flex items-center justify-center text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                        </svg>
                        بازگشت به لیست
                    </a>
                </div>

                <!-- منوی ناوبری -->
                <x-admin-nav />

                <!-- جدول لاگ‌ها -->
                <!-- جدول لاگ‌ها -->
                <div class="overflow-x-auto bg-white rounded-lg shadow overflow-y-auto relative">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    #
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    کاربر
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    عملیات
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    مورد تغییر
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    تاریخ
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    عملیات
                                </th>
                    </tr>
                </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($logs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                    {{ $log->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $log->causer ? ($log->causer->name ?? $log->causer->username ?? $log->causer->mobile ?? '-') : 'سیستم' }}
                                    </div>
                                    @if($log->causer && $log->causer->user_type)
                                        <div class="text-sm text-gray-500">
                                            {{ $typeLabels[$log->causer->user_type] ?? $log->causer->user_type }}
                                        </div>
                                    @endif
                            </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if($log->event == 'created') bg-green-100 text-green-800
                                        @elseif($log->event == 'updated') bg-blue-100 text-blue-800
                                        @elseif($log->event == 'deleted') bg-red-100 text-red-800
                                        @elseif($log->event == 'login') bg-purple-100 text-purple-800
                                        @elseif($log->event == 'logout') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                {{ $eventLabels[$log->event] ?? $log->event }}
                                    </span>
                            </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="text-sm text-gray-900">
                                {{ $modelLabels[class_basename($log->subject_type) ?? ''] ?? class_basename($log->subject_type) }}
                                @if($log->subject_id)
                                            <span class="text-gray-500">(شناسه: {{ $log->subject_id }})</span>
                                        @endif

                                        {{-- نمایش اطلاعات اضافی بر اساس نوع مدل --}}
                                        @if($log->subject)
                                            <div class="text-xs text-gray-600 mt-1">
                                                @if(class_basename($log->subject_type) === 'User')
                                                    {{ $log->subject->name ?? $log->subject->username ?? $log->subject->mobile ?? 'بدون نام' }}
                                                    @if($log->subject->user_type)
                                                        <span class="text-gray-400">({{ $typeLabels[$log->subject->user_type] ?? $log->subject->user_type }})</span>
                                                    @endif
                                                @elseif(class_basename($log->subject_type) === 'Organization')
                                                    {{ $log->subject->name ?? 'بدون نام' }}
                                                    @if($log->subject->type)
                                                        <span class="text-gray-400">({{ $typeLabels[$log->subject->type] ?? $log->subject->type }})</span>
                                                    @endif
                                                @elseif(class_basename($log->subject_type) === 'Family')
                                                    {{ $log->subject->head_name ?? 'خانواده' }}
                                                    @if($log->subject->family_code)
                                                        <span class="text-gray-400">(کد: {{ $log->subject->family_code }})</span>
                                                    @endif
                                                @elseif(class_basename($log->subject_type) === 'Member')
                                                    {{ $log->subject->name ?? 'عضو' }}
                                                    @if($log->subject->national_id)
                                                        <span class="text-gray-400">({{ $log->subject->national_id }})</span>
                                                    @endif
                                                @elseif(class_basename($log->subject_type) === 'Region')
                                                    {{ $log->subject->name ?? 'منطقه' }}
                                                @else
                                                    {{ $log->subject->name ?? $log->subject->title ?? $log->subject->display_name ?? '' }}
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    @if($log->description && !$log->subject)
                                        <div class="text-sm text-gray-500 mt-1">
                                            {{ Str::limit($log->description, 50) }}
                                        </div>
                                @endif
                            </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    {{ jdate($log->created_at)->format('Y/m/d H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex justify-center">
                                        <a href="{{ route('admin.logs.show', $log) }}" class="text-blue-600 hover:text-blue-900" title="مشاهده جزئیات">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    هیچ لاگی ثبت نشده است.
                                </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
                </div>

                <!-- پیجینیشن -->
            <div class="mt-4">
                    @if(isset($logs) && $logs->hasPages())
                {{ $logs->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
