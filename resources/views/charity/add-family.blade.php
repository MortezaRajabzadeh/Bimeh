<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('افزودن خانواده جدید') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container mx-auto px-4">
            <livewire:charity.family-wizard />
        </div>
    </div>
</x-app-layout> 