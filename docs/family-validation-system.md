# 📋 سیستم تایید وضعیت خانواده‌ها

سیستم تایید وضعیت خانواده‌ها شامل ۳ آیکون اصلی است که وضعیت تکمیل اطلاعات، محرومیت منطقه و مدارک مورد نیاز را نمایش می‌دهد.

## 🎯 ویژگی‌های کلیدی

### ✅ آیکون ۱: اطلاعات هویتی (ID)
- **هدف**: بررسی تکمیل بودن اطلاعات هویتی اعضای خانواده
- **فیلدهای بررسی شده**: `first_name`, `last_name`, `national_code`, `birth_date`
- **رنگ‌بندی**:
  - 🟢 **سبز**: اطلاعات همه اعضا کامل است (۱۰۰٪)
  - 🟡 **زرد**: درصدی از اعضا اطلاعات ناقص دارند (۳۰-۹۹٪)
  - 🔴 **قرمز**: اطلاعات اکثر اعضا ناقص است (۰-۲۹٪)

### 🌍 آیکون ۲: وضعیت محرومیت منطقه‌ای
- **هدف**: نمایش وضعیت محرومیت منطقه جغرافیایی خانواده
- **مسیر تشخیص**: `خانواده → خیریه → منطقه → استان`
- **رنگ‌بندی**:
  - 🟢 **سبز**: منطقه غیرمحروم
  - 🔴 **قرمز**: منطقه محروم (نیاز به توجه ویژه)
  - ⚪ **خاکستری**: وضعیت نامشخص

### 📄 آیکون ۳: مدارک مورد نیاز
- **هدف**: بررسی آپلود مدارک مربوط به شرایط خاص اعضا
- **انواع مدارک**:
  - مدرک بیماری خاص (`has_chronic_disease`)
  - مدرک معلولیت (`has_disability`)
- **رنگ‌بندی**:
  - 🟢 **سبز**: مدارک کامل و معتبر (۱۰۰٪)
  - 🟡 **زرد**: مدارک ناقص یا در بررسی (۳۰-۹۹٪)
  - 🔴 **قرمز**: مدارک ثبت نشده یا ناقص (۰-۲۹٪)
  - ✅ **تیک آبی**: هیچ عضوی نیاز به مدرک ندارد

## 🛠️ نحوه استفاده

### ۱. نمایش آیکون‌های ساده
```blade
<x-family-validation-icons :family="$family" size="sm" />
```

**پارامترها:**
- `family`: نمونه مدل خانواده
- `size`: اندازه آیکون‌ها (`sm`, `md`, `lg`)
- `showTooltips`: نمایش tooltip (پیشفرض: `true`)

### ۲. نمایش جزئیات کامل
```blade
<x-family-validation-detail :family="$family" :showActions="true" />
```

**پارامترها:**
- `family`: نمونه مدل خانواده
- `showActions`: نمایش اقدامات پیشنهادی (پیشفرض: `true`)

### ۳. استفاده از Helper کلاس
```php
use App\Helpers\FamilyValidationHelper;

// محاسبه نمره کلی
$overallData = FamilyValidationHelper::calculateOverallScore($family);

// بررسی آمادگی برای تایید
$readiness = FamilyValidationHelper::isReadyForApproval($family);

// دریافت پیام‌های کاربرپسند
$messages = FamilyValidationHelper::getUserFriendlyMessages($validationData);

// دریافت اقدامات پیشنهادی
$actions = FamilyValidationHelper::getSuggestedActions($validationData);
```

## ⚙️ تنظیمات

### فایل تنظیمات: `config/ui.php`

```php
return [
    'status_colors' => [
        'complete' => [
            'color' => 'green',
            'bg_class' => 'bg-green-100',
            'text_class' => 'text-green-800',
            'border_class' => 'border-green-300',
            'icon_class' => 'text-green-600',
        ],
        // سایر رنگ‌ها...
    ],
    
    'validation_thresholds' => [
        'complete_min' => 100, // حداقل درصد برای وضعیت کامل
        'partial_min' => 30,   // حداقل درصد برای وضعیت جزئی
    ],
    
    'family_validation_icons' => [
        'identity' => [
            'title' => 'اطلاعات هویتی',
            'required_fields' => ['first_name', 'last_name', 'national_code', 'birth_date'],
        ],
        // سایر تنظیمات...
    ],
];
```

## 🔧 متدهای موجود در مدل Family

```php
// دریافت وضعیت اطلاعات هویتی
$identityStatus = $family->getIdentityValidationStatus();

// دریافت وضعیت محرومیت منطقه
$locationStatus = $family->getLocationValidationStatus();

// دریافت وضعیت مدارک
$documentsStatus = $family->getDocumentsValidationStatus();

// دریافت تمام وضعیت‌ها
$allStatuses = $family->getAllValidationStatuses();
```

## 📊 ساختار خروجی داده‌ها

### وضعیت اطلاعات هویتی
```php
[
    'status' => 'partial',           // complete|partial|none|unknown
    'percentage' => 75,              // درصد تکمیل
    'message' => 'اطلاعات 3 از 4 عضو کامل است',
    'complete_members' => 3,         // تعداد اعضای کامل
    'total_members' => 4,            // کل اعضا
    'details' => [                   // جزئیات هر عضو
        [
            'member_id' => 1,
            'name' => 'علی احمدی',
            'completion_rate' => 100,
            'field_status' => [
                'first_name' => true,
                'last_name' => true,
                'national_code' => true,
                'birth_date' => false
            ],
            'is_head' => true
        ],
        // سایر اعضا...
    ]
]
```

