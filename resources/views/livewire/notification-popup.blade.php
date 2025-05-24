@php
    $bgColor = match($type) {
        'success' => 'bg-green-100',
        'error' => 'bg-red-100',
        'warning' => 'bg-yellow-100',
        default => 'bg-blue-100'
    };
    
    $textColor = match($type) {
        'success' => 'text-green-800',
        'error' => 'text-red-800',
        'warning' => 'text-yellow-800',
        default => 'text-blue-800'
    };
    
    $icon = match($type) {
        'success' => 'check-circle',
        'error' => 'x-circle',
        'warning' => 'exclamation',
        default => 'information'
    };

    $positionClasses = match($position) {
        'top-right' => 'top-4 right-4',
        'top-left' => 'top-4 left-4',
        'bottom-right' => 'bottom-4 right-4',
        'bottom-left' => 'bottom-4 left-4',
        'top-center' => 'top-4 left-1/2 transform -translate-x-1/2',
        'bottom-center' => 'bottom-4 left-1/2 transform -translate-x-1/2',
        default => 'top-4 right-4'
    };
@endphp

<div x-data="{ show: @entangle('show') }" 
     x-show="show" 
     x-init="setTimeout(() => $wire.dismiss(), {{ $duration }})"
     x-transition:enter="transform ease-out duration-300 transition"
     x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
     x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed {{ $positionClasses }} z-50 rounded-md p-4 {{ $bgColor }} shadow-lg max-w-sm w-full"
     role="alert">
    <div class="flex justify-between items-start">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 {{ $textColor }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    @if($icon === 'check-circle')
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    @elseif($icon === 'x-circle')
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    @elseif($icon === 'exclamation')
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    @else
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    @endif
                </svg>
            </div>
            <div class="mr-3">
                <p class="text-sm font-medium {{ $textColor }}">{{ $message }}</p>
            </div>
        </div>
        @if($dismissible)
            <button wire:click="dismiss" class="mr-1 flex-shrink-0 {{ $textColor }} hover:{{ $textColor }} focus:outline-none">
                <span class="sr-only">بستن</span>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        @endif
    </div>
</div>
