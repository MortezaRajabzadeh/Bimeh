# Family Edit Authorization System
# سیستم مجوزدهی ویرایش خانواده

## نمای کلی (Overview)

این مستند نحوه عملکرد سیستم مجوزدهی ویرایش خانواده و اعضای خانواده را در سیستم Laravel Super Starter توضیح می‌دهد.

This document explains how the family and member edit authorization system works in the Laravel Super Starter application.

---

## قوانین مجوزدهی (Authorization Rules)

### کاربران ادمین (Admin Users)
- **می‌توانند ویرایش کنند**: تمام خانواده‌ها در هر وضعیتی از wizard_status
- **لاگ‌گیری**: تمام ویرایش‌های ادمین از طریق `FamilyObserver` برای audit trail ثبت می‌شوند
- **دلیل**: ادمین‌ها برای رفع مشکلات و مدیریت سیستم نیاز به دسترسی کامل دارند

**Can edit**: All families in any wizard status  
**Logging**: All admin edits are logged via `FamilyObserver` for audit trail  
**Reason**: Admins need full control to fix issues and manage the system

---

### کاربران خیریه (Charity Users)
- **می‌توانند ویرایش کنند**: فقط خانواده‌های خودشان در وضعیت `PENDING` یا `null`
- **نمی‌توانند ویرایش کنند**: خانواده‌ها در وضعیت‌های `REVIEWING`, `APPROVED`, `INSURED` و سایر وضعیت‌ها
- **دلیل**: وقتی خانواده از PENDING عبور کرد، تحت بررسی بیمه است و نباید توسط خیریه تغییر کند

**Can edit**: Only their own families in `PENDING` or `null` wizard status  
**Cannot edit**: Families in `REVIEWING`, `APPROVED`, `INSURED`, or other statuses  
**Reason**: Once a family moves past PENDING, it's under insurance review and should not be modified by charity

---

### کاربران بیمه (Insurance Users)
- **نمی‌توانند ویرایش کنند**: هیچ خانواده‌ای
- **دلیل**: کاربران بیمه فقط بررسی و تایید خانواده‌ها را انجام می‌دهند، داده‌های خانواده را ویرایش نمی‌کنند

**Cannot edit**: Any families  
**Reason**: Insurance users only review and approve families, they don't edit family data

---

## اجزای پیاده‌سازی (Implementation Components)

### 1. FamilyPolicy (`app/Policies/FamilyPolicy.php`)

منطق مجوزدهی را تعریف می‌کند:

**Defines the authorization logic:**

```php
public function update(User $user, Family $family): bool
{
    // ادمین‌ها می‌توانند هر خانواده‌ای را ویرایش کنند
    // Admins can edit any family
    if ($user->isAdmin()) {
        return true;
    }
    
    // خیریه‌ها فقط می‌توانند خانواده‌های خودشان را در وضعیت PENDING/null ویرایش کنند
    // Charities can only edit their own families in PENDING/null status
    if ($user->isCharity()) {
        $ownsFamily = $family->charity_id === $user->organization_id;
        $isEditable = $family->wizard_status === InsuranceWizardStep::PENDING->value 
                   || $family->wizard_status === null;
        return $ownsFamily && $isEditable;
    }
    
    return false;
}
```

**متد‌های Policy:**
- `update(User $user, Family $family)`: بررسی مجوز ویرایش خانواده
- `updateMembers(User $user, Family $family)`: بررسی مجوز ویرایش اعضای خانواده (به `update` واگذار می‌شود)

---

### 2. FamilySearch Component (`app/Livewire/Charity/FamilySearch.php`)

مجوزدهی را در متدهای ویرایش اعمال می‌کند:

**Enforces authorization in edit methods:**

