<?php

namespace App\Http\Livewire;

use Livewire\Component;

class NotificationPopup extends Component
{
    public $show = false;
    public $message = '';
    public $type = 'info';
    public $position = 'top-right';
    public $duration = 20000;
    public $dismissible = true;

    protected $listeners = ['show-toast' => 'showToast'];

    public function showToast($data)
    {
        $this->type = $data['type'] ?? 'info';
        $this->message = $data['message'] ?? '';
        $this->position = $data['position'] ?? 'top-right';
        $this->duration = $data['duration'] ?? 20000;
        $this->dismissible = $data['dismissible'] ?? true;
        $this->show = true;
    }

    public function dismiss()
    {
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.notification-popup');
    }
} 