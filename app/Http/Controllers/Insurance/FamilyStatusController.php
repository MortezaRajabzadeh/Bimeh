<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FamilyStatusController extends Controller
{
    /**
     * تغییر وضعیت دسته‌ای خانواده‌ها
     * 
     * این متد وضعیت خانواده‌های انتخاب شده را تغییر می‌دهد
     * اگر وضعیت از "در انتظار حمایت" به "در انتظار صدور" تغییر کند، نیاز به سهم‌بندی است
     * که این تغییر با استفاده از کامپوننت Livewire ShareAllocationModal انجام می‌شود
     *
     * @param Request $request درخواست HTTP شامل آیدی خانواده‌ها و وضعیت جدید
     * @return \Illuminate\Http\JsonResponse پاسخ JSON شامل وضعیت عملیات
     */
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'family_ids' => 'required|array',
            'family_ids.*' => 'exists:families,id',
            'status' => 'required|string|in:pending,reviewing,approved,insured,renewal',
        ]);

        try {
            // دریافت پارامترها از درخواست
            $familyIds = $request->input('family_ids');
            $status = $request->input('status');
            $currentStatus = $request->input('current_status');
            
            // لاگ کردن درخواست برای دیباگ
            Log::info('درخواست تغییر وضعیت خانواده‌ها:', [
                'family_ids' => $familyIds,
                'status' => $status,
                'current_status' => $currentStatus,
                'ip' => $request->ip()
            ]);

            // اگر وضعیت فعلی مشخص شده، فقط آن خانواده‌ها را تغییر می‌دهیم
            $query = Family::whereIn('id', $familyIds);
            if ($currentStatus) {
                $query->where('status', $currentStatus);
            }

            $families = $query->get();
            if ($families->isEmpty()) {
                Log::warning('هیچ خانواده‌ای برای تغییر وضعیت یافت نشد.', [
                    'family_ids' => $familyIds,
                    'current_status' => $currentStatus
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'هیچ خانواده‌ای برای تغییر وضعیت یافت نشد.'
                ], 404);
            }

            // پاسخ مناسب برای وضعیت‌های مختلف
            $response = [
                'success' => true,
                'count' => $families->count(),
                'status' => $status,
                'require_shares' => false,
                'family_ids' => $familyIds,
            ];

            // اگر از "در انتظار حمایت" به "در انتظار صدور" تغییر کرده، نیاز به سهم‌بندی داریم
            if ($currentStatus === 'reviewing' && $status === 'approved') {
                Log::info('نیاز به سهم‌بندی برای خانواده‌ها', [
                    'family_ids' => $familyIds,
                    'count' => count($familyIds)
                ]);
                
                $response['require_shares'] = true;
                return response()->json($response);
            }

            // تغییر وضعیت در دیتابیس
            DB::transaction(function() use ($familyIds, $status, $currentStatus) {
                $query = Family::whereIn('id', $familyIds);
                if ($currentStatus) {
                    $query->where('status', $currentStatus);
                }
                
                $query->update(['status' => $status]);
            });
            
            Log::info('وضعیت خانواده‌ها با موفقیت تغییر یافت', [
                'count' => $families->count(),
                'status' => $status,
                'family_ids' => $familyIds
            ]);

            $response['message'] = "وضعیت {$families->count()} خانواده با موفقیت به {$status} تغییر یافت.";
            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('خطا در تغییر وضعیت خانواده‌ها: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'خطا در تغییر وضعیت خانواده‌ها: ' . $e->getMessage()
            ], 500);
        }
    }
} 