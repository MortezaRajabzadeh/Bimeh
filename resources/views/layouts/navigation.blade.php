<nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-30 w-full">
    
<div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <!-- دکمه‌های عملیات و دسترسی‌ها -->
            <div class="flex items-center space-x-reverse space-x-2 sm:space-x-4 overflow-x-auto hide-scrollbar gap-4">
                <!-- دکمه دانلود اکسل -->
                <a href="#" class="inline-flex items-center px-3 py-2 text-sm font-medium text-green-600 bg-white border border-green-600 rounded-md hover:bg-green-50 transition" onclick="event.preventDefault();">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    <span class=" sm:inline">دانلود اکسل</span>
                    <!-- <span class="sm:hidden">دانلود</span> -->
                </a>
                
                <!-- دکمه‌های پنل‌ها -->
                @if(auth()->check())
                    @if(auth()->user()->hasRole('charity'))
                    <a href="{{ route('charity.dashboard') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-white border border-blue-600 rounded-md hover:bg-blue-50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span class="hidden sm:inline">پنل خیریه</span>
                        <span class="sm:hidden">خیریه</span>
                    </a>
                    @endif

                    @if(auth()->user()->hasRole('insurance'))
                    <a href="{{ route('insurance.dashboard') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-purple-600 bg-white border border-purple-600 rounded-md hover:bg-purple-50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span class="hidden sm:inline">پنل بیمه</span>
                        <span class="sm:hidden">بیمه</span>
                    </a>
                    @endif
                @endif
            </div>

            <!-- پروفایل کاربر و دکمه خروج -->
            <div class="flex items-center">
                @if(auth()->check())
    
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-500 bg-white border border-red-500 rounded-md hover:bg-red-50 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span class="sm:inline">خروج</span>
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