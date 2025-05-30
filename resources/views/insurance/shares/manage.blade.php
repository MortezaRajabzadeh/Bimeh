<x-app-layout>
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">🔄 مدیریت سهم‌بندی Real-Time</h1>
                <p class="text-gray-600 mt-1">مدیریت فوری سهم‌های بیمه با قابلیت‌های پیشرفته</p>
            </div>
            
            <div class="flex space-x-2 space-x-reverse">
                <a href="{{ route('insurance.shares.index') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    مشاهده لیست
                </a>
                
                <a href="{{ route('insurance.shares.create') }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    افزودن سهم
                </a>
            </div>
        </div>

        <!-- Family Selection -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-3">انتخاب خانواده برای مدیریت سهم‌ها</h3>
            
            <form method="GET" action="{{ route('insurance.shares.manage') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="family_id" class="block text-sm font-medium text-blue-700 mb-2">خانواده</label>
                    <select name="family_id" id="family_id" 
                            class="w-full px-3 py-2 border border-blue-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">انتخاب خانواده...</option>
                        @foreach($families as $family)
                            <option value="{{ $family->id }}" {{ request('family_id') == $family->id ? 'selected' : '' }}>
                                {{ $family->name }} ({{ $family->family_code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label for="insurance_status" class="block text-sm font-medium text-blue-700 mb-2">وضعیت بیمه</label>
                    <select name="insurance_status" id="insurance_status"
                            class="w-full px-3 py-2 border border-blue-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="active" {{ request('insurance_status') == 'active' ? 'selected' : '' }}>فعال</option>
                        <option value="pending" {{ request('insurance_status') == 'pending' ? 'selected' : '' }}>در انتظار</option>
                        <option value="expired" {{ request('insurance_status') == 'expired' ? 'selected' : '' }}>منقضی</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md w-full">
                        جستجو
                    </button>
                </div>
            </form>
        </div>

        @if($selectedFamily && $familyInsurance)
            <!-- Selected Family Info -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-green-800">
                            📋 خانواده: {{ $selectedFamily->name }}
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3 text-sm">
                            <div>
                                <span class="text-green-600 font-medium">کد خانواده:</span>
                                <span class="text-green-800">{{ $selectedFamily->family_code }}</span>
                            </div>
                            <div>
                                <span class="text-green-600 font-medium">حق بیمه:</span>
                                <span class="text-green-800">{{ number_format($familyInsurance->premium_amount ?? 0) }} تومان</span>
                            </div>
                            <div>
                                <span class="text-green-600 font-medium">وضعیت:</span>
                                <span class="px-2 py-1 rounded text-xs {{ $familyInsurance->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $familyInsurance->status === 'active' ? 'فعال' : ($familyInsurance->status === 'pending' ? 'در انتظار' : 'منقضی') }}
                                </span>
                            </div>
                            <div>
                                <span class="text-green-600 font-medium">تعداد اعضا:</span>
                                <span class="text-green-800">{{ $selectedFamily->members_count ?? 0 }} نفر</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ShareManager Livewire Component -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 ml-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    مدیریت سهم‌بندی
                </h3>
                
                @livewire('insurance.share-manager', ['familyInsuranceId' => $familyInsurance->id])
            </div>
        @else
            <!-- No Family Selected -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-800 mb-2">انتخاب خانواده</h3>
                <p class="text-gray-600 mb-4">برای شروع مدیریت سهم‌بندی، لطفاً ابتدا یک خانواده انتخاب کنید</p>
                
                <div class="flex justify-center space-x-4 space-x-reverse">
                    <a href="{{ route('insurance.shares.index') }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        مشاهده همه سهم‌ها
                    </a>
                    <a href="{{ route('insurance.families.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                        انتخاب از لیست خانواده‌ها
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
</x-app-layout> 