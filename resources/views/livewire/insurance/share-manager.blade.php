<div class="bg-white rounded-lg shadow-lg p-6">
    <!-- هدر -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">مدیریت سهم‌بندی حق بیمه</h3>
            <p class="text-sm text-gray-600 mt-1">
                خانواده: {{ $familyInsurance->family->family_code ?? 'نامشخص' }} | 
                نوع بیمه: {{ $familyInsurance->insurance_type }}
            </p>
        </div>
        <button 
            wire:click="toggleAddForm" 
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
        >
            @if($showAddForm)
                انصراف
            @else
                افزودن سهم جدید
            @endif
        </button>
    </div>

    <!-- نمایش پیام‌ها -->
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <!-- خلاصه درصدها -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 p-4 rounded-lg">
            <div class="text-sm text-blue-600 font-medium">درصد تخصیص یافته</div>
            <div class="text-2xl font-bold text-blue-900">{{ number_format($totalPercentage, 2) }}%</div>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
            <div class="text-sm text-green-600 font-medium">درصد باقیمانده</div>
            <div class="text-2xl font-bold text-green-900">{{ number_format($remainingPercentage, 2) }}%</div>
        </div>
        <div class="bg-gray-50 p-4 rounded-lg">
            <div class="text-sm text-gray-600 font-medium">مبلغ کل حق بیمه</div>
            <div class="text-2xl font-bold text-gray-900">
                {{ number_format($familyInsurance->premium_amount ?? 0) }} تومان
            </div>
        </div>
    </div>

    <!-- فرم افزودن سهم جدید -->
    @if($showAddForm)
        <div class="bg-gray-50 p-6 rounded-lg mb-6">
            <h4 class="text-md font-semibold text-gray-900 mb-4">افزودن سهم جدید</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- درصد مشارکت -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">درصد مشارکت</label>
                    <input 
                        type="number" 
                        wire:model="percentage" 
                        step="0.01" 
                        min="0.01" 
                        max="{{ $remainingPercentage }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="مثال: 25.50"
                    >
                    @error('percentage') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- نوع پرداخت‌کننده -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">نوع پرداخت‌کننده</label>
                    <select wire:model="payer_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">انتخاب کنید</option>
                        @foreach($payerTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('payer_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- نام پرداخت‌کننده -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">نام پرداخت‌کننده</label>
                    <input 
                        type="text" 
                        wire:model="payer_name" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="نام پرداخت‌کننده"
                    >
                    @error('payer_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- سازمان (در صورت نیاز) -->
                @if(in_array($payer_type, ['insurance_company', 'charity', 'bank']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">سازمان</label>
                        <select wire:model="payer_organization_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">انتخاب سازمان</option>
                            @foreach($organizations as $org)
                                <option value="{{ $org['id'] }}">{{ $org['name'] }}</option>
                            @endforeach
                        </select>
                        @error('payer_organization_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                @endif

                <!-- کاربر (برای فرد خیر) -->
                @if($payer_type === 'individual_donor')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">فرد خیر</label>
                        <select wire:model="payer_user_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">انتخاب فرد خیر</option>
                            @foreach($users as $user)
                                <option value="{{ $user['id'] }}">{{ $user['name'] }}</option>
                            @endforeach
                        </select>
                        @error('payer_user_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                @endif

                <!-- توضیحات -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">توضیحات</label>
                    <textarea 
                        wire:model="description" 
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="توضیحات اضافی (اختیاری)"
                    ></textarea>
                    @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex justify-end mt-4 space-x-2 space-x-reverse">
                <button 
                    wire:click="toggleAddForm" 
                    class="px-4 py-2 text-gray-600 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors"
                >
                    انصراف
                </button>
                <button 
                    wire:click="addShare" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                >
                    افزودن سهم
                </button>
            </div>
        </div>
    @endif

    <!-- لیست سهم‌ها -->
    <div class="space-y-4">
        @forelse($shares as $share)
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <div class="flex items-center">
                                <span class="text-lg font-semibold text-blue-600">{{ number_format($share['percentage'], 2) }}%</span>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">{{ $share['payer_name'] }}</div>
                                <div class="text-sm text-gray-500">
                                    {{ $payerTypes[$share['payer_type']] ?? $share['payer_type'] }}
                                </div>
                            </div>
                        </div>
                        
                        @if($share['description'])
                            <div class="mt-2 text-sm text-gray-600">
                                {{ $share['description'] }}
                            </div>
                        @endif

                        <div class="mt-2 flex items-center space-x-4 space-x-reverse text-sm">
                            <span class="text-gray-600">
                                مبلغ: {{ number_format($share['amount'] ?? 0) }} تومان
                            </span>
                            
                            @if($share['is_paid'])
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✓ پرداخت شده
                                </span>
                                @if($share['payment_date'])
                                    <span class="text-gray-500">
                                        {{ \Carbon\Carbon::parse($share['payment_date'])->format('Y/m/d') }}
                                    </span>
                                @endif
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    در انتظار پرداخت
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 space-x-reverse">
                        @if(!$share['is_paid'])
                            <button 
                                wire:click="markAsPaid({{ $share['id'] }})"
                                class="text-green-600 hover:text-green-800 text-sm font-medium"
                                title="علامت‌گذاری به عنوان پرداخت شده"
                            >
                                ✓ پرداخت شد
                            </button>
                        @endif
                        
                        <button 
                            wire:click="deleteShare({{ $share['id'] }})"
                            onclick="return confirm('آیا از حذف این سهم اطمینان دارید؟')"
                            class="text-red-600 hover:text-red-800 text-sm font-medium"
                            title="حذف سهم"
                        >
                            حذف
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-8 text-gray-500">
                <div class="text-4xl mb-2">📊</div>
                <div class="text-lg font-medium">هیچ سهمی تعریف نشده است</div>
                <div class="text-sm">برای شروع، سهم جدیدی اضافه کنید</div>
            </div>
        @endforelse
    </div>

    <!-- نوار پیشرفت -->
    @if(count($shares) > 0)
        <div class="mt-6">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>پیشرفت تخصیص</span>
                <span>{{ number_format($totalPercentage, 2) }}% از 100%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div 
                    class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                    style="width: {{ $totalPercentage > 100 ? 100 : $totalPercentage }}%"
                ></div>
            </div>
            @if($totalPercentage >= 100)
                <div class="text-green-600 text-sm mt-1 font-medium">
                    ✓ تخصیص کامل شده است
                </div>
            @endif
        </div>
    @endif
</div>
