<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex-1 min-w-0">
                    <h2 class="text-3xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        مدیریت خسارات پرداخت شده
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        مدیریت و پیگیری خسارات بیمه‌ای پرداخت شده به خانواده‌ها
                    </p>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        @if (session()->has('success'))
            <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="mr-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6 rounded-md bg-red-50 p-4 border border-red-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="mr-3">
                        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">

            @if($addMode)
                <div class="mb-8 bg-gray-50 border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-bold mb-4 text-gray-700">افزودن خسارت جدید</h3>
            <form wire:submit.prevent="addClaim" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block mb-1 text-gray-600">خانواده</label>
                    <input type="text" wire:model.debounce.300ms="familySearch" placeholder="جستجو خانواده..." class="border rounded px-3 py-2 w-full mb-2" />
                    <x-select-with-icon wire:model.defer="family_id" name="family_id">
                        <option value="">انتخاب کنید...</option>
                        @foreach($families->filter(fn($f) => empty($familySearch) || str_contains($f->family_code, $familySearch) || (isset($f->head) && str_contains(($f->head->first_name ?? '') . ' ' . ($f->head->last_name ?? ''), $familySearch))) as $family)
                            <option value="{{ $family->id }}">{{ $family->family_code }} - {{ ($family->head->first_name ?? '') . ' ' . ($family->head->last_name ?? '') }} - {{ $family->head->mobile ?? '' }}</option>
                        @endforeach
                    </x-select-with-icon>
                    @error('family_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    @if($selectedFamily)
                        <div class="text-xs text-gray-500 mt-1">کد: {{ $selectedFamily->family_code ?? '-' }} | سرپرست: {{ $selectedFamily->head->first_name ?? '' }} {{ $selectedFamily->head->last_name ?? '' }} | موبایل: {{ $selectedFamily->head->mobile ?? '-' }}</div>
                    @endif
                </div>
                <div>
                    <label class="block mb-1 text-gray-600">تراکنش</label>
                    <input type="text" wire:model.debounce.300ms="transactionSearch" placeholder="جستجو تراکنش..." class="border rounded px-3 py-2 w-full mb-2" />
                    <x-select-with-icon wire:model.defer="funding_transaction_id" name="funding_transaction_id">
                        <option value="">انتخاب کنید...</option>
                        @foreach($transactions->filter(fn($t) => empty($transactionSearch) || str_contains($t->reference_no ?? '', $transactionSearch) || str_contains($t->description ?? '', $transactionSearch)) as $tx)
                            <option value="{{ $tx->id }}">{{ $tx->reference_no ?? $tx->id }} - {{ number_format($tx->amount) }} تومان - {{ $tx->description }}</option>
                        @endforeach
                    </x-select-with-icon>
                    @error('funding_transaction_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    @if($selectedTransaction)
                        <div class="text-xs text-gray-500 mt-1">کد: {{ $selectedTransaction->reference_no ?? '-' }} | مبلغ: {{ number_format($selectedTransaction->amount ?? 0) }} | توضیح: {{ $selectedTransaction->description ?? '-' }}</div>
                    @endif
                </div>
                <div>
                    <label class="block mb-1 text-gray-600">زمان ثبت</label>
                    <input type="text" value="{{ jdate(now())->format('Y/m/d') }}" class="border border-gray-300 rounded-lg px-2 py-1 w-full bg-gray-100" disabled />
                </div>
                <div>
                    <label class="block mb-1 text-gray-600">تاریخ صدور بیمه نامه</label>
                    <input type="text" wire:model.defer="issue_date" class="border border-gray-300 rounded-lg px-2 py-1 w-full jalali-datepicker" autocomplete="off" data-jdp />
                    @error('issue_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block mb-1 text-gray-600">تاریخ پرداخت خسارت</label>
                    <input type="text" wire:model.defer="paid_at" class="border border-gray-300 rounded-lg px-2 py-1 w-full jalali-datepicker" autocomplete="off" data-jdp />
                    @error('paid_at') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block mb-1 text-gray-600">مبلغ (تومان) <span class="text-red-500">*</span></label>
                    <input type="number" wire:model.defer="amount" class="border border-gray-300 rounded-lg px-2 py-1 w-full" />
                    @error('amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block mb-1 text-gray-600">شرح</label>
                    <input type="text" wire:model.defer="description" class="border border-gray-300 rounded-lg px-2 py-1 w-full" />
                    @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="md:col-span-5 flex gap-2 mt-4">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-xl flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        ثبت خسارت
                    </button>
                    <button type="button" wire:click="cancelAdd" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded-xl flex items-center gap-2">
                        انصراف
                    </button>
                </div>
            </form>
                </div>
            @else
                <!-- Action Buttons -->
                <div class="mb-6">
            <div class="sm:flex sm:items-center sm:justify-between">
                <div class="sm:flex-auto">
                    <p class="text-sm text-gray-700">
                        لیست تمامی خسارات پرداخت شده به خانواده‌ها
                    </p>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <a href="{{ route('insurance.claims-summary') }}" 
                           class="inline-flex items-center justify-center gap-x-2 rounded-lg bg-purple-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-purple-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-600 transition-colors min-w-[120px]">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                            </svg>
                            نمای تجمعی
                        </a>
                        
                        <button wire:click="showExcelUpload" type="button" 
                                class="inline-flex items-center justify-center gap-x-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition-colors min-w-[120px]">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            آپلود اکسل
                        </button>
                        
                        <button wire:click="showAddForm" type="button" 
                                class="inline-flex items-center justify-center gap-x-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-green-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 transition-colors min-w-[120px]">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            ثبت خسارت جدید
                        </button>
                    </div>
                </div>
            </div>
                </div>
            @endif

            <!-- Claims Table -->
            <div class="bg-gray-50 rounded-lg overflow-hidden border border-gray-200">
        <div class="overflow-hidden">
            <div class="overflow-x-auto max-w-full">
            <table class="w-full divide-y divide-gray-200 table-fixed">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="w-32 px-3 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            کد خانواده
                        </th>
                        <th scope="col" class="w-28 px-3 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            زمان ثبت
                        </th>
                        <th scope="col" class="w-28 px-3 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            تاریخ صدور
                        </th>
                        <th scope="col" class="w-28 px-3 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            تاریخ پرداخت
                        </th>
                        <th scope="col" class="w-24 px-3 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            مبلغ خسارت
                        </th>
                        <th scope="col" class="w-24 px-3 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            نوع بیمه
                        </th>
                        <th scope="col" class="w-32 px-3 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            شرح
                        </th>
                        <th scope="col" class="w-16 px-3 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            عملیات
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($claims as $claim)
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <!-- کد خانواده -->
                            <td class="px-3 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                @if($claim->family)
                                    <div class="text-center">
                                        <div class="text-sm font-medium text-gray-900">{{ $claim->family->family_code }}</div>
                                        @if($claim->family->head)
                                            <div class="text-xs text-gray-500">{{ $claim->family->head->first_name }} {{ $claim->family->head->last_name }}</div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                            
                            <!-- زمان ثبت -->
                            <td class="px-3 py-4 whitespace-nowrap text-center text-xs text-gray-500">
                                {{ $claim->created_at ? jdate($claim->created_at)->format('Y/m/d H:i') : '-' }}
                            </td>
                            
                            <!-- تاریخ صدور بیمه‌نامه -->
                            <td class="px-3 py-4 whitespace-nowrap text-center text-xs text-gray-500">
                                {{ $claim->issue_date ?? '-' }}
                            </td>
                            
                            <!-- تاریخ پرداخت -->
                            <td class="px-3 py-4 whitespace-nowrap text-center text-xs text-gray-500">
                                {{ $claim->paid_at ?? '-' }}
                            </td>
                            
                            <!-- مبلغ خسارت -->
                            <td class="px-3 py-4 whitespace-nowrap text-center">
                                <div class="text-xs font-medium text-gray-900">
                                    {{ number_format($claim->amount ?? 0) }}
                                    <span class="text-xs text-gray-500 font-normal block">تومان</span>
                                </div>
                            </td>
                            
                            <!-- نوع بیمه -->
                            <td class="px-3 py-4 whitespace-nowrap text-center">
                                @if($claim->insurance_type)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ Str::limit($claim->insurance_type, 10) }}
                                    </span>
                                @elseif($claim->transaction && $claim->transaction->description)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ Str::limit($claim->transaction->description, 10) }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        نامشخص
                                    </span>
                                @endif
                            </td>
                            
                            <!-- شرح -->
                            <td class="px-3 py-4 whitespace-nowrap text-center text-xs text-gray-500">
                                <div class="max-w-24 truncate mx-auto" title="{{ $claim->description ?? '-' }}">
                                    {{ $claim->description ?? '-' }}
                                </div>
                            </td>
                            
                            <!-- عملیات -->
                            <td class="px-3 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="editClaim({{ $claim->id }})" 
                                            class="text-indigo-600 hover:text-indigo-900 transition-colors p-1 rounded-md hover:bg-indigo-50" 
                                            title="ویرایش">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button wire:click="deleteClaim({{ $claim->id }})" 
                                            class="text-red-600 hover:text-red-900 transition-colors p-1 rounded-md hover:bg-red-50" 
                                            title="حذف">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @if($editId === $claim->id)
                            <tr class="bg-blue-50">
                                <td colspan="8" class="px-6 py-4">
                                    <div class="bg-white rounded-lg shadow-sm border border-blue-200 p-6">
                                        <h4 class="text-lg font-medium text-gray-900 mb-4">ویرایش خسارت</h4>
                                        <form wire:submit.prevent="updateClaim" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                            <div class="lg:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">خانواده</label>
                                                <input type="text" wire:model.debounce.300ms="familySearch" placeholder="جستجو خانواده..." class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 mb-2" />
                                                <x-select-with-icon wire:model.defer="family_id" name="family_id" class="w-full">
                                                    <option value="">انتخاب کنید...</option>
                                                    @foreach($families->filter(fn($f) => empty($familySearch) || str_contains($f->family_code, $familySearch) || (isset($f->head) && str_contains(($f->head->first_name ?? '') . ' ' . ($f->head->last_name ?? ''), $familySearch))) as $family)
                                                        <option value="{{ $family->id }}">{{ $family->family_code }} - {{ ($family->head->first_name ?? '') . ' ' . ($family->head->last_name ?? '') }}</option>
                                                    @endforeach
                                                </x-select-with-icon>
                                                @error('family_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">مبلغ (تومان)</label>
                                                <input type="number" wire:model.defer="amount" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
                                                @error('amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">تاریخ صدور</label>
                                                <input type="text" wire:model.defer="issue_date" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 jalali-datepicker" autocomplete="off" data-jdp />
                                                @error('issue_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">تاریخ پرداخت</label>
                                                <input type="text" wire:model.defer="paid_at" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 jalali-datepicker" autocomplete="off" data-jdp />
                                                @error('paid_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                            </div>
                                            
                                            <div class="lg:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">شرح</label>
                                                <input type="text" wire:model.defer="description" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
                                                @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                            </div>
                                            
                                            <div class="lg:col-span-4 flex justify-end gap-3 pt-4 border-t border-gray-200">
                                                <button type="button" wire:click="cancelEdit" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                    انصراف
                                                </button>
                                                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                    ذخیره تغییرات
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">هیچ خسارتی ثبت نشده</h3>
                                    <p class="mt-1 text-sm text-gray-500">برای شروع کار، اولین خسارت را ثبت کنید.</p>
                                    <div class="mt-6">
                                        <button wire:click="showAddForm" type="button" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            <svg class="-mr-1 mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                            </svg>
                                            ثبت خسارت جدید
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                @endforelse
            </tbody>
            </table>
            </div>
        </div>
        {{-- Pagination Section --}}
    @if($claims->hasPages())
        <div class="mt-6 border-t border-gray-200 pt-4" id="pagination-section">
            <div class="flex flex-wrap items-center justify-between">
                <!-- شمارنده - سمت راست -->
                <div class="text-sm text-gray-600 order-1 ml-auto">
                    نمایش {{ $claims->firstItem() ?? 0 }} تا {{ $claims->lastItem() ?? 0 }} از {{ $claims->total() ?? 0 }} خسارت
                </div>
                <!-- شماره صفحات - وسط -->
                <div class="flex items-center justify-center order-2 flex-grow mx-4">
                    <button type="button" wire:click="{{ !$claims->onFirstPage() ? 'previousPage' : '' }}"
                        class="{{ !$claims->onFirstPage() ? 'text-green-600 hover:bg-green-50 cursor-pointer' : 'text-gray-400 opacity-50 cursor-not-allowed' }} bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M7.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L10.586 10 7.293 6.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div class="flex h-9 border border-gray-300 rounded-md overflow-hidden shadow-sm divide-x divide-gray-300 mx-1">
                        @php
                            $start = max($claims->currentPage() - 2, 1);
                            $end = min($start + 4, $claims->lastPage());
                            if ($end - $start < 4 && $start > 1) {
                                $start = max(1, $end - 4);
                            }
                        @endphp
                        @if($start > 1)
                            <button type="button" wire:click="gotoPage(1)" class="bg-white text-gray-600 hover:bg-gray-50 h-full px-3 inline-flex items-center justify-center text-sm">1</button>
                            @if($start > 2)
                                <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                            @endif
                        @endif
                        @for($i = $end; $i >= $start; $i--)
                            <button type="button" wire:click="gotoPage({{ $i }})"
                                class="{{ $claims->currentPage() == $i ? 'bg-green-100 text-green-800 font-medium' : 'bg-white text-gray-600 hover:bg-gray-50' }} h-full px-3 inline-flex items-center justify-center text-sm">
                                {{ $i }}
                            </button>
                        @endfor
                        @if($end < $claims->lastPage())
                            @if($end < $claims->lastPage() - 1)
                                <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                            @endif
                            <button type="button" wire:click="gotoPage({{ $claims->lastPage() }})" class="bg-white text-gray-600 hover:bg-gray-50 h-full px-3 inline-flex items-center justify-center text-sm">{{ $claims->lastPage() }}</button>
                        @endif
                    </div>
                    <button type="button" wire:click="{{ $claims->hasMorePages() ? 'nextPage' : '' }}"
                        class="{{ $claims->hasMorePages() ? 'text-green-600 hover:bg-green-50 cursor-pointer' : 'text-gray-400 opacity-50 cursor-not-allowed' }} bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <!-- تعداد نمایش - سمت چپ -->
                <div class="flex items-center order-3 mr-auto">
                    <span class="text-sm text-gray-600 ml-2">تعداد نمایش:</span>

                    <x-select-with-icon wire:model="perPage" class="h-9 w-16 text-sm bg-white shadow-sm">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </x-select-with-icon>
                </div>
                </div>
            </div>
        @endif

        {{-- Excel Upload Modal --}}
    @if($showExcelUploadModal)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative" wire:click.stop>
                    <!-- هدر مودال -->
                    <div class="border-b border-gray-200 p-6 text-center relative">
                        <button wire:click="hideExcelUpload" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">آپلود فایل اکسل خسارات</h3>
                        <p class="text-sm text-gray-600">برای وارد کردن خسارات پرداخت شده به صورت دسته جمعی، فایل اکسل خود را آپلود نمایید.</p>
                    </div>

                    <!-- محتوای مودال -->
                    <div class="p-6">
                        @if($isUploading)
                            <!-- Loading State -->
                            <div class="text-center py-8">
                                <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-green-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="mt-4 text-gray-600">{{ $uploadProgress }}</p>
                            </div>
                        @else
                            <!-- منطقه Drag & Drop -->
                            <form wire:submit.prevent="importExcel">
                                <input type="file" wire:model="excelFile" accept=".xlsx,.xls" class="hidden" id="excel-upload-input">
                                <label for="excel-upload-input" class="block cursor-pointer">
                                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center mb-6 hover:border-green-400 transition-colors">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <p class="text-gray-600 mb-2 font-medium">فایل اکسل خود را در اینجا قرار دهید</p>
                                        <p class="text-xs text-gray-500">یا برای انتخاب فایل کلیک کنید</p>
                                    </div>
                                </label>

                                @if($excelFile)
                                    <div class="mb-4 text-green-700 text-sm font-bold flex items-center justify-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        فایل انتخاب شد: {{ $excelFile->getClientOriginalName() }}
                                    </div>
                                @endif

                                @error('excelFile')
                                    <div class="text-red-500 mt-2 text-sm text-center">{{ $message }}</div>
                                @enderror

                                <!-- دکمه‌های عملیات -->
                                <div class="flex gap-3">
                                    <button type="button" wire:click="hideExcelUpload" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 px-4 rounded-lg text-sm font-medium transition-colors flex items-center justify-center">
                                        انصراف
                                    </button>

                                    @if($excelFile)
                                        <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg text-sm font-medium transition-colors flex items-center justify-center">
                                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            آپلود فایل
                                        </button>
                                    @endif
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
            </div>
        @endif

    </div>
</div>

@push('scripts')
<script src="/vendor/jalalidatepicker/jalalidatepicker.min.js"></script>
<script>
    document.addEventListener('livewire:load', function () {
        jalaliDatepicker.startWatch({
            minDate: '1390/01/01',
            maxDate: '1450/12/29',
            autoClose: true,
            format: 'YYYY/MM/DD',
            theme: 'green',
        });
    });
    document.addEventListener('DOMContentLoaded', function () {
        jalaliDatepicker.startWatch();
    });
    window.addEventListener('refreshJalali', function () {
        jalaliDatepicker.startWatch();
    });
</script>
@endpush 