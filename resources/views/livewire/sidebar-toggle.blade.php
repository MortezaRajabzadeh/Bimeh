<div
    x-data="{
        collapsed: JSON.parse(localStorage.getItem('sidebarCollapsed')) || false,
        toggle() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('sidebarCollapsed', this.collapsed);
            
            // ارسال رویداد به Alpine.js
            const event = new CustomEvent('sidebar-toggle', { 
                detail: { collapsed: this.collapsed }
            });
            window.dispatchEvent(event);
            
            // ارسال به Livewire
            this.$wire.set('collapsed', this.collapsed);
        }
    }"
    x-init="() => {
        // به روزرسانی اولیه وضعیت
        $wire.set('collapsed', collapsed);
    }"
>
    <!-- دکمه‌ی باز و بسته کردن -->
    <button
        @click="toggle"
        class="fixed z-50 top-1/2 -translate-y-1/2 transition-all duration-300 bg-green-500 text-white p-2 rounded-l-md shadow-md hover:bg-green-600"
        :class="collapsed ? 'right-16' : 'right-64'"
        aria-label="باز و بسته کردن منو"
        :aria-expanded="!collapsed"
    >
        <svg x-show="!collapsed" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <svg x-show="collapsed" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
    </button>
</div>
