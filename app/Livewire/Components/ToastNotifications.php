<?php

namespace App\Livewire\Components;

use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class ToastNotifications extends Component
{
    public $toasts = [];

    protected $listeners = ['toast', 'notify', 'show-notification'];

    public function mount()
    {
        // خواندن پیام‌های فلش از session و تبدیل آن‌ها به toast
        $this->handleFlashMessages();
    }

    /**
     * تبدیل پیام‌های فلش به toast
     */
    protected function handleFlashMessages()
    {
        // بررسی پیام‌های موفقیت
        if (Session::has('success')) {
            $this->addToast(Session::get('success'), 'success', 8000);
            Session::forget('success');
        }
        
        if (Session::has('message')) {
            $this->addToast(Session::get('message'), 'success', 8000);
            Session::forget('message');
        }
        
        // بررسی پیام‌های خطا
        if (Session::has('error')) {
            $this->addToast(Session::get('error'), 'error', 12000);
            Session::forget('error');
        }
        
        // بررسی پیام‌های هشدار
        if (Session::has('warning')) {
            $this->addToast(Session::get('warning'), 'warning', 10000);
            Session::forget('warning');
        }
        
        // بررسی پیام‌های اطلاع‌رسانی
        if (Session::has('info')) {
            $this->addToast(Session::get('info'), 'info', 8000);
            Session::forget('info');
        }
    }

    /**
     * اضافه کردن toast جدید
     */
    private function addToast($message, $type = 'success', $duration = 8000, $persistent = false)
    {
        try {
            $id = uniqid('toast_', true);
            
            // اطمینان حاصل می‌کنیم که پیام حتما رشته باشد
            $message = is_array($message) ? json_encode($message, JSON_UNESCAPED_UNICODE) : (string)$message;
            
            // حداکثر تعداد توست‌ها (جلوگیری از انباشت)
            if (count($this->toasts) >= 5) {
                array_shift($this->toasts);
            }
            
            $this->toasts[] = [
                'id' => $id,
                'message' => $message,
                'type' => $type,
                'duration' => $duration,
                'persistent' => $persistent,
                'created_at' => now()->timestamp
            ];

            Log::info('Toast added', [
                'id' => $id,
                'type' => $type,
                'duration' => $duration,
                'persistent' => $persistent
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error adding toast: ' . $e->getMessage());
        }
    }

    /**
     * متد اصلی برای نمایش toast
     */
    public function toast($data)
    {
        try {
            // پردازش داده‌های ورودی
            if (is_string($data)) {
                $this->addToast($data, 'success', 8000);
                return;
            }
            
            if (is_array($data)) {
                $message = $data['message'] ?? 'پیام خالی';
                $type = $data['type'] ?? 'success';
                $duration = $data['duration'] ?? $this->getDefaultDuration($type);
                $persistent = $data['persistent'] ?? false;
                
                $this->addToast($message, $type, $duration, $persistent);
            }
            
        } catch (\Exception $e) {
            Log::error('Error in toast method: ' . $e->getMessage());
            // در صورت خطا، حداقل یک پیام ساده نمایش دهیم
            $this->addToast('خطا در نمایش پیام', 'error', 8000);
        }
    }

    /**
     * نمایش نوتیفیکیشن
     */
    public function notify($data = null)
    {
        if ($data === null) {
            return;
        }
        
        $this->toast($data);
    }

    /**
     * نمایش نوتیفیکیشن (listener جدید)
     */
    public function showNotification($data = null)
    {
        if ($data === null) {
            return;
        }
        
        $this->toast($data);
    }

    /**
     * دریافت مدت زمان پیش‌فرض بر اساس نوع پیام
     */
    private function getDefaultDuration($type)
    {
        return match($type) {
            'success' => 8000,
            'error' => 12000,
            'warning' => 10000,
            'info' => 8000,
            default => 8000
        };
    }

    /**
     * حذف toast
     */
    public function removeToast($id)
    {
        $this->toasts = collect($this->toasts)
            ->reject(fn ($toast) => $toast['id'] === $id)
            ->values()
            ->toArray();
            
        Log::info('Toast removed', ['id' => $id]);
    }

    /**
     * پاک کردن تمام توست‌ها
     */
    public function clearAllToasts()
    {
        $this->toasts = [];
    }

    public function render()
    {
        return view('livewire.components.toast-notifications');
    }
} 
