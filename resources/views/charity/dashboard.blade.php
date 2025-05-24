<x-app-layout>
    <div class="py-6">
        <div class="container mx-auto px-4">
            <!-- بخش آمارهای داشبورد -->
            <livewire:charity.dashboard-stats />
            
            <!-- فاصله و خط جداکننده بین دو بخش -->
            <div class="my-10 border-t border-gray-200"></div>
       
            <!-- جدول خانواده‌ها با Livewire -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <livewire:charity.family-search />
            </div>
        </div>
    </div>
</x-app-layout> 