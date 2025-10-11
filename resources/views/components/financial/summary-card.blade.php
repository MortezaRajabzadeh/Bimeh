@props([
    'title' => '',
    'value' => null,
    'valueUnit' => '',
    'description' => null,
    'icon' => 'currency',
    'iconColor' => 'blue',
    'gradientFrom' => 'blue-50',
    'gradientTo' => 'indigo-50',
    'borderColor' => 'blue-200',
    'status' => null,
    'statusType' => null
])

<div {{ $attributes->merge(['class' => "px-6 py-4 border-t border-gray-200"]) }}>
    <div class="bg-gradient-to-r from-{{ $gradientFrom }} to-{{ $gradientTo }} rounded-lg p-4 border border-{{ $borderColor }}">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-{{ $iconColor }}-100 p-3 rounded-full">
                    @if($icon === 'currency')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-{{ $iconColor }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                    @elseif($icon === 'info')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-{{ $iconColor }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-{{ $iconColor }}-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    @endif
                </div>
                <div class="mr-4">
                    <p class="text-sm font-medium text-gray-700">{{ $title }}</p>
                    @if($value !== null)
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $value }}
                            @if($valueUnit)
                                <span class="text-sm font-normal text-gray-600">{{ $valueUnit }}</span>
                            @endif
                        </p>
                    @endif
                    @if($description)
                        <p class="text-sm text-gray-700 mb-3">{{ $description }}</p>
                    @endif
                </div>
            </div>
            
            @if($status)
                <div class="text-left">
                    <p class="text-xs text-gray-500">{{ __('financial.titles.financial_status') }}</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        {{ $statusType === 'positive' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $status }}
                    </span>
                </div>
            @endif
        </div>
        
        {{-- Default slot for custom content --}}
        {{ $slot }}
        
        {{-- Actions slot --}}
        @isset($actions)
            <div class="mt-3">
                {{ $actions }}
            </div>
        @endisset
        
        {{-- Footer slot --}}
        @isset($footer)
            <div class="mt-3 pt-3 border-t border-gray-200">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>