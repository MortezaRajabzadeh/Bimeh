<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- بررسی وضعیت کاربر و نقش‌ها -->
                @if(auth()->check())
                    <div class="hidden">
                        وضعیت کاربر: 
                        {{ auth()->user()->name }} - 
                        نقش‌ها: 
                        @foreach(auth()->user()->roles as $role)
                            {{ $role->name }}
                        @endforeach
                    </div>
                @endif
                
                <!-- دکمه دانلود اکسل با شرط ساده‌تر -->
                <!-- @if(auth()->check() && (auth()->user()->hasRole('charity') || auth()->user()->hasRole('admin'))) -->
                <!-- دکمه دانلود اکسل - برای ادمین و خیریه -->
                <!-- <a href="#" class="flex items-center px-4 py-2 text-sm text-green-600 border border-green-600 rounded-lg ml-2 hover:bg-green-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    دانلود اکسل
                </a> -->
                <!-- @endif -->
                

                <a href="#" class="flex items-center px-4 py-2 text-sm text-green-600 border border-green-600 rounded-lg ml-2 hover:bg-green-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    دانلود اکسل 
                </a>
                
                <!-- دکمه بازگشت - نمایش فقط برای غیر ادمین ها -->
                
                
                @if(auth()->check() && auth()->user()->hasRole('charity'))
                <!-- دکمه‌های مخصوص خیریه -->
                <a href="{{ route('charity.dashboard') }}" class="flex items-center px-4 py-2 text-sm text-blue-600 ml-2 hover:text-blue-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    پنل خیریه
                </a>
                @endif

                @if(auth()->check() && auth()->user()->hasRole('insurance'))
                <!-- دکمه‌های مخصوص بیمه -->
                <a href="{{ route('insurance.dashboard') }}" class="flex items-center px-4 py-2 text-sm text-purple-600 ml-2 hover:text-purple-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    پنل بیمه
                </a>
                @endif

                <!-- Other Icons - مشترک برای همه -->
                <div class="flex items-center">
                    <a href="#" class="text-green-600 hover:text-green-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- بخش بودجه باقیمانده - فقط برای ادمین -->
            @if(auth()->check() && auth()->user()->hasRole('admin'))
            <div class="hidden sm:flex sm:items-center">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 ml-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4zm3 1h6v4H7V5zm8 8a1 1 0 01-1 1H6a1 1 0 01-1-1v-2a1 1 0 011-1h8a1 1 0 011 1v2z" clip-rule="evenodd" />
                    </svg>
                    <div class="flex flex-col items-end">
                        <span class="text-gray-500 text-xs">بودجه باقیمانده</span>
                        <span class="text-green-600 font-bold">۲ میلیارد و ۳۵۰ میلیون تومان</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- بخش اطلاعات کاربر خیریه -->
            @if(auth()->check() && auth()->user()->hasRole('charity'))
            <div class="hidden sm:flex sm:items-center">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <div class="flex flex-col items-end">
                        <span class="text-gray-500 text-xs">خیریه</span>
                        <span class="text-blue-600 font-bold">{{ auth()->user()->name }}</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- بخش اطلاعات بیمه -->
            @if(auth()->check() && auth()->user()->hasRole('insurance'))
            <div class="hidden sm:flex sm:items-center">
                <div class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <div class="flex flex-col items-end">
                        <span class="text-gray-500 text-xs">شرکت بیمه</span>
                        <span class="text-purple-600 font-bold">{{ auth()->user()->name }}</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- آیکون کاربر سمت راست -->
            <div class="flex items-center">
                <a href="{{ route('profile.edit') }}" class="text-green-600 p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </a>
            </div>

            <!-- Hamburger -->
            <div class="flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->

</nav> 