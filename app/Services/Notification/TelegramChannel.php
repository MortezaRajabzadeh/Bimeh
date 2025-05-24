<?php

namespace App\Services\Notification;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Exception\TelegramException;
use Illuminate\Support\Facades\Log;

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

    /**
     * ارسال پیام متنی ساده
     *
     * @param int|string $chatId شناسه چت یا کاربر
     * @param string $message متن پیام
     * @return bool
     */
    public function send(int|string $chatId, string $message): bool
    {
        try {
            $result = Request::sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
            ]);
            
            return $result->isOk();
        } catch (TelegramException $e) {
            Log::error('خطا در ارسال پیام تلگرام: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ارسال پیام با دکمه‌های درون خطی
     *
     * @param int|string $chatId شناسه چت یا کاربر
     * @param string $message متن پیام
     * @param array $buttons آرایه‌ای از دکمه‌ها در قالب [['text' => 'عنوان', 'callback_data' => 'داده'], ...]
     * @return bool
     */
    public function sendWithInlineButtons(int|string $chatId, string $message, array $buttons): bool
    {
        try {
            $inlineKeyboard = ['inline_keyboard' => array_chunk($buttons, 2)];
            
            $result = Request::sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'reply_markup' => json_encode($inlineKeyboard),
                'parse_mode' => 'HTML',
            ]);
            
            return $result->isOk();
        } catch (TelegramException $e) {
            Log::error('خطا در ارسال پیام با دکمه‌های درون خطی: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ارسال عکس به همراه توضیحات
     *
     * @param int|string $chatId شناسه چت یا کاربر
     * @param string $photoUrl آدرس عکس (URL یا فایل ID)
     * @param string $caption توضیحات عکس (اختیاری)
     * @return bool
     */
    public function sendPhoto(int|string $chatId, string $photoUrl, string $caption = ''): bool
    {
        try {
            $result = Request::sendPhoto([
                'chat_id' => $chatId,
                'photo' => $photoUrl,
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ]);
            
            return $result->isOk();
        } catch (TelegramException $e) {
            Log::error('خطا در ارسال عکس: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ارسال فایل به عنوان سند
     *
     * @param int|string $chatId شناسه چت یا کاربر
     * @param string $fileUrl آدرس فایل (URL یا فایل ID)
     * @param string $caption توضیحات فایل (اختیاری)
     * @return bool
     */
    public function sendDocument(int|string $chatId, string $fileUrl, string $caption = ''): bool
    {
        try {
            $result = Request::sendDocument([
                'chat_id' => $chatId,
                'document' => $fileUrl,
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ]);
            
            return $result->isOk();
        } catch (TelegramException $e) {
            Log::error('خطا در ارسال سند: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * دریافت اطلاعات درباره ربات
     *
     * @return array|null
     */
    public function getMe(): ?array
    {
        try {
            $result = Request::getMe();
            
            if ($result->isOk()) {
                return $result->getResult();
            }
            
            return null;
        } catch (TelegramException $e) {
            Log::error('خطا در دریافت اطلاعات ربات: ' . $e->getMessage());
            return null;
        }
    }
}
