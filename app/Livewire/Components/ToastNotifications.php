<?php

namespace App\Livewire\Components;

use Livewire\Component;
use Illuminate\Support\Facades\Session;

class ToastNotifications extends Component
{
    public $toasts = [];

    protected $listeners = ['toast'];

    public function mount()
    {
        // خواندن پیام‌های فلش از session و تبدیل آن‌ها به toast
        $this->handleFlashMessages();
        
        // پاکسازی session بعد از خواندن پیام‌ها
        Session::forget(['success', 'error', 'warning', 'info']);
    }

    /**
     * تبدیل پیام‌های فلش به toast
     */
    protected function handleFlashMessages()
    {
        $this->toasts = [];
        
        // بررسی پیام‌های موفقیت
        if (Session::has('success')) {
            $this->toast(Session::get('success'), 'success');
        }
        
        // بررسی پیام‌های خطا
        if (Session::has('error')) {
            $this->toast(Session::get('error'), 'error');
        }
        
        // بررسی پیام‌های هشدار
        if (Session::has('warning')) {
            $this->toast(Session::get('warning'), 'warning');
        }
        
        // بررسی پیام‌های اطلاع‌رسانی
        if (Session::has('info')) {
            $this->toast(Session::get('info'), 'info');
        }
    }

    public function toast($message, $type = 'success')
    {
        $id = uniqid();
        
        // اگر پیام به شکل آرایه باشد، فرمت آن را اصلاح می‌کنیم
        if (is_array($message) && isset($message['message'])) {
            $type = $message['type'] ?? $type;
            $message = $message['message'];
        }
        
        // اطمینان حاصل می‌کنیم که پیام حتما رشته باشد
        $message = is_array($message) ? json_encode($message) : (string)$message;
        
        $this->toasts[] = [
            'id' => $id,
            'message' => $message,
            'type' => $type,
        ];

        $this->dispatch('toast-start-timer', id: $id);
        
        // برنامه‌ریزی برای حذف اعلان بعد از 10 ثانیه
        $this->dispatch('removeToastAfterDelay', id: $id, delay: 10000);
    }

    public function removeToast($id)
    {
        $this->toasts = collect($this->toasts)
            ->reject(fn ($toast) => $toast['id'] === $id)
            ->toArray();
    }

    public function render()
    {
        return view('livewire.components.toast-notifications');
    }
} 
