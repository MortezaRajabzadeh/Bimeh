<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('داشبورد خیریه') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-2xl mb-6">به سیستم میکروبیمه خوش آمدید</h1>
                    
                    <!-- کارت‌های اطلاعاتی با لایوویر -->
                    @livewire('charity.dashboard-stats')
                    
                    <br>
                    </br>
                    
                    <!-- جستجو و فیلتر با لایوویر -->
                    @livewire('charity.family-search')
                </div>
            </div>
        </div>
    </div>

    <style>
        /* استایل برای اسکرول افقی در موبایل */
        @media (max-width: 1280px) {
            .overflow-x-auto {
                overflow-x: auto;
            }
        }
    </style>
</x-app-layout> 