#### متد `editMember($memberId)`
```php
public function editMember($memberId)
{
    $member = Member::find($memberId);
    $family = $member->family;
    
    try {
        Gate::authorize('updateMembers', $family);
    } catch (AuthorizationException $e) {
        // نمایش پیام خطای متناسب با context
        // Show context-aware error message
        $this->dispatch('notify', [
            'message' => $this->getAuthorizationErrorMessage($family),
            'type' => 'error'
        ]);
        
        // ثبت لاگ تلاش غیرمجاز
        // Log unauthorized attempt
        Log::warning('Unauthorized member edit attempt', [
            'user_id' => Auth::id(),
            'member_id' => $memberId,
            'family_id' => $family->id,
            'wizard_status' => $family->wizard_status
        ]);
        
        return;
    }
    
    // ادامه فرآیند ویرایش...
    // Continue edit process...
}
```

#### متد `saveMember()`
```php
public function saveMember()
{
    $member = Member::find($this->editingMemberId);
    $family = $member->family;
    
    try {
        Gate::authorize('updateMembers', $family);
    } catch (AuthorizationException $e) {
        // نمایش خطا و reset حالت ویرایش
        // Show error and reset edit state
        $this->editingMemberId = null;
        return;
    }
    
    // اعتبارسنجی و ذخیره...
    // Validate and save...
}
```

#### متد `getAuthorizationErrorMessage($family)`
پیام‌های خطای سفارشی بر اساس wizard_status تولید می‌کند:

**Generates context-aware error messages based on wizard_status:**

```php
protected function getAuthorizationErrorMessage($family)
{
    $wizardStatus = $family->wizard_status;
    
    if ($wizardStatus) {
        $statusEnum = InsuranceWizardStep::from($wizardStatus);
        $statusLabel = $statusEnum->label();
        
        return match($wizardStatus) {
            'reviewing' => "این خانواده در مرحله {$statusLabel} است و فقط ادمین می‌تواند ویرایش کند",
            'approved' => "این خانواده تایید شده ({$statusLabel}) و فقط ادمین می‌تواند ویرایش کند",
            'insured' => "این خانواده بیمه شده ({$statusLabel}) و فقط ادمین می‌تواند ویرایش کند",
            // ...
            default => "این خانواده در مرحله {$statusLabel} است و فقط ادمین می‌تواند ویرایش کند"
        };
    }
    
    return 'شما مجوز ویرایش این خانواده را ندارید. فقط ادمین می‌تواند ویرایش کند';
}
```

---

### 3. Blade View (`resources/views/livewire/charity/family-search.blade.php`)

به صورت شرطی المان‌های UI را نمایش/مخفی می‌کند:

**Conditionally shows/hides UI elements:**

```blade
@can('updateMembers', $family)
    {{-- نمایش دکمه ویرایش / Show edit button --}}
    <button wire:click="editMember({{ $member->id }})">
        ویرایش
    </button>
@else
    {{-- نمایش نشان "فقط ادمین" / Show "Admin Only" badge --}}
    <div class="inline-flex items-center px-2 py-1 rounded-md text-xs bg-yellow-50 border border-yellow-200 text-yellow-800" 
         title="وضعیت: {{ $family->wizard_status ? \App\Enums\InsuranceWizardStep::from($family->wizard_status)->label() : 'در انتظار تایید' }}">
        <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
        </svg>
        <span>فقط ادمین</span>
    </div>
@endcan
```

---

## جریان وضعیت Wizard (Wizard Status Flow)

```
PENDING (قابل ویرایش توسط خیریه / Editable by charity)
    ↓
REVIEWING (فقط ادمین / Admin only)
    ↓
SHARE_ALLOCATION (فقط ادمین / Admin only)
    ↓
APPROVED (فقط ادمین / Admin only)
    ↓
EXCEL_UPLOAD (فقط ادمین / Admin only)
    ↓
INSURED (فقط ادمین / Admin only)
```

---

## پیام‌های خطا (Error Messages)

پیام‌های خطای سفارشی بر اساس wizard_status:

**Context-aware error messages based on wizard status:**

