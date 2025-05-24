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
    $attributeLabels = [
        'code' => 'کد',
        'name' => 'نام',
        'type' => 'نوع',
        'is_active' => 'وضعیت',
        // ... سایر کلیدها
    ];
    $typeLabels = [
        'charity' => 'خیریه',
        'admin' => 'ادمین',
        'insurance' => 'بیمه',
        // ...
    ];
@endphp

<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <h2 class="text-2xl font-bold text-center mb-6">جزئیات لاگ فعالیت</h2>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-2xl mx-auto">
            <div class="mb-4">
                <span class="font-bold">کاربر:</span>
                {{ $activity->causer ? ($activity->causer->username ?? $activity->causer->name ?? $activity->causer->mobile ?? '-') : '-' }}
            </div>
            <div class="mb-4">
                <span class="font-bold">عملیات:</span>
                {{ $eventLabels[$activity->event] ?? $activity->event }}
            </div>
            <div class="mb-4">
                <span class="font-bold">شرح:</span>
                {{ readableDescription($activity) }}
            </div>
            <div class="mb-4">
                <span class="font-bold">مورد تغییر:</span>
                {{ $modelLabels[class_basename($activity->subject_type) ?? ''] ?? class_basename($activity->subject_type) }}
                @if($activity->subject_id)
                    (کد: {{ $activity->subject_id }})
                @endif
            </div>
            <div class="mb-4">
                <span class="font-bold">تاریخ:</span>
                {{ jdate($activity->created_at)->format('Y/m/d H:i') }}
            </div>
            <div class="mb-4">
                <span class="font-bold">اطلاعات اضافی (properties):</span>
                @if(isset($activity->properties['attributes']) && is_array($activity->properties['attributes']))
                    <table class="min-w-full text-xs text-right bg-gray-50 rounded mb-4">
                        <tbody>
                        @foreach($activity->properties['attributes'] as $key => $value)
                            <tr>
                                <td class="px-2 py-1 font-bold text-gray-700">
                                    {{ $attributeLabels[$key] ?? $key }}
                                </td>
                                <td class="px-2 py-1">
                                    @if($key === 'is_active')
                                        {{ $value ? 'فعال' : 'غیرفعال' }}
                                    @elseif($key === 'type')
                                        {{ $typeLabels[$value] ?? $value }}
                                    @else
                                        {{ $value ?? '-' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <pre class="bg-gray-100 rounded p-2 text-xs overflow-x-auto">{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
            </div>
            <div class="mt-6 text-center">
                <a href="{{ route('admin.logs.index') }}" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-full text-sm hover:bg-gray-400 transition">بازگشت به لیست</a>
            </div>
        </div>
    </div>
</x-app-layout> 