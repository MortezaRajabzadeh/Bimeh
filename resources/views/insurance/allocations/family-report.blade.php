<x-app-layout>
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <!-- هدر -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-1">گزارش تخصیصات خانواده</h1>
            <div class="flex items-center text-gray-600 text-sm">
                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-md">{{ $family->family_code }}</span>
                <span class="mx-2">-</span>
                <span>{{ optional($family->head)->first_name }} {{ optional($family->head)->last_name }}</span>
            </div>
        </div>
        <div class="flex gap-2 mt-4 md:mt-0">
            <a href="{{ route('insurance.allocations.create', ['family_id' => $family->id]) }}" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md flex items-center">
                <i class="fas fa-plus ml-2"></i>
                تخصیص جدید
            </a>
            <a href="{{ route('insurance.allocations.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md flex items-center">
                <i class="fas fa-arrow-right ml-2"></i>
                بازگشت
            </a>
        </div>
    </div>

    <!-- آمارهای کلی -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border-r-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">حق بیمه کل</p>
                    <h3 class="text-2xl font-bold text-blue-500">{{ number_format($allocationStatus['total_premium']) }}</h3>
                    <span class="text-xs text-gray-500">تومان</span>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-money-bill-wave text-blue-500 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-r-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">درصد تخصیص یافته</p>
                    <h3 class="text-2xl font-bold text-green-500">{{ $allocationStatus['total_percentage'] }}%</h3>
                    <span class="text-xs text-gray-500">از کل حق بیمه</span>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-percentage text-green-500 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-r-4 border-indigo-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">مبلغ تخصیص یافته</p>
                    <h3 class="text-2xl font-bold text-indigo-500">{{ number_format($allocationStatus['total_amount']) }}</h3>
                    <span class="text-xs text-gray-500">تومان</span>
                </div>
                <div class="bg-indigo-100 p-3 rounded-full">
                    <i class="fas fa-coins text-indigo-500 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-r-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">مبلغ باقی‌مانده</p>
                    <h3 class="text-2xl font-bold text-yellow-500">{{ number_format($allocationStatus['remaining_amount']) }}</h3>
                    <span class="text-xs text-gray-500">تومان</span>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <i class="fas fa-hourglass-half text-yellow-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- نوار پیشرفت کلی -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <div class="flex justify-between items-center mb-2">
            <h6 class="font-medium text-gray-700">پیشرفت تخصیص بودجه</h6>
            <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded-full">{{ $allocationStatus['total_percentage'] }}% تکمیل شده</span>
        </div>
        <div class="relative pt-1">
            <div class="overflow-hidden h-5 text-xs flex rounded bg-gray-200">
                <div style="width: {{ $allocationStatus['total_percentage'] }}%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-green-500"></div>
            </div>
        </div>
        <div class="flex justify-between mt-2 text-xs text-gray-500">
            <div>تخصیص یافته: {{ $allocationStatus['total_percentage'] }}%</div>
            <div>باقی‌مانده: {{ $allocationStatus['remaining_percentage'] }}%</div>
        </div>
    </div>

    <!-- اطلاعات و آمار -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- اطلاعات خانواده -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b border-gray-200">
                <h5 class="font-bold text-gray-700 flex items-center">
                    <i class="fas fa-users ml-2 text-gray-500"></i>
                    اطلاعات خانواده
                </h5>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="border-r border-gray-200 pr-4">
                        <p class="text-gray-500 text-sm mb-1">کد خانواده:</p>
                        <p class="font-medium">{{ $family->family_code ?? 'نامشخص' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm mb-1">تعداد اعضا:</p>
                        <p class="font-medium">{{ $family->total_members ?? 0 }} نفر</p>
                    </div>
                    <div class="border-r border-gray-200 pr-4">
                        <p class="text-gray-500 text-sm mb-1">حق بیمه کل:</p>
                        <p class="font-medium text-blue-600">{{ number_format($family->total_premium) }} تومان</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm mb-1">وضعیت تایید:</p>
                        <p>
                            @if($family->verification_status === 'approved')
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">تایید شده</span>
                            @elseif($family->verification_status === 'pending')
                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">در انتظار تایید</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">{{ $family->verification_status }}</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- توزیع منابع مالی -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b border-gray-200">
                <h5 class="font-bold text-gray-700 flex items-center">
                    <i class="fas fa-chart-pie ml-2 text-gray-500"></i>
                    توزیع منابع مالی
                </h5>
            </div>
            <div class="p-4">
                @if($allocations->count() > 0)
                    @php
                        $sourcesSummary = $allocations->groupBy('fundingSource.name')->map(function($group) {
                            return [
                                'percentage' => $group->sum('percentage'),
                                'amount' => $group->sum('amount'),
                                'count' => $group->count()
                            ];
                        });
                    @endphp
                    
                    <div class="space-y-3">
                        @foreach($sourcesSummary as $sourceName => $summary)
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-medium text-sm">{{ $sourceName }}</span>
                                    <span class="text-blue-600 text-sm">{{ $summary['percentage'] }}%</span>
                                </div>
                                <div class="relative pt-1">
                                    <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                                        <div style="width: {{ $summary['percentage'] }}%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500"></div>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ number_format($summary['amount']) }} تومان ({{ $summary['count'] }} تخصیص)
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-blue-50 text-blue-700 p-3 rounded-md">
                        هنوز هیچ تخصیصی برای این خانواده ثبت نشده است.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- وضعیت تخصیص‌ها -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="bg-yellow-100 w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3">
                <i class="fas fa-clock text-yellow-500 text-2xl"></i>
            </div>
            <h6 class="font-medium text-gray-700 mb-1">در انتظار تایید</h6>
            <h3 class="text-2xl font-bold text-yellow-500">{{ $allocations->where('status', 'pending')->count() }}</h3>
            <p class="text-xs text-gray-500">تخصیص</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="bg-green-100 w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3">
                <i class="fas fa-check-circle text-green-500 text-2xl"></i>
            </div>
            <h6 class="font-medium text-gray-700 mb-1">تایید شده</h6>
            <h3 class="text-2xl font-bold text-green-500">{{ $allocations->where('status', 'approved')->count() }}</h3>
            <p class="text-xs text-gray-500">تخصیص</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="bg-blue-100 w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3">
                <i class="fas fa-money-check-alt text-blue-500 text-2xl"></i>
            </div>
            <h6 class="font-medium text-gray-700 mb-1">پرداخت شده</h6>
            <h3 class="text-2xl font-bold text-blue-500">{{ $allocations->where('status', 'paid')->count() }}</h3>
            <p class="text-xs text-gray-500">تخصیص</p>
        </div>
    </div>

    <!-- جدول تخصیص‌ها -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h5 class="font-bold text-gray-700 flex items-center">
                <i class="fas fa-list-alt ml-2 text-gray-500"></i>
                لیست تخصیص‌های بودجه
            </h5>
            <a href="{{ route('insurance.allocations.create', ['family_id' => $family->id]) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 text-sm rounded-md flex items-center">
                <i class="fas fa-plus ml-1"></i>
                تخصیص جدید
            </a>
        </div>
        
        @if($allocations->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">منبع مالی</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">درصد</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مبلغ (تومان)</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ ایجاد</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($allocations as $allocation)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                                        {{ $allocation->fundingSource->name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-blue-600 font-bold">{{ $allocation->percentage }}%</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-800 font-medium">
                                    {{ number_format($allocation->amount) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($allocation->status === 'pending')
                                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">در انتظار</span>
                                    @elseif($allocation->status === 'approved')
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">تایید شده</span>
                                    @elseif($allocation->status === 'paid')
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">پرداخت شده</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ jdate($allocation->created_at)->format('Y/m/d H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex space-x-2 space-x-reverse">
                                        <a href="{{ route('insurance.allocations.show', $allocation) }}" 
                                            class="text-indigo-600 hover:text-indigo-900" title="مشاهده جزئیات">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        @if($allocation->status === 'pending')
                                            <a href="{{ route('insurance.allocations.edit', $allocation) }}" 
                                                class="text-gray-600 hover:text-gray-900" title="ویرایش">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <form action="{{ route('insurance.allocations.approve', $allocation) }}" 
                                                method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-900" 
                                                        title="تایید" onclick="return confirm('آیا از تایید این تخصیص مطمئن هستید؟')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if($allocation->status === 'approved')
                                            <form action="{{ route('insurance.allocations.mark-as-paid', $allocation) }}" 
                                                method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="text-blue-600 hover:text-blue-900" 
                                                        title="علامت‌گذاری پرداخت" onclick="return confirm('آیا این تخصیص پرداخت شده است؟')">
                                                    <i class="fas fa-money-check-alt"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-10">
                <div class="mb-4">
                    <i class="fas fa-folder-open text-gray-300 text-5xl"></i>
                </div>
                <h5 class="text-xl font-medium text-gray-500 mb-2">هیچ تخصیصی یافت نشد</h5>
                <p class="text-gray-500 mb-4">برای این خانواده هیچ تخصیص بودجه‌ای ثبت نشده است.</p>
                <a href="{{ route('insurance.allocations.create', ['family_id' => $family->id]) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md inline-flex items-center">
                    <i class="fas fa-plus ml-2"></i>
                    ایجاد اولین تخصیص
                </a>
            </div>
        @endif
    </div>
</div>
</x-app-layout> 