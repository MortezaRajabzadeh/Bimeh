<?php

namespace App\Http\Livewire\Components;

use Livewire\Component;

class ToastNotifications extends Component
{
    public $toasts = [];

    protected $listeners = ['toast'];

    public function mount()
    {
        $this->toasts = [];
    }

    public function toast($message, $type = 'success')
    {
        $id = uniqid();
        $this->toasts[] = [
            'id' => $id,
            'message' => $message,
            'type' => $type,
        ];

        $this->dispatch('toast-start-timer', id: $id);
    }

    public function removeToast($id)
    {
        $this->toasts = collect($this->toasts)
            ->reject(fn ($toast) => $toast['id'] === $id)
            ->toArray();
    }

    public function render()
    {
        return view('livewire.components.toast-notifications', [
            'toasts' => $this->toasts ?? [],
        ]);
    }
} 