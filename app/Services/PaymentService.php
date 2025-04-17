<?php
namespace App\Services;

use Shetabit\Payment\Facade\Payment;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function initiate(int $amount, string $callbackUrl, string $description = 'خرید آنلاین')
    {
        try {
            return Payment::callbackUrl($callbackUrl)
                ->purchase(
                    Payment::amount($amount),
                    function ($driver, $transactionId) {
                        Log::info("تراکنش شروع شد - transaction_id: {$transactionId}");
                    }
                )
                ->pay()
                ->render();
        } catch (\Throwable $e) {
            Log::error("خطا در پرداخت: " . $e->getMessage());
            throw new \Exception("خطا در پرداخت. لطفاً مجدداً تلاش کنید.");
        }
    }
    
    /**
     * تایید تراکنش پرداخت
     */
    public function verify(string $authority, int $amount)
    {
        try {
            $receipt = Payment::amount($amount)->transactionId($authority)->verify();
            
            Log::info("تراکنش تایید شد", [
                'reference_id' => (string) $receipt,
                'transaction_id' => $authority,
                'amount' => $amount
            ]);
            
            return [
                'status' => true,
                'reference_id' => (string) $receipt,
                'transaction_id' => $authority
            ];
        } catch (\Exception $e) {
            Log::error("خطا در تایید پرداخت: " . $e->getMessage());
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
