<div class="container mx-auto px-6 py-6 max-w-7xl">
    <div class="max-w-5xl mx-auto mb-8">
        <h2 class="text-2xl font-extrabold text-gray-800 mb-6 border-b pb-2">افزودن بودجه جدید</h2>
        @if (session()->has('success'))
            <div class="bg-green-100 text-green-800 rounded px-4 py-2 mb-4">{{ session('success') }}</div>
        @endif
        <form wire:submit.prevent="addTransaction" wire:key="add-transaction-form-{{ $formKey }}" class="bg-white rounded-xl shadow p-6 grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <div class="w-full">
                <label for="source_id" class="block mb-1 font-bold">منبع بودجه</label>
                <div class="rtl-select-wrapper">
                    <select id="source_id" wire:model="source_id"
                        class="rtl-select block w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">انتخاب منبع</option>
                        @foreach($sources as $source)
                            <option value="{{ $source->id }}">{{ $source->name }} ({{ $typeLabels[$source->type] ?? $source->type }})</option>
                        @endforeach
                    </select>
                </div>
                @error('source_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block mb-1 font-bold">مبلغ (تومان)</label>
                <input type="number" wire:model="amount" class="border rounded px-3 py-2 w-full" min="1000" />
                @error('amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block mb-1 font-bold">توضیحات</label>
                <input type="text" wire:model="description" class="border rounded px-3 py-2 w-full" />
                @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block mb-1 font-bold">شماره پیگیری (اختیاری)</label>
                <input type="text" wire:model="reference_no" class="border rounded px-3 py-2 w-full" />
                @error('reference_no') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg text-base flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    ثبت بودجه
                </button>
            </div>
        </form>
    </div>

    <div class="max-w-5xl mx-auto mb-8">
        <h3 class="text-xl font-extrabold text-gray-800 mb-6 border-b pb-2">مدیریت منابع بودجه</h3>
        <div class="bg-white rounded-xl shadow p-6 mb-8">
            <form wire:submit.prevent="addSource" class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-6 items-end">
                <input type="text" wire:model="source_name" placeholder="نام منبع" class="border rounded px-3 py-2 w-full text-right" />
                <div class="w-full">
                    <label for="source_type" class="block mb-1 font-bold">نوع منبع</label>
                    <div class="rtl-select-wrapper">
                        <select id="source_type" wire:model="source_type"
                            class="rtl-select block w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="charity">سازمان</option>
                            <option value="bank">بانک</option>
                            <option value="person">شخص حقیقی</option>
                            <option value="government">دولت</option>
                            <option value="other">سایر</option>
                        </select>
                    </div>
                </div>
                <input type="text" wire:model="source_description" placeholder="توضیحات" class="border rounded px-3 py-2 w-full text-right" />
                <button type="submit" class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded text-sm min-w-[120px] transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    افزودن منبع
                </button>
            </form>
            <table class="min-w-full bg-white rounded-xl shadow-sm border border-gray-100 mb-2">
                <thead class="bg-gray-50">
                    <tr class="text-center text-gray-700">
                        <th class="py-2 px-4">نام</th>
                        <th class="py-2 px-4">نوع</th>
                        <th class="py-2 px-4">توضیحات</th>
                        <th class="py-2 px-4">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $typeLabels = [
                            'charity' => 'سازمان',
                            'bank' => 'بانک',
                            'person' => 'شخص حقیقی',
                            'government' => 'دولت',
                            'other' => 'سایر',
                        ];
                    @endphp
                    @foreach($sources as $src)
                        <tr class="text-center hover:bg-blue-50 transition">
                            <td class="py-2 px-4">@if($showSourceEditModal && $source_edit_id == $src->id)
                                <input type="text" wire:model.defer="source_edit_name" class="border rounded px-2 py-1 w-full" />
                            @else
                                {{ $src->name }}
                            @endif</td>
                            <td class="py-2 px-4">@if($showSourceEditModal && $source_edit_id == $src->id)
                                {{ $typeLabels[$src->type] ?? $src->type }}
                            @else
                                {{ $typeLabels[$src->type] ?? $src->type }}
                            @endif</td>
                            <td class="py-2 px-4">@if($showSourceEditModal && $source_edit_id == $src->id)
                                <input type="text" wire:model.defer="source_edit_description" class="border rounded px-2 py-1 w-full" />
                            @else
                                {{ $src->description }}
                            @endif</td>
                            <td class="py-2 px-4 flex gap-2 justify-center">
                                @if($showSourceEditModal && $source_edit_id == $src->id)
                                    <button wire:click.prevent="updateSource" class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded text-sm">ذخیره</button>
                                    <button wire:click.prevent="$set('showSourceEditModal', false)" class="bg-gray-400 hover:bg-gray-500 text-white px-3 py-2 rounded text-sm">انصراف</button>
                                @else
                                    <button wire:click.prevent="showEditSource({{ $src->id }})" class="text-orange-500 hover:text-orange-700" title="ویرایش">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.1 2.1 0 1 1 2.97 2.97L7.5 19.789l-4 1 1-4 12.362-12.302z" /></svg>
                                    </button>
                                    <button wire:click.prevent="deleteSource({{ $src->id }})" class="text-red-500 hover:text-red-700" title="حذف">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="max-w-5xl mx-auto">
        <h3 class="text-xl font-extrabold text-gray-800 mb-6 border-b pb-2">آخرین تراکنش‌های بودجه</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-xl shadow-sm border border-gray-100">
                <thead class="bg-gray-50">
                    <tr class="text-center text-gray-700">
                        <th class="py-2 px-4">منبع</th>
                        <th class="py-2 px-4">مبلغ</th>
                        <th class="py-2 px-4">توضیحات</th>
                        <th class="py-2 px-4">شماره پیگیری</th>
                        <th class="py-2 px-4">تاریخ ثبت</th>
                        <th class="py-2 px-4">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                        <tr class="text-center hover:bg-green-50 transition">
                            <td class="py-2 px-4">{{ $trx->source->name ?? '-' }}</td>
                            <td class="py-2 px-4 font-bold text-green-700">{{ number_format($trx->amount) }}</td>
                            <td class="py-2 px-4">{{ $trx->description }}</td>
                            <td class="py-2 px-4">{{ $trx->reference_no }}</td>
                            <td class="py-2 px-4">{{ jdate($trx->created_at)->format('Y/m/d H:i') }}</td>
                            <td class="py-2 px-4 flex gap-2 justify-center">
                                <button wire:click.prevent="showEditTransaction({{ $trx->id }})" class="text-orange-500 hover:text-orange-700" title="ویرایش">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.1 2.1 0 1 1 2.97 2.97L7.5 19.789l-4 1 1-4 12.362-12.302z" /></svg>
                                </button>
                                <button wire:click.prevent="deleteTransaction({{ $trx->id }})" class="text-red-500 hover:text-red-700" title="حذف">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </td>
                        </tr>
                        @if($showEditModal && $edit_id == $trx->id)
                            <tr class="bg-gray-50">
                                <td colspan="6">
                                    <form wire:submit.prevent="updateTransaction" class="flex flex-col md:flex-row gap-2 items-end p-2">
                                        <div class="rtl-select-wrapper w-full md:w-1/4">
                                            <select wire:model="edit_source_id" class="rtl-select border rounded px-2 py-1 w-full">
                                                @foreach($sources as $source)
                                                    <option value="{{ $source->id }}">{{ $source->name }} ({{ $source->type }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <input type="number" wire:model="edit_amount" class="border rounded px-2 py-1 w-full md:w-1/4" min="1000" />
                                        <input type="text" wire:model="edit_description" class="border rounded px-2 py-1 w-full md:w-1/4" />
                                        <input type="text" wire:model="edit_reference_no" class="border rounded px-2 py-1 w-full md:w-1/4" />
                                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">ذخیره</button>
                                        <button type="button" wire:click="$set('showEditModal', false)" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded">انصراف</button>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="py-4 text-center text-gray-400">تراکنشی ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function formatNumber(num) {
        if (!num) return '';
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '٬');
    }
    document.addEventListener('livewire:load', function () {
        const display = document.getElementById('amount_display');
        const hidden = document.getElementById('amount');
        if (display && hidden) {
            // مقدار اولیه
            if (hidden.value) display.value = formatNumber(hidden.value);
            display.addEventListener('input', function () {
                let raw = display.value.replace(/[^\d]/g, '');
                display.value = formatNumber(raw);
                hidden.value = raw;
                // تریگر Livewire
                hidden.dispatchEvent(new Event('input', { bubbles: true }));
            });
            // اگر Livewire مقدار را ریست کرد
            window.Livewire && Livewire.hook && Livewire.hook('element.updated', (el, comp) => {
                if (el === hidden) display.value = formatNumber(hidden.value);
            });
        }
    });
</script>
@endpush
