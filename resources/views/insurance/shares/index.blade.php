<x-app-layout>
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">🏛️ مدیریت سهم‌بندی بیمه</h1>
                <p class="text-gray-600 mt-1">مدیریت سهم‌های مختلف در پرداخت حق بیمه خانواده‌ها</p>
            </div>
            
        </div>

        <!-- Filter Section -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <form method="GET" action="{{ route('insurance.shares.index') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">خانواده</label>
                    <input type="text" name="family" value="{{ request('family') }}" 
                           placeholder="نام خانواده..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-right">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md w-full">
                        جستجو
                    </button>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">شناسه تخصیص</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">خانواده</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">پرداخت‌کننده</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">درصد</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">مبلغ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاریخ ایجاد</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">عملیات</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($shares as $share)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $share->family_insurance_id ?? ('تخصیص #' . $share->id) }}
                            </div>
                            <div class="text-xs text-gray-500">
                                @if($share->created_at)
                                    <span>تاریخ تخصیص: {{ jdate($share->created_at)->format('Y/m/d') }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                @if($share->familyInsurance && $share->familyInsurance->family)
                                    {{ $share->familyInsurance->family->name }}
                                @else
                                    <span class="text-gray-500">چندین خانواده</span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-500">
                                @if($share->familyInsurance && $share->familyInsurance->family)
                                    کد: {{ $share->familyInsurance->family->family_code }}
                                @else
                                    <span class="text-xs text-blue-500">تخصیص دسته‌ای از فایل</span>
                                @endif
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                @if($share->payer_type === 'organization' && $share->payerOrganization)
                                    {{ $share->payerOrganization->name ?? 'نامشخص' }}
                                @elseif($share->payer_type === 'user' && $share->payerUser)
                                    {{ $share->payerUser->name ?? 'نامشخص' }}
                                @else
                                    {{ $share->payer_name ?? 'نامشخص' }}
                                @endif
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ match($share->payer_type ?? '') {
                                    'insurance' => '🏢 شرکت بیمه',
                                    'charity' => '🏥 خیریه',
                                    'bank' => '🏦 بانک',
                                    'government' => '🏛️ دولت',
                                    'benefactor' => '👤 فرد خیر',
                                    'csr' => '💼 بودجه CSR',
                                    default => $share->payer_type ?? 'نامشخص'
                                } }}
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900">{{ $share->percentage ?? 0 }}%</span>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900">{{ number_format($share->amount ?? 0) }} تومان</span>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $share->created_at ? jdate($share->created_at)->format('Y/m/d') : '-' }}
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                @can('view insurance shares')
                                <a href="{{ route('insurance.shares.show', $share) }}" 
                                   class="text-blue-600 hover:text-blue-900" title="مشاهده">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                @endcan
                                
                                @can('edit insurance shares')
                                <a href="{{ route('insurance.shares.edit', $share) }}" 
                                   class="text-indigo-600 hover:text-indigo-900" title="ویرایش">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @endcan
                                
                                @if(($share->payment_status ?? '') === 'pending')
                                <form action="{{ route('insurance.shares.mark-paid', $share) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-900" title="علامت‌گذاری به عنوان پرداخت شده">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                                
                                @can('delete insurance shares')
                                <form action="{{ route('insurance.shares.destroy', $share) }}" method="POST" class="inline" 
                                      onsubmit="return confirm('آیا از حذف این سهم اطمینان دارید؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="حذف">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            هیچ سهمی یافت نشد
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($shares->hasPages())
        <div class="mt-6">
            {{ $shares->links() }}
        </div>
        @endif
    </div>
</div>
</x-app-layout> 