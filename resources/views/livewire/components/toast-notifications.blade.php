<div>
    @if(count($toasts ?? []))
        <div class="fixed top-5 left-5 z-50 space-y-3 toast-container" id="toast-container">
            @foreach($toasts as $toast)
                <div
                    x-data="{ show: true }"
                    x-show="show"
                    x-init="
                        // تایمر برای بستن خودکار هر نوتیفیکیشن
                        @if(!($toast['persistent'] ?? false))
                            setTimeout(() => show = false, {{ $toast['duration'] ?? 8000 }});
                            setTimeout(() => $wire.removeToast('{{ $toast['id'] }}'), {{ ($toast['duration'] ?? 8000) + 500 }});
                        @endif
                    "
                    x-transition:enter="transform ease-out duration-300 transition"
                    x-transition:enter-start="opacity-0 -translate-x-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="toast-notification toast-{{ $toast['type'] }} max-w-lg w-full notification-card"
                    role="{{ $toast['type'] === 'error' ? 'alert' : 'status' }}"
                    aria-live="polite">
                    <div class="toast-content">
                        <div class="toast-icon">
                            @if($toast['type'] === 'success')
                                <svg class="icon-success" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @elseif($toast['type'] === 'error')
                                <svg class="icon-error" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @elseif($toast['type'] === 'warning')
                                <svg class="icon-warning" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            @else
                                <svg class="icon-info" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                        </div>
                        <div class="toast-message">
                            {{ $toast['message'] }}
                            @if($toast['persistent'] ?? false)
                                <div class="text-xs mt-1 text-gray-500">
                                    (این پیام تا زمان بستن نمایش داده می‌شود)
                                </div>
                            @endif
                        </div>
                        <button
                            @click="show = false; $wire.removeToast('{{ $toast['id'] }}')"
                            class="toast-close"
                            aria-label="بستن">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // لیسنر برای رویداد notify و نوتیفیکیشن‌های سیستمی
        const notifyEventListeners = ['notify', 'show-notification', 'toast'];
        
        notifyEventListeners.forEach(eventName => {
            window.addEventListener(eventName, function(event) {
                if (event.detail && (event.detail.message || typeof event.detail === 'string')) {
                    if (typeof Livewire !== 'undefined') {
                        const eventData = typeof event.detail === 'string' 
                            ? { message: event.detail, type: 'info' }
                            : event.detail;
                            
                        Livewire.dispatch(eventName, eventData);
                    }
                }
            });
        });
    });
</script>

<style>
    .toast-container {
        direction: ltr;
        min-width: 300px;
        max-width: 500px;
        pointer-events: none; /* برای عبور کلیک از کانتینر */
    }
    
    .notification-card {
        pointer-events: auto; /* برای فعال کردن کلیک روی کارت */
    }
    
    .toast-notification {
        border-width: 1px;
        border-style: solid;
        border-radius: 0.75rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        margin-bottom: 0.75rem;
        overflow: hidden;
    }
    
    .toast-content {
        display: flex;
        align-items: flex-start;
        padding: 1rem 1.25rem;
    }
    
    .toast-icon {
        flex-shrink: 0;
        margin-right: 0.75rem;
        margin-top: 0.125rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .toast-icon svg {
        width: 1.5rem;
        height: 1.5rem;
    }
    
    .toast-message {
        flex: 1;
        font-size: 0.875rem;
        line-height: 1.25rem;
        font-weight: 500;
        white-space: pre-line; /* برای حفظ خطوط جدید */
    }
    
    .toast-close {
        margin-left: 0.5rem;
        color: rgba(107, 114, 128, 0.7);
        transition: color 0.15s ease-in-out;
        height: 1.5rem;
        width: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 9999px;
    }
    
    .toast-close:hover {
        color: rgba(107, 114, 128, 1);
        background-color: rgba(0, 0, 0, 0.05);
    }
    
    /* استایل‌های انواع توست */
    .toast-success {
        background-color: #f0fdf4;
        border-color: #86efac;
    }
    
    .toast-success .toast-icon svg {
        color: #16a34a;
    }
    
    .toast-error {
        background-color: #fef2f2;
        border-color: #fca5a5;
    }
    
    .toast-error .toast-icon svg {
        color: #dc2626;
    }
    
    .toast-warning {
        background-color: #fffbeb;
        border-color: #fcd34d;
    }
    
    .toast-warning .toast-icon svg {
        color: #d97706;
    }
    
    .toast-info {
        background-color: #eff6ff;
        border-color: #93c5fd;
    }
    
    .toast-info .toast-icon svg {
        color: #2563eb;
    }
</style> 
