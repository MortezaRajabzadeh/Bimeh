<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\DynamicDataExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Family;
use Illuminate\Support\Facades\DB;
use App\Enums\InsuranceWizardStep; // اضافه کردن use برای enum

class FamilyController extends Controller
{
    public function downloadExcel(Request $request)
    {
        // دریافت داده‌ها از درخواست
        $selected = $request->input('selected', []);
        $filters = $request->input('filters', []);
        
        // ایجاد کوئری پایه
        $query = Family::query()->with('head', 'city');
        
        // ============================================================== 
        // ========== شروع بخش کلیدی اصلاح شده ========================= 
        // ============================================================== 

        // دریافت تب فعال از فیلترها (با یک مقدار پیش‌فرض برای اطمینان) 
        $activeTab = $filters['activeTab'] ?? 'pending'; 

        // اعمال فیلتر بر اساس تب فعال (منطق کپی شده از کامپوننت) 
        switch ($activeTab) { 
            case 'pending': 
                $query->where('status', '!=', 'deleted') 
                      ->where('wizard_status', InsuranceWizardStep::PENDING->value); 
                break; 
                
            case 'reviewing': 
                $query->where('status', '!=', 'deleted') 
                      ->where('wizard_status', InsuranceWizardStep::REVIEWING->value); 
                break; 
                
            case 'approved': 
                $query->where('status', '!=', 'deleted') 
                      ->where('wizard_status', InsuranceWizardStep::APPROVED->value); 
                break; 
                
            case 'excel': 
                $query->where('status', '!=', 'deleted') 
                      ->where('wizard_status', InsuranceWizardStep::EXCEL_UPLOAD->value); 
                break; 
                
            case 'insured': 
                $query->where('status', '!=', 'deleted') 
                      ->where('wizard_status', InsuranceWizardStep::INSURED->value); 
                break; 
                
            case 'deleted': 
                $query->where('status', 'deleted'); 
                break; 
        }

        // ============================================================== 
        // ========== پایان بخش کلیدی اصلاح شده ========================== 
        // ============================================================== 
        
        // اعمال سایر فیلترها
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhereHas('head', function($q) use ($search) {
                      $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('national_code', 'like', "%{$search}%");
                  });
            });
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['province_id'])) {
            $query->whereHas('city', function($q) use ($filters) {
                $q->where('province_id', $filters['province_id']);
            });
        }
        
        if (!empty($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }
        
        if (!empty($filters['district_id'])) {
            $query->where('district_id', $filters['district_id']);
        }
        
        if (!empty($filters['region_id'])) {
            $query->where('region_id', $filters['region_id']);
        }
        
        if (!empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }
        
        if (!empty($filters['charity_id'])) {
            $query->where('charity_id', $filters['charity_id']);
        }
        
        // اگر آیتم‌های انتخاب شده وجود دارند، فقط آنها را دریافت کن
        if (!empty($selected)) {
            $query->whereIn('id', $selected);
        }
        
        // دریافت داده‌ها
        $families = $query->orderBy($filters['sortField'] ?? 'created_at', $filters['sortDirection'] ?? 'desc')->get();
        
        if ($families->isEmpty()) {
            return response()->json(['error' => 'هیچ داده‌ای برای دانلود با فیلترهای اعمال شده وجود ندارد.'], 404);
        }
        
        // تعریف هدرها و کلیدهای داده متناظر با آن‌ها
        $headings = [
            'شناسه خانواده',
            'نام سرپرست',
            'کد ملی سرپرست',
            'تعداد اعضای خانواده',
            'شهر',
        ];
        
        $dataKeys = [
            'id',
            'head.full_name',
            'head.national_code',
            'members_count',
            'city.name',
        ];
        
        // ایجاد نام فایل و دانلود
        $filename = 'families-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
        
        return Excel::download(new DynamicDataExport($families, $headings, $dataKeys), $filename);
    }

}