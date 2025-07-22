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
                    <!-- دو دراپ‌داون برای ادمین -->
                    <div class="flex items-center gap-2">
                        <!-- دراپ‌داون انتخاب نوع شرکت -->
                        <div x-data="{ open: false }" class="relative">
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
                                <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <div x-show="open" @click.away="open = false" class="absolute -left-10 mt-2 w-40 bg-white border border-gray-200 rounded-md shadow-lg z-50">
                                <!-- گزینه‌های نقش -->
                                <form method="POST" action="{{ route('admin.switch-role.store') }}"> 
                                    @csrf 
                                    <input type="hidden" name="role" value="charity"> 
                                    <button type="submit" class="flex items-center justify-between w-full text-right px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ auth()->user()->isActiveAs('charity') ? 'bg-blue-50' : '' }}">
                                        <span>سازمان خیریه</span>
                                        @if(auth()->user()->isActiveAs('charity'))
                                        <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        @endif
                                    </button>
                                </form>
                                <!-- سایر گزینه‌ها... -->
                            </div>
                        </div>
                
                        <!-- دراپ‌داون انتخاب کاربر -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center gap-1 px-3 py-1.5 text-sm font-medium bg-blue-50 border border-blue-300 rounded-md hover:bg-blue-100 transition focus:outline-none">
                                @php
                                    $currentUser = session('impersonated_user_id') ? 
                                        \App\Models\User::find(session('impersonated_user_id')) : 
                                        auth()->user();
                                @endphp
                                <span>{{ $currentUser->name }}</span>
                                @if(session('impersonated_user_id'))
                                <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                @endif
                                <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <div x-show="open" @click.away="open = false" class="absolute -left-10 mt-2 w-56 bg-white border border-gray-200 rounded-md shadow-lg z-50 max-h-60 overflow-y-auto">
                                @php
                                    $activeRole = auth()->user()->getActiveRole();
                                    // نمایش همه کاربران بجای فیلتر بر اساس نقش فعلی
                                    $users = \App\Models\User::whereHas('roles', function($q) {
                                        $q->whereIn('name', ['admin', 'charity', 'insurance']);
                                    })->with('organization')->get();
                                @endphp
                                
                                @foreach($users as $user)
                                <form method="POST" action="{{ route('admin.impersonate-user.store') }}">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                    <button type="submit" class="flex items-center justify-between w-full text-right px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ session('impersonated_user_id') == $user->id ? 'bg-blue-50' : '' }}">
                                        <div>
                                            <div class="font-medium">{{ $user->name }}</div>
                                            @if($user->organization)
                                            <div class="text-xs text-gray-500">{{ $user->organization->name }}</div>
                                            @endif
                                        </div>
                                        @if(session('impersonated_user_id') == $user->id)
                                        <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        @endif
                                    </button>
                                </form>
                                @endforeach
                                
                                @if(session('impersonated_user_id'))
                                <div class="border-t border-gray-200">
                                    <form method="POST" action="{{ route('admin.stop-impersonating-user') }}">
                                        @csrf
                                        <button type="submit" class="w-full text-right px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                            بازگشت به حساب اصلی
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </div>
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
                        
                        <div x-show="open" @click.away="open = false" class="absolute -left-24 mt-2 w-56 bg-white border border-gray-200 rounded-md shadow-lg z-50">
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

                            <div class="border-t border-gray-200"></div>
                            
                            <!-- عملیات کاربر -->
                            <div class="py-1">
                                <!-- تنظیمات کاربر -->
                                <a href="{{ route('profile.edit') ?? '#' }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">
                                    <svg class="h-4 w-4 ml-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span>تنظیمات حساب کاربری</span>
                                </a>
                                
                                <!-- تغییر رمز عبور -->
                                <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">
                                    <svg class="h-4 w-4 ml-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z"></path>
                                    </svg>
                                    <span>تغییر رمز عبور</span>
                                </a>
                                
                                <div class="border-t border-gray-200 my-1"></div>
                                
                                <!-- خروج از حساب -->
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center w-full text-right px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                                        <svg class="h-4 w-4 ml-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                        </svg>
                                        <span>خروج از حساب</span>
                                    </button>
                                </form>
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