<!-- منوی اصلی سایت -->
<div class="flex relative">
    <!-- اصل منو -->
    <div 
        id="sidebar-menu" 
        x-data="{ 
            collapsed: localStorage.getItem('sidebar-collapsed') === 'true',
            toggle() {
                this.collapsed = !this.collapsed;
                localStorage.setItem('sidebar-collapsed', this.collapsed);
                
                // اعمال تغییرات روی المان‌های DOM
                if (this.collapsed) {
                    this.$refs.menuContainer.classList.add('menu-collapsed');
                    this.$refs.menuContainer.classList.remove('menu-expanded');
                } else {
                    this.$refs.menuContainer.classList.add('menu-expanded');
                    this.$refs.menuContainer.classList.remove('menu-collapsed');
                }
            },
            init() {
                // تنظیم اولیه بر اساس وضعیت ذخیره شده
                if (this.collapsed) {
                    this.$refs.menuContainer.classList.add('menu-collapsed');
                    this.$refs.menuContainer.classList.remove('menu-expanded');
                } else {
                    this.$refs.menuContainer.classList.add('menu-expanded');
                    this.$refs.menuContainer.classList.remove('menu-collapsed');
                }
            }
        }"
        x-ref="menuContainer"
        :class="{'menu-collapsed': collapsed, 'menu-expanded': !collapsed}"
        x-init="init()"
    >
        <div class="sidebar-header sticky top-0 z-[50] bg-white dark:bg-slate-800 h-[85px] flex items-center ps-8 pe-6">
            <div class="logo-segment flex items-center justify-between w-full">
                <a href="{{ route('home') }}" class="flex items-center space-x-2 rtl:space-x-reverse">
                    <div class="logo-icon">
                        <img
                            class="h-10"
                            src="{{ getSettings('app_logo') }}"
                            alt="{{ getSettings('app_name') }}"
                        >
                    </div>
                    <span
                        class="text-xl font-Inter font-bold text-slate-900 dark:text-slate-100 transition-all duration-150 text-center logo-title"
                        :class="{'opacity-0 text-[0px]': collapsed}"
                    >{{ getSettings('app_name') }}</span>
                </a>
            </div>
        </div>
        <div class="sidebar-body h-[calc(100%-70px)] overflow-hidden hover:overflow-auto relative rtl:border-r ltr:border-l border-slate-200 dark:border-slate-700">
            <div class="sidebar-body-wrapper w-full">
                <div class="nav-wrapper mb-10">
                    <div class="flex items-center">
                        <div class="nav-header-title pe-3 ps-8 tracking-wide my-4 font-semibold uppercase text-sm text-slate-600 dark:text-slate-300" :class="{'opacity-0 text-[0px]': collapsed}">
                            مدیریت سیستم
                        </div>
                    </div>
                    <ul class="sidebar-menu relative">
                        <li class="sidebar-menu-title">
                            <a href="{{ route('admin.dashboard') }}"
                                class="navItem {{ Request::is('admin/dashboard*') ? 'active' : '' }}" :class="{'before:w-[230px] before:h-[36px] before:absolute before:top-[50%] before:translate-y-[-50%]': !collapsed}">
                                <span class="flex items-center space-x-1">
                                    <i class="w-6 h-6 text-center text-xl leading-[1.6]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-[24px] w-[24px]" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="3" width="7" height="9"></rect>
                                            <rect x="14" y="3" width="7" height="5"></rect>
                                            <rect x="14" y="12" width="7" height="9"></rect>
                                            <rect x="3" y="16" width="7" height="5"></rect>
                                        </svg>
                                    </i>
                                </span>
                                <span class="transition-all duration-100 ml-[11px]" :class="{'opacity-0 text-[0px]': collapsed}">داشبورد</span>
                            </a>
                        </li>
                        
                        <li class="sidebar-menu-title">
                            <a href="{{ route('admin.users.index') }}"
                                class="navItem {{ Request::is('admin/users*') ? 'active' : '' }}" :class="{'before:w-[230px] before:h-[36px] before:absolute before:top-[50%] before:translate-y-[-50%]': !collapsed}">
                                <span class="flex items-center space-x-1">
                                    <i class="w-6 h-6 text-center text-xl leading-[1.6]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-[24px] w-[24px]" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="9" cy="7" r="4"></circle>
                                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                        </svg>
                                    </i>
                                </span>
                                <span class="transition-all duration-100 ml-[11px]" :class="{'opacity-0 text-[0px]': collapsed}">کاربران</span>
                            </a>
                        </li>

                        <li class="sidebar-menu-title">
                            <a href="{{ route('admin.roles.index') }}"
                                class="navItem {{ Request::is('admin/roles*') ? 'active' : '' }}" :class="{'before:w-[230px] before:h-[36px] before:absolute before:top-[50%] before:translate-y-[-50%]': !collapsed}">
                                <span class="flex items-center space-x-1">
                                    <i class="w-6 h-6 text-center text-xl leading-[1.6]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-[24px] w-[24px]" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path
                                                d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71">
                                            </path>
                                            <path
                                                d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71">
                                            </path>
                                        </svg>
                                    </i>
                                </span>
                                <span class="transition-all duration-100 ml-[11px]" :class="{'opacity-0 text-[0px]': collapsed}">نقش‌ها</span>
                            </a>
                        </li>

                        <li class="sidebar-menu-title">
                            <a href="{{ route('admin.permissions.index') }}"
                                class="navItem {{ Request::is('admin/permissions*') ? 'active' : '' }}" :class="{'before:w-[230px] before:h-[36px] before:absolute before:top-[50%] before:translate-y-[-50%]': !collapsed}">
                                <span class="flex items-center space-x-1">
                                    <i class="w-6 h-6 text-center text-xl leading-[1.6]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-[24px] w-[24px]" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                        </svg>
                                    </i>
                                </span>
                                <span class="transition-all duration-100 ml-[11px]" :class="{'opacity-0 text-[0px]': collapsed}">مجوزها</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="nav-wrapper mb-10">
                    <div class="flex items-center">
                        <div class="nav-header-title pe-3 ps-8 tracking-wide my-4 font-semibold uppercase text-sm text-slate-600 dark:text-slate-300" :class="{'opacity-0 text-[0px]': collapsed}">
                            خیریه
                        </div>
                    </div>
                    <ul class="sidebar-menu relative">
                        <li class="sidebar-menu-title">
                            <a href="{{ route('charity.index') }}"
                                class="navItem {{ Request::is('charity') ? 'active' : '' }}" :class="{'before:w-[230px] before:h-[36px] before:absolute before:top-[50%] before:translate-y-[-50%]': !collapsed}">
                                <span class="flex items-center space-x-1">
                                    <i class="w-6 h-6 text-center text-xl leading-[1.6]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-[24px] w-[24px]" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="3" width="7" height="9"></rect>
                                            <rect x="14" y="3" width="7" height="5"></rect>
                                            <rect x="14" y="12" width="7" height="9"></rect>
                                            <rect x="3" y="16" width="7" height="5"></rect>
                                        </svg>
                                    </i>
                                </span>
                                <span class="transition-all duration-100 ml-[11px]" :class="{'opacity-0 text-[0px]': collapsed}">داشبورد خیریه</span>
                            </a>
                        </li>

                        <li class="sidebar-menu-title">
                            <a href="{{ route('charity.family.index') }}"
                                class="navItem {{ Request::is('charity/family*') ? 'active' : '' }}" :class="{'before:w-[230px] before:h-[36px] before:absolute before:top-[50%] before:translate-y-[-50%]': !collapsed}">
                                <span class="flex items-center space-x-1">
                                    <i class="w-6 h-6 text-center text-xl leading-[1.6]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-[24px] w-[24px]" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="9" cy="7" r="4"></circle>
                                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                        </svg>
                                    </i>
                                </span>
                                <span class="transition-all duration-100 ml-[11px]" :class="{'opacity-0 text-[0px]': collapsed}">مدیریت خانواده‌ها</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- کنترل باز و بسته شدن منو -->
        <button 
            @click="toggle()"
            class="absolute left-5 top-1/2 -translate-y-1/2 z-[1] h-8 w-8 rounded-full bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 flex items-center justify-center text-slate-900 dark:text-white transition-all duration-150"
        >
            <svg 
                xmlns="http://www.w3.org/2000/svg" 
                class="h-5 w-5" 
                fill="none" 
                viewBox="0 0 24 24" 
                stroke="currentColor" 
                :class="{'rotate-180': !collapsed}"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>
</div>

<style>
    /* استایل‌های مربوط به آیکون‌ها در حالت بسته منو */
    #sidebar-menu[x-data] .menu-icon {
        transition: all 0.3s ease;
    }
    
    /* کاهش پدینگ در حالت جمع شده */
    #sidebar-menu[x-data] .w-16 a, 
    #sidebar-menu[x-data] .w-16 button {
        padding-left: 0;
        padding-right: 0;
        justify-content: center;
    }
    
    /* موقعیت آیکون‌ها در حالت بسته */
    #sidebar-menu[x-data] .w-16 .menu-icon {
        margin-left: 0;
        margin-right: 0;
        display: flex;
        justify-content: center;
        width: 100%;
    }
    
    /* استایل متن‌ها */
    .menu-text {
        transition: opacity 0.2s ease;
    }
</style> 