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
            <!-- دکمه‌های عملیات و دسترسی‌ها -->
            <div class="flex items-center space-x-reverse space-x-2 sm:space-x-4 overflow-x-auto hide-scrollbar gap-4">
                <!-- دکمه آپلود اکسل خانواده‌ها (فقط در صفحه داشبورد خیریه) -->
                @if(auth()->check() && auth()->user()->isActiveAs('charity') && request()->routeIs('charity.dashboard'))
                <button onclick="openUploadModal()" class="inline-flex items-center px-3 py-2 text-sm font-medium text-green-600 bg-white border border-green-600 rounded-md hover:bg-green-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <span class="hidden sm:inline">وارد کردن با فایل اکسل</span>
                    <span class="sm:hidden">آپلود</span>
                </button>
                @endif
                
                <!-- دکمه‌های پنل‌ها براساس نقش فعلی کاربر -->
                @if(auth()->check())
                    @php $activeRole = auth()->user()->getActiveRole(); @endphp
                    
                    <!-- دکمه پنل خیریه - فقط برای کاربران با نقش خیریه -->
                    @if($activeRole === 'charity')
                    <a href="{{ route('charity.dashboard') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-white border border-blue-600 rounded-md hover:bg-blue-50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span class="hidden sm:inline">پنل خیریه</span>
                        <span class="sm:hidden">خیریه</span>
                    </a>
                    @endif

                    <!-- دکمه پنل بیمه - فقط برای کاربران با نقش بیمه -->
                    @if($activeRole === 'insurance')
                    <a href="{{ route('insurance.dashboard') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-purple-600 bg-white border border-purple-600 rounded-md hover:bg-purple-50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span class="hidden sm:inline">پنل بیمه</span>
                        <span class="sm:hidden">بیمه</span>
                    </a>
                    @endif

                    <!-- دکمه پنل ادمین (فقط برای ادمین) -->
                    @if($activeRole === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-indigo-600 bg-white border border-indigo-600 rounded-md hover:bg-indigo-50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="hidden sm:inline">پنل ادمین</span>
                        <span class="sm:hidden">ادمین</span>
                    </a>
                    @endif
                @endif
            </div>

            <!-- نمایش بودجه در وسط نوار -->
            @if(auth()->check() && (auth()->user()->isActiveAs('insurance') || auth()->user()->isActiveAs('admin')))
                @php
                    // محاسبه موجودی کل همانند FinancialReportController
                    $totalCredit = FundingTransaction::sum('amount');
                    $totalDebit = InsuranceAllocation::sum('amount') + 
                                  InsuranceImportLog::sum('total_insurance_amount') +
                                  InsurancePayment::sum('total_amount');
                    $totalBudget = $totalCredit;
                    $remainingBudget = $totalCredit - $totalDebit;

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

            <!-- پروفایل کاربر و دکمه‌های سمت چپ -->
            <div class="flex items-center space-x-reverse space-x-2">
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
                    @endif
                    
                    <!-- دکمه دانلود اکسل -->
                    @if(isset($families) && $families->count() > 0)
            <button type="button" 
               wire:click="downloadInsuranceExcel"
               wire:loading.attr="disabled"
               class="inline-flex items-center justify-center px-4 py-2 text-sm font-bold text-green-400 bg-black border border-green-400 rounded-lg hover:bg-gray-800 transition-all duration-200 shadow-lg shadow-green-500/10 disabled:opacity-50 disabled:cursor-not-allowed">
                
                <!-- آیکون لودینگ که هنگام دانلود نمایش داده می‌شود -->
                <svg wire:loading wire:target="downloadInsuranceExcel" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>

                <!-- آیکون دانلود -->
                <svg wire:loading.remove wire:target="downloadInsuranceExcel" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0L8 8m4 4v12" />
                </svg>

                <span>دانلود اکسل</span>
            </button>
        @endif

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