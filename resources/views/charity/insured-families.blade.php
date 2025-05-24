<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">خانواده‌های بیمه شده</h1>
            <a href="{{ route('charity.add-family') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                افزودن خانواده جدید
            </a>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                @livewire('charity.family-search', ['status' => 'insured'])
            </div>
        </div>
    </div>
</x-app-layout> 