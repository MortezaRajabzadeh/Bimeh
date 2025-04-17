<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationPopup extends Component
{
    public string $type = 'info';
    public string $message = '';
    public string $position = 'top-right';
    public int $duration = 3000;
    public bool $dismissible = true;
    public bool $show = true;

    protected $listeners = ['showNotification' => 'show'];

    public function show($message, $type = 'info', $position = 'top-right', $duration = 3000)
    {
        $this->message = $message;
        $this->type = $type;
        $this->position = $position;
        $this->duration = $duration;
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
