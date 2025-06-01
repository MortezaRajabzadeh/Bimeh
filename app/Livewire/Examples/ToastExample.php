<?php

namespace App\Livewire\Examples;

use Livewire\Component;

class ToastExample extends Component
{
    public function showSuccessToast()
    {
        $this->dispatch('toast', 'این یک پیام موفقیت است!', 'success');
    }
    
    public function showErrorToast()
    {
        $this->dispatch('toast', 'این یک پیام خطا است!', 'error');
    }
    
    public function showWarningToast()
    {
        $this->dispatch('toast', 'این یک پیام هشدار است!', 'warning');
    }
    
    public function showInfoToast()
    {
        $this->dispatch('toast', 'این یک پیام اطلاع‌رسانی است!', 'info');
    }
    
    public function render()
    {
        return view('livewire.examples.toast-example');
    }
}
