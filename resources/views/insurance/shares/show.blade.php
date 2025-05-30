<x-app-layout>
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">📋 جزئیات سهم بیمه</h1>
                <p class="text-gray-600 mt-1">مشاهده کامل اطلاعات سهم‌بندی</p>
            </div>
            
            <div class="flex space-x-2 space-x-reverse">
                <a href="{{ route('insurance.shares.index') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    بازگشت به لیست
                </a>
                
                @can('edit insurance shares')
                <a href="{{ route('insurance.shares.edit', $insuranceShare) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    ویرایش
                </a>
                @endcan
            </div>
        </div>

        <!-- Family Information -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-4 flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                اطلاعات خانواده
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <span class="text-blue-600 font-medium">نام خانواده:</span>
                    <div class="text-blue-800 font-semibold">{{ $insuranceShare->familyInsurance->family->name }}</div>
                </div>
                <div>
                    <span class="text-blue-600 font-medium">کد خانواده:</span>
                    <div class="text-blue-800">{{ $insuranceShare->familyInsurance->family->family_code }}</div>
                </div>
                <div>
                    <span class="text-blue-600 font-medium">حق بیمه کل:</span>
                    <div class="text-blue-800">{{ number_format($insuranceShare->familyInsurance->premium_amount ?? 0) }} تومان</div>
                </div>
            </div>
        </div>

        <!-- Share Details -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Share Information -->
            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    اطلاعات سهم
                </h3>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-white rounded border">
                        <span class="text-gray-600 font-medium">درصد سهم:</span>
                        <span class="text-lg font-bold text-gray-800">{{ $insuranceShare->percentage }}%</span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-white rounded border">
                        <span class="text-gray-600 font-medium">مبلغ سهم:</span>
                        <span class="text-lg font-bold text-green-600">{{ number_format($insuranceShare->amount) }} تومان</span>
                    </div>
                    
                    <div class="flex justify-between items-center p-3 bg-white rounded border">
                        <span class="text-gray-600 font-medium">تاریخ ایجاد:</span>
                        <span class="text-gray-800">{{ $insuranceShare->created_at ? $insuranceShare->created_at->format('Y/m/d H:i') : '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Payer Information -->
            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    اطلاعات پرداخت‌کننده
                </h3>
                
                <div class="space-y-4">
                    <div class="p-3 bg-white rounded border">
                        <span class="text-gray-600 font-medium block mb-1">نوع پرداخت‌کننده:</span>
                        <span class="text-gray-800">
                            {{ match($insuranceShare->payer_type) {
                                'insurance_company' => '🏢 شرکت بیمه',
                                'charity' => '🏥 خیریه',
                                'bank' => '🏦 بانک',
                                'government' => '🏛️ دولت',
                                'individual_donor' => '👤 فرد خیر',
                                'csr_budget' => '💼 بودجه CSR',
                                'other' => '📋 سایر',
                                default => $insuranceShare->payer_type
                            } }}
                        </span>
                    </div>
                    
                    <div class="p-3 bg-white rounded border">
                        <span class="text-gray-600 font-medium block mb-1">نام پرداخت‌کننده:</span>
                        <span class="text-gray-800 font-semibold">
                            @if($insuranceShare->payer_type === 'organization' && $insuranceShare->payerOrganization)
                                {{ $insuranceShare->payerOrganization->name }}
                            @elseif($insuranceShare->payer_type === 'user' && $insuranceShare->payerUser)
                                {{ $insuranceShare->payerUser->name }}
                            @else
                                {{ $insuranceShare->payer_name }}
                            @endif
                        </span>
                    </div>

                    @if($insuranceShare->description)
                    <div class="p-3 bg-white rounded border">
                        <span class="text-gray-600 font-medium block mb-1">توضیحات:</span>
                        <span class="text-gray-800">{{ $insuranceShare->description }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Payment Status -->
        <div class="bg-white border rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                </svg>
                وضعیت پرداخت
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 border rounded-lg">
                    <span class="text-gray-600 font-medium block mb-2">وضعیت:</span>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold 
                        {{ $insuranceShare->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 
                           ($insuranceShare->payment_status === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                        {{ match($insuranceShare->payment_status) {
                            'pending' => 'در انتظار پرداخت',
                            'paid' => 'پرداخت شده',
                            'overdue' => 'عقب‌افتاده',
                            default => $insuranceShare->payment_status
                        } }}
                    </span>
                </div>
                
                @if($insuranceShare->payment_date)
                <div class="p-4 border rounded-lg">
                    <span class="text-gray-600 font-medium block mb-2">تاریخ پرداخت:</span>
                    <span class="text-gray-800">{{ $insuranceShare->payment_date ? \Carbon\Carbon::parse($insuranceShare->payment_date)->format('Y/m/d') : '-' }}</span>
                </div>
                @endif
                
                @if($insuranceShare->payment_reference)
                <div class="p-4 border rounded-lg">
                    <span class="text-gray-600 font-medium block mb-2">شماره پیگیری:</span>
                    <span class="text-gray-800 font-mono">{{ $insuranceShare->payment_reference }}</span>
                </div>
                @endif
            </div>
            
            @if($insuranceShare->payment_status === 'pending')
            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <form action="{{ route('insurance.shares.mark-paid', $insuranceShare) }}" method="POST" class="space-y-4">
                    @csrf
                    <p class="text-yellow-800 font-medium">علامت‌گذاری به عنوان پرداخت شده:</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="payment_date" class="block text-sm font-medium text-yellow-700 mb-1">تاریخ پرداخت:</label>
                            <input type="date" name="payment_date" id="payment_date" required
                                   value="{{ date('Y-m-d') }}"
                                   class="w-full px-3 py-2 border border-yellow-300 rounded-md focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        
                        <div>
                            <label for="payment_reference" class="block text-sm font-medium text-yellow-700 mb-1">شماره پیگیری (اختیاری):</label>
                            <input type="text" name="payment_reference" id="payment_reference" 
                                   placeholder="شماره پیگیری پرداخت"
                                   class="w-full px-3 py-2 border border-yellow-300 rounded-md focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                    </div>
                    
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        تایید پرداخت
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
</x-app-layout> 