@props([
    'paginator',
    'showPerPage' => true,
    'perPageOptions' => [10, 15, 20, 30, 50],
    'showCounter' => true,
    'maxLinks' => 5,
    'counterPosition' => 'left',
    'perPagePosition' => 'right'
])

@if($paginator->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        <div class="flex flex-wrap items-center justify-between">
            
            {{-- Per Page Selector --}}
            @if($showPerPage)
                <div class="flex items-center order-1 {{ $perPagePosition === 'left' ? 'mr-auto' : 'ml-auto' }}">
                    <span class="text-sm text-gray-600 ml-2">{{ __('financial.messages.per_page') }}</span>
                    <div class="relative">
                        <form method="GET" action="{{ request()->url() }}" class="inline">
                            @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <select name="per_page" onchange="this.form.submit()" 
                                    class="h-9 w-20 border border-gray-300 rounded-md pr-8 pl-3 py-1 text-sm bg-white shadow-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 transition-colors duration-200 text-center appearance-none" 
                                    style="-webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: none;">
                                @foreach($perPageOptions as $option)
                                    <option value="{{ $option }}" {{ request('per_page', 15) == $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </form>
                        {{-- Dropdown Arrow --}}
                        <div class="absolute inset-y-0 right-2 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Page Numbers Navigation --}}
            <div class="flex items-center justify-center order-2 flex-grow mx-4" dir="ltr">
                {{-- Previous Button --}}
                @if(!$paginator->onFirstPage())
                    <a href="{{ $paginator->previousPageUrl() }}" 
                       class="text-green-600 hover:bg-green-50 cursor-pointer bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm text-lg font-bold">
                        ‹
                    </a>
                @else
                    <span class="text-gray-400 opacity-50 cursor-not-allowed bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm text-lg font-bold">
                        ‹
                    </span>
                @endif

                <div class="flex h-9 border border-gray-300 rounded-md overflow-hidden shadow-sm divide-x divide-gray-300 mx-1">
                    @php
                        $start = max($paginator->currentPage() - 2, 1);
                        $end = min($start + 4, $paginator->lastPage());
                        if ($end - $start < 4 && $start > 1) {
                            $start = max(1, $end - 4);
                        }
                    @endphp

                    {{-- First page if not in range --}}
                    @if($start > 1)
                        <a href="{{ $paginator->url(1) }}" 
                           class="bg-white text-gray-600 hover:bg-green-50 hover:text-green-700 h-full px-3 inline-flex items-center justify-center text-sm transition-colors duration-200">1</a>
                        @if($start > 2)
                            <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                        @endif
                    @endif

                    {{-- Page number links --}}
                    @for($i = $start; $i <= $end; $i++)
                        @if($paginator->currentPage() == $i)
                            <span class="bg-green-100 text-green-800 font-medium h-full px-3 inline-flex items-center justify-center text-sm">
                                {{ $i }}
                            </span>
                        @else
                            <a href="{{ $paginator->url($i) }}" 
                               class="bg-white text-gray-600 hover:bg-green-50 hover:text-green-700 h-full px-3 inline-flex items-center justify-center text-sm transition-colors duration-200">
                                {{ $i }}
                            </a>
                        @endif
                    @endfor

                    {{-- Last page if not in range --}}
                    @if($end < $paginator->lastPage())
                        @if($end < $paginator->lastPage() - 1)
                            <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                        @endif
                        <a href="{{ $paginator->url($paginator->lastPage()) }}" 
                           class="bg-white text-gray-600 hover:bg-green-50 hover:text-green-700 h-full px-3 inline-flex items-center justify-center text-sm transition-colors duration-200">{{ $paginator->lastPage() }}</a>
                    @endif
                </div>

                {{-- Next Button --}}
                @if($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" 
                       class="text-green-600 hover:bg-green-50 cursor-pointer bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm text-lg font-bold">
                        ›
                    </a>
                @else
                    <span class="text-gray-400 opacity-50 cursor-not-allowed bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm text-lg font-bold">
                        ›
                    </span>
                @endif
            </div>

            {{-- Counter --}}
            @if($showCounter)
                <div class="text-sm text-gray-600 order-3 {{ $counterPosition === 'left' ? 'ml-auto' : 'mr-auto' }}">
                    {{ __('financial.messages.showing_results', [
                        'from' => $paginator->firstItem(),
                        'to' => $paginator->lastItem(),
                        'total' => $paginator->total()
                    ]) }}
                </div>
            @endif
        </div>
    </div>
@endif