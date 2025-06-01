<x-app-layout>
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <!-- هدر -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-1">جزئیات تخصیص بودجه #{{ $allocation->id }}</h1>
            <p class="text-gray-600">مشاهده و مدیریت تخصیص بودجه خانواده</p>
        </div>
        <div class="flex gap-2 mt-4 md:mt-0">
            <a href="{{ route('insurance.allocations.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md flex items-center">
                <i class="fas fa-arrow-right ml-2"></i>
                بازگشت
            </a>
            
            @if($allocation->status === 'pending')
                <a href="{{ route('insurance.allocations.edit', $allocation) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-md flex items-center">
                    <i class="fas fa-edit ml-2"></i>
                    ویرایش
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- اطلاعات اصلی تخصیص - 2/3 -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                    <h5 class="font-bold text-gray-700 flex items-center">
                        <i class="fas fa-info-circle ml-2 text-gray-500"></i>
                        اطلاعات تخصیص
                    </h5>
                    
                    <!-- نمایش وضعیت -->
                    @if($allocation->status === 'pending')
                        <span class="px-3 py-1 text-sm rounded-full bg-yellow-100 text-yellow-800">در انتظار تایید</span>
                    @elseif($allocation->status === 'approved')
                        <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800">تایید شده</span>
                    @elseif($allocation->status === 'paid')
                        <span class="px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-800">پرداخت شده</span>
                    @endif
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-sm text-gray-500 mb-1">خانواده:</dt>
                                    <dd>
                                        <a href="{{ route('insurance.allocations.family-report', $allocation->family_id) }}" 
                                           class="text-blue-600 hover:text-blue-800 flex items-center">
                                            <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded text-xs ml-2">{{ $allocation->family->family_code }}</span>
                                            <span>{{ optional($allocation->family->head)->first_name }} {{ optional($allocation->family->head)->last_name }}</span>
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 mb-1">منبع مالی:</dt>
                                    <dd>
                                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                                            {{ $allocation->fundingSource->name }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 mb-1">نوع منبع:</dt>
                                    <dd>{{ $allocation->fundingSource->source_type }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 mb-1">درصد تخصیص:</dt>
                                    <dd>
                                        <span class="text-blue-600 text-xl font-bold">{{ $allocation->percentage }}%</span>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-sm text-gray-500 mb-1">مبلغ تخصیص:</dt>
                                    <dd>
                                        <span class="text-green-600 text-xl font-bold">
                                            {{ number_format($allocation->amount) }} تومان
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 mb-1">ایجاد شده توسط:</dt>
                                    <dd>{{ $allocation->creator->name ?? 'نامشخص' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-gray-500 mb-1">تاریخ ایجاد:</dt>
                                    <dd>{{ jdate($allocation->created_at)->format('Y/m/d H:i') }}</dd>
                                </div>
                                @if($allocation->approved_at)
                                    <div>
                                        <dt class="text-sm text-gray-500 mb-1">تایید شده توسط:</dt>
                                        <dd>{{ $allocation->approver->name ?? 'نامشخص' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-gray-500 mb-1">تاریخ تایید:</dt>
                                        <dd>{{ jdate($allocation->approved_at)->format('Y/m/d H:i') }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>

                    @if($allocation->description)
                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <h6 class="text-sm text-gray-500 mb-2">توضیحات:</h6>
                            <p class="bg-gray-50 p-3 rounded-md">{{ $allocation->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- عملیات مدیریتی -->
            @if($allocation->status !== 'paid')
                <div class="bg-white rounded-lg shadow">
                    <div class="p-4 border-b border-gray-200">
                        <h5 class="font-bold text-gray-700 flex items-center">
                            <i class="fas fa-cogs ml-2 text-gray-500"></i>
                            عملیات مدیریتی
                        </h5>
                    </div>
                    <div class="p-6">
                        <div class="flex flex-wrap gap-3">
                            @if($allocation->status === 'pending')
                                <!-- تایید تخصیص -->
                                <form action="{{ route('insurance.allocations.approve', $allocation) }}" 
                                      method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md flex items-center" 
                                            onclick="return confirm('آیا از تایید این تخصیص مطمئن هستید؟')">
                                        <i class="fas fa-check ml-2"></i>
                                        تایید تخصیص
                                    </button>
                                </form>

                                <!-- حذف تخصیص -->
                                <form action="{{ route('insurance.allocations.destroy', $allocation) }}" 
                                      method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md flex items-center" 
                                            onclick="return confirm('آیا از حذف این تخصیص مطمئن هستید؟ این عمل قابل بازگشت نیست.')">
                                        <i class="fas fa-trash ml-2"></i>
                                        حذف تخصیص
                                    </button>
                                </form>
                            @endif

                            @if($allocation->status === 'approved')
                                <!-- علامت‌گذاری پرداخت -->
                                <form action="{{ route('insurance.allocations.mark-as-paid', $allocation) }}" 
                                      method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md flex items-center" 
                                            onclick="return confirm('آیا این تخصیص پرداخت شده است؟')">
                                        <i class="fas fa-money-check-alt ml-2"></i>
                                        علامت‌گذاری پرداخت
                                    </button>
                                </form>
                            @endif
                        </div>

                        @if($allocation->status === 'paid')
                            <div class="bg-blue-50 text-blue-700 p-4 rounded-md mt-4">
                                <i class="fas fa-info-circle ml-2"></i>
                                این تخصیص پرداخت شده و امکان تغییر آن وجود ندارد.
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- ستون کناری - 1/3 -->
        <div class="space-y-6">
            <!-- خلاصه وضعیت خانواده -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b border-gray-200">
                    <h6 class="font-bold text-gray-700 flex items-center">
                        <i class="fas fa-chart-pie ml-2 text-gray-500"></i>
                        وضعیت کلی خانواده
                    </h6>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="text-center">
                            <p class="text-xs text-gray-500 mb-1">حق بیمه کل</p>
                            <div class="text-blue-600 font-bold">
                                {{ number_format($allocationStatus['total_premium']) }}
                            </div>
                            <p class="text-xs text-gray-500">تومان</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-500 mb-1">درصد تخصیص</p>
                            <div class="text-green-600 font-bold">
                                {{ $allocationStatus['total_percentage'] }}%
                            </div>
                            <p class="text-xs text-gray-500">از کل بودجه</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-500 mb-1">مبلغ تخصیص یافته</p>
                            <div class="text-indigo-600 font-bold">
                                {{ number_format($allocationStatus['total_amount']) }}
                            </div>
                            <p class="text-xs text-gray-500">تومان</p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-gray-500 mb-1">باقی‌مانده</p>
                            <div class="text-yellow-600 font-bold">
                                {{ number_format($allocationStatus['remaining_amount']) }}
                            </div>
                            <p class="text-xs text-gray-500">تومان</p>
                        </div>
                    </div>

                    <!-- نوار پیشرفت -->
                    <div class="mt-4">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>پیشرفت تخصیص</span>
                            <span>{{ $allocationStatus['total_percentage'] }}%</span>
                        </div>
                        <div class="relative pt-1">
                            <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                                <div style="width: {{ $allocationStatus['total_percentage'] }}%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-green-500"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- سایر تخصیص‌های خانواده -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b border-gray-200">
                    <h6 class="font-bold text-gray-700 flex items-center">
                        <i class="fas fa-list-alt ml-2 text-gray-500"></i>
                        سایر تخصیص‌های این خانواده
                    </h6>
                </div>
                <div>
                    @if($allocationStatus['allocations']->count() > 1)
                        <div class="divide-y divide-gray-100">
                            @foreach($allocationStatus['allocations'] as $otherAllocation)
                                @if($otherAllocation->id != $allocation->id)
                                    <a href="{{ route('insurance.allocations.show', $otherAllocation) }}" 
                                       class="block p-4 hover:bg-gray-50 transition">
                                        <div class="flex justify-between items-center mb-1">
                                            <div class="flex items-center">
                                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 ml-2">
                                                    {{ $otherAllocation->fundingSource->name }}
                                                </span>
                                                <span class="font-medium">{{ $otherAllocation->percentage }}%</span>
                                            </div>
                                            <div>
                                                @if($otherAllocation->status === 'pending')
                                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">در انتظار</span>
                                                @elseif($otherAllocation->status === 'approved')
                                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">تایید شده</span>
                                                @elseif($otherAllocation->status === 'paid')
                                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">پرداخت شده</span>
                                                @endif
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500">
                                            {{ number_format($otherAllocation->amount) }} تومان
                                        </p>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                        <div class="p-4 border-t border-gray-200">
                            <a href="{{ route('insurance.allocations.family-report', $allocation->family_id) }}" 
                               class="w-full block text-center bg-indigo-50 hover:bg-indigo-100 text-indigo-700 py-2 px-4 rounded-md text-sm">
                                <i class="fas fa-chart-bar ml-2"></i>
                                گزارش کامل تخصیص‌ها
                            </a>
                        </div>
                    @else
                        <div class="p-4 text-center text-gray-500">
                            هیچ تخصیص دیگری برای این خانواده ثبت نشده است.
                        </div>
                    @endif
                </div>
            </div>

            <!-- لینک‌های مفید -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b border-gray-200">
                    <h6 class="font-bold text-gray-700 flex items-center">
                        <i class="fas fa-link ml-2 text-gray-500"></i>
                        لینک‌های مفید
                    </h6>
                </div>
                <div class="p-2">
                    <div class="grid grid-cols-1 gap-2">
                        <a href="{{ route('insurance.allocations.family-report', $allocation->family_id) }}" 
                           class="p-2 hover:bg-gray-50 rounded-md flex items-center text-gray-700">
                            <i class="fas fa-file-alt ml-2 text-gray-500"></i>
                            گزارش تخصیص‌های خانواده
                        </a>
                        <a href="{{ route('insurance.families.show', $allocation->family_id) }}" 
                           class="p-2 hover:bg-gray-50 rounded-md flex items-center text-gray-700">
                            <i class="fas fa-users ml-2 text-gray-500"></i>
                            مشاهده پرونده خانواده
                        </a>
                        <a href="{{ route('insurance.allocations.create', ['family_id' => $allocation->family_id]) }}" 
                           class="p-2 hover:bg-gray-50 rounded-md flex items-center text-gray-700">
                            <i class="fas fa-plus ml-2 text-gray-500"></i>
                            تخصیص جدید برای این خانواده
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout> 
@endsection 