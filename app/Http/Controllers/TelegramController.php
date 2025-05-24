<?php

namespace App\Http\Controllers;

use App\Services\Notification\TelegramChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Entities\ServerResponse;

class TelegramController extends Controller
{
    protected TelegramChannel $telegramChannel;
    
    public function __construct(TelegramChannel $telegramChannel)
    {
        $this->telegramChannel = $telegramChannel;
    }
    
    /**
     * پردازش webhook تلگرام
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
    
    /**
     * تنظیم webhook تلگرام
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function setupWebhook()
    {
        $token = config('telegram.bot_token');
        $username = config('telegram.bot_username');
        
        if (empty($token) || empty($username)) {
            return response()->json(['status' => 'error', 'message' => 'تنظیمات ربات تلگرام پیکربندی نشده است.'], 500);
        }
        
        try {
            $telegram = new Telegram($token, $username);
            
            // آدرس وب‌هوک
            $webhookUrl = url('/telegram/webhook');
            
            // پاک کردن وب‌هوک قبلی
            $result = $telegram->deleteWebhook();
            
            if (!$result->isOk()) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'خطا در حذف وب‌هوک قبلی: ' . $result->getDescription()
                ], 500);
            }
            
            // تنظیم وب‌هوک جدید
            $result = $telegram->setWebhook($webhookUrl);
            
            if ($result->isOk()) {
                return response()->json([
                    'status' => 'success', 
                    'message' => 'وب‌هوک با موفقیت تنظیم شد.',
                    'webhook_url' => $webhookUrl
                ]);
            } else {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'خطا در تنظیم وب‌هوک: ' . $result->getDescription()
                ], 500);
            }
        } catch (TelegramException $e) {
            Log::error('خطا در تنظیم webhook تلگرام: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * دریافت اطلاعات ربات
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBotInfo()
    {
        $botInfo = $this->telegramChannel->getMe();
        
        if ($botInfo === null) {
            return response()->json(['status' => 'error', 'message' => 'خطا در دریافت اطلاعات ربات'], 500);
        }
        
        // بررسی وضعیت webhook
        try {
            $token = config('telegram.bot_token');
            $username = config('telegram.bot_username');
            
            $telegram = new Telegram($token, $username);
            
            // دریافت اطلاعات webhook با استفاده از متد Request
            $webhookInfoResponse = \Longman\TelegramBot\Request::getWebhookInfo();
            
            if (!$webhookInfoResponse->isOk()) {
                throw new TelegramException('خطا در دریافت اطلاعات webhook: ' . $webhookInfoResponse->getDescription());
            }
            
            $webhookInfo = $webhookInfoResponse->getResult();
            
            return response()->json([
                'status' => 'success',
                'bot' => [
                    'id' => $botInfo['id'] ?? null,
                    'username' => $botInfo['username'] ?? null,
                    'first_name' => $botInfo['first_name'] ?? null,
                    'can_join_groups' => $botInfo['can_join_groups'] ?? null,
                    'can_read_all_group_messages' => $botInfo['can_read_all_group_messages'] ?? null,
                ],
                'webhook' => [
                    'url' => $webhookInfo->getUrl(),
                    'has_custom_certificate' => $webhookInfo->getHasCustomCertificate(),
                    'pending_update_count' => $webhookInfo->getPendingUpdateCount(),
                    'max_connections' => $webhookInfo->getMaxConnections(),
                    'last_error_date' => $webhookInfo->getLastErrorDate(),
                    'last_error_message' => $webhookInfo->getLastErrorMessage(),
                ],
                'config' => [
                    'token_defined' => !empty($token),
                    'username_defined' => !empty($username),
                    'admin_user_id' => env('TELEGRAM_ADMIN_USER_ID', null),
                ]
            ]);
        } catch (TelegramException $e) {
            Log::error('خطا در دریافت اطلاعات webhook تلگرام: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'partial',
                'message' => 'اطلاعات ربات دریافت شد اما خطا در دریافت اطلاعات webhook.',
                'error' => $e->getMessage(),
                'bot' => [
                    'id' => $botInfo['id'] ?? null,
                    'username' => $botInfo['username'] ?? null,
                    'first_name' => $botInfo['first_name'] ?? null
                ]
            ]);
        }
    }
} 