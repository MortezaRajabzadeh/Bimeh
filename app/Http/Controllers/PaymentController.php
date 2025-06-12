<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
        // middleware در constructor کار نمی‌کند، باید در routes یا به صورت attribute استفاده شود
    }
    
    /**
     * درخواست پرداخت جدید
     */
    public function request(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1000',
            'description' => 'nullable|string|max:255',
        ]);
        
        $amount = $request->amount;
        $description = $request->description ?? 'پرداخت آنلاین';
        $callbackUrl = route('payment.callback');
        
        try {
            $paymentResponse = $this->paymentService->initiate($amount, $callbackUrl, $description);
            return $paymentResponse; // هدایت به درگاه پرداخت
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    
    /**
     * پس از بازگشت از درگاه پرداخت
     */
    public function callback(Request $request)
    {
        $authority = $request->Authority ?? $request->authority ?? null;
        $status = $request->Status ?? $request->status ?? null;
        
        if (!$authority || $status !== 'OK') {
            return redirect()->route('dashboard')
                ->with('error', 'پرداخت ناموفق یا لغو شده است ❌');
        }
        
        // در حالت واقعی مقدار از دیتابیس بازیابی می‌شود
        $amount = session('payment_amount', 10000);
        
        try {
            $result = $this->paymentService->verify($authority, $amount);
            
            if ($result['status']) {
                // در اینجا باید وضعیت سفارش را به روز کنید
                return redirect()->route('dashboard')
                    ->with('success', 'پرداخت با موفقیت انجام شد ✅');
            }
            
            return redirect()->route('dashboard')
                ->with('error', 'پرداخت تایید نشد: ' . ($result['message'] ?? 'خطای نامشخص'));
        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', 'خطا در تایید پرداخت: ' . $e->getMessage());
        }
    }
} 