| wizard_status | پیام فارسی | English Message |
|--------------|------------|-----------------|
| **PENDING** | "خطای غیرمنتظره: شما باید بتوانید این خانواده را ویرایش کنید" | "Unexpected error: You should be able to edit this family" |
| **REVIEWING** | "این خانواده در مرحله تخصیص سهمیه است و فقط ادمین می‌تواند ویرایش کند" | "This family is in review stage and only admin can edit" |
| **APPROVED** | "این خانواده تایید شده و فقط ادمین می‌تواند ویرایش کند" | "This family is approved and only admin can edit" |
| **INSURED** | "این خانواده بیمه شده و فقط ادمین می‌تواند ویرایش کند" | "This family is insured and only admin can edit" |

---

## لاگ‌گیری (Logging)

همه تلاش‌های ویرایش لاگ می‌شوند:

**All edit attempts are logged:**

### ویرایش‌های مجاز توسط ادمین (Authorized edits by admin)
- لاگ از طریق `FamilyObserver` با user_id و تغییرات
- Logged via `FamilyObserver` with user_id and changes

**مثال لاگ:**
```php
[
    'user_id' => 1,
    'family_id' => 123,
    'changes' => [
        'relationship' => ['پدر', 'مادر'],
        'occupation' => ['کارگر', 'بازنشسته']
    ],
    'timestamp' => '2025-01-07 10:30:45'
]
```

### تلاش‌های غیرمجاز (Unauthorized attempts)
- لاگ در کامپوننت `FamilySearch` با user_id, family_id, member_id, و wizard_status
- Logged in `FamilySearch` component with user_id, family_id, member_id, and wizard_status

**مثال لاگ:**
```php
Log::warning('Unauthorized member edit attempt', [
    'user_id' => 5,
    'member_id' => 456,
    'family_id' => 123,
    'wizard_status' => 'reviewing'
]);
```

---

## تست‌ها (Testing)

تست‌های authorization را اجرا کنید:

**Run the authorization tests:**

```bash
php artisan test --filter=FamilyEditAuthorizationTest
```

### سناریوهای تست (Test Scenarios)

✅ **تست 1**: ادمین می‌تواند خانواده را در هر وضعیتی ویرایش کند  
✅ **تست 2**: خیریه می‌تواند خانواده خود را در PENDING ویرایش کند  
✅ **تست 3**: خیریه می‌تواند خانواده خود را با status خالی ویرایش کند  
❌ **تست 4**: خیریه نمی‌تواند خانواده خود را در REVIEWING ویرایش کند  
❌ **تست 5**: خیریه نمی‌تواند خانواده خود را در APPROVED ویرایش کند  
❌ **تست 6**: خیریه نمی‌تواند خانواده خیریه دیگر را ویرایش کند  
❌ **تست 7**: کاربر بیمه نمی‌تواند هیچ خانواده‌ای را ویرایش کند  
🔒 **تست 8**: دکمه ویرایش برای کاربران غیرمجاز مخفی است  
📛 **تست 9**: نشان "فقط ادمین" برای کاربران غیرمجاز نمایش داده می‌شود  
📝 **تست 10**: تلاش‌های غیرمجاز لاگ می‌شوند  
💬 **تست 11**: پیام‌های خطای متناسب با context نمایش داده می‌شوند  
🚫 **تست 12**: ذخیره تغییرات توسط کاربر غیرمجاز مسدود می‌شود  

---

## نشانگرهای UI (UI Indicators)

### دکمه ویرایش (مجاز) - Edit Button (Authorized)
- دکمه آبی با آیکون ویرایش
- کلیک کردن فیلدهای ویرایش inline را نمایش می‌دهد

**Blue button with edit icon**  
**Clicking shows inline edit fields**

```html
<button class="bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
    <svg><!-- edit icon --></svg>
    ویرایش
</button>
```

---

### نشان "فقط ادمین" (غیرمجاز) - Admin Only Badge (Unauthorized)
- نشان زرد با آیکون قفل
- Tooltip وضعیت wizard_status فعلی را نمایش می‌دهد
- متن: "فقط ادمین"

