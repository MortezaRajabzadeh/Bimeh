# 📋 راهنمای استفاده از Custom Select در پروژه

این راهنما برای حل مشکل آیکون dropdown که روی متن می‌افتد در سایت‌های فارسی طراحی شده.

## 🎯 مشکل اصلی
در سایت‌های فارسی (RTL)، آیکون پیش‌فرض dropdown در سمت راست قرار می‌گیرد و روی متن می‌افتد.

## ✅ راه‌حل‌های موجود

### 1️⃣ استفاده از کامپوننت Blade (توصیه شده)

```html
<!-- روش ساده -->
<x-custom-select wire:model.live="perPage" width="w-20" class="text-center">
    <option value="5">5</option>
    <option value="10">10</option>
    <option value="15">15</option>
    <option value="20">20</option>
    <option value="30">30</option>
</x-custom-select>

<!-- با آرایه options -->
<x-custom-select 
    wire:model="status" 
    :options="[
        'active' => 'فعال',
        'inactive' => 'غیرفعال',
        'pending' => 'در انتظار'
    ]"
    placeholder="وضعیت را انتخاب کنید"
    class="text-right"
/>
```

### 2️⃣ استفاده از کلاس CSS مشترک

```html
<!-- با background image -->
<select class="custom-select w-32 h-9 border rounded-md">
    <option>گزینه 1</option>
    <option>گزینه 2</option>
</select>

<!-- با wrapper (بهتر) -->
<div class="select-wrapper w-32">
    <select class="h-9 border rounded-md">
        <option>گزینه 1</option>
        <option>گزینه 2</option>
    </select>
    <svg class="dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
    </svg>
</div>
```

### 3️⃣ ساخت دستی (برای موارد خاص)

```html
<div class="relative w-full">
    <select 
        wire:model="myField"
        class="appearance-none w-full border border-gray-300 rounded-md pr-4 pl-10 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
    >
        <option value="">انتخاب کنید...</option>
        <option value="1">گزینه 1</option>
        <option value="2">گزینه 2</option>
    </select>
    
    <!-- آیکون dropdown -->
    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </div>
</div>
```

## 🎨 انواع استایل‌ها

### سایزهای مختلف:
```html
<!-- کوچک -->
<div class="select-wrapper select-sm w-16">
    <select class="h-8 text-xs">...</select>
    <svg class="dropdown-icon">...</svg>
</div>

<!-- عادی -->
<div class="select-wrapper w-24">
    <select class="h-9 text-sm">...</select>
    <svg class="dropdown-icon">...</svg>
</div>

<!-- بزرگ -->
<div class="select-wrapper select-lg w-32">
    <select class="h-10 text-base">...</select>
    <svg class="dropdown-icon">...</svg>
</div>
```

### رنگ‌های مختلف:
```html
<!-- سبز (پیش‌فرض) -->
<x-custom-select class="focus:ring-green-500 focus:border-green-500">

<!-- آبی -->
<x-custom-select class="focus:ring-blue-500 focus:border-blue-500">

<!-- قرمز -->
<x-custom-select class="focus:ring-red-500 focus:border-red-500">
```

## 📱 نکات مهم

### ✅ انجام دهید:
- از `pr-4 pl-10` برای padding استفاده کنید
- همیشه `appearance-none` اضافه کنید
- آیکون را `pointer-events-none` کنید
- از `relative` و `absolute` positioning استفاده کنید

### ❌ انجام ندهید:
- از `text-align: center` بدون padding مناسب استفاده نکنید
- آیکون را در سمت راست قرار ندهید
- CSS های قدیمی browser را نادیده نگیرید

## 🔧 عیب‌یابی

### مشکل: آیکون هنوز روی متن می‌افتد
```css
/* اضافه کنید */
select {
    padding-left: 2.5rem !important;
    padding-right: 1rem !important;
}
```

### مشکل: آیکون ظاهر نمی‌شود
```html
<!-- z-index اضافه کنید -->
<div class="absolute ... z-10">
```

### مشکل: در موبایل درست کار نمی‌کند
```css
@media (max-width: 640px) {
    .select-wrapper .dropdown-icon {
        width: 0.875rem;
        height: 0.875rem;
    }
}
```

## 📁 فایل‌های مرتبط

- `resources/views/components/custom-select.blade.php` - کامپوننت اصلی
- `resources/css/app.css` - CSS های مشترک
- `resources/views/livewire/insurance/deprived-areas.blade.php` - نمونه استفاده

## ⚡ مثال‌های کاربردی

### فرم‌ها:
```html
<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium mb-2">استان</label>
        <x-custom-select wire:model="province_id" :options="$provinces" />
    </div>
    <div>
        <label class="block text-sm font-medium mb-2">وضعیت</label>
        <x-custom-select wire:model="status" class="text-center">
            <option value="active">فعال</option>
            <option value="inactive">غیرفعال</option>
        </x-custom-select>
    </div>
</div>
```

### جداول و فیلترها:
```html
<div class="flex items-center gap-4 mb-6">
    <span class="text-sm text-gray-600">تعداد نمایش:</span>
    <x-custom-select wire:model.live="perPage" width="w-20" class="text-center">
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
    </x-custom-select>
</div>
```

---

💡 **نکته**: این کامپوننت‌ها در تمام پروژه قابل استفاده هستند و نیازی به تکرار کد نیست! 