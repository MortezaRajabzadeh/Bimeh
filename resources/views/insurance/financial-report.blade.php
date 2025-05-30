<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <!-- عنوان اصلی -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            {{ __('financial.titles.financial_report') }}
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">{{ __('financial.descriptions.report_overview') }}</p>
                    </div>
                    <div>
                        <!-- دکمه دانلود اکسل -->
                        <a href="{{ route('insurance.financial-report.export') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ __('financial.actions.export_excel') }}
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- کارت خلاصه مالی -->
            <div class="px-6 py-4">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 border border-blue-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="bg-blue-100 p-3 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                </svg>
                            </div>
                            <div class="mr-4">
                                <p class="text-sm font-medium text-gray-700">{{ __('financial.titles.account_balance') }}</p>
                                <p class="text-2xl font-bold text-gray-900">{{ number_format($balance) }} <span class="text-sm font-normal text-gray-600">ریال</span></p>
                            </div>
                        </div>
                        <div class="text-left">
                            <p class="text-xs text-gray-500">{{ __('financial.titles.financial_status') }}</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $balance > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $balance > 0 ? __('financial.statuses.positive') : __('financial.statuses.negative') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- جدول تراکنش‌ها -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        {{ __('financial.titles.transactions') }}
                    </h3>
                    <div class="bg-blue-100 px-3 py-1 rounded-full">
                        <span class="text-sm font-medium text-blue-800">
                            {{ __('financial.messages.total_transactions', ['count' => $transactionsPaginated->total()]) }}
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('financial.table_headers.transaction_description') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('financial.table_headers.date') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('financial.table_headers.amount') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('financial.table_headers.type') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($transactionsPaginated as $idx => $t)
                                <tbody x-data="{ open: false }" class="divide-y divide-gray-200">
                                    <tr class="hover:bg-gray-50 transition-colors duration-200 
                                        {{ $t['type'] === 'credit' ? 'border-r-4 border-green-400' : 'border-r-4 border-red-400' }}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                @if(in_array($t['title'], [__('financial.transaction_types.premium_payment'), __('financial.transaction_types.premium_import')]))
                                                    <button @click="open = !open"
                                                            type="button"
                                                            class="flex-shrink-0 ml-3 bg-gray-100 hover:bg-gray-200 rounded-full p-2 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200"
                                                            :class="open ? 'rotate-180' : ''">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                        </svg>
                                                    </button>
                                                @endif
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">{{ $t['title'] }}</div>
                                                    <div class="text-xs text-gray-500">{{ __('financial.messages.transaction_number', ['number' => $transactionsPaginated->firstItem() + $idx]) }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ $t['date_formatted'] }}</div>
                                            <div class="text-xs text-gray-500">{{ jdate($t['date'])->format('H:i') }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium {{ $t['type'] === 'credit' ? 'text-green-700' : 'text-red-700' }}">
                                                {{ $t['type'] === 'credit' ? '+' : '-' }}
                                                {{ number_format($t['amount']) }} ریال
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $t['type'] === 'credit' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                <span class="w-1.5 h-1.5 ml-1 rounded-full {{ $t['type'] === 'credit' ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                                {{ $t['type'] === 'credit' ? __('financial.transaction_types.credit') : __('financial.transaction_types.debit') }}
                                            </span>
                                        </td>
                                    </tr>

                                    @if(in_array($t['title'], [__('financial.transaction_types.premium_payment'), __('financial.transaction_types.premium_import')]))
                                        <tr x-show="open" x-transition:enter="transition ease-out duration-200" 
                                            x-transition:enter-start="opacity-0 transform scale-95" 
                                            x-transition:enter-end="opacity-100 transform scale-100" 
                                            x-cloak class="bg-blue-50">
                                            <td colspan="4" class="px-6 py-4">
                                                <div class="bg-white rounded-lg p-4 border border-blue-200">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <h4 class="text-sm font-medium text-gray-900 mb-2">{{ __('financial.messages.payment_details') }}</h4>
                                                            @if($t['family_count'] > 0)
                                                                <p class="text-sm text-gray-700 mb-3">
                                                                    @if($t['family_count'] === 1)
                                                                        {{ __('financial.descriptions.payment_single_family', ['amount' => number_format($t['amount'])]) }}
                                                                    @else
                                                                        {{ __('financial.descriptions.payment_for_families', ['count' => $t['family_count'], 'amount' => number_format($t['amount'])]) }}
                                                                    @endif
                                                                </p>
                                                                <div class="flex items-center space-x-4 space-x-reverse mb-3">
                                                                    <span class="text-sm text-gray-600">
                                                                        <strong>{{ $t['family_count'] }}</strong> خانواده
                                                                    </span>
                                                                    <span class="text-sm text-gray-600">
                                                                        <strong>{{ $t['members_count'] }}</strong> نفر
                                                                    </span>
                                                                </div>
                                                                <div class="flex space-x-2 space-x-reverse">
                                                                    @if($t['title'] === __('financial.transaction_types.premium_import'))
                                                                        @php
                                                                            $allCodes = array_merge($t['created_family_codes'] ?? [], $t['updated_family_codes'] ?? []);
                                                                        @endphp
                                                                        @if(count($allCodes) > 0)
                                                                            <a href="{{ route('insurance.families.list', ['codes' => implode(',', $allCodes)]) }}" 
                                                                               class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200" 
                                                                               target="_blank">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                                </svg>
                                                                                {{ __('financial.actions.view_families') }}
                                                                            </a>
                                                                        @endif
                                                                    @elseif($t['payment_id'])
                                                                        <a href="{{ route('insurance.financial-report.payment-details', $t['payment_id']) }}?type={{ $t['title'] === __('financial.transaction_types.premium_payment') ? 'allocation' : 'payment' }}" 
                                                                           class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                            </svg>
                                                                            {{ __('financial.actions.view_details') }}
                                                                        </a>
                                                                    @endif
                                                                </div>
                                                            @else
                                                                <p class="text-sm text-gray-700">{{ __('financial.descriptions.import_payment') }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="text-gray-500 text-sm">{{ __('financial.messages.no_transactions') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination تراکنش‌ها -->
            @if($transactionsPaginated->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex flex-wrap items-center justify-between">
                        <!-- تعداد نمایش - سمت راست -->
                        <div class="flex items-center order-1 mr-auto">
                            <span class="text-sm text-gray-600 ml-2">{{ __('financial.messages.per_page') }}</span>
                            <div class="relative">
                                <form method="GET" action="{{ request()->url() }}" class="inline">
                                    @foreach(request()->except(['per_page', 'page']) as $key => $value)
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endforeach
                                    <select name="per_page" onchange="this.form.submit()" class="h-9 w-20 border border-gray-300 rounded-md pr-8 pl-3 py-1 text-sm bg-white shadow-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500 transition-colors duration-200 text-center appearance-none" style="-webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: none;">
                                        <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10</option>
                                        <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                                        <option value="20" {{ request('per_page', 15) == 20 ? 'selected' : '' }}>20</option>
                                        <option value="30" {{ request('per_page', 15) == 30 ? 'selected' : '' }}>30</option>
                                        <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                                    </select>
                                </form>
                                <!-- آیکون dropdown -->
                                <div class="absolute inset-y-0 right-2 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- شماره صفحات - وسط (چپ به راست) -->
                        <div class="flex items-center justify-center order-2 flex-grow mx-4" dir="ltr">
                            @if(!$transactionsPaginated->onFirstPage())
                                <a href="{{ $transactionsPaginated->previousPageUrl() }}" class="text-green-600 hover:bg-green-50 cursor-pointer bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm text-lg font-bold">
                                    ‹
                                </a>
                            @else
                                <span class="text-gray-400 opacity-50 cursor-not-allowed bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm text-lg font-bold">
                                    ‹
                                </span>
                            @endif
                            
                            <div class="flex h-9 border border-gray-300 rounded-md overflow-hidden shadow-sm divide-x divide-gray-300 mx-1">
                                @php
                                    $start = max($transactionsPaginated->currentPage() - 2, 1);
                                    $end = min($start + 4, $transactionsPaginated->lastPage());
                                    if ($end - $start < 4 && $start > 1) {
                                        $start = max(1, $end - 4);
                                    }
                                @endphp
                                
                                @if($start > 1)
                                    <a href="{{ $transactionsPaginated->url(1) }}" class="bg-white text-gray-600 hover:bg-green-50 hover:text-green-700 h-full px-3 inline-flex items-center justify-center text-sm transition-colors duration-200">1</a>
                                    @if($start > 2)
                                        <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                                    @endif
                                @endif
                                
                                @for($i = $start; $i <= $end; $i++)
                                    @if($transactionsPaginated->currentPage() == $i)
                                        <span class="bg-green-100 text-green-800 font-medium h-full px-3 inline-flex items-center justify-center text-sm">
                                            {{ $i }}
                                        </span>
                                    @else
                                        <a href="{{ $transactionsPaginated->url($i) }}" class="bg-white text-gray-600 hover:bg-green-50 hover:text-green-700 h-full px-3 inline-flex items-center justify-center text-sm transition-colors duration-200">
                                            {{ $i }}
                                        </a>
                                    @endif
                                @endfor
                                
                                @if($end < $transactionsPaginated->lastPage())
                                    @if($end < $transactionsPaginated->lastPage() - 1)
                                        <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                                    @endif
                                    <a href="{{ $transactionsPaginated->url($transactionsPaginated->lastPage()) }}" class="bg-white text-gray-600 hover:bg-green-50 hover:text-green-700 h-full px-3 inline-flex items-center justify-center text-sm transition-colors duration-200">{{ $transactionsPaginated->lastPage() }}</a>
                                @endif
                            </div>
                            
                            @if($transactionsPaginated->hasMorePages())
                                <a href="{{ $transactionsPaginated->nextPageUrl() }}" class="text-green-600 hover:bg-green-50 cursor-pointer bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm text-lg font-bold">
                                    ›
                                </a>
                            @else
                                <span class="text-gray-400 opacity-50 cursor-not-allowed bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm text-lg font-bold">
                                    ›
                                </span>
                            @endif
                        </div>

                        <!-- شمارنده - سمت چپ -->
                        <div class="text-sm text-gray-600 order-3 ml-auto">
                            {{ __('financial.messages.showing_results', [
                                'from' => $transactionsPaginated->firstItem(),
                                'to' => $transactionsPaginated->lastItem(),
                                'total' => $transactionsPaginated->total()
                            ]) }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- گزارش ایمپورت‌های اکسل -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ __('financial.titles.import_reports') }}
                    </h3>
                    <div class="bg-green-100 px-3 py-1 rounded-full">
                        <span class="text-sm font-medium text-green-800">
                            {{ __('financial.messages.total_amount', ['amount' => number_format($totalAmount)]) }}
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('financial.table_headers.date') }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('financial.table_headers.user') }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('financial.table_headers.file_name') }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('financial.table_headers.total_rows') }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('financial.table_headers.new') }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('financial.table_headers.updated') }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('financial.table_headers.unchanged') }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('financial.table_headers.errors') }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('financial.table_headers.amount') }}</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('financial.table_headers.families') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($logs as $log)
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ jdate($log->created_at)->format('Y/m/d') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $log->user->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 font-medium">{{ $log->file_name }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ $log->total_rows }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ $log->created_count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $log->updated_count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                            {{ $log->skipped_count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            {{ $log->error_count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ number_format($log->total_insurance_amount) }} ریال
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-500">
                                        @if($log->family_codes && is_array($log->family_codes))
                                            <div class="max-w-xs overflow-hidden">
                                                <span class="text-xs">{{ implode(', ', array_slice($log->family_codes, 0, 3)) }}</span>
                                                @if(count($log->family_codes) > 3)
                                                    <span class="text-xs text-gray-400">... و {{ count($log->family_codes) - 3 }} مورد دیگر</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="text-gray-500 text-sm">{{ __('financial.messages.no_import_reports') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination ایمپورت لاگ‌ها -->
            @if($logs->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex flex-wrap items-center justify-between">
                        <!-- شماره صفحات - وسط (چپ به راست) -->
                        <div class="flex items-center justify-center flex-grow" dir="ltr">
                            @if(!$logs->onFirstPage())
                                <a href="{{ $logs->previousPageUrl() }}" class="text-green-600 hover:bg-green-50 cursor-pointer bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm text-lg font-bold">
                                    ‹
                                </a>
                            @else
                                <span class="text-gray-400 opacity-50 cursor-not-allowed bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm text-lg font-bold">
                                    ‹
                                </span>
                            @endif
                            
                            <div class="flex h-9 border border-gray-300 rounded-md overflow-hidden shadow-sm divide-x divide-gray-300 mx-1">
                                @php
                                    $start = max($logs->currentPage() - 2, 1);
                                    $end = min($start + 4, $logs->lastPage());
                                    if ($end - $start < 4 && $start > 1) {
                                        $start = max(1, $end - 4);
                                    }
                                @endphp
                                
                                @if($start > 1)
                                    <a href="{{ $logs->url(1) }}" class="bg-white text-gray-600 hover:bg-green-50 hover:text-green-700 h-full px-3 inline-flex items-center justify-center text-sm transition-colors duration-200">1</a>
                                    @if($start > 2)
                                        <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                                    @endif
                                @endif
                                
                                @for($i = $start; $i <= $end; $i++)
                                    @if($logs->currentPage() == $i)
                                        <span class="bg-green-100 text-green-800 font-medium h-full px-3 inline-flex items-center justify-center text-sm">
                                            {{ $i }}
                                        </span>
                                    @else
                                        <a href="{{ $logs->url($i) }}" class="bg-white text-gray-600 hover:bg-green-50 hover:text-green-700 h-full px-3 inline-flex items-center justify-center text-sm transition-colors duration-200">
                                            {{ $i }}
                                        </a>
                                    @endif
                                @endfor
                                
                                @if($end < $logs->lastPage())
                                    @if($end < $logs->lastPage() - 1)
                                        <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                                    @endif
                                    <a href="{{ $logs->url($logs->lastPage()) }}" class="bg-white text-gray-600 hover:bg-green-50 hover:text-green-700 h-full px-3 inline-flex items-center justify-center text-sm transition-colors duration-200">{{ $logs->lastPage() }}</a>
                                @endif
                            </div>
                            
                            @if($logs->hasMorePages())
                                <a href="{{ $logs->nextPageUrl() }}" class="text-green-600 hover:bg-green-50 cursor-pointer bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm text-lg font-bold">
                                    ›
                                </a>
                            @else
                                <span class="text-gray-400 opacity-50 cursor-not-allowed bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm text-lg font-bold">
                                    ›
                                </span>
                            @endif
                        </div>

                        <!-- شمارنده - سمت چپ -->
                        <div class="text-sm text-gray-600">
                            {{ __('financial.messages.showing_reports', [
                                'from' => $logs->firstItem(),
                                'to' => $logs->lastItem(),
                                'total' => $logs->total()
                            ]) }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout> 