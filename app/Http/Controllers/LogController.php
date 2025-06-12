<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;

class LogController extends Controller
{
    /**
     * نمایش صفحه لاگ‌های سیستم
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $logs = DB::table('activity_log')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('logs.index', compact('logs'));
    }
} 
