<?php

namespace App\Http\Livewire\Charity;

use App\Models\Family;
use App\Models\Member;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class DashboardStats extends Component
{
    public $insuredFamilies = 0;
    public $uninsuredFamilies = 0;
    public $insuredMembers = 0;
    public $uninsuredMembers = 0;

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        // بررسی اینکه وضعیت بیمه در کدام جدول ذخیره می‌شود
        // فرض بر این است که وضعیت بیمه در جدول families است
        
        // محاسبه تعداد خانواده‌های بیمه شده و بدون بیمه
        $this->insuredFamilies = Family::where('is_insured', 1)->count();
        $this->uninsuredFamilies = Family::where('is_insured', 0)->count();

        // محاسبه تعداد کل اعضای خانواده‌های بیمه شده و بدون بیمه
        $this->insuredMembers = Member::whereHas('family', function ($query) {
            $query->where('is_insured', 1);
        })->count();
        $this->uninsuredMembers = Member::whereHas('family', function ($query) {
            $query->where('is_insured', 0);
        })->count();
        
        // در صورت بروز مشکل، اعداد را از طریق SQL مستقیم محاسبه می‌کنیم
        try {
            if ($this->insuredFamilies == 0 && $this->uninsuredFamilies == 0) {
                // نتایج آمار خانواده‌ها با SQL
                $familyStats = DB::select("
                    SELECT 
                        SUM(CASE WHEN is_insured = 1 THEN 1 ELSE 0 END) as insured_count,
                        SUM(CASE WHEN is_insured = 0 THEN 1 ELSE 0 END) as uninsured_count
                    FROM families
                    WHERE deleted_at IS NULL
                ");
                
                if (!empty($familyStats)) {
                    $this->insuredFamilies = $familyStats[0]->insured_count ?? 0;
                    $this->uninsuredFamilies = $familyStats[0]->uninsured_count ?? 0;
                }
                
                // نتایج آمار اعضا با SQL
                $memberStats = DB::select("
                    SELECT 
                        COUNT(m.id) as member_count,
                        SUM(CASE WHEN f.is_insured = 1 THEN 1 ELSE 0 END) as insured_count,
                        SUM(CASE WHEN f.is_insured = 0 THEN 1 ELSE 0 END) as uninsured_count
                    FROM members m
                    JOIN families f ON m.family_id = f.id
                    WHERE m.deleted_at IS NULL AND f.deleted_at IS NULL
                ");
                
                if (!empty($memberStats)) {
                    $this->insuredMembers = $memberStats[0]->insured_count ?? 0;
                    $this->uninsuredMembers = $memberStats[0]->uninsured_count ?? 0;
                }
            }
        } catch (\Exception $e) {
            // در صورت بروز خطا، از مقادیر پیش‌فرض استفاده می‌کنیم
            $this->insuredFamilies = $this->insuredFamilies ?: 0;
            $this->uninsuredFamilies = $this->uninsuredFamilies ?: 0;
            $this->insuredMembers = $this->insuredMembers ?: 0;
            $this->uninsuredMembers = $this->uninsuredMembers ?: 0;
        }
    }

    public function render()
    {
        return view('livewire.charity.dashboard-stats');
    }
} 