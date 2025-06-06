<x-app-layout>
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">✏️ ویرایش سهم بیمه</h1>
                <p class="text-gray-600 mt-1">ویرایش اطلاعات سهم‌بندی موجود</p>
            </div>
            
            <div class="flex space-x-2 space-x-reverse">
                <a href="{{ route('insurance.shares.index') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    بازگشت به لیست
                </a>
                
                <a href="{{ route('insurance.shares.show', $insuranceShare) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    مشاهده
                </a>
            </div>
        </div>

        <!-- Insurance Batch Information (Read-only) -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-3">اطلاعات تخصیص بیمه</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-blue-600 font-medium">شناسه تخصیص:</span>
                    <span class="text-blue-800 font-semibold">{{ $insuranceShare->family_insurance_id ?? ('تخصیص #' . $insuranceShare->id) }}</span>
                </div>
                <div>
                    <span class="text-blue-600 font-medium">کد خانواده:</span>
                    <span class="text-blue-800">{{ $insuranceShare->familyInsurance->family->family_code ?? 'چندین خانواده' }}</span>
                </div>
                <div>
                    <span class="text-blue-600 font-medium">حق بیمه کل:</span>
                    <span class="text-blue-800">{{ number_format($insuranceShare->familyInsurance->premium_amount ?? 0) }} تومان</span>
                </div>
                <div>
                    <span class="text-blue-600 font-medium">تاریخ ایجاد:</span>
                    <span class="text-blue-800">{{ $insuranceShare->created_at ? jdate($insuranceShare->created_at)->format('Y/m/d') : '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <form action="{{ route('insurance.shares.update', $insuranceShare) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <!-- Basic Information -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <label for="percentage" class="block text-sm font-medium text-gray-700 mb-2">
                        درصد سهم <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">%</span>
                        <input type="number" name="percentage" id="percentage" 
                               min="0" max="100" step="0.01" required
                               value="{{ old('percentage', $insuranceShare->percentage) }}"
                               placeholder="مثال: 30"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right pl-8">
                    </div>
                    @error('percentage')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                        مبلغ سهم (اختیاری)
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">تومان</span>
                        <input type="number" name="amount" id="amount" 
                               min="0" step="1000"
                               value="{{ old('amount', $insuranceShare->amount) }}"
                               placeholder="مبلغ به تومان"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right pl-16">
                    </div>
                    <p class="mt-1 text-sm text-gray-500">اگر خالی باشد، بر اساس درصد محاسبه می‌شود</p>
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Payer Type -->
            <div>
                <label for="payer_type" class="block text-sm font-medium text-gray-700 mb-2">
                    نوع پرداخت‌کننده <span class="text-red-500">*</span>
                </label>
                <select name="payer_type" id="payer_type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right appearance-none bg-no-repeat pr-4"
                        style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 20 20\'%3E%3Cpath stroke=\'%236b7280\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M6 8l4 4 4-4\'/%3E%3C/svg%3E'); background-position: left 0.5rem center; background-size: 1.5em 1.5em;">
                    <option value="">انتخاب کنید...</option>
                    @foreach($payerTypes as $value => $label)
                        <option value="{{ $value }}" {{ old('payer_type', $insuranceShare->payer_type) === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('payer_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Payer Selection (Dynamic based on type) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Organization Payer -->
                <div id="organization_payer" style="display: none;">
                    <label for="payer_organization_id" class="block text-sm font-medium text-gray-700 mb-2">
                        سازمان پرداخت‌کننده
                    </label>
                    <select name="payer_organization_id" id="payer_organization_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right appearance-none bg-no-repeat pr-4"
                            style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 20 20\'%3E%3Cpath stroke=\'%236b7280\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M6 8l4 4 4-4\'/%3E%3C/svg%3E'); background-position: left 0.5rem center; background-size: 1.5em 1.5em;">
                        <option value="">انتخاب کنید...</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}" {{ old('payer_organization_id', $insuranceShare->payer_organization_id) == $org->id ? 'selected' : '' }}>
                                {{ $org->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- User Payer -->
                <div id="user_payer" style="display: none;">
                    <label for="payer_user_id" class="block text-sm font-medium text-gray-700 mb-2">
                        فرد پرداخت‌کننده
                    </label>
                    <select name="payer_user_id" id="payer_user_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right appearance-none bg-no-repeat pr-4"
                            style="background-image: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 20 20\'%3E%3Cpath stroke=\'%236b7280\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M6 8l4 4 4-4\'/%3E%3C/svg%3E'); background-position: left 0.5rem center; background-size: 1.5em 1.5em;">
                        <option value="">انتخاب کنید...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('payer_user_id', $insuranceShare->payer_user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Custom Payer Name -->
                <div id="custom_payer">
                    <label for="payer_name" class="block text-sm font-medium text-gray-700 mb-2">
                        نام پرداخت‌کننده <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="payer_name" id="payer_name" 
                           value="{{ old('payer_name', $insuranceShare->payer_name) }}"
                           placeholder="نام پرداخت‌کننده را وارد کنید"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right">
                    @error('payer_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    توضیحات
                </label>
                <textarea name="description" id="description" rows="3"
                          placeholder="توضیحات اضافی در مورد این سهم..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-right">{{ old('description', $insuranceShare->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t">
                <a href="{{ route('insurance.shares.show', $insuranceShare) }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg">
                    انصراف
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                    به‌روزرسانی سهم
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const payerTypeSelect = document.getElementById('payer_type');
    const organizationDiv = document.getElementById('organization_payer');
    const userDiv = document.getElementById('user_payer');
    const customDiv = document.getElementById('custom_payer');

    function togglePayerFields() {
        const selectedType = payerTypeSelect.value;
        
        // Hide all
        organizationDiv.style.display = 'none';
        userDiv.style.display = 'none';
        customDiv.style.display = 'block';

        // Show relevant field
        if (selectedType === 'organization') {
            organizationDiv.style.display = 'block';
        } else if (selectedType === 'user') {
            userDiv.style.display = 'block';
        }
    }

    // Initial toggle
    togglePayerFields();

    // Toggle on change
    payerTypeSelect.addEventListener('change', togglePayerFields);
});
</script>
</x-app-layout> 