<!-- منوی اصلی سایت -->
<div class="relative" x-data="{ collapsed: false }">
    <!-- دکمه‌ی باز/بسته کردن منو -->
    <button @click="collapsed = !collapsed" class="fixed top-5 right-0 z-50 p-2 bg-white rounded-r-lg shadow-md" :class="collapsed ? 'mr-16' : 'mr-64'">
        <svg x-show="!collapsed" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        <svg x-show="collapsed" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
    </button>
    
    <!-- منوی کناری با قابلیت جمع شدن -->
    <div class="sidebar-menu h-screen fixed right-0 top-0 z-40 bg-white border-l border-gray-200 overflow-y-auto flex flex-col transition-all duration-300 ease-in-out"
         :class="{'w-16 collapsed': collapsed, 'w-64': !collapsed}">
        <!-- Logo Section -->
        <div class="flex flex-col items-center justify-center py-6 border-b border-gray-200">
            <!-- <div class="text-green-500 font-bold text-lg mt-2" x-show="!collapsed">میکروبیمه</div> -->
            <div :class="collapsed ? 'w-10 h-10' : 'w-20 h-20'">
                <img src="{{ asset('images/image.png') }}" alt="لوگو خیریه" class="w-full">
            </div>
        </div>

        <!-- Menu Items -->
        <div class="flex-grow">
            <a href="{{ route('dashboard') }}" class="flex items-center py-4 hover:bg-gray-100 {{ request()->routeIs('dashboard') ? 'bg-gray-100' : '' }}"
               :class="collapsed ? 'px-3 justify-center' : 'px-6'">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-700" :class="collapsed ? '' : 'ml-3'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                </svg>
                <span x-show="!collapsed">داشبورد</span>
            </a>

            <a href="{{ route('charity.insured-families') }}" class="flex items-center py-4 hover:bg-gray-100 {{ request()->routeIs('charity.insured-families') ? 'bg-green-500 text-white' : '' }}"
               :class="collapsed ? 'px-3 justify-center' : 'px-6'">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 {{ request()->routeIs('charity.insured-families') ? 'text-white' : 'text-gray-700' }}" :class="collapsed ? '' : 'ml-3'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <div class="flex flex-col" x-show="!collapsed">
                    <span>خانواده‌های بیمه شده</span>
                    <span class="text-xs {{ request()->routeIs('charity.insured-families') ? 'text-white' : 'text-gray-500' }}">
                        @if(isset($insuredFamilies) && isset($insuredMembers))
                            ({{ $insuredFamilies }} خانواده - {{ $insuredMembers }} نفر)
                        @else
                            (۰ خانواده - ۰ نفر)
                        @endif
                    </span>
                </div>
            </a>
            

            <a href="{{ route('charity.uninsured-families') }}" class="flex items-center py-4 hover:bg-gray-100 {{ request()->routeIs('charity.uninsured-families') ? 'bg-red-500 text-white' : '' }}"
               :class="collapsed ? 'px-3 justify-center' : 'px-6'">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 {{ request()->routeIs('charity.uninsured-families') ? 'text-white' : 'text-gray-700' }}" :class="collapsed ? '' : 'ml-3'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
                <div class="flex flex-col" x-show="!collapsed">
                    <span>خانواده‌های بدون پوشش</span>
                    <span class="text-xs {{ request()->routeIs('charity.uninsured-families') ? 'text-white' : 'text-gray-500' }}">
                        @if(isset($uninsuredFamilies) && isset($uninsuredMembers))
                            ({{ $uninsuredFamilies }} خانواده - {{ $uninsuredMembers }} نفر)
                        @else
                            (۰ خانواده - ۰ نفر)
                        @endif
                    </span>
                </div>
            </a>

            <a href="{{ route('charity.add-family') }}" class="flex items-center py-4 hover:bg-gray-100 {{ request()->routeIs('charity.add-family') ? 'bg-blue-500 text-white' : '' }}"
               :class="collapsed ? 'px-3 justify-center' : 'px-6'">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 {{ request()->routeIs('charity.add-family') ? 'text-white' : 'text-green-500' }}" :class="collapsed ? '' : 'ml-3'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span x-show="!collapsed">افزودن خانواده جدید</span>
            </a>
        </div>

        <!-- Footer Menu Items -->
        <div class="border-t border-gray-200 py-2">
            <a href="{{ route('charity.settings') }}" class="flex items-center py-4 hover:bg-gray-100 {{ request()->routeIs('charity.settings') ? 'bg-gray-100' : '' }}"
               :class="collapsed ? 'px-3 justify-center' : 'px-6'">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-700" :class="collapsed ? '' : 'ml-3'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span x-show="!collapsed">تنظیمات</span>
            </a>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="w-full flex items-center py-4 hover:bg-gray-100 text-red-500"
                       :class="collapsed ? 'px-3 justify-center' : 'px-6'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" :class="collapsed ? '' : 'ml-3'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span x-show="!collapsed">خروج</span>
                </button>
            </form>
        </div>
    </div>
</div> 