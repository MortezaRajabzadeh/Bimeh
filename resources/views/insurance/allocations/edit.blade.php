<x-app-layout>
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <!-- هدر -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-1">ویرایش تخصیص بودجه #{{ $allocation->id }}</h1>
            <p class="text-gray-600">تغییر اطلاعات تخصیص بودجه خانواده</p>
        </div>
        <div class="flex gap-2 mt-4 md:mt-0">
            <a href="{{ route('insurance.allocations.show', $allocation) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md flex items-center">
                <i class="fas fa-eye ml-2"></i>
                مشاهده جزئیات
            </a>
            <a href="{{ route('insurance.allocations.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md flex items-center">
                <i class="fas fa-arrow-right ml-2"></i>
                بازگشت
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- فرم ویرایش - 2/3 -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b border-gray-200">
                    <h5 class="font-bold text-gray-700 flex items-center">
                        <i class="fas fa-edit ml-2 text-gray-500"></i>
                        فرم ویرایش تخصیص بودجه
                    </h5>
                </div>
                <div class="p-6">
                    <form action="{{ route('insurance.allocations.update', $allocation) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-6">
                            <label for="family_id" class="block text-sm font-medium text-gray-700 mb-2">خانواده</label>
                            <div class="bg-gray-100 p-3 rounded-md flex items-center">
                                <i class="fas fa-users text-gray-500 ml-2"></i>
                                <span>{{ $family->family_code }} - {{ optional($family->head)->first_name }} {{ optional($family->head)->last_name }}</span>
                                <input type="hidden" name="family_id" value="{{ $family->id }}">
                            </div>
                        </div>

                        <div class="mb-6">
                            <label for="funding_source_id" class="block text-sm font-medium text-gray-700 mb-2">منبع مالی</label>
                            <select name="funding_source_id" id="funding_source_id" class="form-select rounded-md w-full border-gray-300" required>
                                <option value="">انتخاب منبع مالی</option>
                                @foreach($fundingSources as $source)
                                    <option value="{{ $source->id }}" {{ $allocation->funding_source_id == $source->id ? 'selected' : '' }}>
                                        {{ $source->name }} ({{ $source->type }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="percentage" class="block text-sm font-medium text-gray-700 mb-2">درصد تخصیص (%)</label>
                                <div class="relative">
                                    <input type="number" name="percentage" id="percentage" class="form-input rounded-md w-full border-gray-300" 
                                           min="1" max="100" step="0.01" value="{{ $allocation->percentage }}" required>
                                    <div class="absolute inset-y-0 left-0 flex items-center pointer-events-none px-3 text-gray-500">
                                        <i class="fas fa-percent"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">درصد تخصیص از کل حق بیمه خانواده</p>
                            </div>
                            
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">مبلغ تخصیص (تومان)</label>
                                <div class="relative">
                                    <input type="number" name="amount" id="amount" class="form-input rounded-md w-full border-gray-300" 
                                           min="0" step="1000" value="{{ $allocation->amount }}" required>
                                    <div class="absolute inset-y-0 left-0 flex items-center pointer-events-none px-3 text-gray-500">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">محاسبه خودکار: {{ number_format($family->total_premium) }} × درصد</p>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                            <textarea name="description" id="description" rows="3" class="form-textarea rounded-md w-full border-gray-300">{{ $allocation->description }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">اختیاری - هر توضیح اضافی در مورد این تخصیص</p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md">
                                <i class="fas fa-save ml-2"></i>
                                ذخیره تغییرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ستون کناری - 1/3 -->
        <div class="space-y-6">
            <!-- اطلاعات خانواده -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b border-gray-200">
                    <h6 class="font-bold text-gray-700 flex items-center">
                        <i class="fas fa-info-circle ml-2 text-gray-500"></i>
                        اطلاعات خانواده
                    </h6>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">نام خانواده:</p>
                            <p class="font-medium">{{ $family->family_code }} - {{ optional($family->head)->first_name }} {{ optional($family->head)->last_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">تعداد اعضا:</p>
                            <p class="font-medium">{{ $family->members_count ?? 0 }} نفر</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">حق بیمه کل:</p>
                            <p class="font-medium text-blue-600">{{ number_format($family->total_premium) }} تومان</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- راهنمای ویرایش -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b border-gray-200">
                    <h6 class="font-bold text-gray-700 flex items-center">
                        <i class="fas fa-question-circle ml-2 text-gray-500"></i>
                        راهنمای ویرایش
                    </h6>
                </div>
                <div class="p-4">
                    <div class="space-y-3 text-sm text-gray-600">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 bg-blue-100 text-blue-600 p-1 rounded-full mt-1 ml-2">
                                <i class="fas fa-info text-xs"></i>
                            </div>
                            <p>امکان تغییر خانواده وجود ندارد. برای تخصیص به خانواده دیگر، لطفا یک تخصیص جدید ایجاد کنید.</p>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 bg-blue-100 text-blue-600 p-1 rounded-full mt-1 ml-2">
                                <i class="fas fa-info text-xs"></i>
                            </div>
                            <p>با تغییر درصد، مبلغ به صورت خودکار محاسبه می‌شود، اما شما می‌توانید آن را دستی نیز تغییر دهید.</p>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 bg-yellow-100 text-yellow-600 p-1 rounded-full mt-1 ml-2">
                                <i class="fas fa-exclamation text-xs"></i>
                            </div>
                            <p>فقط تخصیص‌های با وضعیت "در انتظار تایید" قابل ویرایش هستند.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- تخصیص فعلی -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b border-gray-200">
                    <h6 class="font-bold text-gray-700 flex items-center">
                        <i class="fas fa-clipboard-list ml-2 text-gray-500"></i>
                        اطلاعات تخصیص فعلی
                    </h6>
                </div>
                <div class="p-4">
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">شناسه تخصیص:</p>
                            <p class="font-medium">#{{ $allocation->id }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">وضعیت:</p>
                            <p>
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">در انتظار تایید</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">خانواده:</p>
                            <p class="font-medium">{{ $allocation->family->family_code }} - {{ optional($allocation->family->head)->first_name }} {{ optional($allocation->family->head)->last_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">ایجاد شده در:</p>
                            <p class="font-medium">{{ jdate($allocation->created_at)->format('Y/m/d H:i') }}</p>
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
    const familyTotalPremium = {{ $family->total_premium }};
    const percentageInput = document.getElementById('percentage');
    const amountInput = document.getElementById('amount');

    // محاسبه مبلغ بر اساس درصد
    percentageInput.addEventListener('input', function() {
        const percentage = parseFloat(this.value) || 0;
        const amount = Math.round((percentage / 100) * familyTotalPremium);
        amountInput.value = amount;
    });

    // محاسبه درصد بر اساس مبلغ
    amountInput.addEventListener('input', function() {
        const amount = parseFloat(this.value) || 0;
        const percentage = familyTotalPremium > 0 ? (amount / familyTotalPremium) * 100 : 0;
        percentageInput.value = percentage.toFixed(2);
    });
});
</script>
@endpush
</x-app-layout> 