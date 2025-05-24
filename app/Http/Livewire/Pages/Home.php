<?php

namespace App\Http\Livewire\Pages;

use Livewire\Component;
use Livewire\Attributes\Title;

class Home extends Component
{
    public bool $isAppActive = false;
    
    /**
     * مقداردهی اولیه کامپوننت
     */
    public function mount()
    {
        $this->isAppActive = cache()->get('app_status', false);
    }
    
    /**
     * تغییر وضعیت فعال بودن اپلیکیشن
     */
    public function toggleApp()
    {
        $this->isAppActive = !$this->isAppActive;
        cache()->put('app_status', $this->isAppActive);

        $message = $this->isAppActive 
            ? 'اپلیکیشن با موفقیت فعال شد.' 
            : 'اپلیکیشن غیرفعال شد.';

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message
        ]);
    }
    
    /**
     * مدیریت افزودن سفر جدید
     */
    public function addNewTrip()
    {
        if (!$this->isAppActive) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'لطفاً ابتدا اپلیکیشن را فعال کنید.'
            ]);
            return;
        }

        return $this->redirect('/trips/create', navigate: true);
    }
    
    /**
     * نمایش کامپوننت
     */
    #[Title('پینوتو - صفحه اصلی')]
    public function render()
    {
        return view('livewire.pages.home');
    }
} 