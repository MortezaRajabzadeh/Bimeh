<?php

namespace App\Services\Notification;


use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;

class TelegramChannel
{
    protected Telegram $bot;

    public function __construct()
    {
        $this->bot = new Telegram(
            config('telegram.bot_token'),
            config('telegram.bot_username')
        );
    }

    public function send(int|string $chatId, string $message): bool
    {
        return Request::sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
        ])->isOk();
    }
}
