<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('لاگ تغییرات سیستم') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="overflow-x-auto relative">
                        <table class="w-full text-sm text-right text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="py-3 px-6">تاریخ</th>
                                    <th scope="col" class="py-3 px-6">کاربر</th>
                                    <th scope="col" class="py-3 px-6">عملیات</th>
                                    <th scope="col" class="py-3 px-6">موضوع</th>
                                    <th scope="col" class="py-3 px-6">توضیحات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr class="bg-white border-b">
                                        <td class="py-4 px-6">{{ jdate($log->created_at)->format('Y/m/d H:i') }}</td>
                                        <td class="py-4 px-6">{{ $log->causer_type ? class_basename($log->causer_type) . ' #' . $log->causer_id : '-' }}</td>
                                        <td class="py-4 px-6">{{ $log->event }}</td>
                                        <td class="py-4 px-6">{{ $log->subject_type ? class_basename($log->subject_type) . ' #' . $log->subject_id : '-' }}</td>
                                        <td class="py-4 px-6">{{ $log->description }}</td>
                                    </tr>
                                @empty
                                    <tr class="bg-white border-b">
                                        <td colspan="5" class="py-4 px-6 text-center">هیچ لاگی ثبت نشده است.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 