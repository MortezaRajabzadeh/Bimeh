<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Family;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * نمایش داشبورد خیریه
     * کامپوننت‌های لایوویر به صورت خودکار بارگذاری می‌شوند
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('charity.dashboard');
    }
    
    /**
     * نمایش خانواده‌های بیمه شده
     * 
     * @return \Illuminate\View\View
     */
    public function insuredFamilies(Request $request)
    {
        $search = $request->input('search');
        $sort = $request->input('sort', 'created_at-desc');
        
        // پارس کردن فیلد و جهت مرتب‌سازی
        [$sortField, $sortDirection] = explode('-', $sort . '-desc');
        
        $families = Family::where('charity_id', Auth::user()->organization_id)
                    ->where('is_insured', true)
                    ->when($search, function($query) use ($search) {
                        return $query->where(function($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhereHas('head', function($subq) use ($search) {
                                   $subq->where('national_id', 'like', "%{$search}%")
                                       ->orWhere('phone', 'like', "%{$search}%")
                                       ->orWhere('full_name', 'like', "%{$search}%");
                              });
                        });
                    })
                    ->orderBy($sortField, $sortDirection)
                    ->paginate(15)
                    ->appends(['search' => $search, 'sort' => $sort]);
                    
        return view('charity.insured-families', compact('families'));
    }
    
    /**
     * نمایش خانواده‌های بدون پوشش بیمه
     * 
     * @return \Illuminate\View\View
     */
    public function uninsuredFamilies(Request $request)
    {
        $search = $request->input('search');
        $priority = $request->input('priority');
        $sort = $request->input('sort', 'created_at-desc');
        
        // پارس کردن فیلد و جهت مرتب‌سازی
        [$sortField, $sortDirection] = explode('-', $sort . '-desc');
        
        $families = Family::where('charity_id', Auth::user()->organization_id)
                    ->where('is_insured', false)
                    ->when($priority, function($query) use ($priority) {
                        return $query->where('priority', $priority);
                    })
                    ->when($search, function($query) use ($search) {
                        return $query->where(function($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhereHas('head', function($subq) use ($search) {
                                   $subq->where('national_id', 'like', "%{$search}%")
                                       ->orWhere('phone', 'like', "%{$search}%")
                                       ->orWhere('full_name', 'like', "%{$search}%");
                              });
                        });
                    })
                    ->orderBy($sortField, $sortDirection)
                    ->paginate(15)
                    ->appends(['search' => $search, 'priority' => $priority, 'sort' => $sort]);
                    
        return view('charity.uninsured-families', compact('families'));
    }
    
    /**
     * فرم افزودن خانواده جدید
     * 
     * @return \Illuminate\View\View
     */
    public function addFamily()
    {
        return view('charity.add-family');
    }
}