### وضعیت محرومیت منطقه
```php
[
    'status' => 'none',              // complete|none|unknown
    'message' => 'منطقه محروم: تهران (رتبه محرومیت: 2)',
    'province_name' => 'تهران',
    'is_deprived' => true,
    'deprivation_rank' => 2,
    'path' => ['خانواده → خیریه → منطقه → استان']
]
```

### وضعیت مدارک
```php
[
    'status' => 'partial',           // complete|partial|none
    'percentage' => 50,              // درصد تکمیل
    'message' => 'مدارک 1 از 2 عضو نیازمند کامل است',
    'members_requiring_docs' => 2,   // تعداد اعضای نیازمند
    'members_with_complete_docs' => 1, // تعداد اعضا با مدارک کامل
    'details' => [                   // جزئیات هر عضو
        [
            'member_id' => 1,
            'name' => 'علی احمدی',
            'required_docs' => ['chronic_disease', 'disability'],
            'uploaded_docs' => 1,
            'completion_rate' => 50,
            'doc_status' => [
                'chronic_disease' => [
                    'required' => true,
                    'uploaded' => true,
                    'label' => 'مدرک بیماری مزمن'
                ],
                'disability' => [
                    'required' => true,
                    'uploaded' => false,
                    'label' => 'مدرک معلولیت'
                ]
            ],
            'is_head' => true
        ],
        // سایر اعضا...
    ]
]
```

## 🎨 سفارشی‌سازی ظاهر

### تغییر رنگ‌بندی
رنگ‌های آیکون‌ها را در فایل `config/ui.php` تغییر دهید:

```php
'status_colors' => [
    'complete' => [
        'bg_class' => 'bg-emerald-100',      // رنگ پس‌زمینه
        'text_class' => 'text-emerald-800',  // رنگ متن
        'icon_class' => 'text-emerald-600',  // رنگ آیکون
        'border_class' => 'border-emerald-300', // رنگ حاشیه
    ],
    // سایر وضعیت‌ها...
]
```

### تغییر آستانه‌های درصد
```php
'validation_thresholds' => [
    'complete_min' => 95,    // تغییر از ۱۰۰ به ۹۵
    'partial_min' => 40,     // تغییر از ۳۰ به ۴۰
]
```

### افزودن فیلدهای جدید برای بررسی
```php
'family_validation_icons' => [
    'identity' => [
        'required_fields' => [
            'first_name', 
            'last_name', 
            'national_code', 
            'birth_date',
            'father_name',    // فیلد جدید
            'mobile'          // فیلد جدید
        ],
    ],
]
```

## 🚀 مثال‌های کاربردی

### ۱. نمایش در جدول خانواده‌ها
```blade
@forelse($families as $family)
    <tr>
        <td>{{ $family->family_code }}</td>
        <td>{{ $family->head?->full_name }}</td>
        <td class="text-center">
            <x-family-validation-icons :family="$family" size="sm" />
        </td>
    </tr>
@empty
    <tr><td colspan="3">هیچ خانواده‌ای یافت نشد</td></tr>
@endforelse
```

### ۲. صفحه جزئیات خانواده
```blade
<div class="family-details">
    <h2>جزئیات خانواده {{ $family->family_code }}</h2>
    
    <!-- آیکون‌های خلاصه -->
    <div class="mb-6">
        <x-family-validation-icons :family="$family" size="lg" />
    </div>
    
    <!-- جزئیات کامل -->
    <x-family-validation-detail :family="$family" />
</div>
```

### ۳. بررسی برنامه‌نویسی آمادگی
```php
use App\Helpers\FamilyValidationHelper;

public function approveFamily(Family $family)
{
    $readiness = FamilyValidationHelper::isReadyForApproval($family);
    
    if (!$readiness['is_ready']) {
        return back()->withErrors([
            'validation' => 'خانواده آماده تایید نیست. اقدامات مورد نیاز: ' . 
                           implode('، ', $readiness['required_actions'])
        ]);
    }
    
    $family->update(['status' => 'approved']);
    
    return back()->with('success', 'خانواده با موفقیت تایید شد');
}
```

## 🔍 عیب‌یابی

### مشکلات رایج

**۱. آیکون‌ها نمایش داده نمی‌شوند:**
- بررسی کنید فایل `config/ui.php` وجود دارد
- اطمینان حاصل کنید کامپوننت‌ها در مسیر صحیح قرار دارند

**۲. درصدها نادرست محاسبه می‌شوند:**
- بررسی کنید روابط `members` در مدل `Family` صحیح است
- اطمینان حاصل کنید فیلدهای مورد نظر در دیتابیس موجودند

**۳. رنگ‌ها اعمال نمی‌شوند:**
- بررسی کنید کلاس‌های Tailwind مورد نظر در فایل CSS شامل شده‌اند
- cache پیکربندی را پاک کنید: `php artisan config:clear`

### لاگ‌گذاری برای عیب‌یابی
```php
// اضافه کردن لاگ در مدل Family
public function getIdentityValidationStatus(): array
{
    \Log::info('Family validation check', [
        'family_id' => $this->id,
        'members_count' => $this->members->count()
    ]);
    
    // ادامه کد...
}
```

## 📝 یادداشت‌های مهم

1. **کارایی**: متدهای اعتبارسنجی ممکن است کند باشند. برای نمایش در لیست‌های طولانی از cache استفاده کنید.

2. **امنیت**: اطلاعات حساس در tooltip ها نمایش داده نمی‌شوند.

3. **دسترسی**: تمام آیکون‌ها دارای `aria-label` و `title` مناسب هستند.

4. **رسپانسیو**: آیکون‌ها در تمام اندازه‌های صفحه به درستی نمایش داده می‌شوند.

---

**توسعه‌دهنده**: تیم توسعه سیستم مدیریت خانواده‌ها  
**آخرین به‌روزرسانی**: {{ now()->format('Y/m/d') }}  
**نسخه**: v1.0.0 