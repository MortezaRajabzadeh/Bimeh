@php
use Illuminate\Support\Facades\Session;
use App\Models\FundingTransaction;
use App\Models\InsuranceAllocation;
use App\Models\InsuranceImportLog;
use App\Models\InsurancePayment;
@endphp

<nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-30 w-full">
    
<div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-16">
                
            <!-- دکمه‌های سمت راست -->
            <div class="flex items-center space-x-reverse space-x-2">
            @if(auth()->check() && auth()->user()->isActiveAs('charity'))
                <button onclick="openUploadModal()" class="inline-flex items-center px-3 py-2 text-sm font-medium text-green-600 bg-white border border-green-600 rounded-md hover:bg-green-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <span class="hidden sm:inline">وارد کردن با فایل اکسل</span>
                    <span class="sm:hidden">آپلود</span>
                </button>
                @endif

                @if(auth()->check())
                    @if(auth()->user()->hasRole('admin'))
                    <!-- دکمه تغییر نقش برای ادمین -->
                    <div x-data="{ open: false }" class="relative ml-4">
                        <button @click="open = !open" class="flex items-center gap-1 px-3 py-1.5 text-sm font-medium bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition focus:outline-none">
                            @php
                                $activeRole = auth()->user()->getActiveRole();
                                $roleName = match($activeRole) {
                                    'charity' => 'سازمان خیریه',
                                    'insurance' => 'بیمه',
                                    default => 'ادمین'
                                };
                            @endphp
                            <span>{{ $roleName }}</span>
                            
                            @if(auth()->user()->isImpersonating())
                            <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            @endif
                            
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" class="absolute left-0 mt-2 w-40 bg-white border border-gray-200 rounded-md shadow-lg z-50">
                            <!-- گزینه خیریه -->
                            <form method="POST" action="{{ route('admin.switch-role.store') }}"> @csrf <input type="hidden" name="role" value="charity"> <button type="submit" class="flex items-center justify-between w-full text-right px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ auth()->user()->isActiveAs('charity') ? 'bg-blue-50' : '' }}"><span>سازمان خیریه</span>@if(auth()->user()->isActiveAs('charity'))<svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>@endif</button></form>
                            <!-- گزینه بیمه -->
                            <form method="POST" action="{{ route('admin.switch-role.store') }}"> @csrf <input type="hidden" name="role" value="insurance"> <button type="submit" class="flex items-center justify-between w-full text-right px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ auth()->user()->isActiveAs('insurance') ? 'bg-blue-50' : '' }}"><span>بیمه</span>@if(auth()->user()->isActiveAs('insurance'))<svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>@endif</button></form>
                            <!-- گزینه ادمین -->
                            <form method="POST" action="{{ route('admin.switch-role.store') }}"> @csrf <input type="hidden" name="role" value="admin"> <button type="submit" class="flex items-center justify-between w-full text-right px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 border-t border-gray-200 {{ auth()->user()->isActiveAs('admin') ? 'bg-blue-50' : '' }}"><span>ادمین</span>@if(auth()->user()->isActiveAs('admin'))<svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>@endif</button></form>
                        </div>
                    </div>
                    @else
                    <!-- دراپ‌داون نقش کاربر و اطلاعات -->
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center gap-1 px-3 py-1.5 text-sm font-medium bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition focus:outline-none">
                            @php
                                $activeRole = auth()->user()->getActiveRole();
                                $roleName = match($activeRole) {
                                    'charity' => 'سازمان خیریه',
                                    'insurance' => 'بیمه',
                                    default => 'ادمین'
                                };
                                $organization = auth()->user()->organization;
                                $organizationName = $organization ? $organization->name : 'بدون سازمان';
                            @endphp
                            <span>{{ $organizationName }}</span>
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" class="absolute left-0 mt-2 w-56 bg-white border border-gray-200 rounded-md shadow-lg z-50">
                            <!-- نمایش اطلاعات کاربر -->
                            <div class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium">{{ auth()->user()->name }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ auth()->user()->email }}</div>
                                <div class="text-xs font-medium text-blue-600 mt-1">{{ $roleName }}</div>
                            </div>
                            
                            <div class="border-t border-gray-200"></div>
                            
                            <!-- نمایش اطلاعات سازمان -->
                            <div class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium">{{ $organizationName }}</div>
                                @if($organization)
                                    <div class="text-xs text-gray-500 mt-1">{{ $organization->type ?? 'نوع نامشخص' }}</div>
                                @endif
                            </div>
                        
                        </div>
                    </div>
                    @endif
                    
                @endif
            </div>

            <!-- نمایش بودجه در وسط نوار -->
            @if(auth()->check() && (auth()->user()->isActiveAs('insurance') || auth()->user()->isActiveAs('admin')))
              @php
                    // محاسبه موجودی کل با استفاده از کش
                    $remainingBudget = Cache::remember('remaining_budget', now()->addMinutes(10), function () {
                        $totalCredit = FundingTransaction::sum('amount');
                        $totalDebit = InsuranceAllocation::sum('amount') + 
                                      InsuranceImportLog::sum('total_insurance_amount') +
                                      InsurancePayment::sum('total_amount');
                        return $totalCredit - $totalDebit;
                    });
                
                    function formatBudget($number) {
                        $result = '';
                        $billions = floor($number / 1000000000);
                        $millions = floor(($number % 1000000000) / 1000000);
                        
                        if ($billions > 0) {
                            $result .= number_format($billions) . ' میلیارد';
                            if ($millions > 0) {
                                $result .= ' و ' . number_format($millions) . ' میلیون';
                            }
                        } elseif ($millions > 0) {
                            $result = number_format($millions) . ' میلیون';
                        } else {
                            $result = number_format($number);
                        }
                        
                        return $result;
                    }
                @endphp
                <!-- نمایش در دسکتاپ -->
                <div class="hidden md:flex items-center gap-6">
                    <div class="w-px h-10 bg-gray-200"></div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-medium text-gray-700">بودجه باقی مانده  </span>
                        <span class="text-2xl font-bold text-green-600">{{ formatBudget($remainingBudget) }} <span class="text-2xl font-bold text-green-600">تومان</span></span>
                    </div>
                    <a href="{{ route('insurance.funding-manager') }}" 
                       class="p-1.5 -mr-1 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-full transition-colors"
                       title="مدیریت بودجه">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </a>
                </div>
                
                <!-- نمایش در موبایل -->
                <div class="flex md:hidden items-center gap-2">
                    <span class="text-xl font-medium text-gray-700">بودجه باقی مانده  </span>
                    <span class="text-2xl font-bold text-green-600">{{ formatBudget($remainingBudget) }} <span class="text-2xl font-bold text-green-600">تومان</span></span>
                    <a href="{{ route('insurance.funding-manager') }}" 
                       class="p-1.5 -mr-1 text-gray-500 hover:text-green-600 rounded-full transition-colors"
                       title="مدیریت بودجه">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </a>
                </div>
            @endif

            <!-- دکمه خروج و عملیات کاربر -->
            <div class="flex items-center space-x-reverse space-x-2">
                @if(auth()->check())
                    <!-- دکمه خروج -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center p-2 text-gray-500 bg-white rounded-full hover:bg-red-50 hover:text-red-600 transition" title="خروج از حساب کاربری">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-white border border-blue-600 rounded-md hover:bg-blue-50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        <span>ورود</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</nav>

<style>
/* مخفی کردن اسکرول‌بار افقی */
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
/* تنظیمات ریسپانسیو */
@media (max-width: 100%) {
    nav .max-w-7xl {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
}
</style>