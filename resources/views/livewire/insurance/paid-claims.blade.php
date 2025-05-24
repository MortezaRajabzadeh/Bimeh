<div class="container mx-auto px-4 py-6">
    <h2 class="text-2xl font-bold text-center mb-8 text-gray-800">خسارات پرداخت شده</h2>

    @if (session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-center">
            {{ session('success') }}
        </div>
    @endif

    @if($addMode)
        <div class="mb-8 bg-white border border-gray-200 rounded-2xl shadow-lg p-6">
            <h3 class="text-lg font-bold mb-4 text-gray-700">افزودن خسارت جدید</h3>
            <form wire:submit.prevent="addClaim" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block mb-1 text-gray-600">خانواده</label>
                    <input type="text" wire:model.debounce.300ms="familySearch" placeholder="جستجو خانواده..." class="border rounded px-3 py-2 w-full mb-2" />
                    <x-select-with-icon wire:model.defer="family_id" name="family_id">
                        <option value="">انتخاب کنید...</option>
                        @foreach($families->filter(fn($f) => empty($familySearch) || str_contains($f->family_code, $familySearch) || (isset($f->head) && str_contains($f->head->full_name ?? '', $familySearch))) as $family)
                            <option value="{{ $family->id }}">{{ $family->family_code }} - {{ $family->head->full_name ?? '---' }} - {{ $family->head->mobile ?? '' }}</option>
                        @endforeach
                    </x-select-with-icon>
                    @error('family_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    @if($selectedFamily)
                        <div class="text-xs text-gray-500 mt-1">کد: {{ $selectedFamily->family_code ?? '-' }} | سرپرست: {{ $selectedFamily->head->full_name ?? '-' }} | موبایل: {{ $selectedFamily->head->mobile ?? '-' }}</div>
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
        <div class="flex justify-end mb-4">
            <button wire:click="showAddForm" class="bg-gradient-to-l from-green-500 to-green-700 hover:from-green-600 hover:to-green-800 text-white font-bold py-2 px-6 rounded-xl flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                افزودن خسارت جدید
            </button>
        </div>
    @endif

    <div class="overflow-x-auto rounded-2xl shadow-lg bg-white border border-gray-100">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr class="text-center text-gray-700 text-base">
                    <th class="py-3 px-4">زمان ثبت</th>
                    <th class="py-3 px-4">تاریخ صدور بیمه نامه</th>
                    <th class="py-3 px-4">تاریخ پرداخت خسارت</th>
                    <th class="py-3 px-4">مبلغ</th>
                    <th class="py-3 px-4">شرح</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($claims as $claim)
                    <tr class="text-center hover:bg-green-50 transition">
                        <td class="py-3 px-4 text-gray-700">
                            {{ $claim->created_at ? jdate($claim->created_at)->format('Y/m/d') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-gray-700">
                            {{ $claim->issue_date ?? '-' }}
                        </td>
                        <td class="py-3 px-4 text-gray-700">
                            {{ $claim->paid_at ?? '-' }}
                        </td>
                        <td class="py-3 px-4 font-bold text-green-700">
                            {{ number_format($claim->amount) }} <span class="text-xs font-normal">تومان</span>
                        </td>
                        <td class="py-3 px-4 text-gray-700">
                            {{ $claim->description ?? '-' }}
                        </td>
                        <td class="py-3 px-4 flex gap-2 justify-center">
                            <button wire:click="editClaim({{ $claim->id }})" class="text-blue-600 hover:text-blue-800 transition" title="ویرایش">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M16.732 3.732a2.121 2.121 0 1 1 3 3L7.5 19.5H3v-4.5L16.732 3.732z"/>
                                </svg>
                            </button>
                            <button wire:click="deleteClaim({{ $claim->id }})" class="text-red-600 hover:text-red-800 transition" title="حذف">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </td>
                    </tr>
                    @if($editId === $claim->id)
                        <tr>
                            <td colspan="6" class="bg-blue-50">
                                <form wire:submit.prevent="updateClaim" class="grid grid-cols-1 md:grid-cols-5 gap-4 p-4">
                                    <div>
                                        <label class="block mb-1 text-gray-600">خانواده</label>
                                        <input type="text" wire:model.debounce.300ms="familySearch" placeholder="جستجو خانواده..." class="border rounded px-3 py-2 w-full mb-2" />
                                        <x-select-with-icon wire:model.defer="family_id" name="family_id">
                                            <option value="">انتخاب کنید...</option>
                                            @foreach($families->filter(fn($f) => empty($familySearch) || str_contains($f->family_code, $familySearch) || (isset($f->head) && str_contains($f->head->full_name ?? '', $familySearch))) as $family)
                                                <option value="{{ $family->id }}">{{ $family->family_code }} - {{ $family->head->full_name ?? '---' }} - {{ $family->head->mobile ?? '' }}</option>
                                            @endforeach
                                        </x-select-with-icon>
                                        @error('family_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        @if($selectedFamily)
                                            <div class="text-xs text-gray-500 mt-1">کد: {{ $selectedFamily->family_code ?? '-' }} | سرپرست: {{ $selectedFamily->head->full_name ?? '-' }} | موبایل: {{ $selectedFamily->head->mobile ?? '-' }}</div>
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
                                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-xl flex items-center gap-2">
                                            ذخیره ویرایش
                                        </button>
                                        <button type="button" wire:click="cancelEdit" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded-xl flex items-center gap-2">
                                            انصراف
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-gray-400">هیچ خسارتی ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
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