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
            @if(auth()->check() && auth()->user()->isActiveAs('charity') && request()->is('charity/add-family*'))
                <button type="button" onclick="openUploadModal()" class="inline-flex items-center px-3 py-2 text-sm font-medium text-green-600 bg-white border border-green-600 rounded-md hover:bg-green-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <span class="hidden sm:inline">وارد کردن خانواده جدید با فایل اکسل</span>
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
                <div class="hidden md:flex items-center gap-6" id="budget-display-desktop">
                    <div class="w-px h-10 bg-gray-200"></div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-medium text-gray-700">بودجه باقی مانده  </span>
                        <span class="text-2xl font-bold text-green-600" id="budget-amount-desktop">{{ formatBudget($remainingBudget) }} <span class="text-2xl font-bold text-green-600">تومان</span></span>
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
                <div class="flex md:hidden items-center gap-2" id="budget-display-mobile">
                    <span class="text-xl font-medium text-gray-700">بودجه باقی مانده  </span>
                    <span class="text-2xl font-bold text-green-600" id="budget-amount-mobile">{{ formatBudget($remainingBudget) }} <span class="text-2xl font-bold text-green-600">تومان</span></span>
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

            <!-- اسکریپت برای به‌روزرسانی بودجه -->
            <script>
                document.addEventListener('livewire:init', () => {
                    Livewire.on('budget-updated', () => {
                        // به‌روزرسانی بودجه با درخواست AJAX
                        fetch('/api/budget/remaining')
                            .then(response => response.json())
                            .then(data => {
                                const formatBudget = (number) => {
                                    let result = '';
                                    const billions = Math.floor(number / 1000000000);
                                    const millions = Math.floor((number % 1000000000) / 1000000);

                                    if (billions > 0) {
                                        result += billions.toLocaleString('fa-IR') + ' میلیارد';
                                        if (millions > 0) {
                                            result += ' و ' + millions.toLocaleString('fa-IR') + ' میلیون';
                                        }
                                    } else if (millions > 0) {
                                        result = millions.toLocaleString('fa-IR') + ' میلیون';
                                    } else {
                                        result = number.toLocaleString('fa-IR');
                                    }

                                    return result;
                                };

                                const formattedBudget = formatBudget(data.remaining_budget);
                                
                                // به‌روزرسانی نمایش دسکتاپ
                                const desktopElement = document.getElementById('budget-amount-desktop');
                                if (desktopElement) {
                                    desktopElement.innerHTML = formattedBudget + ' <span class="text-2xl font-bold text-green-600">تومان</span>';
                                }

                                // به‌روزرسانی نمایش موبایل
                                const mobileElement = document.getElementById('budget-amount-mobile');
                                if (mobileElement) {
                                    mobileElement.innerHTML = formattedBudget + ' <span class="text-2xl font-bold text-green-600">تومان</span>';
                                }
                            })
                            .catch(error => {
                                console.error('خطا در به‌روزرسانی بودجه:', error);
                            });
                    });
                });
            </script>

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

<!-- مودال آپلود اکسل خانواده‌ها -->
@if(auth()->check() && auth()->user()->isActiveAs('charity') && request()->is('charity/add-family*'))
<div id="uploadModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 hidden" onclick="closeUploadModalOnBackdrop(event)">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative" onclick="event.stopPropagation()">
            <!-- هدر مودال -->
            <div class="border-b border-gray-200 p-6 text-center relative">
                <button type="button" onclick="closeUploadModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <h3 class="text-xl font-bold text-gray-800 mb-2">وارد کردن با فایل اکسل</h3>
                <p class="text-sm text-gray-600">برای وارد کردن اطلاعات خانواده‌ها به صورت دسته جمعی، ابتدا فایل نمونه را طبق فایل نمونه آماده کرده و آن را آپلود نمایید.</p>
            </div>

            <!-- محتوای مودال -->
            <div class="p-6">
                <!-- منطقه Drag & Drop -->
                <div id="dropZone" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center mb-6 hover:border-green-400 transition-colors cursor-pointer">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <p id="dropZoneText" class="text-gray-600 mb-2 font-medium">فایل آماده شده را در اینجا قرار دهید</p>
                    <p class="text-xs text-gray-500">یا برای انتخاب فایل کلیک کنید</p>
                    <input type="file" id="excelFile" accept=".xlsx,.xls,.csv" class="hidden">
                </div>

                <!-- دکمه‌های عملیات -->
                <div class="flex gap-3">
                    <button type="button" onclick="downloadTemplate()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 px-4 rounded-lg text-sm font-medium transition-colors flex items-center justify-center">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        دانلود فایل نمونه
                    </button>

                    <button type="button" onclick="uploadFile()" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg text-sm font-medium transition-colors flex items-center justify-center">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        آپلود فایل
                    </button>
                </div>

                <!-- فرم مخفی برای آپلود -->
                <form id="uploadForm" action="{{ route('charity.families.import') }}" method="POST" enctype="multipart/form-data" class="hidden">
                    @csrf
                    <input type="hidden" name="district_id" id="districtSelect" value="1">
                    <input type="file" id="hiddenFileInput" name="file" accept=".xlsx,.xls,.csv">
                </form>
            </div>
        </div>
    </div>
