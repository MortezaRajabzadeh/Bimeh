<?php

namespace App\Livewire;

use Livewire\Component;

class SidebarToggle extends Component
{
    public bool $collapsed = false;

    public function mount()
    {
        // خواندن وضعیت اولیه از session اگر وجود داشته باشد
        $this->collapsed = session('sidebar_collapsed', false);
    }

    public function updatedCollapsed($value)
    {
        // ذخیره وضعیت در session برای استفاده در سمت سرور
        session(['sidebar_collapsed' => $value]);

        // ارسال رویداد به سایر کامپوننت‌ها (Livewire 3)
        $this->dispatch('sidebarToggled', $value);
    }

    public function render()
    {
        return view('livewire.sidebar-toggle');
    }
} 