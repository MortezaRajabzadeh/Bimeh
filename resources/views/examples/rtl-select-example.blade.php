<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مثال Select Box در محیط RTL</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">
            مثال Select Box در محیط RTL
        </h1>

        <!-- مثال 1: استفاده از کامپوننت -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-bold mb-4">روش 1: استفاده از کامپوننت</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-2 font-bold">انتخاب شهر:</label>
                    <x-select-with-icon>
                        <option value="">انتخاب کنید</option>
                        <option value="tehran">تهران</option>
                        <option value="isfahan">اصفهان</option>
                        <option value="shiraz">شیراز</option>
                        <option value="mashhad">مشهد</option>
                    </x-select-with-icon>
                </div>
                <div>
                    <label class="block mb-2 font-bold">انتخاب استان:</label>
                    <x-select-with-icon>
                        <option value="">انتخاب کنید</option>
                        <option value="tehran">تهران</option>
                        <option value="isfahan">اصفهان</option>
                        <option value="fars">فارس</option>
                        <option value="khorasan">خراسان رضوی</option>
                    </x-select-with-icon>
                </div>
            </div>
        </div>

        <!-- مثال 2: استفاده مستقیم از کلاس‌ها -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-bold mb-4">روش 2: استفاده مستقیم از کلاس‌ها</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-2 font-bold">نوع کاربر:</label>
                    <div class="rtl-select-wrapper">
                        <select class="rtl-select block w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">انتخاب کنید</option>
                            <option value="admin">مدیر</option>
                            <option value="user">کاربر عادی</option>
                            <option value="guest">مهمان</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block mb-2 font-bold">وضعیت:</label>
                    <div class="rtl-select-wrapper">
                        <select class="rtl-select block w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="">انتخاب کنید</option>
                            <option value="active">فعال</option>
                            <option value="inactive">غیرفعال</option>
                            <option value="pending">در انتظار</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- مثال 3: حالت غیرفعال -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <h2 class="text-xl font-bold mb-4">روش 3: حالت غیرفعال</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-2 font-bold">فیلد غیرفعال:</label>
                    <div class="rtl-select-wrapper">
                        <select class="rtl-select block w-full border rounded px-3 py-2" disabled>
                            <option value="">این فیلد غیرفعال است</option>
                            <option value="option1">گزینه 1</option>
                            <option value="option2">گزینه 2</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block mb-2 font-bold">فیلد عادی:</label>
                    <div class="rtl-select-wrapper">
                        <select class="rtl-select block w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">انتخاب کنید</option>
                            <option value="option1">گزینه 1</option>
                            <option value="option2">گزینه 2</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- تست LTR -->
        <div class="bg-white p-6 rounded-lg shadow-md" dir="ltr">
            <h2 class="text-xl font-bold mb-4">Test LTR Mode</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-2 font-bold">Select City:</label>
                    <div class="rtl-select-wrapper">
                        <select class="rtl-select block w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Choose...</option>
                            <option value="new-york">New York</option>
                            <option value="london">London</option>
                            <option value="paris">Paris</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block mb-2 font-bold">Select Country:</label>
                    <x-select-with-icon>
                        <option value="">Choose...</option>
                        <option value="usa">United States</option>
                        <option value="uk">United Kingdom</option>
                        <option value="france">France</option>
                    </x-select-with-icon>
                </div>
            </div>
        </div>

        <!-- راهنمای استفاده -->
        <div class="bg-blue-50 p-6 rounded-lg shadow-md mt-6">
            <h2 class="text-xl font-bold mb-4 text-blue-800">راهنمای استفاده</h2>
            <div class="text-blue-700 space-y-2">
                <p><strong>روش 1:</strong> استفاده از کامپوننت <code>&lt;x-select-with-icon&gt;</code></p>
                <p><strong>روش 2:</strong> استفاده از کلاس‌های <code>rtl-select</code> و <code>rtl-select-wrapper</code></p>
                <p><strong>ویژگی‌ها:</strong></p>
                <ul class="list-disc list-inside mr-4 space-y-1">
                    <li>آیکون dropdown در سمت چپ برای RTL و سمت راست برای LTR</li>
                    <li>متن همچنان راست‌چین در RTL و چپ‌چین در LTR</li>
                    <li>سازگار با تمام مرورگرهای مدرن</li>
                    <li>پشتیبانی از حالت disabled</li>
                    <li>سازگار با Tailwind CSS</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html> 