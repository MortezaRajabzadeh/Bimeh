<x-app-layout>
<div class="container mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold text-center mb-8 text-gray-800">گزارش مالی</h2>
    <div class="overflow-x-auto rounded-2xl shadow-lg bg-white border border-gray-100">
        <table class="min-w-full table-fixed text-center" dir="rtl">
            <colgroup>
                <col style="width:33.33%" />
                <col style="width:33.33%" />
                <col style="width:33.33%" />
            </colgroup>
            <thead>
                <tr class="bg-gray-400 text-white text-lg font-bold">
                    <th class="px-6 py-4 text-center align-middle">عنوان تراکنش</th>
                    <th class="px-6 py-4 text-center align-middle">تاریخ</th>
                    <th class="px-6 py-4 text-center align-middle">مبلغ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $idx => $t)
                    <tbody x-data="{ open: false }" class="transition-all duration-300">
                        <tr @click="open = !open"
                            class="cursor-pointer text-lg {{ $t['type'] === 'credit' ? 'bg-green-100' : 'bg-red-100' }}">
                            <td class="px-6 py-4 text-center font-bold relative">
                                @if(in_array($t['title'], ['حق بیمه پرداختی', 'بیمه پرداختی (ایمپورت اکسل)']))
                                    <button @click.stop="open = !open"
                                            type="button"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 bg-pink-200 rounded-full p-1 focus:outline-none transition-transform"
                                            :class="open ? 'rotate-180' : ''">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700" fill="none"
                                             viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                @endif
                                {{ $t['title'] }}
                            </td>
                            <td class="px-6 py-4 text-center">{{ $t['date'] }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="{{ $t['type'] === 'credit' ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $t['type'] === 'credit' ? '+' : '-' }}
                                    {{ number_format($t['amount']) }} ریال
                                </span>
                            </td>
                        </tr>

                        @if(in_array($t['title'], ['حق بیمه پرداختی', 'بیمه پرداختی (ایمپورت اکسل)']))
                        <tr x-show="open" x-transition x-cloak class="bg-red-50">
                            <td colspan="3" class="text-center px-6 py-6 align-middle text-base text-gray-700">
                            @php
                                $allCodes = array_merge($t['created_family_codes'] ?? [], $t['updated_family_codes'] ?? []);
                            @endphp
                            @if(count($allCodes))
                                پرداخت حق بیمه برای {{ count($allCodes) }} خانواده به مبلغ {{ number_format($t['amount']) }} ریال
                                <a href="{{ route('insurance.families.list', ['codes' => implode(',', $allCodes)]) }}" class="text-blue-600 underline ml-2" target="_blank">(مشاهده بیمه‌شدگان)</a>
                            @else
                                پرداخت حق بیمه
                            @endif
                            </td>
                        </tr>
                        @endif
                    </tbody>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-4 text-gray-500">هیچ تراکنشی ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="bg-gray-200 text-xl font-bold text-gray-800">
                    <td colspan="2" style="width:50%" class="px-6 py-4 text-center align-middle">بودجه باقی‌مانده:</td>
                    <td class="px-6 py-4 text-center align-middle">{{ number_format($balance) }} ریال</td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="my-8">
        <h2 class="text-xl font-bold text-center mb-4">گزارش ایمپورت‌های اکسل بیمه</h2>
        <div class="mb-4 text-center">
            <span class="font-bold">مجموع کل مبلغ بیمه‌های ثبت‌شده:</span>
            <span class="text-green-700">{{ number_format($totalAmount) }} ریال</span>
        </div>
        <div class="overflow-x-auto">
    <table class="min-w-full bg-white rounded shadow border table-fixed">
        <thead class="bg-gray-200">
            <tr>
                <th class="w-1/10 px-3 py-2 text-center align-middle">تاریخ</th>
                <th class="w-1/10 px-3 py-2 text-center align-middle">کاربر</th>
                <th class="w-1/10 px-3 py-2 text-center align-middle">نام فایل</th>
                <th class="w-1/10 px-3 py-2 text-center align-middle">کل ردیف</th>
                <th class="w-1/10 px-3 py-2 text-center align-middle">جدید</th>
                <th class="w-1/10 px-3 py-2 text-center align-middle">بروزرسانی</th>
                <th class="w-1/10 px-3 py-2 text-center align-middle">بدون تغییر</th>
                <th class="w-1/10 px-3 py-2 text-center align-middle">خطا</th>
                <th class="w-1/10 px-3 py-2 text-center align-middle">مجموع مبلغ</th>
                <th class="w-1/10 px-3 py-2 text-center align-middle">خانواده‌ها</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr class="border-b hover:bg-gray-50">
                <td class="w-1/10 px-3 py-2 text-center align-middle">{{ jdate($log->created_at)->format('Y/m/d') }}</td>
                <td class="w-1/10 px-3 py-2 text-center align-middle">{{ $log->user->name ?? '-' }}</td>
                <td class="w-1/10 px-3 py-2 text-center align-middle">{{ $log->file_name }}</td>
                <td class="w-1/10 px-3 py-2 text-center align-middle">{{ $log->total_rows }}</td>
                <td class="w-1/10 px-3 py-2 text-center align-middle text-green-700">{{ $log->created_count }}</td>
                <td class="w-1/10 px-3 py-2 text-center align-middle text-blue-700">{{ $log->updated_count }}</td>
                <td class="w-1/10 px-3 py-2 text-center align-middle text-gray-500">{{ $log->skipped_count }}</td>
                <td class="w-1/10 px-3 py-2 text-center align-middle text-red-700">{{ $log->error_count }}</td>
                <td class="w-1/10 px-3 py-2 text-center align-middle">{{ number_format($log->total_insurance_amount) }} ریال</td>
                <td class="w-1/10 px-3 py-2 text-center align-middle">
                    @if($log->family_codes && is_array($log->family_codes))
                        <span class="text-xs">{{ implode(', ', $log->family_codes) }}</span>
                    @else
                        -
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="10" class="text-center py-4 text-gray-400">گزارشی ثبت نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
</div>
</x-app-layout> 