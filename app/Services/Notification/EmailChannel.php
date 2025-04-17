<?php
namespace App\Services\Notification;

use Illuminate\Support\Facades\Mail;

class EmailChannel
{
    public function send(string $to, string $subject, string $body): void
    {
        Mail::raw($body, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });
    }
}
