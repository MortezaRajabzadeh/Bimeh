<x-app-layout>
<div class="container mx-auto px-4 py-6 max-w-7xl">
    <!-- هدر و خلاصه آمار -->
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">مدیریت تخصیص بودجه</h1>
                <p class="text-gray-600">تخصیص بودجه خانواده‌های آپلود شده از منابع مالی مختلف</p>
                <div class="mt-1 text-sm text-gray-600">
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-md">تعداد کل خانواده‌ها: {{ \App\Models\Family::count() }}</span>
                    <span class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded-md mr-2">تعداد کل اعضا: {{ \App\Models\Member::count() }}</span>
                </div>
            </div>
            <a href="{{ route('insurance.allocations.create') }}" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-md flex items-center">
                <i class="fas fa-plus ml-2"></i>
                تخصیص جدید
            </a>
        </div>

        <!-- آمار کلی - کارت‌های آماری -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4 border-r-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">در انتظار صدور</p>
                        <h3 class="text-2xl font-bold text-purple-700">{{ \App\Models\Family::where('status', 'approved')->count() }}</h3>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-users text-purple-500 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border-r-4 border-warning">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">در انتظار تایید</p>
                        <h3 class="text-2xl font-bold text-warning">{{ \App\Models\FamilyFundingAllocation::pending()->count() }}</h3>
                    </div>
                    <div class="bg-warning-light p-3 rounded-full">
                        <i class="fas fa-hourglass-half text-warning text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border-r-4 border-success">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">تایید شده</p>
                        <h3 class="text-2xl font-bold text-success">{{ \App\Models\FamilyFundingAllocation::approved()->count() }}</h3>
                    </div>
                    <div class="bg-success-light p-3 rounded-full">
                        <i class="fas fa-check-circle text-success text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border-r-4 border-info">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">پرداخت شده</p>
                        <h3 class="text-2xl font-bold text-info">{{ \App\Models\FamilyFundingAllocation::paid()->count() }}</h3>
                    </div>
                    <div class="bg-info-light p-3 rounded-full">
                        <i class="fas fa-money-check-alt text-info text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 border-r-4 border-gray-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">کل تخصیصات</p>
                        <h3 class="text-2xl font-bold text-gray-700">{{ \App\Models\FamilyFundingAllocation::count() }}</h3>
                    </div>
                    <div class="bg-gray-200 p-3 rounded-full">
                        <i class="fas fa-chart-line text-gray-700 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول تخصیصات -->
    <div class="bg-white rounded-lg shadow">
        @if($allocations->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">شناسه</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">خانواده</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">منبع مالی</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">درصد</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">مبلغ (تومان)</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">وضعیت</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاریخ</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($allocations as $allocation)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $allocation->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('insurance.allocations.family-report', $allocation->family_id) }}" 
                                       class="text-blue-600 hover:text-blue-800 font-medium">
                                        {{ $allocation->family->family_code }} - {{ optional($allocation->family->head)->first_name }} {{ optional($allocation->family->head)->last_name }}
                                    </a>
                                </td>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $allocation->created_at->diffForHumans() }}</td>
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

            <!-- پیجینیشن -->
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $allocations->links() }}
            </div>
        @else
            <div class="text-center py-10">
                <div class="mb-4">
                    <i class="fas fa-inbox text-gray-300 text-5xl"></i>
                </div>
                <h5 class="text-xl font-medium text-gray-500 mb-2">هیچ تخصیص بودجه‌ای یافت نشد</h5>
                <p class="text-gray-500 mb-4">برای شروع، یک تخصیص جدید ایجاد کنید.</p>
                <a href="{{ route('insurance.allocations.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg inline-flex items-center shadow-lg transition-all duration-200 text-lg">
                    <i class="fas fa-plus-circle ml-2"></i>
                    ایجاد تخصیص اول
                </a>
            </div>
        @endif
    </div>
</div>
</x-app-layout> 