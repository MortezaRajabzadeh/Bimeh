<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * نمایش لیست لاگ فعالیت‌ها
     */
    public function index(Request $request)
    {
        Gate::authorize('view system logs');
        
        $query = Activity::with('causer');
        
        // فیلتر بر اساس نوع فعالیت
        if ($request->filled('log_name')) {
            $query->where('log_name', $request->input('log_name'));
        }
        
        // فیلتر بر اساس کاربر انجام دهنده
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->input('causer_id'));
        }
        
        // فیلتر بر اساس نوع ایونت
        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }
        
        // فیلتر بر اساس تاریخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        
        $logs = $query->latest()->paginate(25);
        
        return view('admin.logs.index', compact('logs'));
    }
    
    /**
     * نمایش جزئیات یک لاگ فعالیت
     */
    public function show(Activity $activity)
    {
        Gate::authorize('view system logs');
        
        return view('admin.logs.show', compact('activity'));
    }
} 
