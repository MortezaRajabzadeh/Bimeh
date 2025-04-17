<?php

namespace App\Http\Controllers;

use App\Services\Notification\TelegramChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Exception\TelegramException;

class TelegramController extends Controller
{
    protected TelegramChannel $telegramChannel;
    
    public function __construct(TelegramChannel $telegramChannel)
    {
        $this->telegramChannel = $telegramChannel;
    }
    
    /**
     * پردازش webhook تلگرام
     */
    public function handle(Request $request)
    {
        $token = config('telegram.bot_token');
        $username = config('telegram.bot_username');
        
        if (empty($token) || empty($username)) {
            Log::error('تنظیمات ربات تلگرام پیکربندی نشده است.');
            return response()->json(['status' => 'error', 'message' => 'Bot settings not configured.'], 500);
        }
        
        try {
            // ایجاد نمونه Telegram
            $telegram = new Telegram($token, $username);
            
            // تنظیم webhook
            $telegram->enableAdmin(env('TELEGRAM_ADMIN_USER_ID', 0));
            
            // پردازش webhook
            $telegram->handle();
            
            return response()->json(['status' => 'success']);
        } catch (TelegramException $e) {
            Log::error('خطا در پردازش webhook تلگرام: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
} 