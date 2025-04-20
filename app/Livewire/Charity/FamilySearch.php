<?php

namespace App\Livewire\Charity;

use App\Models\Family;
use App\Models\Region;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FamilySearch extends Component
{
    use WithPagination;
    
    // متغیرهای مورد نیاز برای جستجو و فیلتر
    public $search = '';
    public $status = '';
    public $region = '';
    
    // تغییر صفحه پیجینیشن
    protected $queryString = ['search', 'status', 'region'];
    
    // ریست پیجینیشن هنگام تغییر پارامترها
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingStatus()
    {
        $this->resetPage();
    }
    
    public function updatingRegion()
    {
        $this->resetPage();
    }
    
    // دریافت داده‌های خانواده‌ها با فیلترها
    public function getFamilies()
    {
        // $charity_id = Auth::user()->organization_id;
        // برای تست: حذف موقت فیلتر charity_id
        
        $query = Family::query();
        
        // اعمال فیلتر جستجو
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('family_code', 'LIKE', '%' . $this->search . '%')
                  ->orWhereHas('members', function ($mq) {
                      $mq->where('is_head', true)
                         ->where(function ($sq) {
                             $sq->where('first_name', 'LIKE', '%' . $this->search . '%')
                                ->orWhere('last_name', 'LIKE', '%' . $this->search . '%');
                         });
                  });
            });
        }
        
        // اعمال فیلتر وضعیت بیمه
        if ($this->status === 'insured') {
            $query->where('is_insured', true);
        } elseif ($this->status === 'uninsured') {
            $query->where('is_insured', false);
        }
        
        // اعمال فیلتر منطقه
        if (!empty($this->region)) {
            $query->where('region_id', $this->region);
        }
        
        // لاگ تعداد نتایج برای دیباگ
        $totalCount = $query->count();
        Log::info('Livewire FamilySearch - Query result count', ['count' => $totalCount]);
        
        return $query->with(['region', 'members' => function ($q) {
                $q->where('is_head', true);
            }])
            ->latest()
            ->paginate(10);
    }
    
    // دریافت لیست مناطق برای فیلتر
    public function getRegions()
    {
        return Region::active()->get();
    }
    
    // آمار کلی
    public function getStats()
    {
        // برای تست: حذف موقت فیلتر charity_id
        $stats = [
            'insuredFamilies' => Family::where('is_insured', true)->count(),
            'uninsuredFamilies' => Family::where('is_insured', false)->count(),
            'insuredMembers' => 0,
            'uninsuredMembers' => 0
        ];
        
        // محاسبه تعداد اعضا
        $stats['insuredMembers'] = \App\Models\Member::whereHas('family', function($query) {
            $query->where('is_insured', true);
        })->count();
        
        $stats['uninsuredMembers'] = \App\Models\Member::whereHas('family', function($query) {
            $query->where('is_insured', false);
        })->count();
        
        return $stats;
    }
    
    public function render()
    {
        $families = $this->getFamilies();
        $regions = $this->getRegions();
        $stats = $this->getStats();
        
        Log::info('Livewire FamilySearch - Render Data', [
            'search' => $this->search,
            'status' => $this->status,
            'region' => $this->region,
            'families_count' => $families->count(),
            'regions_count' => $regions->count(),
        ]);
        
        return view('livewire.charity.family-search', [
            'families' => $families,
            'regions' => $regions,
            'insuredFamilies' => $stats['insuredFamilies'],
            'uninsuredFamilies' => $stats['uninsuredFamilies'],
            'insuredMembers' => $stats['insuredMembers'],
            'uninsuredMembers' => $stats['uninsuredMembers'],
        ]);
    }
}
