<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('افزودن خانواده جدید') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container mx-auto px-4">
            <!-- نمایش پیام‌های موفقیت و خطا -->
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <div class="text-green-800 whitespace-pre-line">{{ session('success') }}</div>
                    </div>
                    @if(session('results'))
                        @php $results = session('results'); @endphp
                        <div class="mt-3 text-sm text-green-700">
                            @if(isset($results['families_created']) && $results['families_created'] > 0)
                                <div>🏠 {{ $results['families_created'] }} خانواده جدید ثبت شد</div>
                            @endif
                            @if(isset($results['members_added']) && $results['members_added'] > 0)
                                <div>👥 {{ $results['members_added'] }} عضو جدید اضافه شد</div>
                            @endif
                            @if(isset($results['failed']) && $results['failed'] > 0)
                                <div>⚠️ {{ $results['failed'] }} ردیف دارای مشکل بود</div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-600 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <div class="text-red-800 whitespace-pre-line">{{ session('error') }}</div>
                    </div>
                    @if(session('results') && isset(session('results')['errors']))
                        @php $results = session('results'); @endphp
                        <div class="mt-3 text-sm text-red-700">
                            <div class="font-medium mb-2">خطاهای موجود:</div>
                            @foreach($results['errors'] as $error)
                                <div class="mb-1">• {{ $error }}</div>
                            @endforeach
                            @if(isset($results['total_errors']) && $results['total_errors'] > $results['showing_count'])
                                <div class="text-xs text-red-600 mt-2">
                                    و {{ $results['total_errors'] - $results['showing_count'] }} خطای دیگر...
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            <livewire:charity.family-wizard />
        </div>
    </div>
</x-app-layout> 