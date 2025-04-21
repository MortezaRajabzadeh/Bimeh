<?php

namespace App\Livewire\Charity;

use App\Models\Family;
use App\Models\Region;
use App\Models\Member;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class FamilySearch extends Component
{
    use WithPagination;
    
    public $search = '';
    public $status = '';
    public $region = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $expandedFamily = null;
    public $familyMembers = [];
    public $regions = [];
    
    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'region' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];
    
    public function mount()
    {
        $this->regions = Region::all();
    }
    
    public function render()
    {
        $query = Family::query()
            ->with(['region', 'members']);
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%')
                  ->orWhere('address', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->status !== '') {
            $query->where('is_insured', $this->status === 'insured');
        }
        
        if ($this->region) {
            $query->where('region_id', $this->region);
        }
        
        $query->orderBy($this->sortField, $this->sortDirection);
        
        $families = $query->paginate(10);
        
        return view('livewire.charity.family-search', [
            'families' => $families,
            'regions' => $this->regions
        ]);
    }
    
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
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function toggleFamily($familyId)
    {
        if ($this->expandedFamily === $familyId) {
            $this->expandedFamily = null;
            $this->familyMembers = [];
        } else {
            $this->expandedFamily = $familyId;
            $family = Family::with('members')->findOrFail($familyId);
            $this->familyMembers = $family->members;
        }
    }
    
    /**
     * تنظیم سرپرست خانواده
     *
     * @param int $familyId شناسه خانواده
     * @param int $memberId شناسه عضو
     * @return void
     */
    public function setFamilyHead($familyId, $memberId)
    {
        $family = Family::findOrFail($familyId);
        
        // فقط اگر خانواده تایید نشده باشد، اجازه تغییر سرپرست را بدهیم
        if (!$family->verified_at) {
            // ابتدا همه اعضای خانواده را به عنوان غیر-سرپرست علامت‌گذاری می‌کنیم
            Member::where('family_id', $familyId)
                ->update(['is_head' => false]);
            
            // سپس عضو انتخاب شده را به عنوان سرپرست تعیین می‌کنیم
            Member::where('id', $memberId)
                ->update(['is_head' => true]);
            
            // به‌روزرسانی لیست اعضا
            $this->familyMembers = Member::where('family_id', $familyId)->get();
            
            // نمایش پیام موفقیت
            $this->dispatch('show-toast', [
                'message' => 'سرپرست خانواده با موفقیت تغییر کرد', 
                'type' => 'success'
            ]);
        } else {
            // نمایش پیام خطا
            $this->dispatch('show-toast', [
                'message' => 'امکان تغییر سرپرست برای خانواده‌های تایید شده وجود ندارد', 
                'type' => 'error'
            ]);
        }
    }
    
    public function verifyFamily($familyId)
    {
        // بررسی دسترسی کاربر
        if (!Auth::check() || !Gate::allows('verify-family')) {
            $this->dispatch('show-toast', [
                'message' => 'شما اجازه تایید خانواده را ندارید',
                'type' => 'error'
            ]);
            return;
        }
        
        $family = Family::findOrFail($familyId);
        
        // اگر قبلاً تایید شده، اطلاع بدهیم
        if ($family->verified_at) {
            $this->dispatch('show-toast', [
                'message' => 'این خانواده قبلاً تایید شده است',
                'type' => 'error'
            ]);
            return;
        }
        
        // تایید و ذخیره تاریخ تایید
        $family->verified_at = now();
        $family->verified_by = Auth::id();
        $family->save();
        
        // نمایش پیام موفقیت
        $this->dispatch('show-toast', [
            'message' => 'خانواده با موفقیت تایید شد',
            'type' => 'success'
        ]);
    }
    
    public function copyText($text)
    {
        $this->dispatch('copy-text', $text);
    }
}
