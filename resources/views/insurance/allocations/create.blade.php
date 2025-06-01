<x-app-layout>
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <!-- هدر -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-1">ایجاد تخصیص بودجه جدید</h1>
            <p class="text-gray-600">تخصیص بودجه از منابع مالی به خانواده‌های بیمه شده از آخرین فایل آپلود شده</p>
        </div>
        <div class="flex gap-2 mt-4 md:mt-0">
            <a href="{{ route('insurance.allocations.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md flex items-center">
                <i class="fas fa-arrow-right ml-2"></i>
                بازگشت به لیست
            </a>
        </div>
    </div>

    <!-- متغیرهای موردنیاز برای جاوااسکریپت -->
    <div id="app-data" 
         data-total-families="{{ $insuredFamilies }}" 
         data-total-premium="{{ $totalFamilyPremium }}" 
         class="hidden"></div>

    <!-- نمایش پیام‌های خطا -->
    @if ($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <h3 class="font-bold mb-2">لطفاً خطاهای زیر را برطرف کنید:</h3>
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- فرم ایجاد - 2/3 -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b border-gray-200">
                    <h5 class="font-bold text-gray-700 flex items-center">
                        <i class="fas fa-plus-circle ml-2 text-gray-500"></i>
                        فرم ایجاد تخصیص بودجه برای همه خانواده‌های بیمه شده
                    </h5>
                </div>
                <div class="p-6">
                    @if(isset($noImportFound) && $noImportFound)
                        <div class="bg-yellow-100 p-6 rounded-lg mb-6">
                            <div class="flex items-center mb-4">
                                <i class="fas fa-exclamation-triangle text-yellow-600 text-3xl ml-4"></i>
                                <div>
                                    <h4 class="font-bold text-lg text-yellow-800 mb-1">هیچ فایل اکسلی آپلود نشده است</h4>
                                    <p class="text-yellow-700">برای ایجاد تخصیص بودجه، ابتدا باید فایل اکسل حاوی اطلاعات خانواده‌ها و حق بیمه آن‌ها را آپلود کنید.</p>
                                </div>
                            </div>
                            <div class="flex justify-center">
                                <a href="{{ route('insurance.families.approval') }}" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-md inline-flex items-center shadow-md">
                                    <i class="fas fa-upload ml-2"></i>
                                    رفتن به صفحه آپلود فایل اکسل
                                </a>
                            </div>
                        </div>
                    @elseif(isset($noFamilyCodesFound) && $noFamilyCodesFound)
                        <div class="bg-yellow-100 p-6 rounded-lg mb-6">
                            <div class="flex items-center mb-4">
                                <i class="fas fa-exclamation-triangle text-yellow-600 text-3xl ml-4"></i>
                                <div>
                                    <h4 class="font-bold text-lg text-yellow-800 mb-1">هیچ خانواده‌ای در آخرین فایل آپلود شده یافت نشد</h4>
                                    <p class="text-yellow-700">به نظر می‌رسد آخرین فایل اکسل آپلود شده فاقد اطلاعات خانواده است یا فرمت آن صحیح نیست.</p>
                                </div>
                            </div>
                            <div class="flex justify-center">
                                <a href="{{ route('insurance.families.approval') }}" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-md inline-flex items-center shadow-md">
                                    <i class="fas fa-upload ml-2"></i>
                                    آپلود دوباره فایل اکسل
                                </a>
                            </div>
                        </div>
                    @else
                    <form action="{{ route('insurance.allocations.store') }}" method="POST" id="allocationForm">
                        @csrf

                        <!-- نمایش اطلاعات خانواده‌ها -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">خانواده‌های بیمه شده</label>
                            <div class="bg-blue-50 p-4 rounded-md">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-info-circle text-blue-600 ml-2"></i>
                                    <p class="text-blue-700">تخصیص برای تمام خانواده‌های بیمه شده انجام می‌شود</p>
                                </div>
                                <div class="flex gap-3">
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 ml-1">تعداد کل خانواده‌ها:</span>
                                        <span class="font-bold text-blue-700">{{ $insuredFamilies }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-sm text-gray-600 ml-1">مجموع حق بیمه:</span>
                                        <span class="font-bold text-blue-700">{{ number_format($totalFamilyPremium, 0, '.', '٬') }} تومان</span>
                                    </div>
                                </div>
                                
                                @if(isset($lastImportLog))
                                <div class="mt-3 border-t border-blue-200 pt-2">
                                    <div class="flex items-center">
                                        <i class="fas fa-file-excel text-green-600 ml-2"></i>
                                        <p class="text-gray-700">اطلاعات از فایل: <span class="font-bold">{{ $lastImportLog->file_name }}</span></p>
                                    </div>
                                    <div class="flex items-center mt-1">
                                        <i class="fas fa-calendar-alt text-gray-500 ml-2"></i>
                                        <p class="text-gray-600">تاریخ آپلود: 
                                            <span class="font-medium">
                                                @if($lastImportLog->created_at)
                                                    @php
                                                        try {
                                                            echo jdate($lastImportLog->created_at)->format('Y/m/d H:i');
                                                        } catch (\Exception $e) {
                                                            echo $lastImportLog->created_at->format('Y/m/d H:i');
                                                        }
                                                    @endphp
                                                @endif
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                @endif
                                
                                @if($totalFamilyPremium == 0)
                                <div class="mt-2 p-2 bg-yellow-100 text-yellow-800 rounded-md">
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-triangle ml-2"></i>
                                        <p>توجه: مجموع حق بیمه خانواده‌ها صفر است. قبل از تخصیص بودجه، لطفاً ابتدا فایل اکسل حاوی اطلاعات حق بیمه را آپلود کنید.</p>
                                    </div>
                                    <div class="mt-2">
                                        <a href="{{ route('insurance.families.approval') }}" class="text-blue-600 hover:text-blue-800 underline">
                                            <i class="fas fa-upload ml-1"></i>
                                            رفتن به صفحه آپلود فایل اکسل
                                        </a>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- انتخاب منبع مالی -->
                        <div class="mb-6">
                            <label for="funding_source_id" class="block text-sm font-medium text-gray-700 mb-2">منبع مالی</label>
                            <select name="funding_source_id" id="funding_source_id" class="form-select rounded-md w-full border-gray-300" required>
                                <option value="">انتخاب منبع مالی...</option>
                                @foreach($fundingSources as $source)
                                    <option value="{{ $source->id }}" 
                                            data-budget="{{ $source->annual_budget ?? $source->budget ?? 0 }}"
                                            data-allocated="{{ $sourcesAllocations[$source->id] ?? 0 }}">
                                        {{ $source->name }} (بودجه: {{ number_format($source->annual_budget ?? $source->budget ?? 0, 0, '.', '٬') }} تومان)
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">فقط منابع مالی فعال نمایش داده می‌شوند.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="percentage" class="block text-sm font-medium text-gray-700 mb-2">درصد تخصیص (%)</label>
                                <div class="relative">
                                    <input type="number" name="percentage" id="percentage" class="form-input rounded-md w-full border-gray-300" 
                                           min="1" max="100" step="0.01" value="{{ old('percentage', 10) }}" required>
                                    <div class="absolute inset-y-0 left-0 flex items-center pointer-events-none px-3 text-gray-500">
                                        <i class="fas fa-percent"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">درصد تخصیص از کل حق بیمه هر خانواده</p>
                            </div>
                            
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">مبلغ کل تخصیص (تومان)</label>
                                <div class="relative">
                                    <input type="text" name="amount_display" id="amount_display" class="form-input rounded-md w-full border-gray-300" 
                                           readonly>
                                    <input type="hidden" name="amount" id="amount" value="0">
                                    <div class="absolute inset-y-0 left-0 flex items-center pointer-events-none px-3 text-gray-500">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">مبلغ محاسبه شده بر اساس درصد تخصیص (به صورت خودکار پر می‌شود)</p>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                            <textarea name="description" id="description" rows="3" class="form-textarea rounded-md w-full border-gray-300">{{ old('description') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">اختیاری - هر توضیح اضافی در مورد این تخصیص</p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-md" id="submitBtn">
                                <i class="fas fa-plus-circle ml-2"></i>
                                ثبت تخصیص
                            </button>
                        </div>
                    </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- ستون کناری - 1/3 -->
        <div class="space-y-6">
            <!-- پیش‌نمایش اطلاعات -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b border-gray-200">
                    <h6 class="font-bold text-gray-700 flex items-center">
                        <i class="fas fa-eye ml-2 text-gray-500"></i>
                        پیش‌نمایش تخصیص
                    </h6>
                </div>
                <div class="p-4">
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">تعداد خانواده‌ها:</p>
                            <p class="font-medium text-blue-600">{{ $insuredFamilies }} خانواده</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">منبع مالی:</p>
                            <p class="font-medium" id="preview-source">
                                <span class="italic text-gray-400">هنوز انتخاب نشده...</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">درصد تخصیص:</p>
                            <p class="font-medium text-blue-600" id="preview-percentage">-</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">مبلغ کل تخصیص:</p>
                            <p class="font-medium text-green-600" id="preview-amount">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // دریافت متغیرها از DOM
    const appData = document.getElementById('app-data');
    const totalFamilies = parseInt(appData.dataset.totalFamilies || 0);
    const totalPremium = parseInt(appData.dataset.totalPremium || 0);

    console.log('تعداد خانواده‌ها:', totalFamilies);
    console.log('مجموع حق بیمه:', totalPremium);

    // المان‌های فرم
    const allocationForm = document.getElementById('allocationForm');
    const sourceSelect = document.getElementById('funding_source_id');
    const percentageInput = document.getElementById('percentage');
    const amountInput = document.getElementById('amount');
    const amountDisplay = document.getElementById('amount_display');
    const submitBtn = document.getElementById('submitBtn');

    // المان‌های پیش‌نمایش
    const previewSource = document.getElementById('preview-source');
    const previewPercentage = document.getElementById('preview-percentage');
    const previewAmount = document.getElementById('preview-amount');
    
    // محاسبه مبلغ کل بر اساس درصد
    function calculateTotalAmount(percentage) {
        const percentageValue = parseFloat(percentage) || 0;
        
        if (totalPremium > 0 && percentageValue > 0) {
            return Math.round((percentageValue / 100) * totalPremium);
        }
        
        return 0;
    }
    
    // فرمت‌کردن اعداد با جداکننده فارسی
    function formatNumber(number) {
        return new Intl.NumberFormat('fa-IR', { 
            useGrouping: true, 
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(number).replace(/,/g, '٬');
    }
    
    // به‌روزرسانی پیش‌نمایش
    function updatePreview() {
        const sourceId = sourceSelect.value;
        const sourceName = sourceSelect.options[sourceSelect.selectedIndex]?.text || '';
        const percentage = percentageInput.value;
        const amount = calculateTotalAmount(percentage);
        
        // به‌روزرسانی منبع مالی
        if (sourceId && sourceName) {
            previewSource.innerHTML = sourceName;
        } else {
            previewSource.innerHTML = '<span class="italic text-gray-400">هنوز انتخاب نشده...</span>';
        }
        
        // به‌روزرسانی درصد
        if (percentage) {
            previewPercentage.textContent = percentage + '%';
        } else {
            previewPercentage.textContent = '-';
        }
        
        // به‌روزرسانی مبلغ
        if (percentage) {
            previewAmount.textContent = formatNumber(amount) + ' تومان';
            amountInput.value = amount;
            amountDisplay.value = formatNumber(amount);
        } else {
            previewAmount.textContent = '-';
            amountInput.value = 0;
            amountDisplay.value = '';
        }
        
        // بررسی امکان ثبت فرم
        validateForm();
    }
    
    // اعتبارسنجی فرم
    function validateForm() {
        const sourceId = sourceSelect.value;
        const percentage = parseFloat(percentageInput.value) || 0;
        
        // غیرفعال کردن دکمه ثبت اگر مقادیر نامعتبر هستند
        if (!sourceId || percentage <= 0 || percentage > 100 || totalFamilies === 0 || totalPremium === 0) {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            submitBtn.classList.remove('hover:bg-green-600');
        } else {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            submitBtn.classList.add('hover:bg-green-600');
        }
    }
    
    // رویدادهای تغییر
    sourceSelect.addEventListener('change', function() {
        updatePreview();
    });
    
    percentageInput.addEventListener('input', function() {
        updatePreview();
    });
    
    // اجرای اولیه
    updatePreview();
    
    // پیشگیری از ارسال فرم در صورت نامعتبر بودن
    allocationForm.addEventListener('submit', function(e) {
        if (submitBtn.disabled) {
            e.preventDefault();
            alert('لطفاً همه فیلدهای ضروری را پر کنید.');
            return false;
        }
        
        // اضافه کردن انیمیشن لودینگ به دکمه ثبت
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> در حال ثبت...';
        submitBtn.classList.add('opacity-75');
        
        return true;
    });
});
</script>
@endpush
</x-app-layout> 