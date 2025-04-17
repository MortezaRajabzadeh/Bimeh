<?php
namespace App\Services\Notification;

class NotificationService
{
    protected SmsChannel $sms;
    protected TelegramChannel $telegram;
    protected EmailChannel $email;

    public function __construct(
        SmsChannel $sms,
        TelegramChannel $telegram,
        EmailChannel $email
    ) {
        $this->sms = $sms;
        $this->telegram = $telegram;
        $this->email = $email;
    }

    public function viaSms(string $mobile, string $message): bool
    {
        return $this->sms->send($mobile, $message);
    }

    public function viaTelegram(int|string $chatId, string $message): bool
    {
        return $this->telegram->send($chatId, $message);
    }

    public function viaEmail(string $to, string $subject, string $message): void
    {
        $this->email->send($to, $subject, $message);
    }
}
