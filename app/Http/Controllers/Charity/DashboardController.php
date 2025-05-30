<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Family;
use App\Models\Member;
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
        // محاسبه آمارهای کلی
        $stats = $this->calculateStats();
        
        return view('charity.dashboard', compact('stats'));
    }
    
    /**
     * محاسبه آمارهای کلی برای سایدبار
     * 
     * @return array
     */
    private function calculateStats()
    {
        $charityId = Auth::user()->organization_id;
        
        // آمارهای کلی
        $totalFamilies = Family::where('charity_id', $charityId)->count();
        $totalMembers = Member::whereHas('family', function($query) use ($charityId) {
            $query->where('charity_id', $charityId);
        })->count();
        
        // وضعیت رکوردها - بر اساس رابطه insurances و فیلد is_insured
        $insuredFamilies = Family::where('charity_id', $charityId)
            ->where(function($q) {
                $q->whereHas('insurances')
                  ->orWhere('is_insured', true)
                  ->orWhere('is_insured', 1);
            })
            ->count();
            
        $uninsuredFamilies = Family::where('charity_id', $charityId)
            ->whereDoesntHave('insurances')
            ->where(function($q) {
                $q->where('is_insured', false)
                  ->orWhere('is_insured', 0)
                  ->orWhereNull('is_insured');
            })
            ->count();
            
        $newFamilies = Family::where('charity_id', $charityId)
            ->where('status', 'pending')
            ->count();
            
        $membersWithDisability = Member::whereHas('family', function($query) use ($charityId) {
            $query->where('charity_id', $charityId);
        })->where('has_disability', true)->count();
        
        $membersWithChronicDisease = Member::whereHas('family', function($query) use ($charityId) {
            $query->where('charity_id', $charityId);
        })->where('has_chronic_disease', true)->count();

        return [
            'total_families' => $totalFamilies,
            'total_members' => $totalMembers,
            'insured_families' => $insuredFamilies,
            'uninsured_families' => $uninsuredFamilies,
            'new_families' => $newFamilies,
            'members_with_disability' => $membersWithDisability,
            'members_with_chronic_disease' => $membersWithChronicDisease,
        ];
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
                    
        // محاسبه آمارها برای سایدبار
        $stats = $this->calculateStats();
                    
        return view('charity.insured-families', compact('families', 'stats'));
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
                    
        // محاسبه آمارها برای سایدبار
        $stats = $this->calculateStats();
                    
        return view('charity.uninsured-families', compact('families', 'stats'));
    }
    
    /**
     * فرم افزودن خانواده جدید
     * 
     * @return \Illuminate\View\View
     */
    public function addFamily()
    {
        // محاسبه آمارها برای سایدبار
        $stats = $this->calculateStats();
        
        return view('charity.add-family', compact('stats'));
    }
}