**Yellow badge with lock icon**  
**Tooltip shows current wizard status**  
**Text: "فقط ادمین"**

```html
<div class="bg-yellow-50 border-yellow-200 text-yellow-800" 
     title="وضعیت: در حال بررسی">
    <svg><!-- lock icon --></svg>
    <span>فقط ادمین</span>
</div>
```

---

## لایه‌های امنیتی (Security Layers)

| لایه | محل | عملکرد |
|------|-----|--------|
| **UI (Blade)** | `@can` directive | مخفی کردن دکمه‌های ویرایش |
| **Backend (Livewire)** | `editMember()` | بلاک کردن شروع ویرایش |
| **Backend (Livewire)** | `saveMember()` | بلاک کردن ذخیره تغییرات |
| **Policy** | `FamilyPolicy` | منطق اصلی authorization |
| **Observer** | `FamilyObserver` | لاگ تغییرات Admin |

| Layer | Location | Function |
|-------|----------|----------|
| **UI (Blade)** | `@can` directive | Hide edit buttons |
| **Backend (Livewire)** | `editMember()` | Block starting edit |
| **Backend (Livewire)** | `saveMember()` | Block saving changes |
| **Policy** | `FamilyPolicy` | Core authorization logic |
| **Observer** | `FamilyObserver` | Log admin changes |

---

## نمودار معماری (Architecture Diagram)

```
User Request
     ↓
[Blade View]
  @can('updateMembers', $family)
     ↓
[FamilyPolicy]
  - Check user role
  - Check family ownership
  - Check wizard_status
     ↓
[Authorized] ────→ [Livewire Component]
     ↓                    ↓
[Edit Member]      [Gate::authorize()]
     ↓                    ↓
[Save Changes]     [Update Database]
     ↓                    ↓
[Success]          [FamilyObserver]
                        ↓
                   [Log Changes]

[Unauthorized] ───→ [Show Error Badge]
                        ↓
                   [Log Attempt]
```

---

## بهبودهای آینده (Future Enhancements)

1. ✨ **مجوزهای سطح فیلد**: افزودن مجوزهای مبتنی بر نقش برای فیلدهای خاص  
   **Field-level permissions**: Add role-based edit permissions for specific fields

2. 🔓 **دسترسی موقت**: پیاده‌سازی grants دسترسی موقت توسط ادمین  
   **Temporary access**: Implement temporary edit access grants by admin

3. 📜 **تایم‌لاین تاریخچه**: ایجاد تایم‌لاین تاریخچه ویرایش در جزئیات خانواده  
   **Edit history timeline**: Create edit history timeline in family details

4. 🔔 **سیستم اعلان**: ایجاد سیستم اعلان برای درخواست‌های ویرایش  
   **Notification system**: Create notification system for edit requests

5. 🎯 **Audit Dashboard**: داشبورد تخصصی برای مشاهده تمام تلاش‌های ویرایش و تغییرات  
   **Audit Dashboard**: Dedicated dashboard to view all edit attempts and changes

---

## تماس و پشتیبانی (Contact & Support)

برای سوالات یا مشکلات مربوط به سیستم authorization:

**For questions or issues related to the authorization system:**

- 📧 Email: support@example.com
- 📝 Issue Tracker: GitHub Issues
- 📚 Documentation: `/docs`

---

## تاریخچه تغییرات (Changelog)

### Version 1.0.0 (2025-01-07)
- ✅ پیاده‌سازی کامل FamilyPolicy
- ✅ اضافه کردن authorization checks در Livewire
- ✅ پیاده‌سازی UI indicators در Blade
- ✅ اضافه کردن context-aware error messages
- ✅ پیاده‌سازی logging برای تلاش‌های غیرمجاز
- ✅ ایجاد comprehensive test suite

---

**آخرین به‌روزرسانی**: 2025-01-07  
**نگارش**: 1.0.0  
**نویسنده**: Laravel Super Starter Team
