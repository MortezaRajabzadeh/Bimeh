<div x-data="{
    downloading: false,
    showFilterModal: false,
    showRankModal: @entangle('showRankModal'),
    filters: @entangle('tempFilters'),
    addFilter() {
        if (!this.filters) {
            this.filters = [];
        }
        this.filters.push({
            type: 'status',
            operator: 'equals',
            value: '',
            label: ''
        });
    },
    removeFilter(index) {
        this.filters.splice(index, 1);
    },
    updateFilterLabel(index) {
        if (!this.filters[index]) return;

        let label = '';

        switch(this.filters[index].type) {
            case 'status':
                label = 'وضعیت';
                break;
            case 'province':
                label = 'استان';
                break;
            case 'city':
                label = 'شهر';
                break;
            case 'deprivation_rank':
                label = 'رتبه';
                break;
            case 'charity':
                label = 'خیریه معرف';
                break;
            case 'members_count':
                label = 'تعداد اعضا';
                break;
            case 'created_at':
                if (this.filters && this.filters.find(f => f.type === 'status' && f.value === 'insured')) {
                    label = 'تاریخ پایان بیمه';
                } else {
                    label = 'تاریخ عضویت';
                }
                break;
        }

        if (this.filters[index].operator === 'equals') label += ' برابر با';
        else if (this.filters[index].operator === 'not_equals') label += ' مخالف';
        else if (this.filters[index].operator === 'greater_than') label += ' بیشتر از';
        else if (this.filters[index].operator === 'less_than') label += ' کمتر از';
        else if (this.filters[index].operator === 'contains') label += ' شامل';

        this.filters[index].label = label;
    },
    downloadFile(url) {
        this.downloading = true;

        // ایجاد یک لینک مخفی و کلیک روی آن برای شروع دانلود
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', ''); // نام فایل در سمت سرور تعیین می‌شود
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // کمی تاخیر برای اطمینان از شروع دانلود قبل از پنهان کردن لودینگ
        setTimeout(() => {
            this.downloading = false;
        }, 1000);
    }
}" @file-download.window="downloadFile($event.detail.url)">
    @push('styles')
        <link href="{{ asset('css/insurance-wizard.css') }}" rel="stylesheet">
        <style>
            [x-cloak] { display: none !important; }

            /* اضافه کردن استایل‌های مربوط به فیلترها */
            @keyframes slideIn {
                from {
                    transform: translate(-50%, -20px);
                    opacity: 0;
                }
                to {
                    transform: translate(-50%, 0);
                    opacity: 1;
                }
            }

            @keyframes slideOut {
                from {
                    transform: translate(-50%, 0);
                    opacity: 1;
                }
                to {
                    transform: translate(-50%, -20px);
                    opacity: 0;
                }
            }

            .notification-show {
                animation: slideIn 0.3s ease forwards;
            }

            .notification-hide {
                animation: slideOut 0.3s ease forwards;
            }

            .icon-rotate-180 {
                transform: rotate(180deg);
                transition: transform 0.3s ease;
            }

            /* انیمیشن‌های مربوط به toast */
            .toast-show {
                animation: slideIn 0.3s ease forwards;
            }

            .toast-hide {
                animation: slideOut 0.3s ease forwards;
            }

            #toast-notification {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 2px 4px rgba(0, 0, 0, 0.12);
            }

            /* استایل‌های مربوط به جدول اعضای خانواده */
            .family-members-table {
                table-layout: auto;
                width: 100%;
                min-width: 1200px;
            }

            .family-members-table th,
            .family-members-table td {
                white-space: nowrap;
                min-width: 100px;
            }

            /* استایل برای اسکرول افقی */
            .scrollbar-thin::-webkit-scrollbar {
                height: 8px;
                width: 8px;
            }

            .scrollbar-thin::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 4px;
            }

            .scrollbar-thin::-webkit-scrollbar-thumb {
                background: #cbd5e0;
                border-radius: 4px;
            }

            .scrollbar-thin::-webkit-scrollbar-thumb:hover {
                background: #a0aec0;
            }
        </style>
    @endpush

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            console.log('DOM loaded - Debugging modal');

            // گوش دادن به رویداد نمایش مودال
            window.addEventListener('showDeleteModal', event => {
                console.log('showDeleteModal event received');
            });

            // گوش دادن به رویداد بستن مودال
            window.addEventListener('closeDeleteModal', event => {
                console.log('closeDeleteModal event received');
            });
        });
    </script>

    {{-- Debug Panel --}}
    @if(config('app.debug'))
        <div class="bg-gray-800 text-white p-4 mb-4 rounded-lg text-sm overflow-auto max-h-60 font-mono" id="debug-panel">
            <h3 class="text-yellow-400 font-bold mb-2">🐞 Debug Panel</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h4 class="text-green-400 font-bold">Selected Items ({{ count($selected) }})</h4>
                    <pre class="text-xs overflow-auto max-h-20 bg-gray-900 p-2 rounded mt-1">{{ json_encode($selected, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
                <div>
                    <h4 class="text-green-400 font-bold">Status</h4>
                    <pre class="text-xs overflow-auto max-h-20 bg-gray-900 p-2 rounded mt-1">
selectAll: {{ $selectAll ? 'true' : 'false' }}
activeTab: {{ $activeTab }}
total items: {{ $families->count() ?? 0 }}</pre>
                </div>
            </div>

            <div class="mt-4">
                <div class="mb-2 bg-blue-900 p-2 rounded text-xs">
                    <strong class="text-blue-300">📝 Note:</strong> Fixed "Unable to call lifecycle method" error by changing from <code class="text-orange-400">wire:click="updatedSelectAll"</code> to <code class="text-green-400">wire:change="toggleSelectAll"</code>
                </div>
                <div class="mb-2 bg-green-900 p-2 rounded text-xs">
                    <strong class="text-green-300">🚀 Updates:</strong> Fixed conversion errors in <code class="text-green-400">approveSelected</code> method for enum-to-string conversion and added loading state to approval button
                </div>
                <div class="mb-2 bg-purple-900 p-2 rounded text-xs">
                    <strong class="text-purple-300">🔄 Database Fix:</strong> Added missing <code class="text-purple-400">extra_data</code> column to <code class="text-purple-400">family_status_logs</code> table and simplified log creation
                </div>
                <div class="mb-2 bg-gray-900 p-2 rounded text-xs">
                    <strong class="text-yellow-300">Wizard Flow:</strong>
                    <span class="text-gray-300">PENDING → REVIEWING → SHARE_ALLOCATION → APPROVED → EXCEL_UPLOAD → INSURED</span>
                </div>
                <button onclick="clearLogs()" class="bg-red-600 text-white px-2 py-1 rounded text-xs">Clear Logs</button>
                <div id="action-logs" class="mt-2 text-xs bg-gray-900 p-2 rounded max-h-24 overflow-auto"></div>
            </div>
        </div>

        <script>
            function addLog(message) {
                const logsEl = document.getElementById('action-logs');
                const time = new Date().toLocaleTimeString();
                logsEl.innerHTML += `<div>[${time}] ${message}</div>`;
                logsEl.scrollTop = logsEl.scrollHeight;
            }

            function clearLogs() {
                document.getElementById('action-logs').innerHTML = '';
            }

            document.addEventListener('livewire:initialized', () => {
                addLog('Component initialized');

                // Listen for Livewire events
                Livewire.on('reset-checkboxes', () => {
                    addLog('Event: reset-checkboxes received');
                });

                Livewire.on('show-persistent-error', ({ detail }) => {
                    if (detail && detail.message) {
                        alert(detail.message);
                        if (typeof addLog === 'function') {
                            addLog(`Persistent error shown: ${detail.message}`);
                        }
                    } else {
                        const errorMessage = detail || 'یک خطای ناشناخته در نمایش پیام رخ داد.';
                        alert(errorMessage);
                        if (typeof addLog === 'function') {
                            addLog(`Persistent error shown (fallback): ${errorMessage}`);
                        }
                    }
                });

                // Monitor checkbox interactions
                document.getElementById('select-all')?.addEventListener('change', function(e) {
                    addLog(`Select All checkbox changed: ${e.target.checked} (calling toggleSelectAll)`);
                });

                // Monitor individual checkboxes
                document.querySelectorAll('input[wire\\:model="selected"]').forEach(checkbox => {
                    checkbox.addEventListener('change', function(e) {
                        addLog(`Checkbox ${e.target.value} changed: ${e.target.checked}`);
                    });
                });

                // Monitor approve button
                const approveButton = document.querySelector('button[wire\\:click="approveSelected"]');
                if (approveButton) {
                    approveButton.addEventListener('click', function() {
                        addLog('Approve button clicked');
                    });
                }
            });
        </script>
    @endif

    {{-- Notification Messages --}}
    @if (session()->has('message'))
        <div class="rounded-md bg-green-50 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="mr-3">
                    <p class="text-sm font-medium text-green-800">{{ session('message') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="rounded-md bg-red-50 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="mr-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-8">
        <!-- نوار پیشرفت -->
        <div class="mb-8">
            <!-- Main wizard tabs with separate tabs on the sides -->
            <div class="relative flex justify-between items-center">
                <!-- Renewal Tab (Left side) -->
                <div class="flex flex-col items-center relative z-10">
                    <button
                        wire:click="changeTab('renewal')"
                        class="w-14 h-14 rounded-full flex items-center justify-center transition-all duration-500 transform hover:scale-105
                            {{ $activeTab === 'renewal' ? 'bg-emerald-500 shadow-lg shadow-emerald-100 ring-4 ring-emerald-100' : 'bg-gray-100' }}">
                        <span class="text-{{ $activeTab === 'renewal' ? 'white' : 'gray-600' }}">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </span>
                    </button>
                    <span class="mt-3 text-sm font-medium {{ $activeTab === 'renewal' ? 'text-emerald-600' : 'text-gray-500' }}">
                        در انتظار تمدید
                    </span>
                </div>

                <!-- Progress bars and wizard tabs (middle) -->
                <div class="mx-6 flex-1 flex items-center justify-between">
                    <!-- Empty space to push wizard to center -->
                    <div class="w-8"></div>

                    <!-- Pending Tab -->
                    <div class="flex flex-col items-center relative z-10">
                        <button
                            wire:click="changeTab('pending')"
                            class="w-14 h-14 rounded-full flex items-center justify-center transition-all duration-500 transform hover:scale-105
                                {{ $activeTab === 'pending' ? 'bg-blue-600 shadow-lg shadow-blue-100 ring-4 ring-blue-100' :
                                   (in_array($activeTab, ['reviewing', 'approved', 'excel']) ? 'bg-emerald-500 shadow-lg shadow-emerald-100' : 'bg-gray-100') }}">
                            <span class="text-{{ $activeTab === 'pending' ? 'white' : (in_array($activeTab, ['reviewing', 'approved', 'excel']) ? 'white' : 'gray-600') }}">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </span>
                        </button>
                        <span class="mt-3 text-sm font-medium {{ $activeTab === 'pending' ? 'text-blue-600' : (in_array($activeTab, ['reviewing', 'approved', 'excel']) ? 'text-emerald-600' : 'text-gray-500') }}">
                            در انتظار تایید
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="flex-1 flex items-center mx-2">
                        <div class="h-2 w-full rounded-full transition-all duration-500 relative overflow-hidden
                            {{ in_array($activeTab, ['reviewing', 'approved', 'excel']) ? 'bg-emerald-500' : ($activeTab === 'pending' ? 'bg-blue-600' : 'bg-gray-200') }}">
                            @if($activeTab === 'pending')
                                <div class="absolute inset-0 bg-blue-200 animate-pulse"></div>
                            @endif
                        </div>
                    </div>

                    <!-- Reviewing Tab -->
                    <div class="flex flex-col items-center relative z-10">
                        <button
                            wire:click="changeTab('reviewing')"
                            class="w-14 h-14 rounded-full flex items-center justify-center transition-all duration-500 transform hover:scale-105
                                {{ $activeTab === 'reviewing' ? 'bg-blue-600 shadow-lg shadow-blue-100 ring-4 ring-blue-100' :
                                   (in_array($activeTab, ['approved', 'excel']) ? 'bg-emerald-500 shadow-lg shadow-emerald-100' : 'bg-gray-100') }}">
                            <span class="text-{{ $activeTab === 'reviewing' ? 'white' : (in_array($activeTab, ['approved', 'excel']) ? 'white' : 'gray-600') }}">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </span>
                        </button>
                        <span class="mt-3 text-sm font-medium {{ $activeTab === 'reviewing' ? 'text-blue-600' : (in_array($activeTab, ['approved', 'excel']) ? 'text-emerald-600' : 'text-gray-500') }}">
                            تخصیص سهمیه
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="flex-1 flex items-center mx-2">
                        <div class="h-2 w-full rounded-full transition-all duration-500 relative overflow-hidden
                            {{ in_array($activeTab, ['approved', 'excel']) ? 'bg-emerald-500' : ($activeTab === 'reviewing' ? 'bg-blue-600' : 'bg-gray-200') }}">
                            @if($activeTab === 'reviewing')
                                <div class="absolute inset-0 bg-blue-200 animate-pulse"></div>
                            @endif
                        </div>
                    </div>

                    <!-- Approved Tab -->
                    <div class="flex flex-col items-center relative z-10">
                        <button
                            wire:click="changeTab('approved')"
                            class="w-14 h-14 rounded-full flex items-center justify-center transition-all duration-500 transform hover:scale-105
                                {{ $activeTab === 'approved' ? 'bg-blue-600 shadow-lg shadow-blue-100 ring-4 ring-blue-100' :
                                   (in_array($activeTab, ['excel']) ? 'bg-emerald-500 shadow-lg shadow-emerald-100' : 'bg-gray-100') }}">
                            <span class="text-{{ $activeTab === 'approved' ? 'white' : (in_array($activeTab, ['excel']) ? 'white' : 'gray-600') }}">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </button>
                        <span class="mt-3 text-sm font-medium {{ $activeTab === 'approved' ? 'text-blue-600' : (in_array($activeTab, ['excel']) ? 'text-emerald-600' : 'text-gray-500') }}">
                            در انتظار حمایت
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="flex-1 flex items-center mx-2">
                        <div class="h-2 w-full rounded-full transition-all duration-500 relative overflow-hidden
                            {{ in_array($activeTab, ['excel']) ? 'bg-emerald-500' : ($activeTab === 'approved' ? 'bg-blue-600' : 'bg-gray-200') }}">
                            @if($activeTab === 'approved')
                                <div class="absolute inset-0 bg-blue-200 animate-pulse"></div>
                            @endif
                        </div>
                    </div>

                    <!-- Excel Tab -->
                    <div class="flex flex-col items-center relative z-10">
                        <button
                            wire:click="changeTab('excel')"
                            class="w-14 h-14 rounded-full flex items-center justify-center transition-all duration-500 transform hover:scale-105
                                {{ $activeTab === 'excel' ? 'bg-blue-600 shadow-lg shadow-blue-100 ring-4 ring-blue-100' : 'bg-gray-100' }}">
                            <span class="text-{{ $activeTab === 'excel' ? 'white' : 'gray-600' }}">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </span>
                        </button>
                        <span class="mt-3 text-sm font-medium {{ $activeTab === 'excel' ? 'text-blue-600' : 'text-gray-500' }}">
                            در انتظار صدور
                        </span>
                    </div>

                    <!-- Empty space to push wizard to center -->
                    <div class="w-8"></div>
                </div>

<!-- Deleted Tab (Right side) -->
<div class="flex flex-col items-center relative z-10">
    <button
        wire:click="changeTab('deleted')"
        class="w-14 h-14 rounded-full flex items-center justify-center transition-all duration-500 transform hover:scale-105

        {{-- منطق جدید برای رنگ قرمز در حالت فعال --}}
        @if ($activeTab === 'deleted')
            bg-red-600 shadow-lg shadow-red-100 ring-4 ring-red-100
        @else
            bg-gray-100
        @endif
        ">

        <span
            {{-- رنگ آیکون هم بر اساس وضعیت تغییر می‌کند --}}
            class="
            @if ($activeTab === 'deleted')
                text-white
            @else
                text-gray-600
            @endif
            ">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </span>
    </button>
    <span class="mt-3 text-sm font-medium {{ $activeTab === 'deleted' ? 'text-red-600 font-bold' : 'text-gray-500' }}">
        حذف شده ها
    </span>
</div>
            </div>
        </div>

        <!-- محتوای اصلی -->
        <div class="bg-white rounded-xl shadow p-6 mb-8">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <h2 class="text-2xl font-bold text-gray-800">
                        @if($activeTab === 'renewal')
                            لیست خانواده‌های در انتظار تمدید
                        @elseif($activeTab === 'pending')
                            لیست خانواده‌های در انتظار تایید
                        @elseif($activeTab === 'reviewing')
                            لیست خانواده‌های تخصیص سهمیه
                        @elseif($activeTab === 'approved')
                            لیست خانواده‌های در انتظار حمایت
                        @elseif($activeTab === 'excel')
                            لیست خانواده‌های در انتظار صدور
                        @elseif($activeTab === 'deleted')
                            لیست خانواده‌های حذف شده
                        @else
                            لیست خانواده‌های بیمه شده
                        @endif
                    </h2>

                    {{-- دکمه دانلود جدید مشابه family-search.blade.php --}}
                    @if(isset($families) && $families->count() > 0)
                        <div x-data="{ downloading: false }">
                            <button
                                wire:click="export"
                                wire:loading.attr="disabled"
                                wire:target="export"
                                x-on:livewire-upload-start="() => {}"
                                type="button"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-600 bg-white border border-green-600 rounded-md hover:bg-green-50 transition disabled:opacity-50 disabled:cursor-not-allowed">

                                <span wire:loading.remove wire:target="export">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    دانلود اکسل
                                </span>
                                <span wire:loading wire:target="export">
                                    <svg class="animate-spin h-4 w-4 ml-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    در حال آماده‌سازی...
                                </span>
                            </button>
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap gap-3">
                    @if($activeTab === 'pending')
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 disabled:opacity-50"
                            wire:click="approveSelected"
                            wire:loading.attr="disabled"
                            wire:target="approveSelected"
                            {{ count($selected) === 0 ? 'disabled' : '' }}>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            تایید و انتقال به مرحله بعد

                            <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                        </button>
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200 disabled:opacity-50"
                            wire:click="showDeleteConfirmation"
                            wire:loading.attr="disabled"
                            wire:target="showDeleteConfirmation"
                            {{ count($selected) === 0 ? 'disabled' : '' }}>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            حذف خانواده‌ها
                            <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                        </button>
                    @elseif($activeTab === 'reviewing')
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 disabled:opacity-50"
                            wire:click="approveAndContinueSelected"
                            {{ count($selected) === 0 ? 'disabled' : '' }}>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            تخصیص سهم و تایید
                            <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                        </button>
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200 disabled:opacity-50"
                            wire:click="showDeleteConfirmation"
                            {{ count($selected) === 0 ? 'disabled' : '' }}>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            حذف خانواده‌ها
                            <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                        </button>
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-white hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition-colors duration-200 disabled:opacity-50"
                            wire:click="returnToPreviousStage"
                            wire:loading.attr="disabled"
                            wire:target="returnToPreviousStage"
                            {{ count($selected) === 0 ? 'disabled' : '' }}>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 transform rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            بازگشت به مرحله قبل
                            <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                        </button>
                    @elseif($activeTab === 'approved')
                    <button type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
                    wire:click="downloadSampleTemplate">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    دانلود فایل نمونه
                </button>
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200 disabled:opacity-50"
                            wire:click="showDeleteConfirmation"
                            {{ count($selected) === 0 ? 'disabled' : '' }}>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            حذف خانواده‌ها
                            <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                        </button>
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-white hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition-colors duration-200 disabled:opacity-50"
                            wire:click="returnToPreviousStage"
                            wire:loading.attr="disabled"
                            wire:target="returnToPreviousStage"
                            {{ count($selected) === 0 ? 'disabled' : '' }}>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 transform rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            بازگشت به مرحله قبل
                            <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                        </button>
                    @elseif($activeTab === 'excel')
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200 disabled:opacity-50"
                            x-on:click="showExcelUploadModal = true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 011-1h10a2 2 0 012 2v-1m-4-4l-4 4m0 0L8 8m4-4v12" />
                            </svg>
                            ثبت اطلاعات صدور
                        </button>
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200 disabled:opacity-50"
                            wire:click="showDeleteConfirmation"
                            {{ count($selected) === 0 ? 'disabled' : '' }}>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            حذف خانواده‌ها
                            <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                        </button>
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-white hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition-colors duration-200 disabled:opacity-50"
                            wire:click="returnToPreviousStage"
                            wire:loading.attr="disabled"
                            wire:target="returnToPreviousStage"
                            {{ count($selected) === 0 ? 'disabled' : '' }}>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 transform rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            بازگشت به مرحله قبل
                            <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                        </button>
                    @elseif($activeTab === 'insured')
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200 disabled:opacity-50"
                            wire:click="showDeleteConfirmation"
                            {{ count($selected) === 0 ? 'disabled' : '' }}>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            حذف خانواده‌ها
                            <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                        </button>
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-white hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition-colors duration-200 disabled:opacity-50"
                            wire:click="returnToPreviousStage"
                            wire:loading.attr="disabled"
                            wire:target="returnToPreviousStage"
                            {{ count($selected) === 0 ? 'disabled' : '' }}>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 transform rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            بازگشت به مرحله قبل
                            <span class="mr-2 bg-white bg-opacity-20 rounded px-2 py-1 text-xs" x-show="$wire.selected.length > 0" x-text="$wire.selected.length"></span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- نوار جستجو و فیلتر -->
            <div class="mb-8">
                <div class="flex gap-3 items-center">
                    <!-- جستجو -->
                    <div class="relative flex-grow">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input wire:model.live="search" type="text" placeholder="جستجو در تمام فیلدها..."
                               class="border border-gray-300 rounded-lg pl-3 pr-10 py-2.5 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    <!-- دکمه فیلتر جدول -->
                    <button @click="showFilterModal = true"
                            class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                        </svg>
                        فیلتر جدول
                        @if($this->hasActiveFilters())
                            <span class="mr-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full">
                                {{ $this->getActiveFiltersCount() }}
                            </span>
                        @endif
                    </button>

                    <!-- دکمه تنظیمات رتبه -->
                    <button wire:click="openRankModal"
                            class="inline-flex items-center px-4 py-2.5 bg-blue-600 border border-blue-600 rounded-lg text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0h2a2 2 0 002 2v-1m-4 0a2 2 0 012-2h2a2 2 0 012 2v1m-6 0a2 2 0 00-2 2v-1m0 0a2 2 0 00-2 2v1a2 2 0 002 2z"></path>
                        </svg>
                        تنظیمات رتبه
                    </button>
                </div>

                <!-- نمایش فیلترهای فعال -->
                @if($this->hasActiveFilters())
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if($status)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                وضعیت: {{ $status === 'insured' ? 'بیمه شده' : 'بدون بیمه' }}
                                <button wire:click="$set('status', '')" class="mr-1 text-blue-600 hover:text-blue-800">×</button>
                            </span>
                        @endif

                        @if($province)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                استان: {{ $provinces->find($province)->name ?? 'نامشخص' }}
                                <button wire:click="$set('province', '')" class="mr-1 text-green-600 hover:text-green-800">×</button>
                            </span>
                        @endif

                        @if($city)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                شهر: {{ $cities->find($city)->name ?? 'نامشخص' }}
                                <button wire:click="$set('city', '')" class="mr-1 text-purple-600 hover:text-purple-800">×</button>
                            </span>
                        @endif

                        @if($deprivation_rank)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                محرومیت: {{ $deprivation_rank === 'high' ? 'بالا' : ($deprivation_rank === 'medium' ? 'متوسط' : 'پایین') }}
                                <button wire:click="$set('deprivation_rank', '')" class="mr-1 text-orange-600 hover:text-orange-800">×</button>
                            </span>
                        @endif

                        @if($charity)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-pink-100 text-pink-800">
                                خیریه: {{ $organizations->find($charity)->name ?? 'نامشخص' }}
                                <button wire:click="$set('charity', '')" class="mr-1 text-pink-600 hover:text-pink-800">×</button>
                            </span>
                        @endif

                        @if($family_rank_range)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                رتبه:
                                @if($family_rank_range === 'very_high') خیلی بالا
                                @elseif($family_rank_range === 'high') بالا
                                @elseif($family_rank_range === 'medium') متوسط
                                @elseif($family_rank_range === 'low') پایین
                                @elseif($family_rank_range === 'very_low') خیلی پایین
                                @endif
                                <button wire:click="$set('family_rank_range', '')" class="mr-1 text-purple-600 hover:text-purple-800">×</button>
                            </span>
                        @endif

                        @if($specific_criteria && isset($availableRankSettings))
                        @php
                            $criteriaIds = explode(',', $specific_criteria);
                            $selectedCriteriaNames = $availableRankSettings->whereIn('id', $criteriaIds)->pluck('name');
                        @endphp
                        @if($selectedCriteriaNames->count() > 0)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                معیارها: {{ $selectedCriteriaNames->implode('، ') }}
                                <button wire:click="clearCriteriaFilter" class="mr-1 text-indigo-600 hover:text-indigo-800">×</button>
                            </span>
                        @endif
                    @endif

                        <!-- دکمه پاک کردن همه فیلترها -->
                        <button wire:click="clearAllFilters" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200 transition-colors">
                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            پاک کردن همه
                        </button>
                    </div>
                @endif
            </div>

            {{-- نمایش لیست خانواده‌ها --}}
            <div class="w-full overflow-hidden shadow-sm border border-gray-200 rounded-lg">
                @if($activeTab === 'excel')
                {{-- تب در انتظار صدور - آپلود فایل اکسل --}}
                <div class="bg-white p-8 text-center">
                    <div class="mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-4 text-xl font-bold text-gray-800">ثبت اطلاعات صدور بیمه</h3>
                        <p class="mt-2 text-gray-600">فایل اطلاعات صدور بیمه را آپلود کنید تا اطلاعات بیمه‌نامه ثبت شود.</p>
                    </div>

                    <form wire:submit.prevent="uploadInsuranceExcel" class="mt-8 max-w-lg mx-auto">
                        <div class="flex flex-col items-center">
                            <input type="file" wire:model="insuranceExcelFile" accept=".xlsx,.xls" class="hidden" id="excel-upload-input">
                            <label for="excel-upload-input" class="w-full cursor-pointer">
                                <div class="bg-green-600 hover:bg-green-700 text-white rounded-xl py-4 text-lg font-bold flex items-center justify-center gap-2 transition duration-200 ease-in-out">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    آپلود فایل اطلاعات صدور
                                </div>
                            </label>

                            @if($insuranceExcelFile)
                                <div class="mt-4 text-green-700 text-sm font-bold flex items-center justify-center gap-2 animate-fade-in">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    فایل انتخاب شد: {{ $insuranceExcelFile->getClientOriginalName() }}
                                </div>
                                <button type="submit" class="mt-4 w-full bg-green-700 hover:bg-green-800 text-white rounded-xl py-3 text-lg font-bold transition duration-200 ease-in-out animate-fade-in">
                                    تایید و ارسال فایل
                                </button>
                            @endif

                            @error('insuranceExcelFile')
                                <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                            @enderror
                        </div>
                    </form>

                    <div class="mt-8 text-gray-600 text-sm">
                        <p class="font-bold mb-2">راهنمای آپلود فایل:</p>
                        <ul class="list-disc list-inside text-right">
                            <li>فایل باید در فرمت اکسل (.xlsx یا .xls) باشد</li>
                            <li>برای هر خانواده، اطلاعات بیمه را به طور کامل وارد کنید</li>
                            <li>از تغییر ساختار فایل خودداری کنید</li>
                        </ul>
                    </div>
                </div>
            @else
                <div class="w-full overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr class="text-xs text-gray-700">
                                <!-- ستون چک‌باکس -->
                                <th scope="col" class="px-3 py-3 text-right font-medium">
                                    <input type="checkbox" id="select-all"
                                           wire:model.live="selectAll"
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                </th>

                                <!-- 1. رتبه -->
                                <th scope="col" class="px-5 py-3 text-right font-medium">
                                    <button wire:click="sortBy('calculated_rank')" class="flex items-center justify-end w-full">
                                        رتبه
                                        @php $sf = $sortField ?? ''; $sd = $sortDirection ?? ''; @endphp
                                        @if($sf === 'calculated_rank')
                                            <span class="mr-1 text-[0.5rem]">
                                                @if($sd === 'asc')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                @endif
                                            </span>
                                        @else
                                            <span class="mr-1 text-[0.5rem]">▼</span>
                                        @endif
                                    </button>
                                </th>

                                <!-- 2. استان -->
                                <th scope="col" class="px-5 py-3 text-right font-medium">
                                    <button wire:click="sortBy('province_id')" class="flex items-center justify-end w-full">
                                        استان
                                        @if($sf === 'province_id')
                                            <span class="mr-1 text-[0.5rem]">
                                                @if($sd === 'asc')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                @endif
                                            </span>
                                        @else
                                            <span class="mr-1 text-[0.5rem]">▼</span>
                                        @endif
                                    </button>
                                </th>

                                <!-- 3. شهر/روستا -->
                                <th scope="col" class="px-5 py-3 text-right font-medium">
                                    <button wire:click="sortBy('city_id')" class="flex items-center justify-end w-full">
                                        شهر/روستا
                                        @if($sf === 'city_id')
                                            <span class="mr-1 text-[0.5rem]">
                                                @if($sd === 'asc')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                @endif
                                            </span>
                                        @else
                                            <span class="mr-1 text-[0.5rem] text-gray-400">▼</span>
                                        @endif
                                    </button>
                                </th>

                                <!-- 4. تعداد بیمه‌ها -->
                                <th scope="col" class="px-5 py-3 text-right font-medium">
                                    <button wire:click="sortBy('final_insurances_count')" class="flex items-center justify-end w-full">
                                        تعداد بیمه‌ها
                                        @if($sf === 'final_insurances_count')
                                            <span class="mr-1 text-[0.5rem]">
                                                @if($sd === 'asc')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                @endif
                                            </span>
                                        @else
                                            <span class="mr-1 text-[0.5rem]">▼</span>
                                        @endif
                                    </button>
                                </th>

                                <!-- 5. معیار پذیرش -->
                                <th scope="col" class="px-5 py-3 text-right font-medium">
                                    معیار پذیرش
                                </th>

                                <!-- 6. تعداد اعضا -->
                                <th scope="col" class="px-5 py-3 text-right font-medium">
                                    {{ $activeTab === 'pending' ? 'تعداد اعضای خانواده' : 'تعداد اعضا' }}
                                </th>

                                <!-- 7. سرپرست خانوار -->
                                <th scope="col" class="px-5 py-3 text-right font-medium">
                                    <button wire:click="sortBy('head_name')" class="flex items-center justify-end w-full">
                                        سرپرست خانوار
                                        @if($sf === 'head_name')
                                            <span class="mr-1 text-[0.5rem]">
                                                @if($sd === 'asc')
                                                    ▲
                                                @else
                                                    ▼
                                                @endif
                                            </span>
                                        @else
                                            <span class="mr-1 text-[0.5rem] text-gray-400">▼</span>
                                        @endif
                                    </button>
                                </th>

                                <!-- 8. خیریه معرف -->
                                <th scope="col" class="px-5 py-3 text-right font-medium">
                                    <button wire:click="sortBy('charity_id')" class="flex items-center justify-end w-full">
                                        خیریه معرف
                                        @if($sf === 'charity_id')
                                            <span class="mr-1 text-[0.5rem]">
                                                @if($sd === 'asc')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                @endif
                                            </span>
                                        @else
                                            <span class="mr-1 text-[0.5rem]">▼</span>
                                        @endif
                                    </button>
                                </th>

                                <!-- 9. تاریخ عضویت -->
                                <th scope="col" class="px-5 py-3 text-right font-medium">
                                    <button wire:click="sortBy('created_at')" class="flex items-center justify-end w-full">
                                        تاریخ عضویت
                                        @if($sf === 'created_at')
                                            <span class="mr-1 text-[0.5rem]">
                                                @if($sd === 'asc')
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                @endif
                                            </span>
                                        @else
                                            <span class="mr-1 text-[0.5rem] text-gray-400">▼</span>
                                        @endif
                                    </button>
                                </th>



                                <!-- 11. آیکون‌های اعتبارسنجی -->
                                <th scope="col" class="px-5 py-3 text-center font-medium">اعتبارسنجی</th>


                                <th scope="col" class="px-5 py-3 text-center font-medium">جزئیات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse(($families ?? collect([])) as $family)
                                <tr class="hover:bg-gray-50" data-family-id="{{ $family->id }}">
                                    <!-- ستون چک‌باکس -->
                                    <td class="px-3 py-4 whitespace-nowrap border-b border-gray-200">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="family-{{ $family->id }}"
                                                value="{{ $family->id }}"
                                                wire:model.live="selected"
                                                wire:key="checkbox-{{ $family->id }}"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        </div>
                                    </td>

                                    <!-- 1. رتبه -->
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        @if($family->province && isset($family->province->deprivation_rank))
                                            <div class="flex items-center justify-center">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center
                                                    {{ $family->province->deprivation_rank <= 3 ? 'bg-red-100 text-red-800' :
                                                       ($family->province->deprivation_rank <= 6 ? 'bg-yellow-100 text-yellow-800' :
                                                        'bg-green-100 text-green-800') }}">
                                                    {{ $family->province->deprivation_rank }}
                                                </div>
                                            </div>
                                        @else
                                            <div class="flex items-center justify-center">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-100 text-gray-800">
                                                    {{ $loop->iteration }}
                                                </div>
                                            </div>
                                        @endif
                                    </td>

                                    <!-- 2. استان -->
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        {{ $family->province->name ?? 'نامشخص' }}
                                    </td>

                                    <!-- 3. شهر/روستا -->
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        {{ $family->city->name ?? 'نامشخص' }}
                                    </td>

                                    <!-- 4. تعداد بیمه‌ها -->
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        <div class="flex flex-col items-center">
                                            <span class="text-lg font-bold {{ $family->final_insurances_count > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                                {{ $family->final_insurances_count ?? 0 }}
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                {{ $family->final_insurances_count > 0 ? 'عضو بیمه‌دار' : 'بدون بیمه' }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- 5. معیار پذیرش -->
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        @php
                                            // شمارش مشکلات تجمیعی خانواده
                                            $familyProblems = [];
                                            foreach ($family->members as $member) {
                                                if (is_array($member->problem_type)) {
                                                    foreach ($member->problem_type as $problem) {
                                                        if (!isset($familyProblems[$problem])) {
                                                            $familyProblems[$problem] = 0;
                                                        }
                                                        $familyProblems[$problem]++;
                                                    }
                                                }
                                            }

                                            $problemLabels = [
                                                'addiction' => ['label' => 'اعتیاد', 'color' => 'bg-purple-100 text-purple-800'],
                                                'unemployment' => ['label' => 'بیکاری', 'color' => 'bg-orange-100 text-orange-800'],
                                                'special_disease' => ['label' => 'بیماری خاص', 'color' => 'bg-red-100 text-red-800'],
                                                'work_disability' => ['label' => 'ازکارافتادگی', 'color' => 'bg-yellow-100 text-yellow-800'],
                                            ];
                                        @endphp

                                        <div class="flex flex-wrap gap-1">
                                            @if(count($familyProblems) > 0)
                                                @foreach($familyProblems as $problem => $count)
                                                    @if(isset($problemLabels[$problem]))
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $problemLabels[$problem]['color'] }}">
                                                            {{ $problemLabels[$problem]['label'] }}
                                                            @if($count > 1)
                                                                <span class="mr-1 bg-white bg-opacity-50 rounded-full px-1 text-xs">×{{ $count }}</span>
                                                            @endif
                                                        </span>
                                                    @endif
                                                @endforeach
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    بدون مشکل
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- 6. تعداد اعضا -->
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        {{ $family->members->count() ?? 0 }}
                                    </td>

                                    <!-- 7. سرپرست خانوار -->
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        @php
                                            $head = $family->members?->where('is_head', true)->first();
                                        @endphp
                                        @if($head)
                                            <div class="flex items-center justify-center">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                        {{ $head->first_name }} {{ $head->last_name }}
                                                </span>
                                                    </div>
                                            @if($head->national_code)
                                                <div class="text-center mt-1">
                                                    <span class="text-xs text-gray-500">کد ملی: {{ $head->national_code }}</span>
                                                    </div>
                                            @endif
                                        @else
                                            <div class="flex items-center justify-center">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                                    ⚠️ بدون سرپرست
                                                </span>
                                                </div>
                                        @endif
                                    </td>

                                    <!-- 8. خیریه معرف -->
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200 text-center">
                                        @if($family->charity)
                                            @if($family->charity->logo_path)
                                                <div class="flex justify-center">
                                                    <img
                                                        src="{{ $family->charity->logo_url }}"
                                                        alt="{{ $family->charity->name }}"
                                                        class="h-8 w-8 rounded-full object-cover"
                                                        title="{{ $family->charity->name }}"
                                                        loading="lazy"
                                                        onerror="this.onerror=null; this.src='{{ asset('images/default-organization.png') }}'"
                                                    >
                                                </div>
                                            @else
                                                <span class="truncate">{{ $family->charity->name }}</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">نامشخص</span>
                                        @endif
                                    </td>

                                    <!-- 9. تاریخ عضویت -->
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        @if($family->created_at)
                                            @php
                                                try {
                                                    echo \App\Helpers\DateHelper::toJalali($family->created_at);
                                                } catch (\Exception $e) {
                                                    echo $family->created_at->format('Y/m/d');
                                                }
                                            @endphp
                                        @else
                                            -
                                        @endif
                                    </td>



                                    <!-- 11. آیکون‌های اعتبارسنجی -->
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        <div class="flex items-center justify-center">
                                            <x-family-validation-icons :family="$family" size="sm" />
                                        </div>
                                    </td>

                                    <!-- 12. جزئیات -->
                                    <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                        <div class="flex items-center justify-center">
                                            <button wire:click="toggleFamily({{ $family->id }})" class="bg-green-200 hover:bg-green-300 text-green-800 text-xs py-1 px-2 rounded-full transition-all duration-200 ease-in-out toggle-family-btn" data-family-id="{{ $family->id }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block transition-transform duration-200 {{ $expandedFamily === $family->id ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                    @if($expandedFamily === $family->id)
                                <tr class="bg-green-50">
                                <td colspan="{{ auth()->user()->hasRole('admin') ? 10 : 13 }}" class="p-0">
                                <div class="overflow-hidden shadow-inner rounded-lg bg-green-50 p-2">
                                <div class="overflow-x-auto w-full max-h-96 scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100">
                                                        <table class="w-full table-auto bg-green-50 border border-green-100 rounded-lg family-members-table" wire:key="family-{{ $family->id }}">
                                                        <thead>
                                        <tr class="bg-green-100 border-b border-green-200">
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right sticky left-0 bg-green-100">سرپرست؟</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">نسبت</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">نام و نام خانوادگی</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">کد ملی</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">تاریخ تولد</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">شغل</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">نوع مشکل</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">خیریه معرف</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">نوع بیمه</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">پرداخت کننده حق بیمه</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">درصد مشارکت</th>
                                            @if(!auth()->user()->hasRole('admin'))
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">اعتبارسنجی</th>
                                            @endif
                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                                @forelse($familyMembers ?? $family->members ?? [] as $member)
                                                                    <tr class="bg-green-100 border-b border-green-200 hover:bg-green-200" wire:key="member-{{ $member->id }}">
                                                                    <td class="px-5 py-3 text-sm text-gray-800 text-center sticky left-0 bg-green-100">
                                                                            {{-- کاربر بیمه نباید بتواند سرپرست را تغییر دهد --}}
                                                                        @if($member->is_head)
                                                                            <span class="text-blue-500 font-bold inline-flex items-center">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                                </svg>
                                                                                سرپرست
                                                                            </span>
                                                                        @else
                                                                            <span class="text-gray-400">-</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="px-3 py-3 text-sm text-gray-800">
                                                                        {{ $member->relationship_fa ?? ($member->relationship === 'head' ? 'سرپرست' :
                                                                        ($member->relationship === 'spouse' ? 'همسر' :
                                                                        ($member->relationship === 'child' ? 'فرزند' :
                                                                            ($member->relationship === 'parent' ? 'والدین' : 'سایر')))) }}
                                                                    </td>
                                                                    <td class="px-3 py-3 text-sm text-gray-800">
                                                                        {{ $member->first_name }} {{ $member->last_name }}
                                                                    </td>
                                                                    <td class="px-3 py-3 text-sm text-gray-800">{{ $member->national_code ?? '-' }}</td>
                                                                    <td class="px-3 py-3 text-sm text-gray-800">
                                                        @if($member->birth_date)
                                                            @php
                                                                try {
                                                                    echo \App\Helpers\DateHelper::toJalali($member->birth_date);
                                                                } catch (\Exception $e) {
                                                                    echo \Carbon\Carbon::parse($member->birth_date)->format('Y/m/d');
                                                                }
                                                            @endphp
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                                    <td class="px-3 py-3 text-sm text-gray-800">{{ $member->occupation ?? 'بیکار' }}</td>
                                                                    <td class="px-3 py-3 text-sm text-gray-800">
                                                                        @php
                                                                            $problemLabels = [
                                                                                'unemployment' => ['label' => 'بیکاری', 'color' => 'bg-yellow-100 text-yellow-800'],
                                                                                'special_disease' => ['label' => 'بیماری خاص', 'color' => 'bg-red-100 text-red-800'],
                                                                                'addiction' => ['label' => 'اعتیاد', 'color' => 'bg-purple-100 text-purple-800'],
                                                                                'disability' => ['label' => 'ناتوانی جسمی', 'color' => 'bg-blue-100 text-blue-800'],
                                                                                'single_parent' => ['label' => 'سرپرست زن', 'color' => 'bg-pink-100 text-pink-800'],
                                                                            ];

                                                                            $memberProblems = [];
                                                                            if (is_array($member->problem_type)) {
                                                                                foreach ($member->problem_type as $problem) {
                                                                                    if (isset($problemLabels[$problem])) {
                                                                                        $memberProblems[] = $problemLabels[$problem];
                                                                                    }
                                                                                }
                                                                            }
                                                                        @endphp

                                                                        @if(count($memberProblems) > 0)
                                                                            <div class="flex flex-wrap gap-1">
                                                                                @foreach($memberProblems as $problem)
                                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $problem['color'] }}">
                                                                                        {{ $problem['label'] }}
                                                                                    </span>
                                                                                @endforeach
                                                                            </div>
                                                                        @else
                                                                            <span class="px-2 py-0.5 rounded-md text-xs bg-gray-100 text-gray-800">
                                                                                بدون مشکل
                                                                            </span>
                                                                        @endif
                                                                    </td>
                                                                        <td class="px-3 py-3 text-sm text-gray-800 charity-cell">
                                                                            @if($member->organization)
                                                                                @if($member->organization->logo_path)
                                                                                    <img
                                                                                        src="{{ $member->organization->logo_url }}"
                                                                                        alt="{{ $member->organization->name }}"
                                                                                        class="h-8 w-8 rounded-full object-cover"
                                                                                        title="{{ $member->organization->name }}"
                                                                                        onerror="this.onerror=null; this.src='{{ asset('images/default-organization.png') }}';"
                                                                                        loading="lazy"
                                                                                        width="32"
                                                                                        height="32"
                                                                                    >
                                                                                @else
                                                                                    {{ $member->organization->name }}
                                                                                @endif
                                                                            @else
                                                                                <span class="text-gray-400">-</span>
                                                                            @endif
                                                                        </td>
                                                                        <td class="px-3 py-3 text-sm text-gray-800">
                                                                            @php $types = $family->insuranceTypes(); @endphp
                                                                            @if($types->count())
                                                                                @foreach($types as $type)
                                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mr-1 mb-1">{{ $type }}</span>
                                                                                @endforeach
                                                                            @else
                                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1 mb-1">-</span>
                                                                            @endif
                                                                        </td>
                                                                        <td class="px-3 py-3 text-sm text-gray-800">
                                                                            @php $payers = $family->insurancePayers(); @endphp
                                                                            @if($payers->count())
                                                                                @foreach($payers as $payer)
                                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mr-1 mb-1">{{ $payer }}</span>
                                                                                @endforeach
                                                                            @else
                                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1 mb-1">-</span>
                                                                            @endif
                                                                        </td>
                                                                        <td class="px-3 py-3 text-sm text-gray-800">۱۰۰٪</td>

                                                                    @if(!auth()->user()->hasRole('admin'))
                                                                    <td class="px-3 py-3 text-sm text-gray-800 text-center">
                                                                        @php
                                                                            // چک کنیم آیا این عضو نیاز به مدرک دارد
                                                                            $needsDocument = isset($member->needs_document) && $member->needs_document;
                                                                        @endphp

                                                                        @if($needsDocument)
                                                                            <a href="{{ route('charity.family.members.documents.upload', ['family' => $family->id, 'member' => $member->id]) }}"
                                                                                   class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full hover:bg-yellow-200 transition-colors">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                                </svg>
                                                                                آپلود مدرک
                                                                            </a>
                                                                        @else
                                                                            <x-member-validation-icons :member="$member" size="sm" />
                                                                        @endif
                                                                    </td>
                                                                        @endif
                                                                </tr>
                                                                @empty
                                                                    <tr>
                                                                        <td colspan="{{ auth()->user()->hasRole('admin') ? 11 : 12 }}" class="px-3 py-3 text-sm text-gray-500 text-center border-b border-gray-100">
                                                                            عضوی برای این خانواده ثبت نشده است.
                                                                        </td>
                                                                    </tr>
                                                                @endforelse
                                                        </tbody>
                                                    </table>

                                                        <div class="bg-green-100 py-4 px-4 rounded-b border-r border-l border-b border-green-100 flex flex-wrap justify-between items-center gap-4">
                                                            <div class="flex items-center">
                                                                <span class="text-sm text-gray-600 ml-2">شماره موبایل سرپرست:</span>
                                                                <div class="bg-white rounded px-3 py-2 flex items-center">
                                                                    <span class="text-sm text-gray-800">{{ $family->head()?->mobile ?? '09347964873' }}</span>
                                                                    <button type="button" wire:click="copyText('{{ $family->head()?->mobile ?? '09347964873' }}')" class="text-blue-500 mr-2 cursor-pointer">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                            </div>

                                                            <div class="flex items-center">
                                                                <span class="text-sm text-gray-600 ml-2">شماره شبا جهت پرداخت خسارت:</span>
                                                                <div class="bg-white rounded px-3 py-2 flex items-center">
                                                                    <span class="text-sm text-gray-800 ltr">{{ $family->head()?->sheba ?? 'IR056216845813188' }}</span>
                                                                    <button type="button" wire:click="copyText('{{ $family->head()?->sheba ?? 'IR056216845813188' }}')" class="text-blue-500 mr-2 cursor-pointer">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                                                            @empty
                                <tr>
                                    <td colspan="11" class="px-6 py-4 text-center text-gray-500">
                                        هیچ خانواده‌ای یافت نشد.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

    <!-- پیجینیشن -->
    @if(($families ?? null) && ($families->hasPages() ?? false))
    <div class="mt-6 border-t border-gray-200 pt-4" id="pagination-section">
        <div class="flex flex-wrap items-center justify-between">
            <!-- تعداد نمایش - سمت راست -->
            <div class="flex items-center order-1">
                <span class="text-sm text-gray-600 ml-2">تعداد نمایش:</span>
                <select wire:model.live="perPage"
                        class="h-9 w-16 border border-gray-300 rounded-md px-2 py-1 text-sm bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors"
                        style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>

            <!-- شماره صفحات - وسط -->
            <div class="flex items-center justify-center order-2 flex-grow mx-4">
                <!-- دکمه صفحه قبل -->
                <button type="button" wire:click="previousPage('page')" wire:loading.attr="disabled" wire:target="previousPage" @if($families->onFirstPage()) disabled @endif class="{{ !$families->onFirstPage() ? 'text-green-600 hover:bg-green-50 cursor-pointer' : 'text-gray-400 opacity-50 cursor-not-allowed' }} bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm mr-1 transition-colors duration-200">

                    <!-- آیکون لودینگ -->
                    <svg wire:loading wire:target="previousPage" class="animate-spin -ml-1 mr-2 h-4 w-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 0.879 5.824 2.339 8.021l2.66-1.73z"></path>
                    </svg>

                    <!-- آیکون -->
                    <svg wire:loading.remove wire:target="previousPage" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L10.586 10 7.293 6.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>

                <!-- شماره صفحات -->
                <div class="flex h-9 border border-gray-300 rounded-md overflow-hidden shadow-sm divide-x divide-gray-300">
                    @php
                        $start = max($families->currentPage() - 2, 1);
                        $end = min($start + 4, $families->lastPage());
                        if ($end - $start < 4 && $start > 1) {
                            $start = max(1, $end - 4);
                        }
                    @endphp

                    @if($start > 1)
                        <button type="button" wire:click="gotoPage(1, 'page')" wire:key="page-first" class="bg-white text-gray-600 hover:bg-gray-50 h-full px-3 inline-flex items-center justify-center text-sm">1</button>
                        @if($start > 2)
                            <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                        @endif
                    @endif

                    @for($i = $start; $i <= $end; $i++)
                        <button type="button" wire:click="gotoPage({{ $i }}, 'page')" wire:key="page-{{ $i }}" wire:loading.attr="disabled" wire:target="gotoPage" class="{{ ($families->currentPage() == $i) ? 'bg-green-100 text-green-800 font-medium' : 'bg-white text-gray-600 hover:bg-gray-50' }} h-full px-3 inline-flex items-center justify-center text-sm transition-colors duration-200">
                            <span wire:loading.remove wire:target="gotoPage({{ $i }}, 'page')">{{ $i }}</span>
                            <span wire:loading wire:target="gotoPage({{ $i }}, 'page')" class="inline-block">
                                <svg class="animate-spin h-4 w-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 0.879 5.824 2.339 8.021l2.66-1.73z"></path>
                                </svg>
                            </span>
                        </button>
                    @endfor

                    @if($end < $families->lastPage())
                        @if($end < $families->lastPage() - 1)
                            <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                        @endif
                        <button type="button" wire:click="gotoPage({{ $families->lastPage() }}, 'page')" wire:key="page-last" class="bg-white text-gray-600 hover:bg-gray-50 h-full px-3 inline-flex items-center justify-center text-sm">{{ $families->lastPage() }}</button>
                    @endif
                </div>

                <!-- دکمه صفحه بعد -->
                <button type="button" wire:click="nextPage('page')" wire:loading.attr="disabled" wire:target="nextPage" @if(!$families->hasMorePages()) disabled @endif class="{{ $families->hasMorePages() ? 'text-green-600 hover:bg-green-50 cursor-pointer' : 'text-gray-400 opacity-50 cursor-not-allowed' }} bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm ml-1 transition-colors duration-200">
                    <svg wire:loading.remove wire:target="nextPage" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span wire:loading wire:target="nextPage" class="inline-block">
                        <svg class="animate-spin h-4 w-4 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 0.879 5.824 2.339 8.021l2.66-1.73z"></path>
                        </svg>
                    </span>
                </button>
            </div>

            <!-- شمارنده - سمت چپ -->
            <div class="text-sm text-gray-600 order-3">
                نمایش {{ $families->firstItem() ?? 0 }} تا {{ $families->lastItem() ?? 0 }} از {{ $families->total() ?? 0 }} خانواده
            </div>
        </div>
    </div>
    @endif
        </div>
    </div>

    {{-- لوادینگ --}}
    <div class="fixed inset-0 flex items-center justify-center z-50 bg-black bg-opacity-50" wire:loading wire:target="changeTab, approveSelected, returnToPendingSelected, deleteSelected, approveAndContinueSelected">
        <div class="bg-white p-8 rounded-xl shadow-2xl flex items-center max-w-lg mx-auto">
            <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-xl font-semibold text-gray-800">در حال بارگذاری...</span>
        </div>
    </div>

    {{-- مودال‌ها --}}
    <div>
        <!-- مودال حذف خانواده - نسخه بدون Alpine -->
        @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- پس‌زمینه تاری -->
                <div class="fixed inset-0 transition-opacity" aria-hidden="true" wire:click="closeDeleteModal">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
    </div>

                <!-- این المان برای مرکز قرار دادن مودال استفاده می‌شود -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- مودال -->
                <div class="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                    <!-- سربرگ مودال -->
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <!-- دکمه بستن در گوشه بالا سمت چپ -->
                        <button wire:click="closeDeleteModal" type="button" class="absolute top-3 left-3 text-gray-400 hover:text-gray-500">
                            <span class="sr-only">بستن</span>
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">حذف خانواده</h3>

                        <div class="text-center text-xl text-red-500 font-bold mb-6">
                            @if(count($selected) > 1)
                                حذف {{ count($selected) }} خانواده ({{ $totalMembers }} نفر) مورد تایید است
                            @else
                                حذف این خانواده مورد تایید است
                            @endif
                        </div>

                        <div class="text-gray-700 mb-6 leading-relaxed">
                            حذف این خانواده ها به منزله بررسی و اطمینان از عدم تطابق آنها با معیار های سازمان شماست و
                            پس از حذف این خانواده ها به قسمت "حذم شده ها" منتقل میشوند.
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 mb-2">لطفا دلیل عدم تطابق را انتخاب کنید:</label>
                            <select wire:model.defer="deleteReason" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 rounded-md shadow-sm py-2 px-3">
                                <option value="">انتخاب کنید...</option>
                                <option value="incomplete_info">اطلاعات ناقص</option>
                                <option value="duplicate">تکراری</option>
                                <option value="not_eligible">عدم احراز شرایط</option>
                                <option value="address_problem">مشکل در آدرس سکونت</option>
                                <option value="other">سایر موارد</option>
                            </select>
                            @error('deleteReason') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- دکمه‌های اقدام -->
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse justify-between">
                        <!-- دکمه حذف -->
                        <div class="flex items-center gap-3">
                            <button
                                wire:click="deleteSelected"
                                wire:loading.attr="disabled"
                                wire:target="deleteSelected"
                                type="button"
                                class="inline-flex items-center justify-center px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200">

                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                <span class="ml-2">حذف نهایی</span>
                            </button>
                            <span class="text-red-500 text-sm" x-show="deleteReason === ''">لطفا دلیل حذف را انتخاب کنید</span>
                        </div>

                        <!-- دکمه‌های سمت راست -->
                        <div class="flex items-center gap-3">
                            <button wire:click="clearDeleteReason" type="button" class="inline-flex items-center justify-center px-4 py-2.5 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                                <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                <span class="ml-2">پاک کردن</span>
                            </button>
                            <button wire:click="closeDeleteModal" type="button" class="inline-flex items-center justify-center px-4 py-2.5 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                                <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span class="ml-2">بستن</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- مودال تخصیص سهم --}}
    @livewire('insurance.share-allocation-modal')
    {{-- پایان مودال تخصیص سهم --}}

    @stack('scripts')

    <!-- مودال فیلتر -->
    <div x-show="showFilterModal"
        @keydown.escape.window="showFilterModal = false"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4"
        style="display: none;">

        <div @click.away="showFilterModal = false"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95"
             class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">

            <!-- هدر مودال -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">فیلتر جدول</h3>
                        <p class="text-sm text-gray-600">لطفاً فیلترهای مدنظر خود را اعمال کنید. انتخاب محدوده زمانی اجباری است.</p>
                    </div>
                </div>
                <button @click="showFilterModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- محتوای مودال -->
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <!-- جدول فیلترها -->
                <div class="overflow-x-auto bg-white rounded-lg border border-gray-200">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100 text-sm text-gray-700">
                                <th class="px-6 py-4 text-right border-b border-gray-200 font-semibold min-w-[140px]">نوع فیلتر</th>
                                <th class="px-6 py-4 text-right border-b border-gray-200 font-semibold min-w-[200px]">جزئیات فیلتر</th>
                                <th class="px-6 py-4 text-right border-b border-gray-200 font-semibold min-w-[120px]">شرط</th>
                                <th class="px-6 py-4 text-center border-b border-gray-200 font-semibold w-20">حذف</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(filter, index) in filters" :key="index">
                                <tr class="hover:bg-blue-25 transition-colors duration-200">
                                    <!-- نوع فیلتر -->
                                    <td class="px-6 py-5">
                                        <div class="relative">
                                            <select x-model="filter.type" @change="updateFilterLabel(index)"
                                                    class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 appearance-none cursor-pointer transition-all duration-200"
                                                    style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                                <option value="status">وضعیت</option>
                                                <option value="province">استان</option>
                                                <option value="city">شهر</option>
                                                <option value="deprivation_rank">رتبه</option>
                                                <option value="charity">خیریه معرف</option>
                                                <option value="members_count">تعداد اعضا</option>
                                                <option value="created_at">تاریخ پایان بیمه</option>
                                                <option value="weighted_score">امتیاز وزنی</option>
                                            </select>
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- جزئیات فیلتر -->
                                    <td class="px-6 py-5">
                                        <div x-show="filter.type === 'status'" class="relative">
                                            <select x-model="filter.value"
                                                    class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 appearance-none cursor-pointer transition-all duration-200"
                                                    style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                                <option value="">انتخاب وضعیت...</option>
                                                <option value="insured">بیمه شده</option>
                                                <option value="uninsured">بدون بیمه</option>
                                                <option value="pending">در انتظار بررسی</option>
                                                <option value="approved">تایید شده</option>
                                                <option value="rejected">رد شده</option>
                                            </select>
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>

                                        <div x-show="filter.type === 'province'" class="relative">
                                            <select x-model="filter.value"
                                                    class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 appearance-none cursor-pointer transition-all duration-200"
                                                    style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                                <option value="">انتخاب استان...</option>
                                                @if(isset($provinces))
                                                    @foreach($provinces as $province)
                                                        <option value="{{ $province->id }}">{{ $province->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>

                                        <div x-show="filter.type === 'city'" class="relative">
                                            <select x-model="filter.value"
                                                    class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 appearance-none cursor-pointer transition-all duration-200"
                                                    style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                                <option value="">انتخاب شهر...</option>
                                                @if(isset($cities))
                                                    @foreach($cities as $city)
                                                        <option value="{{ $city->id }}">{{ $city->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>

                                        <div x-show="filter.type === 'deprivation_rank'" class="relative">
                                            <select x-model="filter.value"
                                                    class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 appearance-none cursor-pointer transition-all duration-200"
                                                    style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                                <option value="">انتخاب رتبه محرومیت...</option>
                                                <option value="high">محرومیت بالا (1-3)</option>
                                                <option value="medium">محرومیت متوسط (4-6)</option>
                                                <option value="low">محرومیت پایین (7-10)</option>
                                            </select>
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- Special Disease Filter -->
                                        <div x-show="filter.type === 'special_disease'" class="relative">
                                            <select x-model="filter.value"
                                                    class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 appearance-none cursor-pointer transition-all duration-200"
                                                    style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                                <option value="">دارد/ندارد بیماری خاص...</option>
                                                <option value="true">دارد بیماری خاص</option>
                                                <option value="false">ندارد بیماری خاص</option>
                                            </select>
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>

                                        <div x-show="filter.type === 'charity'" class="relative">
                                            <select x-model="filter.value"
                                                    class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 appearance-none cursor-pointer transition-all duration-200"
                                                    style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                                <option value="">انتخاب خیریه...</option>
                                                @if(isset($organizations))
                                                    @foreach($organizations as $organization)
                                                        <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>

                                        <div x-show="filter.type === 'members_count'">
                                            <input type="number" x-model="filter.value" min="1" max="20"
                                                   class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 px-4 transition-all duration-200"
                                                   placeholder="تعداد اعضا">
                                        </div>

                                        <div x-show="filter.type === 'weighted_score'" class="flex space-x-4 rtl:space-x-reverse">
                                            <div class="w-1/2">
                                                <input type="number" x-model="filter.min" placeholder="حداقل امتیاز" step="0.1"
                                                       class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 transition-all duration-200">
                                            </div>
                                            <div class="w-1/2">
                                                <input type="number" x-model="filter.max" placeholder="حداکثر امتیاز" step="0.1"
                                                       class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 transition-all duration-200">
                                            </div>
                                        </div>

                                        <div x-show="filter.type === 'created_at'">
                                            <input type="date" x-model="filter.value"
                                                   class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 px-4 transition-all duration-200">
                                        </div>
                                    </td>

                                    <!-- شرط -->
                                    <td class="px-6 py-5">
                                        <div class="relative">
                                            <select x-model="filter.operator" @change="updateFilterLabel(index)"
                                                    class="w-full h-12 border-2 border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white px-4 appearance-none cursor-pointer transition-all duration-200"
                                                    style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                                                <option value="equals">برابر</option>
                                                <option value="not_equals">مخالف</option>
                                                <template x-if="['members_count', 'created_at'].includes(filter.type)">
                                                    <template>
                                                        <option value="greater_than">بیشتر از</option>
                                                        <option value="less_than">کمتر از</option>
                                                    </template>
                                                </template>
                                            </select>
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- حذف -->
                                    <td class="px-6 py-5 text-center">
                                        <button @click="removeFilter(index)"
                                                class="inline-flex items-center justify-center w-10 h-10 bg-red-50 hover:bg-red-100 text-red-500 hover:text-red-700 rounded-lg transition-all duration-200 group">
                                            <svg class="w-5 h-5 group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <!-- خط اضافه کردن فیلتر جدید -->
                            <tr>
                                <td colspan="4" class="px-6 py-6">
                                    <button @click="addFilter()"
                                            class="w-full flex items-center justify-center gap-3 p-4 text-green-700 hover:text-green-800 hover:bg-green-50 rounded-xl border-2 border-dashed border-green-300 hover:border-green-400 transition-all duration-200 group">
                                        <svg class="w-6 h-6 group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        <span class="font-medium">افزودن فیلتر جدید</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- فوتر مودال -->
            <div class="flex items-center justify-between p-6 border-t border-gray-200 bg-gray-50">
                <div class="flex gap-2">
                    <button wire:click="resetToDefault" @click="showFilterModal = false"
                            class="inline-flex items-center px-4 py-2.5 bg-gray-100 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        بازگشت به پیشفرض
                    </button>

                    <button wire:click="testFilters"
                            class="inline-flex items-center px-4 py-2.5 bg-blue-100 border border-blue-300 rounded-lg text-sm font-medium text-blue-700 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        تست فیلترها
                    </button>
                </div>
       <!-- تایید فیلتر رتبه -->
                <button @click="setTimeout(() => { $wire.applyFilters(); showFilterModal = false; }, 100)"
                        class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg text-sm font-medium hover:from-green-600 hover:to-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors">
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    تایید و اعمال فیلترها
                </button>
                       <!-- تایید فیلتر رتبه -->

            </div>
        </div>
    </div>

    <!-- مودال تنظیمات رتبه -->
    <div x-show="showRankModal"

     @keydown.escape.window="showRankModal = false"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform scale-90"
     x-transition:enter-end="opacity-100 transform scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform scale-100"
     x-transition:leave-end="opacity-0 transform scale-90"
     x-cloak
     class="fixed inset-0 z-30 flex items-center justify-center p-4 bg-black bg-opacity-50">

        <div @click.away="showRankModal = false"
         class="w-full max-w-3xl max-h-[90vh] overflow-y-auto bg-white rounded-lg">

        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-2xl font-bold text-gray-800">تنظیمات رتبه</h3>
            <button @click="showRankModal = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <p class="mb-6 text-center text-gray-700">
                لطفا برای <span class="font-bold">معیار پذیرش</span> لیست شده وزن انتخاب کنید تا پس از تایید در رتبه بندی ها اعمال شود
            </p>

            <!-- جدول معیارهای پذیرش -->
            <div class="overflow-x-auto mb-6">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-700 border-b">
                            <th class="px-3 py-3 text-center">انتخاب</th>
                            <th class="px-3 py-3 text-right">معیار پذیرش</th>
                            <th class="px-3 py-3 text-center">وزن (0-10)</th>
                            <th class="px-3 py-3 text-center">شرح</th>
                            <th class="px-3 py-3 text-center">نیاز به مدرک؟</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!empty($availableRankSettings))
                            @foreach($availableRankSettings as $criterion)
                                <tr class="hover:bg-gray-50 border-b border-gray-200" wire:key="rank-setting-{{ $criterion->id }}">
                                    <td class="px-3 py-3 text-center">
                                        <input type="checkbox" wire:model.live="selectedCriteria.{{ $criterion->id }}" class="form-checkbox h-5 w-5 text-green-500">
                                    </td>
                                    <td class="px-3 py-3 flex justify-between items-center">
                                        <div class="flex space-x-2 rtl:space-x-reverse">
                                            <button wire:click="editRankSetting({{ $criterion->id }})" class="text-orange-500 hover:text-orange-700 ml-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="px-4 py-2 rounded-md text-center w-full" style="background-color: {{ $criterion->color ?? '#e5f7eb' }}">
                                            {{ $criterion->name }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-center">{{ $criterion->weight }}</td>
                                    <td class="px-3 py-3 text-center">
                                        <div class="relative group">
                                            <button type="button" class="text-gray-500 hover:text-gray-700">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </button>
                                            <div class="fixed z-20 hidden group-hover:block bg-white border border-gray-200 rounded-lg shadow-lg p-4 max-w-xs">
                                                <p class="text-sm text-gray-700">{{ $criterion->description }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        @if($criterion->requires_document)
                                            <span class="text-green-500">✓</span>
                                        @else
                                            <span class="text-red-500">✗</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">
                                    معیار رتبه‌بندی تعریف نشده است
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- باکس اضافه کردن معیار جدید -->
            <div x-data="{ showNewCriterionForm: false }" x-init="$watch('$wire.editingRankSettingId', value => { if(value) showNewCriterionForm = true; })" class="mb-6">
                <!-- دکمه اضافه کردن معیار جدید -->
                <div x-show="!showNewCriterionForm" @click="showNewCriterionForm = true" class="border border-green-500 rounded-lg p-4 flex flex-col items-center justify-center cursor-pointer hover:bg-green-50 transition-all duration-300">
                    <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center mb-2">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <span class="text-green-600 font-medium">افزودن معیار جدید</span>
                </div>

                <!-- فرم افزودن/ویرایش معیار -->
                <div x-show="showNewCriterionForm" class="border border-green-500 rounded-lg p-5 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900" x-text="$wire.editingRankSettingId ? 'ویرایش وزن معیار' : 'افزودن معیار جدید'"></h3>
                    </div>
                    
                    <!-- نمایش نام معیار به صورت فقط خواندنی در حالت ویرایش -->
                    <div x-show="$wire.editingRankSettingId" class="mb-4">
                        <label class="block text-gray-700 mb-2">نام معیار</label>
                        <div class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-100 text-gray-600" x-text="$wire.rankSettingName"></div>
                    </div>
                    
                    <!-- فقط در حالت افزودن معیار جدید نمایش داده شود -->
                    <div x-show="!$wire.editingRankSettingId" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 mb-2">اسم معیار پذیرش</label>
                            <input type="text" wire:model="rankSettingName"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">نیاز به مدرک؟</label>
                            <div class="relative">
                                <select wire:model="rankSettingNeedsDoc"
                                        class="w-full px-3 py-2 pr-8 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 rtl text-right appearance-none">
                                    <option value="1">بله</option>
                                    <option value="0">خیر</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center px-2 text-gray-700">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- فیلد وزن که همیشه نمایش داده می‌شود -->
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">وزن معیار پذیرش</label>
                        <div class="relative w-32">
                            <select wire:model="rankSettingWeight"
                                    class="w-full px-3 py-2 pr-8 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 rtl text-right appearance-none">
                                @for($i = 0; $i <= 10; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center px-2 text-gray-700">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- فیلد توضیحات فقط در حالت افزودن معیار جدید -->
                    <div x-show="!$wire.editingRankSettingId" class="mb-4">
                        <label class="block text-gray-700 mb-2">شرح معیار پذیرش در اینجا ذکر میشود و مدارک و نحوه پذیرش در اینجا تعیین میشود</label>
                        <textarea wire:model="rankSettingDescription" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                    </div>

                    <div class="flex justify-center space-x-4 rtl:space-x-reverse">
                        <button @click="showNewCriterionForm = false; $wire.resetRankSettingForm();" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                            انصراف
                        </button>
                        <button wire:click="saveRankSetting" @click="showNewCriterionForm = false" class="bg-green-500 text-white px-6 py-2 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            ذخیره
                        </button>
                    </div>
                </div>
            </div>

            <!-- دکمه های پایینی -->
            <div class="flex justify-between">
                <button wire:click="resetToDefaults" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-md">
                    بازگشت به تنظیمات پیشفرض
                </button>
                <button wire:click="applyCriteria" class="bg-green-500 text-white px-6 py-3 rounded-md flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    تایید و اعمال تنظیمات جدید
                </button>
            </div>
        </div>
     </div>
    </div>
</div>
</div>
