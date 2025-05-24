<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">خانواده‌های بدون پوشش (بیمه)</h1>
        </div>
        <div class="grid grid-cols-1 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                @livewire('charity.family-search', ['status' => 'uninsured'])
            </div>
        </div>
    </div>
</x-app-layout> 