<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Family;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InsuranceFamilyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * تغییر وضعیت چندین خانواده به صورت یکجا
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkUpdateStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'family_ids' => 'required|array',
                'family_ids.*' => 'required|exists:families,id',
                'status' => 'required|string|in:pending,reviewing,approved,insured,renewal,rejected',
                'current_status' => 'nullable|string'
            ]);

            $familyIds = $validated['family_ids'];
            $newStatus = $validated['status'];
            $currentStatus = $validated['current_status'] ?? null;

            // لاگ کردن درخواست برای دیباگ
                'family_ids' => $familyIds,
                'status' => $newStatus,
                'current_status' => $currentStatus,
                'ip' => $request->ip()
            ]);

            // اگر وضعیت فعلی مشخص شده، فقط خانواده‌های با آن وضعیت را آپدیت کن
            $query = Family::whereIn('id', $familyIds);
            if ($currentStatus) {
                $query->where('status', $currentStatus);
            }

            // ترنزکشن دیتابیس برای اطمینان از آپدیت امن
            DB::beginTransaction();
            
            try {
                // تغییر وضعیت خانواده‌ها
                $count = $query->update(['status' => $newStatus]);
                
                DB::commit();
                
                // بررسی اینکه آیا نیاز به سهم‌بندی وجود دارد یا خیر
                $requireShares = false;
                if ($newStatus === 'approved' && $currentStatus === 'reviewing') {
                    $requireShares = true;
                }
                
                return response()->json([
                    'success' => true,
                    'count' => $count,
                    'status' => $newStatus,
                    'require_shares' => $requireShares,
                    'family_ids' => $familyIds
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                
                return response()->json([
                    'success' => false,
                    'message' => 'خطا در تغییر وضعیت خانواده‌ها: ' . $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            
            return response()->json([
                'success' => false,
                'message' => 'خطا: ' . $e->getMessage()
            ], 400);
        }
    }
}