</div>

@endif

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

@if(auth()->check() && auth()->user()->isActiveAs('charity') && request()->is('charity/add-family*'))
<script>
// باز کردن مودال
function openUploadModal() {
    const modal = document.getElementById('uploadModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        // حذف این خط تا فایل انتخاب شده پاک نشود
        // resetModalContent();
    }
}

// بستن مودال
function closeUploadModal() {
    const modal = document.getElementById('uploadModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        // ریست کردن محتوای مودال فقط هنگام بستن
        resetModalContent();
    }
}

// بستن مودال با کلیک روی پس‌زمینه
function closeUploadModalOnBackdrop(event) {
    if (event.target === event.currentTarget) {
        closeUploadModal();
    }
}

// ریست کردن محتوای مودال
function resetModalContent() {
    const fileInput = document.getElementById('excelFile');
    const dropZoneText = document.getElementById('dropZoneText');
    const dropZone = document.getElementById('dropZone');

    if (fileInput) {
        fileInput.value = '';
    }
    if (dropZoneText) {
        dropZoneText.textContent = 'فایل آماده شده را در اینجا قرار دهید';
    }
    if (dropZone) {
        dropZone.classList.remove('border-green-400', 'bg-green-50');
    }
}

// دانلود فایل نمونه
function downloadTemplate() {
    // تست Ajax برای نمایش خطای دقیق
    fetch('{{ route("charity.import.template.families") }}', {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
    })
    .then(response => {
        if (response.ok) {
            // اگر موفقیت‌آمیز بود، دانلود فایل
            window.open('{{ route("charity.import.template.families") }}', '_blank');
        } else {
            // نمایش خطا
            response.text().then(text => {
                console.error('خطا:', response.status, text);
                if (response.status === 401) {
                    alert('ابتدا وارد سیستم شوید.');
                } else if (response.status === 403) {
                    alert('شما مجوز دانلود فایل نمونه را ندارید.');
                } else {
                    alert('خطا در دانلود فایل: ' + response.status);
                }
            });
        }
    })
    .catch(error => {
        console.error('خطا در درخواست:', error);
        alert('خطا در ارتباط با سرور.');
    });
}

// آپلود فایل
function uploadFile() {
    const fileInput = document.getElementById('excelFile');
    const hiddenInput = document.getElementById('hiddenFileInput');
    const uploadButton = document.querySelector('button[onclick="uploadFile()"]');

    if (!fileInput || fileInput.files.length === 0) {
        alert('لطفا ابتدا فایل را انتخاب کنید.');
        return;
    }

    if (!hiddenInput) {
        alert('خطا در سیستم. لطفا صفحه را بازخوانی کنید.');
        return;
    }

    try {
        // نمایش loading state
        if (uploadButton) {
            uploadButton.disabled = true;
            uploadButton.innerHTML = `
                <svg class="animate-spin w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                در حال آپلود...
            `;
        }

        // کپی فایل انتخاب شده به فرم مخفی
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(fileInput.files[0]);
        hiddenInput.files = dataTransfer.files;

        // ارسال فرم
        const form = document.getElementById('uploadForm');
        if (form) {
            form.submit();
        } else {
            alert('خطا در سیستم. لطفا صفحه را بازخوانی کنید.');
            // بازگشت به حالت عادی
            if (uploadButton) {
                uploadButton.disabled = false;
                uploadButton.innerHTML = `
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    آپلود فایل
                `;
            }
        }
    } catch (error) {
        console.error('Error uploading file:', error);
        alert('خطا در آپلود فایل. لطفا مجدد تلاش کنید.');

        // بازگشت به حالت عادی
        if (uploadButton) {
            uploadButton.disabled = false;
            uploadButton.innerHTML = `
                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                آپلود فایل
            `;
        }
    }
}

// Event listeners برای drag & drop
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('excelFile');
    const dropZoneText = document.getElementById('dropZoneText');

    if (!dropZone || !fileInput) {
        return;
    }

    // کلیک برای انتخاب فایل
    dropZone.addEventListener('click', function() {
        fileInput.click();
    });

    // تغییر فایل انتخاب شده
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            const fileName = this.files[0].name;
            if (dropZoneText) {
                dropZoneText.textContent = fileName;
            }
            dropZone.classList.add('border-green-400', 'bg-green-50');
        }
    });

    // Drag & Drop events
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('border-green-400', 'bg-green-50');
    });

    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('border-green-400', 'bg-green-50');
    });

    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('border-green-400', 'bg-green-50');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const fileName = files[0].name;
            fileInput.files = files;
            if (dropZoneText) {
                dropZoneText.textContent = fileName;
            }
            this.classList.add('border-green-400', 'bg-green-50');
        }
    });

    // بستن مودال با کلید ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('uploadModal');
            if (modal && !modal.classList.contains('hidden')) {
                closeUploadModal();
            }
        }
    });
});
</script>
@endif

