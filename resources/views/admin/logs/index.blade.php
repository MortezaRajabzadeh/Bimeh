@php
    $eventLabels = [
        'created' => 'ایجاد',
        'updated' => 'ویرایش',
        'deleted' => 'حذف',
    ];
    $modelLabels = [
        'User' => 'کاربر',
        'Organization' => 'سازمان',
        'Region' => 'منطقه',
        // ... سایر مدل‌ها
    ];
    function readableDescription($log) {
        if ($log->event === 'created') {
            return 'ایجاد ' . ($log->subject ? ($log->subject->display_name ?? $log->subject->name ?? $log->subject->title ?? '') : '') . ' توسط ' . ($log->causer->username ?? $log->causer->name ?? $log->causer->mobile ?? 'سیستم');
        }
        if ($log->event === 'updated') {
            return 'ویرایش ' . ($log->subject ? ($log->subject->display_name ?? $log->subject->name ?? $log->subject->title ?? '') : '') . ' توسط ' . ($log->causer->username ?? $log->causer->name ?? $log->causer->mobile ?? 'سیستم');
        }
        if ($log->event === 'deleted') {
            return 'حذف ' . ($log->subject ? ($log->subject->display_name ?? $log->subject->name ?? $log->subject->title ?? '') : '') . ' توسط ' . ($log->causer->username ?? $log->causer->name ?? $log->causer->mobile ?? 'سیستم');
        }
        return $log->description;
    }
@endphp

<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <h2 class="text-2xl font-bold text-center mb-6">لاگ فعالیت‌های ادمین</h2>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <table class="min-w-full text-sm text-right">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border-b">#</th>
                        <th class="px-4 py-2 border-b">کاربر</th>
                        <th class="px-4 py-2 border-b">عملیات</th>
                        <th class="px-4 py-2 border-b">شرح</th>
                        <th class="px-4 py-2 border-b">مورد تغییر</th>
                        <th class="px-4 py-2 border-b">تاریخ</th>
                        <th class="px-4 py-2 border-b">جزئیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="px-4 py-2 border-b">{{ $log->id }}</td>
                            <td class="px-4 py-2 border-b">
                                {{ $log->causer ? ($log->causer->username ?? $log->causer->name ?? $log->causer->mobile ?? '-') : '-' }}
                            </td>
                            <td class="px-4 py-2 border-b">
                                {{ $eventLabels[$log->event] ?? $log->event }}
                            </td>
                            <td class="px-4 py-2 border-b">
                                {{ readableDescription($log) }}
                            </td>
                            <td class="px-4 py-2 border-b">
                                {{ $modelLabels[class_basename($log->subject_type) ?? ''] ?? class_basename($log->subject_type) }}
                                @if($log->subject_id)
                                    (کد: {{ $log->subject_id }})
                                @endif
                            </td>
                            <td class="px-4 py-2 border-b">{{ jdate($log->created_at)->format('Y/m/d H:i') }}</td>
                            <td class="px-4 py-2 border-b">
                                <a href="{{ route('admin.logs.show', $log) }}" class="text-blue-500 hover:underline">مشاهده</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">لاگی ثبت نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</x-app-layout> 