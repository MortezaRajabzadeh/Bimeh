<x-app-layout>
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">➕ افزودن سهم جدید</h1>
                <p class="text-gray-600 mt-1">تعریف سهم جدید برای پرداخت حق بیمه</p>
            </div>
            
            <a href="{{ route('insurance.shares.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                بازگشت به لیست
            </a>
        </div>

        <!-- Form -->
        <form action="{{ route('insurance.shares.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <!-- Family Insurance Selection -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <label for="family_insurance_id" class="block text-sm font-medium text-gray-700 mb-2">
                        خانواده بیمه شده <span class="text-red-500">*</span>
                    </label>
                    <select name="family_insurance_id" id="family_insurance_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">انتخاب کنید...</option>
                        @if(isset($familyInsurance))
                            <option value="{{ $familyInsurance->id }}" selected>
                                {{ $familyInsurance->family->name }} ({{ $familyInsurance->family->family_code }})
                            </option>
                        @endif
                        @foreach($familyInsurances as $fi)
                            @if(!isset($familyInsurance) || $familyInsurance->id != $fi->id)
                                <option value="{{ $fi->id }}">
                                    {{ $fi->family->name }} ({{ $fi->family->family_code }}) - {{ number_format($fi->premium_amount ?? 0) }} تومان
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('family_insurance_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="percentage" class="block text-sm font-medium text-gray-700 mb-2">
                        درصد سهم <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="percentage" id="percentage" 
                               min="0" max="100" step="0.01" required
                               value="{{ old('percentage') }}"
                               placeholder="مثال: 30"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <span class="absolute right-3 top-2 text-gray-500">%</span>
                    </div>
                    @error('percentage')
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
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <option value="">انتخاب کنید...</option>
                    @foreach($payerTypes as $value => $label)
                        <option value="{{ $value }}" {{ old('payer_type') === $value ? 'selected' : '' }}>
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
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">انتخاب کنید...</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}" {{ old('payer_organization_id') == $org->id ? 'selected' : '' }}>
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
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">انتخاب کنید...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('payer_user_id') == $user->id ? 'selected' : '' }}>
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
                           value="{{ old('payer_name') }}"
                           placeholder="نام پرداخت‌کننده را وارد کنید"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    @error('payer_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Amount (Optional - calculated automatically) -->
            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                    مبلغ (اختیاری - بر اساس درصد محاسبه می‌شود)
                </label>
                <div class="relative">
                    <input type="number" name="amount" id="amount" 
                           min="0" step="1000"
                           value="{{ old('amount') }}"
                           placeholder="مبلغ به تومان"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <span class="absolute right-3 top-2 text-gray-500">تومان</span>
                </div>
                <p class="mt-1 text-sm text-gray-500">اگر مبلغ وارد نکنید، بر اساس درصد و حق بیمه خانواده محاسبه خواهد شد</p>
                @error('amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    توضیحات
                </label>
                <textarea name="description" id="description" rows="3"
                          placeholder="توضیحات اضافی در مورد این سهم..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6 border-t">
                <a href="{{ route('insurance.shares.index') }}" 
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg">
                    انصراف
                </a>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                    ایجاد سهم
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
            customDiv.style.display = 'none';
        } else if (selectedType === 'user') {
            userDiv.style.display = 'block';
            customDiv.style.display = 'none';
        }
    }

    payerTypeSelect.addEventListener('change', togglePayerFields);
    togglePayerFields(); // Initial call
});
</script>
</x-app-layout> 