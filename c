esources\views\livewire\charity\family-search.blade.php
<!-- جدول خانواده‌ها -->
<div class="w-full overflow-hidden shadow-sm border border-gray-200 rounded-lg">
    <!-- عنوان جدول با دکمه دانلود -->
    <div class="flex items-center justify-between p-4 bg-gray-50 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">لیست خانواده‌ها</h3>
        @if(isset($families) && $families->count() > 0)
            <a href=