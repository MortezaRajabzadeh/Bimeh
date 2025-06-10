<?php

namespace App\Livewire\Insurance;

use Livewire\Component;
use App\Models\Family;
use App\Exports\FamilyInsuranceExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use App\Models\FamilyInsurance;
use App\Services\InsuranceShareService;
use App\Models\FamilyStatusLog;
use App\InsuranceWizardStep;
use Carbon\Carbon;
use App\Exports\DynamicDataExport;

use App\Enums\FamilyStatus as FamilyStatusEnum;
use App\Services\InsuranceImportLogger;

class FamiliesApproval extends Component
{
    use WithFileUploads, WithPagination;

    public string $activeTab = 'pending';
    public bool $selectAll = false;
    public array $selected = [];
    public $tab = 'pending'; // اضافه کردن متغیر tab
    
    // متغیرهای جدید برای مودال‌ها
    public bool $showDeleteModal = false;
    public ?string $deleteReason = null;

    public $cached_tab = null;
    public $is_loading = false;
    public $expandedFamily = null;
    public $insuranceExcelFile;
    public $perPage = 15;

    // اضافه کردن متغیرهای مرتب‌سازی
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    // متغیرهای تمدید بیمه
    public $renewalPeriod = 12;
    public $renewalDate = null;
    public $renewalNote = '';
    
    // متغیرهای جستجو و فیلتر
    public $search = '';
    public $province_id = null;
    public $city_id = null;
    public $district_id = null;
    public $region_id = null;
    public $organization_id = null;
    public $charity_id = null;

    protected $paginationTheme = 'tailwind';
    
    // تعریف متغیرهای queryString
    protected $queryString = [
        'page' => ['except' => 1],
        'activeTab' => ['except' => 'pending'],
    ];

    // ایجاد لیستنر برای ذخیره سهم‌بندی
    protected $listeners = [
        'sharesAllocated' => 'onSharesAllocated',
        'reset-checkboxes' => 'onResetCheckboxes',
        'switchToReviewingTab' => 'switchToReviewingTab',
        'updateFamiliesStatus' => 'handleUpdateFamiliesStatus',
        'refreshFamiliesList' => 'refreshFamiliesList',
        'closeShareModal' => 'onCloseShareModal',
        'selectForRenewal' => 'selectForRenewal',
        'renewInsurance' => 'renewInsurance',
        'pageRefreshed' => 'handlePageRefresh' // اضافه کردن listener جدید
    ];

    // تعریف ویژگی wizard_status
    protected $wizard_status = null;

    public function mount()
    {
        // پیش‌فرض تنظیم تب فعال
        $this->activeTab = $this->tab;
        
        // پاکسازی کش هنگام لود اولیه صفحه
        $this->clearFamiliesCache();
        
        Log::info('🔄 FamiliesApproval mounted - Cache cleared for fresh data');
    }
    
    public function hydrate()
    {
        // پاکسازی کش هنگام hydrate شدن کامپوننت
        $this->clearFamiliesCache();
    }
    
    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        Log::info('🔍 updatedSelectAll method called with value: ' . ($value ? 'true' : 'false'));
        
        if ($value) {
            // Get IDs of all families on the current page
            $families = $this->getFamiliesProperty();
            $familyIds = $families->pluck('id')->map(function($id) {
                return (string) $id;
            })->toArray();
            
            $this->selected = $familyIds;
            Log::info('✅ Select all: Selected ' . count($this->selected) . ' families: ' . implode(', ', array_slice($this->selected, 0, 5)) . (count($this->selected) > 5 ? '...' : ''));
        } else {
            $this->selected = [];
            Log::info('❌ Deselect all: Cleared all selections');
        }
    }

    /**
     * Toggle select all functionality - this is a direct callable method
     */
    public function toggleSelectAll($value = null)
    {
        Log::info('🔄 toggleSelectAll method called with value: ' . ($value ? 'true' : 'false'));
        
        $this->selectAll = $value;
        
        if ($this->selectAll) {
            // Get IDs of all families on the current page
            $families = $this->getFamiliesProperty();
            $familyIds = $families->pluck('id')->map(function($id) {
                return (string) $id;
            })->toArray();
            
            $this->selected = $familyIds;
            Log::info('✅ Select all (toggle): Selected ' . count($this->selected) . ' families: ' . implode(', ', array_slice($this->selected, 0, 5)) . (count($this->selected) > 5 ? '...' : ''));
        } else {
            $this->selected = [];
            Log::info('❌ Deselect all (toggle): Cleared all selections');
        }
    }

    public function updatedSelected()
    {
        $families = $this->getFamiliesProperty();
        $oldSelectAll = $this->selectAll;
        $this->selectAll = count($this->selected) > 0 && count($this->selected) === $families->count();
        
        Log::info('🔄 updatedSelected: selected count=' . count($this->selected) . ', total families=' . $families->count() . ', selectAll changed from ' . ($oldSelectAll ? 'true' : 'false') . ' to ' . ($this->selectAll ? 'true' : 'false'));
    }

    public function approveSelected()
    {
        Log::info('🚀 approveSelected method called');
        Log::info('📋 Selected families: ' . count($this->selected) . ' - IDs: ' . implode(', ', array_slice($this->selected, 0, 5)) . (count($this->selected) > 5 ? '...' : ''));
        
        if (empty($this->selected)) {
            Log::warning('⚠️ No families selected, aborting approval process');
            return;
        }
        
        DB::beginTransaction();
        try {
            $batchId = 'batch_' . time() . '_' . uniqid();
            $count = 0;
            $nextStep = null;
            
            foreach ($this->selected as $familyId) {
                $family = Family::find($familyId);
                if (!$family) {
                    Log::warning('⚠️ Family not found with ID: ' . $familyId);
                    continue;
                }
                
                // Log family status safely by converting enum to string if needed
                $currentStatusString = $family->wizard_status ? 
                    (is_object($family->wizard_status) ? $family->wizard_status->value : $family->wizard_status) : 
                    'null';
                Log::info('👪 Processing family ID: ' . $familyId . ' with current status: ' . $currentStatusString);
                
                // اگر از قبل wizard شروع نشده، آن را شروع می‌کنیم
                if (!$family->wizard_status) {
                    $family->syncWizardStatus();
                    $syncedStatus = $family->wizard_status ? 
                        (is_object($family->wizard_status) ? $family->wizard_status->value : $family->wizard_status) : 
                        'null';
                    Log::info('🔄 Initialized wizard status for family: ' . $familyId . ' to: ' . $syncedStatus);
                }
                
                // انتقال به مرحله بعدی با توجه به وضعیت فعلی
                $currentStep = $family->wizard_status ?? InsuranceWizardStep::PENDING;
                if (is_string($currentStep)) {
                    $currentStep = InsuranceWizardStep::from($currentStep);
                }
                
                $nextStep = null;
                
                if ($currentStep === InsuranceWizardStep::PENDING) {
                    $nextStep = InsuranceWizardStep::REVIEWING;
                    Log::info('⏩ Moving family ' . $familyId . ' from PENDING to REVIEWING');
                } elseif ($currentStep === InsuranceWizardStep::REVIEWING) {
                    $nextStep = InsuranceWizardStep::SHARE_ALLOCATION;
                    Log::info('⏩ Moving family ' . $familyId . ' from REVIEWING to SHARE_ALLOCATION');
                }
                
                if ($nextStep) {
                    // به‌روزرسانی wizard_status
                    $family->setAttribute('wizard_status', $nextStep->value);
                    
                    // به‌روزرسانی وضعیت قدیمی
                    switch ($nextStep->value) {
                        case InsuranceWizardStep::REVIEWING->value:
                            $family->setAttribute('status', 'reviewing');
                            break;
                        case InsuranceWizardStep::SHARE_ALLOCATION->value:
                        case InsuranceWizardStep::APPROVED->value:
                            $family->setAttribute('status', 'approved');
                            break;
                        case InsuranceWizardStep::EXCEL_UPLOAD->value:
                        case InsuranceWizardStep::INSURED->value:
                            $family->setAttribute('status', 'insured');
                            $family->setAttribute('is_insured', true);
                            break;
                        case InsuranceWizardStep::RENEWAL->value:
                            $family->setAttribute('status', 'renewal');
                            break;
                    }
                    
                    // ذخیره تغییرات
                    $family->save();
                    
                    // ثبت لاگ تغییر وضعیت - بدون استفاده از extra_data
                    try {
                        FamilyStatusLog::create([
                            'family_id' => $family->id,
                            'user_id' => Auth::id(),
                            'from_status' => $currentStep->value,
                            'to_status' => $nextStep->value,
                            'comments' => "تغییر وضعیت خانواده به مرحله {$nextStep->label()} توسط کاربر",
                            'batch_id' => $batchId
                        ]);
                    
                    $count++;
                        Log::info('✅ Successfully updated family ' . $familyId . ' to status: ' . $nextStep->value . ' (DB status: ' . $family->status . ')');
                    } catch (\Exception $e) {
                        Log::warning('⚠️ Could not log status transition: ' . $e->getMessage());
                        // ادامه اجرا حتی اگر لاگ ثبت نشد
                    }
                } else {
                    Log::warning('⚠️ No next step defined for family ' . $familyId . ' with current step: ' . $currentStep->value);
                }
            }
            
            DB::commit();
            
            session()->flash('message', "{$count} خانواده با موفقیت به مرحله بعد منتقل شدند.");
            Log::info('✅ Transaction committed: ' . $count . ' families approved and moved to next stage');
            
            $this->selected = [];
            $this->selectAll = false;
            $this->resetPage();
            $this->dispatch('reset-checkboxes');
            
            // به‌روزرسانی کش
            $this->clearFamiliesCache();
            
            // انتقال اتوماتیک به تب بعدی
            if ($count > 0) {
                // تشخیص تب بعدی از آخرین مرحله‌ای که پردازش شده
                if ($nextStep) {
                    $nextStepValue = $nextStep->value;
                    if ($nextStepValue === InsuranceWizardStep::REVIEWING->value) {
                        // انتقال به تب reviewing
                        Log::info('🔄 Automatically switching to reviewing tab');
                        $this->setTab('reviewing');
                    } elseif ($nextStepValue === InsuranceWizardStep::SHARE_ALLOCATION->value || 
                             $nextStepValue === InsuranceWizardStep::APPROVED->value) {
                        // انتقال به تب approved
                        Log::info('🔄 Automatically switching to approved tab');
                        $this->setTab('approved');
                    } elseif ($nextStepValue === InsuranceWizardStep::INSURED->value) {
                        // انتقال به تب insured
                        Log::info('🔄 Automatically switching to insured tab');
                        $this->setTab('insured');
                    }
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('❌ Error in approveSelected: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            session()->flash('error', 'خطا در انتقال خانواده‌ها: ' . $e->getMessage());
        }
    }

    /**
     * حذف خانواده‌های انتخاب شده
     */
    public function deleteSelected()
    {
        Log::info('🗑️ deleteSelected method called. Reason: ' . $this->deleteReason);
        
        // اعتبارسنجی انتخاب دلیل حذف
        if (empty($this->deleteReason)) {
            session()->flash('error', 'لطفاً دلیل حذف را انتخاب کنید');
            return;
        }
        
        if (empty($this->selected)) {
            session()->flash('error', 'لطفاً حداقل یک خانواده را انتخاب کنید');
            return;
        }

        try {
            DB::beginTransaction();
            
            $deletedCount = 0;
            $failedCount = 0;
            
            foreach ($this->selected as $familyId) {
                Log::info("🔄 Processing family ID: {$familyId} for deletion");
                
                try {
                    $family = Family::with('members')->findOrFail($familyId);
                    
                    // ایجاد لاگ برای تغییر وضعیت - با فیلدهای متناسب با جدول
                    FamilyStatusLog::create([
                        'family_id' => $family->id,
                        'user_id' => Auth::id(),
                        'from_status' => $family->status,
                        'to_status' => 'deleted', // استفاده از to_status به جای new_status
                        'comments' => $this->deleteReason, // استفاده از comments به جای reason
                        'extra_data' => json_encode([
                            'deleted_at' => now()->toDateTimeString(),
                            'deleted_by' => Auth::user()->name ?? 'سیستم',
                        ]),
                    ]);
                    
                    // آپدیت وضعیت خانواده
                    $family->status = 'deleted';
                    $family->save();
                    
                    Log::info("✅ Family ID: {$familyId} successfully marked as deleted");
                    $deletedCount++;
                } catch (\Exception $e) {
                    Log::error("❌ Error deleting family ID: {$familyId}: " . $e->getMessage());
                    $failedCount++;
                }
            }
            
            DB::commit();
            
        $this->selected = [];
            $this->showDeleteModal = false; // بستن مودال
            $this->deleteReason = null; // پاک کردن دلیل حذف
            
            // پاکسازی کش و به‌روزرسانی لیست
            $this->clearFamiliesCache();
            
            if ($deletedCount > 0 && $failedCount === 0) {
                session()->flash('message', "{$deletedCount} خانواده با موفقیت حذف شدند");
            } elseif ($deletedCount > 0 && $failedCount > 0) {
                session()->flash('message', "{$deletedCount} خانواده با موفقیت حذف شدند و {$failedCount} خانواده با خطا مواجه شدند");
            } else {
                session()->flash('error', "عملیات حذف با خطا مواجه شد");
            }
            
            // رفرش کامپوننت برای به‌روزرسانی لیست‌ها
            $this->dispatch('refreshFamiliesList');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("❌ Critical error in deleteSelected: " . $e->getMessage());
            session()->flash('error', 'خطا در عملیات حذف: ' . $e->getMessage());
        }
    }

    public function returnToPendingSelected()
    {
        Family::whereIn('id', $this->selected)->update(['status' => 'pending']);
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
        $this->dispatch('reset-checkboxes');
    }

    /**
     * تخصیص سهم و تایید نهایی خانواده‌های انتخاب شده
     */
    public function approveAndContinueSelected()
    {
        $this->resetErrorBag();
        
        if (count($this->selected) === 0) {
            session()->flash('error', 'هیچ خانواده‌ای انتخاب نشده است.');
            return;
        }
        
        Log::info('FamiliesApproval::approveAndContinueSelected - شروع تخصیص سهم و تایید', [
            'selected_count' => count($this->selected),
            'selected_ids' => $this->selected
        ]);
        
        // ابتدا مودال تخصیص سهم را نمایش می‌دهیم
        $this->dispatch('openShareAllocationModal', $this->selected);
        
        // گوش دادن به رویداد تکمیل تخصیص سهم
        $this->dispatch('listen:sharesAllocated');
        
        Log::info('FamiliesApproval::approveAndContinueSelected - مودال تخصیص سهم باز شد', [
            'selected_count' => count($this->selected)
        ]);
    }

    /**
     * انتقال خانواده‌های انتخاب شده به مرحله بعدی wizard و به‌روزرسانی وضعیت قدیمی
     */
    public function moveSelectedToNextWizardStep()
    {
        if (empty($this->selected)) {
            return;
        }
        
        DB::beginTransaction();
        try {
            $batchId = 'batch_' . time() . '_' . uniqid();
            $count = 0;
            
            foreach ($this->selected as $familyId) {
                $family = Family::find($familyId);
                if (!$family) continue;
                
                // اگر از قبل wizard شروع نشده، آن را شروع می‌کنیم
                if (!$family->wizard_status) {
                    $family->syncWizardStatus();
                }
                
                $currentStep = $family->wizard_status;
                if (is_string($currentStep)) {
                    $currentStep = InsuranceWizardStep::from($currentStep);
                }
                
                $nextStep = $currentStep->nextStep();
                
                if ($nextStep) {
                    // استفاده از setAttribute به جای دسترسی مستقیم
                    $family->setAttribute('wizard_status', $nextStep->value);
                    
                    // به‌روزرسانی وضعیت قدیمی
                    switch ($nextStep->value) {
                        case InsuranceWizardStep::REVIEWING:
                            $family->setAttribute('status', 'reviewing');
                            break;
                        case InsuranceWizardStep::SHARE_ALLOCATION:
                        case InsuranceWizardStep::APPROVED:
                            $family->setAttribute('status', 'approved');
                            break;
                        case InsuranceWizardStep::EXCEL_UPLOAD:
                        case InsuranceWizardStep::INSURED:
                            $family->setAttribute('status', 'insured');
                            $family->setAttribute('is_insured', true);
                            break;
                        case InsuranceWizardStep::RENEWAL:
                            $family->setAttribute('status', 'renewal');
                            break;
                    }
                    
                    $family->save();
                    
                    // ثبت لاگ تغییر وضعیت
                    FamilyStatusLog::logTransition(
                        $family,
                        $currentStep,
                        $nextStep,
                        "انتقال به مرحله {$nextStep->label()} توسط کاربر",
                        ['batch_id' => $batchId]
                    );
                    
                    $count++;
                }
            }
            
            DB::commit();
            
            session()->flash('message', "{$count} خانواده با موفقیت به مرحله بعد منتقل شدند.");
            
            // به‌روزرسانی UI
            $this->selected = [];
            $this->selectAll = false;
            $this->resetPage();
            $this->dispatch('reset-checkboxes');
            
            // به‌روزرسانی کش
            $this->clearFamiliesCache();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('خطا در انتقال خانواده‌ها به مرحله بعد: ' . $e->getMessage());
            session()->flash('error', 'خطا در انتقال خانواده‌ها: ' . $e->getMessage());
        }
    }

    /**
     * پاک کردن کش خانواده‌ها
     */
    public function clearFamiliesCache()
    {
        try {
            // روش ساده: پاک کردن کل کش
            Cache::flush();
            
            // یا روش دقیق‌تر: پاک کردن کلیدهای مرتبط با خانواده‌ها
            // Cache::forget($this->getCacheKey());
            
            Log::info("🧹 Families cache has been flushed.");
        } catch (\Exception $e) {
            Log::error('❌ Error flushing cache: ' . $e->getMessage()); 
        }
    }
    
    /**
     * به‌روزرسانی کلید کش بر اساس تمام پارامترهای کوئری
     */
    protected function getCacheKey($customTab = null)
    {
        $tab = $customTab ?? $this->tab;
        $step = 'all';
        
        // اگر wizard_status تنظیم شده باشد، از آن در کلید کش استفاده می‌کنیم
        if ($this->wizard_status) {
            if (is_array($this->wizard_status)) {
                $stepValues = array_map(function($step) {
                    return $step instanceof InsuranceWizardStep ? $step->value : $step;
                }, $this->wizard_status);
                
                $step = implode('_', $stepValues);
            } else {
                $step = $this->wizard_status instanceof InsuranceWizardStep ? $this->wizard_status->value : $this->wizard_status;
            }
        }
        
        // استفاده از Auth::id() بجای auth()->id()
        $userId = Auth::id() ? Auth::id() : 'guest';
        
        return 'families_' . $tab . '_wizard_' . $step . '_page_' . $this->getPage() . '_perpage_' . $this->perPage . 
               '_sort_' . $this->sortField . '_' . $this->sortDirection . '_user_' . $userId;
    }

    /**
     * تغییر تب نمایش داده شده
     *
     * @param string $tab
     * @param bool $resetSelections آیا انتخاب‌ها ریست شوند یا خیر
     * @return void
     */
    public function changeTab($tab, $resetSelections = true)
    {
        $this->activeTab = $tab;
        $this->setTab($tab, $resetSelections);
    }

    /**
     * تغییر تب نمایش داده شده
     *
     * @param string $tab
     * @param bool $resetSelections آیا انتخاب‌ها ریست شوند یا خیر
     * @return void
     */
    public function setTab($tab, $resetSelections = true)
    {
        if ($this->tab === $tab) {
            return;
        }
        
        $this->is_loading = true;
        
        $this->cached_tab = $this->tab;
        
        $this->tab = $tab;
        $this->activeTab = $tab; // به‌روزرسانی activeTab
        
        // همگام‌سازی تب‌های قدیمی با مراحل wizard
        if ($tab === 'pending') {
            $this->loadFamiliesByWizardStatus(InsuranceWizardStep::PENDING);
        } elseif ($tab === 'reviewing') {
            $this->loadFamiliesByWizardStatus(InsuranceWizardStep::REVIEWING);
        } elseif ($tab === 'approved') {
            $this->loadFamiliesByWizardStatus([InsuranceWizardStep::SHARE_ALLOCATION, InsuranceWizardStep::APPROVED, InsuranceWizardStep::EXCEL_UPLOAD]);
        } elseif ($tab === 'excel') {
            $this->loadFamiliesByWizardStatus(InsuranceWizardStep::EXCEL_UPLOAD);
        } elseif ($tab === 'insured') {
            $this->loadFamiliesByWizardStatus(InsuranceWizardStep::INSURED);
        } elseif ($tab === 'renewal') {
            $this->loadFamiliesByWizardStatus(InsuranceWizardStep::RENEWAL);
        } elseif ($tab === 'deleted') {
            // تب حذف شده ها - بدون نیاز به wizard status
            $this->wizard_status = null;
        }
        
        // ریست کردن صفحه‌بندی و انتخاب‌ها
        $this->resetPage();
        
        // فقط اگر پارامتر resetSelections درست باشد، انتخاب‌ها را ریست می‌کنیم
        if ($resetSelections) {
        $this->selected = [];
        $this->selectAll = false;
        }
        
        // به‌روزرسانی کش
        $this->clearFamiliesCache();
        
        $this->is_loading = false;
        
        // رفرش صفحه
        $this->dispatch('reset-checkboxes');
    }

    /**
     * بارگذاری خانواده‌ها بر اساس وضعیت wizard
     *
     * @param InsuranceWizardStep|array $wizardStatus
     * @return void
     */
    public function loadFamiliesByWizardStatus($wizardStatus)
    {
        // ذخیره وضعیت wizard برای استفاده در کوئری‌ها
        $this->wizard_status = $wizardStatus;
    }

    /**
     * بهبود getFamiliesProperty برای پشتیبانی از wizard
     */
    public function getFamiliesProperty()
    {
        $cacheKey = $this->getCacheKey();
        
        // بدون استفاده از تگ‌های کش
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($cacheKey) {
            
            Log::info("🔄 Generating new cache for key: {$cacheKey}");
            
            $query = Family::with([
                'province:id,name', 
                'city:id,name', 
                'charity:id,name',
                'members' => function ($query) {
                    $query->select(['id', 'family_id', 'first_name', 'last_name', 'national_code', 'is_head', 'relationship', 'problem_type', 'occupation']);
                }
            ]);
            
            // Count only final insurances (insured status)
            $query->withCount(['insurances as final_insurances_count' => function ($query) {
                $query->where('status', 'insured');
            }]);
                
            // Apply explicit filtering based on active tab
            switch ($this->activeTab) {
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
                    // Only show families with legacy status 'deleted'
                    $query->where('status', 'deleted');
                    break;
                    
                default:
                    // Fallback to pending if activeTab is not recognized
                    $query->where('status', '!=', 'deleted')
                          ->where('wizard_status', InsuranceWizardStep::PENDING->value);
                    break;
            }
            
            // --- بخش اصلاح شده برای مرتب‌سازی هوشمند ---
            if ($this->sortField === 'insurance_payer') {
                // برای مرتب‌سازی بر اساس پرداخت‌کننده، باید جداول را JOIN کنیم
                // ما فقط بر اساس اولین بیمه نهایی شده مرتب‌سازی می‌کنیم
                $query->leftJoin('family_insurances', 'families.id', '=', 'family_insurances.family_id')
                      ->where(function ($q) {
                          // فقط بیمه‌های نهایی شده را در نظر بگیر
                          $q->where('family_insurances.status', 'insured')
                            ->orWhereNull('family_insurances.id'); // برای خانواده‌هایی که هنوز بیمه ندارند
                      })
                      ->orderBy('family_insurances.insurance_payer', $this->sortDirection)
                      ->select('families.*'); // **بسیار مهم**: فقط ستون‌های جدول اصلی را انتخاب کن
            }
            else if ($this->sortField === 'insurance_type') {
                // مرتب‌سازی بر اساس نوع بیمه
                $query->leftJoin('family_insurances', 'families.id', '=', 'family_insurances.family_id')
                      ->where(function ($q) {
                          $q->where('family_insurances.status', 'insured')
                            ->orWhereNull('family_insurances.id');
                      })
                      ->orderBy('family_insurances.insurance_type', $this->sortDirection)
                      ->select('families.*');
            }
            else if ($this->sortField === 'family_head') {
                $query->join('family_members as heads', function ($join) {
                    $join->on('families.id', '=', 'heads.family_id')
                         ->where('heads.is_head', true);
                })
                ->orderBy('heads.first_name', $this->sortDirection)
                ->orderBy('heads.last_name', $this->sortDirection)
                ->select('families.*');
            } else {
                // مرتب‌سازی بر اساس ستون‌های خود جدول families
                $query->orderBy($this->sortField, $this->sortDirection);
            }
            
            return $query->paginate($this->perPage);
        });
    }

    public function toggleFamily($familyId)
    {
        $this->expandedFamily = $this->expandedFamily === $familyId ? null : $familyId;
    }

    public function getTotalMembersProperty()
    {
        if (empty($this->selected)) {
            return 0;
        }
        return Family::withCount('members')->whereIn('id', $this->selected)->get()->sum('members_count');
    }

    /**
     * آماده‌سازی دانلود فایل اکسل برای خانواده‌های انتخاب شده
     */
    public function prepareInsuranceExcelDownload()
    {
        if (count($this->selected) === 0) {
            session()->flash('error', 'لطفاً حداقل یک خانواده را انتخاب کنید.');
            return;
        }
        
        $filename = 'insurance-families-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
        
        // به جای دانلود مستقیم، یک URL امضا شده برای دانلود ایجاد می‌کنیم
        $downloadUrl = URL::signedRoute('families.download-route', [
            'filename' => $filename,
            'type' => 'insurance',
            'ids' => implode(',', $this->selected)
        ]);
        
        // ارسال رویداد به Alpine.js برای شروع دانلود
        $this->dispatch('file-download', ['url' => $downloadUrl]);
    }
    
    /**
     * دانلود فایل اکسل بیمه و انتقال به مرحله بعد
     */
    public function downloadInsuranceExcel()
    {
        if (empty($this->selected)) {
            session()->flash('error', 'لطفاً حداقل یک خانواده را انتخاب کنید');
            return null;
        }
        
        // ذخیره آیدی‌های انتخاب شده قبل از تغییر وضعیت
        $selectedIds = $this->selected;
        
        // انتقال خانواده‌ها به مرحله آپلود اکسل
        DB::beginTransaction();
        try {
            $batchId = 'excel_download_' . time() . '_' . uniqid();
            $count = 0;
            
            foreach ($this->selected as $familyId) {
                $family = Family::find($familyId);
                if (!$family) continue;
                
                // تغییر وضعیت به EXCEL_UPLOAD
                $currentStep = $family->wizard_status;
                if (is_string($currentStep)) {
                    $currentStep = InsuranceWizardStep::from($currentStep);
                }
                
                $family->setAttribute('wizard_status', InsuranceWizardStep::EXCEL_UPLOAD->value);
                $family->status = 'approved'; // از approved استفاده می‌کنیم چون excel مقدار مجاز نیست
                $family->save();
                
                // به‌روزرسانی وضعیت در جدول family_insurances
                $insurances = FamilyInsurance::where('family_id', $family->id)
                    ->where(function($query) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                    })
                    ->get();
                    
                foreach ($insurances as $insurance) {
                    $insurance->status = 'awaiting_upload';  // وضعیت در انتظار آپلود اکسل
                    $insurance->save();
                }
                
                // ثبت لاگ تغییر وضعیت
                FamilyStatusLog::create([
                    'family_id' => $family->id,
                    'user_id' => Auth::id(),
                    'from_status' => $currentStep->value,
                    'to_status' => InsuranceWizardStep::EXCEL_UPLOAD->value,
                    'comments' => "دانلود اکسل بیمه و انتقال به مرحله آپلود اکسل",
                    'batch_id' => $batchId
                ]);
                
                $count++;
            }
            
            DB::commit();
            
            // نمایش پیام موفقیت
            session()->flash('message', "فایل اکسل برای {$count} خانواده دانلود شد و خانواده‌ها به مرحله آپلود اکسل منتقل شدند");
            
            // پاک کردن کش برای به‌روزرسانی لیست‌ها
            $this->clearFamiliesCache();
            
            // انتقال اتوماتیک به تب excel بدون ریست کردن انتخاب‌ها
            $this->changeTab('excel', false);
            
        } catch (\Exception $e) {
            DB::rollback();
            session()->flash('error', 'خطا در تغییر وضعیت: ' . $e->getMessage());
        }
        
        // دانلود فایل اکسل با آیدی‌های ذخیره شده
        return Excel::download(new FamilyInsuranceExport($selectedIds), 'insurance-families.xlsx');
    }
    
    /**
     * آماده‌سازی دانلود فایل اکسل برای خانواده‌های موجود در صفحه
     */
    public function preparePageExcelDownload()
    {
        $filename = 'families-' . $this->activeTab . '-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
        
        // به جای دانلود مستقیم، یک URL امضا شده برای دانلود ایجاد می‌کنیم
        $downloadUrl = URL::signedRoute('families.download-route', [
            'filename' => $filename,
            'type' => 'page',
            'tab' => $this->activeTab,
            'filters' => json_encode([
                'search' => $this->search,
                'province_id' => $this->province_id,
                'city_id' => $this->city_id,
                'district_id' => $this->district_id,
                'region_id' => $this->region_id,
                'organization_id' => $this->organization_id,
                'charity_id' => $this->charity_id,
                'sortField' => $this->sortField,
                'sortDirection' => $this->sortDirection
            ])
        ]);
        
        // ارسال رویداد به Alpine.js برای شروع دانلود
        $this->dispatch('file-download', ['url' => $downloadUrl]);
    }
    
    /**
     * دانلود فایل اکسل برای خانواده‌های موجود در صفحه
     * 
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadPageExcel()
    {
        $query = Family::query()->with([
        'province', 'city', 'district', 'region', 'members', 'head', 'charity', 'organization',
        'insurances' => fn($q) => $q->orderBy('created_at', 'desc'),
        'finalInsurances'
        ]);

        // Apply activeTab filters
        switch ($this->activeTab) {
            case 'pending':
                $query->where('wizard_status', InsuranceWizardStep::PENDING->value)
                    ->where('status', '!=', 'deleted');
                break;
            case 'reviewing':
                $query->where('wizard_status', InsuranceWizardStep::REVIEWING->value)
                    ->where('status', '!=', 'deleted');
                break;
            case 'approved':
                $query->where('wizard_status', InsuranceWizardStep::APPROVED->value)
                    ->where('status', '!=', 'deleted');
                break;
            case 'excel':
                $query->where('wizard_status', InsuranceWizardStep::EXCEL_UPLOAD->value)
                    ->where('status', '!=', 'deleted');
                break;
            case 'insured':
                $query->where('wizard_status', InsuranceWizardStep::INSURED->value)
                    ->where('status', '!=', 'deleted');
                break;
            case 'renewal':
                $query->where('wizard_status', InsuranceWizardStep::RENEWAL->value)
                    ->where('status', '!=', 'deleted');
                break;
            case 'deleted':
                $query->where('status', 'deleted');
                break;
            default:
                $query->where('wizard_status', InsuranceWizardStep::PENDING->value)
                    ->where('status', '!=', 'deleted');
                break;
        }

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('head', fn($sq) => $sq->where('full_name', 'like', '%' . $this->search . '%'))
                  ->orWhere('family_code', 'like', '%' . $this->search . '%');
            });
        }

        // Apply province filter
        if ($this->province_id) {
            $query->where('province_id', $this->province_id);
        }

        // Apply city filter
        if ($this->city_id) {
            $query->where('city_id', $this->city_id);
        }

        // Apply district filter
        if ($this->district_id) {
            $query->where('district_id', $this->district_id);
        }

        // Apply region filter
        if ($this->region_id) {
            $query->where('region_id', $this->region_id);
        }

        // Apply organization filter
        if ($this->organization_id) {
            $query->where('organization_id', $this->organization_id);
        }

        // Apply charity filter
        if ($this->charity_id) {
            $query->where('charity_id', $this->charity_id);
        }

        $families = $query->orderBy($this->sortField, $this->sortDirection)->get();

        if ($families->isEmpty()) {
            session()->flash('error', 'هیچ داده‌ای برای دانلود با فیلترهای فعلی وجود ندارد.');
            return null;
        }

        $headings = [
            'کد خانوار',
            'نام سرپرست',
            'کد ملی سرپرست',
            'استان',
            'شهرستان',
            'منطقه',
            'موسسه خیریه',
            'وضعیت بیمه',
            'تاریخ آخرین وضعیت بیمه',
            'نوع بیمه گر',
            'مبلغ کل بیمه (ریال)',
            'سهم بیمه شونده (ریال)',
            'سهم سایر پرداخت کنندگان (ریال)',
            'تعداد اعضا',
        ];

        $dataKeys = [
        'family_code',
        'head.full_name',
        'head.national_code',
        'province.name',
        'city.name',
        'region.name',
        'charity.name',
        'finalInsurances.0.status',
        'finalInsurances.0.updated_at',
        'finalInsurances.0.insurance_payer',
        'finalInsurances.0.premium_amount',
        'finalInsurances.0.premium_amount',
        'finalInsurances.0.premium_amount',
        'members_count',
    ];

        $filename = 'families-' . $this->activeTab . '-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

        return Excel::download(new DynamicDataExport($families, $headings, $dataKeys), $filename);
    }
    
    /**
     * دانلود فایل اکسل برای خانواده‌های موجود در صفحه
     * 
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export()
    {
        // ۱. دریافت داده‌ها (دقیقاً همان منطق قبلی)
        $query = Family::query()->with([
            'province', 'city', 'district', 'region', 'members', 'head', 'charity', 'organization',
            'insurances' => fn($q) => $q->orderBy('created_at', 'desc'),
            'finalInsurances'
        ]);
        
        // اعمال فیلترها بر اساس تب فعال
        switch ($this->activeTab) {
            case 'pending':
                $query->where('wizard_status', InsuranceWizardStep::PENDING->value)
                    ->where('status', '!=', 'deleted');
                break;
            case 'reviewing':
                $query->where('wizard_status', InsuranceWizardStep::REVIEWING->value)
                    ->where('status', '!=', 'deleted');
                break;
            case 'approved':
                $query->where('wizard_status', InsuranceWizardStep::APPROVED->value)
                    ->where('status', '!=', 'deleted');
                break;
            case 'excel':
                $query->where('wizard_status', InsuranceWizardStep::EXCEL_UPLOAD->value)
                    ->where('status', '!=', 'deleted');
                break;
            case 'insured':
                $query->where('wizard_status', InsuranceWizardStep::INSURED->value)
                    ->where('status', '!=', 'deleted');
                break;
            case 'renewal':
                $query->where('wizard_status', InsuranceWizardStep::RENEWAL->value)
                    ->where('status', '!=', 'deleted');
                break;
            case 'deleted':
                $query->where('status', 'deleted');
                break;
            default:
                $query->where('wizard_status', InsuranceWizardStep::PENDING->value)
                    ->where('status', '!=', 'deleted');
                break;
        }
        
        // اعمال فیلترهای جستجو
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('head', fn($sq) => $sq->where('full_name', 'like', '%' . $this->search . '%'))
                  ->orWhere('family_code', 'like', '%' . $this->search . '%');
            });
        }
        
        // اعمال سایر فیلترها
        if ($this->province_id) {
            $query->where('province_id', $this->province_id);
        }
        
        if ($this->city_id) {
            $query->where('city_id', $this->city_id);
        }
        
        if ($this->district_id) {
            $query->where('district_id', $this->district_id);
        }
        
        if ($this->region_id) {
            $query->where('region_id', $this->region_id);
        }
        
        if ($this->organization_id) {
            $query->where('organization_id', $this->organization_id);
        }
        
        if ($this->charity_id) {
            $query->where('charity_id', $this->charity_id);
        }
        
        $families = $query->orderBy($this->sortField, $this->sortDirection)->get();
        
        if ($families->isEmpty()) {
            $this->dispatch('notify', ['message' => 'داده‌ای برای دانلود وجود ندارد.', 'type' => 'error']);
            return null;
        }
        
        // ۲. تعریف هدرها و کلیدها
        $headings = [
            'کد خانوار',
            'نام سرپرست',
            'کد ملی سرپرست',
            'استان',
            'شهرستان',
            'منطقه',
            'موسسه خیریه',
            'وضعیت بیمه',
            'تاریخ آخرین وضعیت بیمه',
            'نوع بیمه گر',
            'مبلغ کل بیمه (ریال)',
            'سهم بیمه شونده (ریال)',
            'سهم سایر پرداخت کنندگان (ریال)',
            'تعداد اعضا',
        ];
        
        $dataKeys = [
            'family_code',
            'head.full_name',
            'head.national_code',
            'province.name',
            'city.name',
            'district.name',
            'charity.name',
            'wizard_status',
            'finalInsurances.0.updated_at',
            'finalInsurances.0.insurance_type',
            'finalInsurances.0.total_premium',
            'finalInsurances.0.insured_share',
            'finalInsurances.0.other_share',
            'members_count',
        ];
        
        // ۳. ایجاد نام فایل
        $fileName = 'families-' . $this->activeTab . '-' . now()->format('Y-m-d') . '.xlsx';
        
        // ۴. استفاده از Excel::download برای ارسال مستقیم فایل به مرورگر
        return Excel::download(new DynamicDataExport($families, $headings, $dataKeys), $fileName);
    }
    
    /**
     * دانلود فایل اکسل برای خانواده‌های نمایش داده شده در تب فعلی
     * 
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadCurrentViewAsExcel()
    {
        try {
            // دریافت خانواده‌های فعلی بر اساس تب و فیلترها
            $families = $this->getFamiliesProperty();
            
            if ($families->isEmpty()) {
                session()->flash('error', 'هیچ خانواده‌ای برای دانلود وجود ندارد.');
                return;
            }

            // تولید نام فایل بر اساس تب فعال
            $tabNames = [
                'renewal' => 'تمدید',
                'pending' => 'در-انتظار-تایید',
                'reviewing' => 'تخصیص-سهمیه',
                'approved' => 'در-انتظار-حمایت',
                'excel' => 'در-انتظار-صدور',
                'deleted' => 'حذف-شده',
                'insured' => 'بیمه-شده'
            ];
            
            $tabName = $tabNames[$this->activeTab] ?? 'خانواده‌ها';
            $fileName = 'families-' . $tabName . '-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

            // ایجاد export با داده‌های فعلی
            return Excel::download(
                new FamilyInsuranceExport($families->pluck('id')->toArray()),
                $fileName
            );
            
        } catch (\Exception $e) {
            Log::error('خطا در دانلود فایل اکسل: ' . $e->getMessage());
            session()->flash('error', 'خطا در دانلود فایل. لطفاً دوباره تلاش کنید.');
        }
    }
    
    /**
     * دانلود فایل اکسل بیمه و انتقال به مرحله بعد
     */

    public function uploadInsuranceExcel()
    {
        Log::info('⏳ شروع فرآیند آپلود اکسل بیمه');
        
        // اعتبارسنجی فایل
        $this->validate([
            'insuranceExcelFile' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);
            
        Log::info('✅ اعتبارسنجی فایل موفق: ' . ($this->insuranceExcelFile ? $this->insuranceExcelFile->getClientOriginalName() : 'نامشخص'));

        try {
            // ذخیره فایل
            $filename = time() . '_' . $this->insuranceExcelFile->getClientOriginalName();
            Log::info('🔄 ذخیره فایل اکسل با نام: ' . $filename);
            
            $path = $this->insuranceExcelFile->storeAs('excel_imports', $filename, 'public');
            $fullPath = storage_path('app/public/' . $path);
            
            Log::info('📂 مسیر کامل فایل: ' . $fullPath);

            // بررسی وجود فایل
            if (!file_exists($fullPath)) {
                Log::error('❌ فایل آپلود شده وجود ندارد: ' . $fullPath);
                throw new \Exception('فایل آپلود شده قابل دسترسی نیست. لطفاً دوباره تلاش کنید.');
            }

            Log::info('✅ فایل با موفقیت آپلود شد و قابل دسترسی است');
            
            // تفویض به سرویس
            $insuranceService = new \App\Services\InsuranceShareService();
            $result = $insuranceService->completeInsuranceFromExcel($fullPath);
            
            // نمایش پیام موفقیت
            $successMessage = "✅ عملیات ایمپورت با موفقیت انجام شد:\n";
            $successMessage .= "🆕 رکوردهای جدید: {$result['created']}\n";
            $successMessage .= "🔄 رکوردهای به‌روزرسانی شده: {$result['updated']}\n";
            $successMessage .= "❌ خطاها: {$result['skipped']}\n";
            $successMessage .= "💰 مجموع مبلغ بیمه: " . number_format($result['total_insurance_amount']) . " تومان";
            
            if (!empty($result['errors'])) {
                $successMessage .= "\n\n⚠️ جزئیات خطاها:\n" . implode("\n", array_slice($result['errors'], 0, 5));
                if (count($result['errors']) > 5) {
                    $successMessage .= "\n... و " . (count($result['errors']) - 5) . " خطای دیگر";
                }
                session()->flash('error', "جزئیات خطاها:\n" . implode("\n", array_slice($result['errors'], 0, 5)));
            }
            
            session()->flash('message', $successMessage);
            
            // پاک کردن فایل آپلود شده
            $this->reset('insuranceExcelFile');
            
            // **FIXED: Proper post-upload workflow**
            // 1. Switch back to pending tab
            $this->setTab('pending');
            
            // 2. Clear cache to ensure fresh data
            $this->clearFamiliesCache();
            
            // 3. Dispatch refresh event for UI update
            $this->dispatch('refreshFamiliesList');
            
            Log::info('🔄 Successfully redirected to pending tab after Excel upload');
            
        } catch (\Exception $e) {
            Log::error('❌ خطا در پردازش فایل اکسل: ' . $e->getMessage());
            Log::error('❌ جزئیات خطا: ' . $e->getTraceAsString());
            
            session()->flash('error', 'خطا در پردازش فایل اکسل: ' . $e->getMessage());
        }
    }
    
    /**
     * تبدیل تاریخ جلالی یا میلادی به تاریخ کاربن
     */
    private function parseJalaliOrGregorianDate($dateString)
    {
        $dateString = trim($dateString);
        
        // الگوهای متداول تاریخ
        $patterns = [
            // الگوی جلالی: 1403/03/15
            '/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/' => function ($matches) {
                return Jalalian::fromFormat('Y/m/d', $matches[1] . '/' . $matches[2] . '/' . $matches[3])->toCarbon();
            },
            // الگوی جلالی: 1403-03-15
            '/^(\d{4})-(\d{1,2})-(\d{1,2})$/' => function ($matches) {
                return Jalalian::fromFormat('Y-m-d', $matches[1] . '-' . $matches[2] . '-' . $matches[3])->toCarbon();
            },
            // الگوی میلادی: 2024/06/04
            '/^(20\d{2})\/(\d{1,2})\/(\d{1,2})$/' => function ($matches) {
                return Carbon::createFromFormat('Y/m/d', $matches[1] . '/' . $matches[2] . '/' . $matches[3]);
            },
            // الگوی میلادی: 2024-06-04
            '/^(20\d{2})-(\d{1,2})-(\d{1,2})$/' => function ($matches) {
                return Carbon::createFromFormat('Y-m-d', $matches[1] . '-' . $matches[2] . '-' . $matches[3]);
            }
        ];
        
        // تلاش برای تطبیق با الگوها
        foreach ($patterns as $pattern => $callback) {
            if (preg_match($pattern, $dateString, $matches)) {
                return $callback($matches);
            }
        }
        
        // اگر هیچ کدام از الگوها مطابقت نداشت
        throw new \Exception("فرمت تاریخ '{$dateString}' قابل تشخیص نیست. لطفاً از فرمت 1403/03/15 یا 2024-06-04 استفاده کنید.");
    }

    private function parseJalaliDate($dateString, $fieldName, $familyCode, $rowIndex)
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            if (preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $dateString)) {
                return \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $dateString)->toCarbon();
            } else {
                throw new \Exception('Invalid format');
            }
        } catch (\Exception $e) {
            throw new \Exception("ردیف " . ($rowIndex + 1) . ": {$fieldName} نامعتبر برای خانواده {$familyCode}: {$dateString} (فرمت صحیح: 1403/03/01)");
        }
    }

    private function validateInsuranceAmount($amount, $familyCode, $rowIndex)
    {
        // اضافه کردن لاگ برای بررسی مقدار ورودی
        Log::info("مقدار حق بیمه دریافتی برای خانواده {$familyCode}: " . var_export($amount, true) . " - نوع داده: " . gettype($amount));
        
        // اگر مقدار آرایه باشد (احتمالاً خروجی اکسل)
        if (is_array($amount)) {
            Log::info("مقدار آرایه‌ای است: " . json_encode($amount));
            if (isset($amount[0])) {
                $amount = $amount[0];
            }
        }
        
        // تبدیل هر چیزی به رشته برای پردازش
        $amount = (string) $amount;
        
        // حذف کاما از اعداد
        $amount = str_replace(',', '', $amount);
        
        // بررسی اگر مقدار رشته است و شامل ریال یا تومان است
        if (strpos($amount, 'ریال') !== false || strpos($amount, 'تومان') !== false) {
            // حذف کلمات "ریال" و "تومان"
            $amount = str_replace(['ریال', 'تومان'], '', $amount);
            // حذف فاصله‌ها
            $amount = trim($amount);
            Log::info("مقدار پس از حذف واحد پول: {$amount}");
        }
        
        // حذف همه کاراکترهای غیر عددی
        $cleanAmount = preg_replace('/[^0-9]/', '', $amount);
        Log::info("مقدار پس از پاکسازی: {$cleanAmount}");
        
        if (empty($cleanAmount) || !is_numeric($cleanAmount) || (int)$cleanAmount <= 0) {
            throw new \Exception("ردیف " . ($rowIndex + 1) . ": مبلغ بیمه نامعتبر برای خانواده {$familyCode}: {$amount}");
        }
        
        $amount = (float) $cleanAmount;
        Log::info("مقدار نهایی حق بیمه برای خانواده {$familyCode}: {$amount}");
        
        return $amount;
    }

    private function validateInsuranceType($type, $familyCode, $rowIndex)
    {
        $validTypes = ['تکمیلی', 'درمانی', 'عمر', 'حوادث', 'سایر', 'تامین اجتماعی'];
        
        if (!in_array($type, $validTypes)) {
            throw new \Exception("ردیف " . ($rowIndex + 1) . ": نوع بیمه نامعتبر برای خانواده {$familyCode}: {$type}");
        }
        return $type;
    }

    private function safeFormatDate($date)
    {
        if (empty($date)) {
            return null;
        }

        if ($date instanceof \Carbon\Carbon) {
            return $date->format('Y-m-d');
        }

        if (is_string($date)) {
            try {
                $carbonDate = \Carbon\Carbon::parse($date);
                return $carbonDate->format('Y-m-d');
            } catch (\Exception $e) {
                return $date;
            }
        }

        return null;
    }

    /**
     * ذخیره مستقیم اطلاعات بیمه در دیتابیس
     * 
     * @param integer $familyId
     * @param string $insuranceType
     * @param float $premium
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return boolean
     */
    private function saveInsuranceDirectly($familyId, $insuranceType, $premium, $startDate = null, $endDate = null)
    {
        try {
            // حذف رکوردهای قبلی با همین نوع بیمه
            DB::table('family_insurances')
                ->where('family_id', $familyId)
                ->where('insurance_type', $insuranceType)
                ->delete();
            
            // ایجاد رکورد جدید
            $startDate = $startDate ?: now();
            $endDate = $endDate ?: now()->addYear();
            
            $insertData = [
                'family_id' => $familyId,
                'insurance_type' => $insuranceType,
                'premium_amount' => $premium,
                'insurance_payer' => Auth::user()->name ?? 'سیستم',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'insured', // تغییر از 'active' به 'insured'
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            // اگر family_code در دیتابیس وجود دارد
            $family = \App\Models\Family::find($familyId);
            if ($family && $family->family_code) {
                $insertData['family_code'] = $family->family_code;
            }
            
            // ذخیره رکورد
            $id = DB::table('family_insurances')->insertGetId($insertData);
            
            // به‌روزرسانی وضعیت wizard خانواده
            $family->setAttribute('wizard_status', InsuranceWizardStep::INSURED->value);
            $family->setAttribute('status', 'insured');
            $family->save();
            
            Log::info("رکورد بیمه جدید با شناسه {$id} برای خانواده {$familyId} با وضعیت 'insured' ایجاد شد");
            
            return $id;
        } catch (\Exception $e) {
            Log::error("خطا در ذخیره اطلاعات بیمه: " . $e->getMessage());
            return false;
        }
    }

    /**
     * تغییر وضعیت خانواده‌های انتخاب شده - فراخوانی شده از طریق جاوااسکریپت یا livewire blade
     */
    public function updateFamiliesStatus($familyIds, $targetStatus, $currentStatus = null)
    {
        if (empty($familyIds)) {
            session()->flash('error', 'هیچ خانواده‌ای انتخاب نشده است.');
            return;
        }
        
        DB::beginTransaction();
        try {
            $batchId = 'batch_' . time() . '_' . uniqid();
            $count = 0;
            
            foreach ($familyIds as $familyId) {
                $family = Family::find($familyId);
                if (!$family) continue;
                
                // اگر از قبل wizard شروع نشده، آن را شروع می‌کنیم
                if (!$family->wizard_status) {
                    $family->syncWizardStatus();
                }
                
                $currentWizardStep = $family->wizard_status;
                if (is_string($currentWizardStep)) {
                    $currentWizardStep = InsuranceWizardStep::from($currentWizardStep);
                }
                
                $targetWizardStep = null;
                
                // تعیین مرحله wizard متناظر با وضعیت قدیمی
                if ($targetStatus === 'pending') {
                    $targetWizardStep = InsuranceWizardStep::PENDING;
                    $family->status = 'pending';
                } elseif ($targetStatus === 'reviewing') {
                    $targetWizardStep = InsuranceWizardStep::REVIEWING;
                    $family->status = 'reviewing';
                } elseif ($targetStatus === 'approved') {
                    // اگر از reviewing به approved می‌رویم، ابتدا باید از مرحله سهم‌بندی عبور کنیم
                    if ($currentStatus === 'reviewing' || $currentWizardStep === InsuranceWizardStep::REVIEWING) {
                        $targetWizardStep = InsuranceWizardStep::SHARE_ALLOCATION;
                        $family->status = 'reviewing'; // هنوز وضعیت قدیمی reviewing است
                        
                        // نیاز به سهم‌بندی داریم
                        $requireShares = true;
                    } else {
                        $targetWizardStep = InsuranceWizardStep::APPROVED;
                        $family->status = 'approved';
                    }
                } elseif ($targetStatus === 'insured') {
                    $targetWizardStep = InsuranceWizardStep::INSURED;
                    $family->status = 'insured';
                    $family->is_insured = true;
                } elseif ($targetStatus === 'renewal') {
                    $targetWizardStep = InsuranceWizardStep::RENEWAL;
                    $family->status = 'renewal';
                }
                
                if ($targetWizardStep) {
                    // استفاده از setAttribute به جای دسترسی مستقیم
                    $family->setAttribute('wizard_status', $targetWizardStep->value);
                    $family->save();
                    
                    // ثبت لاگ تغییر وضعیت
                    FamilyStatusLog::logTransition(
                        $family,
                        $currentWizardStep,
                        $targetWizardStep,
                        "تغییر وضعیت از {$currentWizardStep->label()} به {$targetWizardStep->label()} توسط کاربر",
                        ['batch_id' => $batchId]
                    );
                    
                    $count++;
                }
            }
            
            DB::commit();
            
            session()->flash('message', "{$count} خانواده با موفقیت به‌روزرسانی شدند.");
            
            // به‌روزرسانی کش
            $this->clearFamiliesCache();
            
            // ریست کردن انتخاب‌ها و رفرش صفحه
            $this->selected = [];
            $this->selectAll = false;
            $this->resetPage();
            $this->dispatch('reset-checkboxes');
            
            // بررسی نیاز به سهم‌بندی
            if (isset($requireShares) && $requireShares) {
                $this->dispatch('openShareAllocationModal', $familyIds);
            }
            
            return [
                'success' => true,
                'message' => "{$count} خانواده با موفقیت به‌روزرسانی شدند.",
                'require_shares' => isset($requireShares) && $requireShares,
                'family_ids' => $familyIds
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('خطا در به‌روزرسانی وضعیت خانواده‌ها: ' . $e->getMessage());
            
            session()->flash('error', 'خطا در به‌روزرسانی وضعیت خانواده‌ها: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در به‌روزرسانی وضعیت خانواده‌ها: ' . $e->getMessage()
            ];
        }
    }

    /**
     * افزودن متد برای کپی کردن متن (شماره موبایل/شبا)
     */
    public function copyText($text)
    {
        $this->dispatch('showToast', ['type' => 'success', 'message' => 'متن با موفقیت کپی شد: ' . $text]);
    }

    /**
     * به‌روزرسانی لیست خانواده‌ها
     */
    public function refreshFamiliesList()
    {
            $this->clearFamiliesCache();
        // فراخوانی رندر مجدد
        $this->render();
    }

    /**
     * بستن مودال سهم‌بندی
     */
    public function onCloseShareModal()
    {
        Log::info('🔄 onCloseShareModal method called');
        $this->dispatch('closeShareModal');
        
        // به‌روزرسانی کش برای به‌روزرسانی لیست‌ها
        $this->clearFamiliesCache();
        
        // رفرش صفحه
        $this->resetPage();
    }

    /**
     * تغییر وضعیت خانواده به بیمه شده و ثبت در دیتابیس
     * 
     * @param \App\Models\Family $family
     * @param string $familyCode
     * @return bool
     */
    private function updateFamilyStatus($family, $familyCode)
    {
        try {
            // فقط اگر وضعیت approved باشد، آن را تغییر دهیم
            if ($family->status === 'approved') {
                $oldStatus = $family->status;
                $family->status = 'insured';
                $result = $family->save();
                
                if ($result) {
                    Log::info("✅ تغییر وضعیت خانواده {$familyCode} از {$oldStatus} به insured با موفقیت انجام شد");
                    return true;
                } else {
                    Log::error("❌ خطا در تغییر وضعیت خانواده {$familyCode} از {$oldStatus} به insured");
                    return false;
                }
            } elseif ($family->status === 'insured') {
                // در صورتی که قبلاً بیمه شده باشد، نیازی به تغییر نیست
                Log::info("ℹ️ خانواده {$familyCode} قبلاً بیمه شده است");
                return true;
            } else {
                Log::warning("⚠️ خانواده {$familyCode} در وضعیت {$family->status} است و نمی‌تواند به بیمه شده تغییر کند");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("❌ خطای استثنا در تغییر وضعیت خانواده {$familyCode}: " . $e->getMessage());
            return false;
        }
    }

    // متد فراخوانی شده بعد از ذخیره سهم‌بندی
    public function onSharesAllocated()
    {
        Log::info('🚀 onSharesAllocated - متد فراخوانی شد با ' . count($this->selected) . ' خانواده انتخاب شده', [
            'selected_family_ids' => $this->selected
        ]);
        
        // بررسی وضعیت فعلی خانواده‌ها و انتقال به مرحله approved
        $families = Family::whereIn('id', $this->selected)->get();
        Log::info('👪 onSharesAllocated - تعداد خانواده‌های یافت شده: ' . $families->count());
        
        DB::beginTransaction();
        try {
            $batchId = 'share_allocation_' . time() . '_' . uniqid();
            $count = 0;
            
            foreach ($families as $family) {
                Log::info('🔄 onSharesAllocated - پردازش خانواده', [
                    'family_id' => $family->id,
                    'family_code' => $family->family_code ?? 'نامشخص',
                    'current_status' => $family->wizard_status
                ]);
                
                // تنظیم وضعیت wizard به APPROVED
                $currentStep = $family->wizard_status;
                if (is_string($currentStep)) {
                    $currentStep = InsuranceWizardStep::from($currentStep);
                }
                
                Log::info('🔄 onSharesAllocated - تغییر وضعیت خانواده به APPROVED', [
                    'family_id' => $family->id,
                    'from_status' => $currentStep ? $currentStep->value : 'نامشخص'
                ]);
                
                // تغییر وضعیت به APPROVED
                $family->setAttribute('wizard_status', InsuranceWizardStep::APPROVED->value);
                $family->setAttribute('status', 'approved');
                $family->save();
                
                Log::info('✅ onSharesAllocated - وضعیت خانواده با موفقیت به‌روزرسانی شد', [
                    'family_id' => $family->id,
                    'new_status' => $family->wizard_status,
                    'new_db_status' => $family->status
                ]);
                    
                // به‌روزرسانی وضعیت در جدول family_insurances
                $insurances = FamilyInsurance::where('family_id', $family->id)
                    ->where(function($query) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                    })
                    ->get();
                
                Log::info('🔍 onSharesAllocated - تعداد بیمه‌های فعال خانواده: ' . $insurances->count());
                    
                foreach ($insurances as $insurance) {
                    $insurance->status = 'pending';  // وضعیت در انتظار آپلود اکسل
                    $insurance->save();
                    
                    Log::info("✅ onSharesAllocated - وضعیت بیمه شماره {$insurance->id} برای خانواده {$family->id} به pending تغییر یافت");
                }
                    
                // ثبت لاگ تغییر وضعیت
                try {
                    FamilyStatusLog::create([
                        'family_id' => $family->id,
                        'user_id' => Auth::id(),
                        'from_status' => $currentStep->value,
                        'to_status' => InsuranceWizardStep::APPROVED->value,
                        'comments' => "تغییر وضعیت به تایید شده پس از تخصیص سهم",
                        'batch_id' => $batchId
                    ]);
                    
                    Log::info("✅ onSharesAllocated - لاگ تغییر وضعیت برای خانواده {$family->id} ثبت شد");
                } catch (\Exception $e) {
                    Log::warning("⚠️ onSharesAllocated - خطا در ثبت لاگ تغییر وضعیت: " . $e->getMessage());
                }
                    
                $count++;
            }
            
            DB::commit();
            Log::info("✅ onSharesAllocated - {$count} خانواده به وضعیت 'تایید شده' منتقل شدند");
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('❌ onSharesAllocated - خطا در تغییر وضعیت خانواده‌ها پس از تخصیص سهم: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);
        }
            
        // پاک کردن کش برای به‌روزرسانی لیست‌ها
        $this->clearFamiliesCache();
        
        
        // ریست کردن انتخاب‌ها
        $this->selected = [];
        $this->selectAll = false;
        $this->dispatch('reset-checkboxes');
        
        // نمایش پیام موفقیت
        session()->flash('message', 'سهم‌بندی با موفقیت انجام شد و خانواده‌ها به مرحله دانلود اکسل منتقل شدند');
        
        // انتقال اتوماتیک به تب approved
        Log::info('🔄 onSharesAllocated - انتقال به تب approved');
        $this->setTab('approved');
    }

    /**
     * هندل کردن به‌روزرسانی وضعیت خانواده‌ها از طریق لایوایر
     * 
     * @param mixed $data
     * @return array
     */
    public function handleUpdateFamiliesStatus($data = null)
    {
        if ($data === null) {
            $data = [];
        }
        
        // اگر $data یک آرایه است، آن را مستقیماً استفاده کنیم
        if (is_array($data)) {
            $familyIds = $data['familyIds'] ?? [];
            $targetStatus = $data['targetStatus'] ?? '';
            $currentStatus = $data['currentStatus'] ?? null;
        } else {
            // اگر $data یک آبجکت است، سعی کنیم تبدیل کنیم
            $familyIds = [];
            $targetStatus = '';
            $currentStatus = null;
            
            try {
                $dataArray = (array)$data;
                $familyIds = $dataArray['familyIds'] ?? [];
                $targetStatus = $dataArray['targetStatus'] ?? '';
                $currentStatus = $dataArray['currentStatus'] ?? null;
            } catch (\Exception $e) {
                Log::error('خطا در تبدیل داده‌ها: ' . $e->getMessage());
            }
        }
        
        $result = $this->updateFamiliesStatus($familyIds, $targetStatus, $currentStatus);
        
        // ارسال رویداد wizardUpdated برای به‌روزرسانی رابط کاربری
        $this->dispatch('wizardUpdated', $result);
        
        return $result;
    }

    /**
     * انتخاب یک خانواده برای تمدید بیمه‌نامه
     * 
     * @param int $familyId
     * @return void
     */
    public function selectForRenewal($familyId)
    {
        $this->selected = [$familyId];
        
        // تنظیم تاریخ پیش‌فرض به تاریخ امروز
        $this->renewalDate = Carbon::today()->format('Y-m-d');
        
        // باز کردن مودال تمدید
        $this->dispatch('openRenewalModal');
    }
    
    /**
     * تمدید بیمه‌نامه‌ برای خانواده‌های انتخاب شده
     * 
     * @return void
     */
    public function renewInsurance()
    {
        if (empty($this->selected)) {
            session()->flash('error', 'لطفاً حداقل یک خانواده را انتخاب کنید.');
            return;
        }
        
        DB::beginTransaction();
        try {
            $batchId = 'renewal_' . time() . '_' . uniqid();
            $count = 0;
            $startDate = Carbon::parse($this->renewalDate);
            
            // محاسبه تاریخ پایان بر اساس دوره تمدید
            $endDate = $startDate->copy()->addMonths($this->renewalPeriod);
            
            foreach ($this->selected as $familyId) {
                $family = Family::find($familyId);
                if (!$family) continue;
                
                // به‌روزرسانی اطلاعات بیمه‌نامه
                $family->insurance_issue_date = $startDate;
                $family->insurance_expiry_date = $endDate;
                $family->setAttribute('wizard_status', InsuranceWizardStep::INSURED->value);
                $family->status = 'insured';
                $family->is_insured = true;
                $family->save();
                
                // ایجاد یا به‌روزرسانی رکورد بیمه
                $insurance = FamilyInsurance::updateOrCreate(
                    ['family_id' => $family->id],
                    [
                        'issue_date' => $startDate,
                        'expiry_date' => $endDate,
                        'renewal_count' => DB::raw('renewal_count + 1'),
                        'last_renewal_date' => Carbon::now(),
                        'renewal_note' => $this->renewalNote,
                        'renewed_by' => Auth::id(),
                    ]
                );
                
                // ثبت لاگ تمدید بیمه
                FamilyStatusLog::logTransition(
                    $family,
                    InsuranceWizardStep::RENEWAL,
                    InsuranceWizardStep::INSURED,
                    "تمدید بیمه‌نامه برای مدت {$this->renewalPeriod} ماه",
                    [
                        'batch_id' => $batchId,
                        'issue_date' => $startDate->format('Y-m-d'),
                        'expiry_date' => $endDate->format('Y-m-d'),
                        'renewal_note' => $this->renewalNote
                    ]
                );
                
                $count++;
            }
            
            DB::commit();
            
            // پاکسازی متغیرها
            $this->selected = [];
            $this->selectAll = false;
            $this->renewalNote = '';
            
            // به‌روزرسانی کش
            $this->clearFamiliesCache();
            
            // ارسال رویداد اتمام تمدید
            $this->dispatch('renewalComplete');
            session()->flash('message', "{$count} بیمه‌نامه با موفقیت تمدید شد.");
            
            // به‌روزرسانی UI
            $this->resetPage();
            $this->dispatch('reset-checkboxes');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('خطا در تمدید بیمه‌نامه: ' . $e->getMessage());
            session()->flash('error', 'خطا در تمدید بیمه‌نامه: ' . $e->getMessage());
        }
    }
    
    /**
     * بازگشت به مرحله قبل برای خانواده‌های انتخاب شده
     */
    public function returnToPreviousStage()
    {
        if (empty($this->selected)) {
            session()->flash('error', 'لطفاً حداقل یک خانواده را انتخاب کنید');
            return;
        }
        
        $this->moveToPreviousStep();
    }

    /**
     * مرتب‌سازی لیست خانواده‌ها بر اساس فیلد انتخابی
     * 
     * @param string $field
     * @return void
     */
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        
        // ریست کردن صفحه بندی
        $this->resetPage();
        
        // پاکسازی کش
        $this->clearFamiliesCache();
    }

    public function render()
    {
        $families = $this->getFamiliesProperty();
        return view('livewire.insurance.families-approval', [
            'families' => $families,
            'totalMembers' => $this->getTotalMembersProperty()
        ]);
    }

    public function moveToPreviousStep()
    {
        Log::info('🔙 moveToPreviousStep method called', [
            'selected_ids' => $this->selected,
            'active_tab' => $this->activeTab ?? 'not_set',
            'user_id' => Auth::id(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        if (empty($this->selected)) {
            $errorMsg = 'هیچ خانواده‌ای برای بازگشت به مرحله قبل انتخاب نشده است. لطفاً ابتدا خانواده‌های مورد نظر را انتخاب کنید.';
            Log::warning('❌ moveToPreviousStep: No families selected.', ['active_tab' => $this->activeTab ?? 'not_set', 'user_id' => Auth::id()]);
            $this->dispatch('show-persistent-error', message: $errorMsg);
            return;
        }

        try {
            Log::info('🔍 moveToPreviousStep: Fetching families from database.', [
                'selected_count' => count($this->selected),
                'selected_ids' => $this->selected
            ]);
            
            $families = Family::whereIn('id', $this->selected)->get();
            
            Log::info('📋 moveToPreviousStep: Families fetched from database.', [
                'fetched_count' => $families->count(),
                'first_few_ids' => $families->take(5)->pluck('id')->toArray()
            ]);
            
            if ($families->isEmpty()) {
                $errorMsg = 'خانواده‌های انتخاب شده یافت نشدند یا مشکلی در دریافت آن‌ها وجود دارد.';
                Log::warning('❌ moveToPreviousStep: Selected families not found or query failed.', [
                    'selected_ids' => $this->selected,
                    'active_tab' => $this->activeTab ?? 'not_set',
                    'user_id' => Auth::id()
                ]);
                $this->dispatch('show-persistent-error', message: $errorMsg);
                return;
            }

            $batchId = 'move_prev_step_' . time() . '_' . uniqid();
            $movedCount = 0;
            $cantMoveCount = 0;
            $errors = [];
            $successMessages = [];

            Log::info('🔄 moveToPreviousStep: Starting database transaction.');
            DB::beginTransaction();

            try {
                Log::info('🔄 moveToPreviousStep: Processing families.', [
                    'batch_id' => $batchId,
                    'total_families' => $families->count()
                ]);

                foreach ($families as $family) {
                    Log::info('👨‍👩‍👧‍👦 moveToPreviousStep: Processing family.', [
                        'family_id' => $family->id,
                        'family_code' => $family->family_code ?? 'unknown',
                        'current_status_value' => $family->wizard_status
                    ]);
                    
                    $currentStepValue = $family->wizard_status;
                    $currentStepEnum = null;

                    if (is_string($currentStepValue) && !empty($currentStepValue)) {
                        try {
                            $currentStepEnum = InsuranceWizardStep::from($currentStepValue);
                            Log::debug('✅ moveToPreviousStep: Current step enum created from string.', [
                                'family_id' => $family->id,
                                'current_step_value' => $currentStepValue,
                                'current_step_enum' => $currentStepEnum->value
                            ]);
                        } catch (\ValueError $e) {
                            Log::error("❌ moveToPreviousStep: Invalid wizard_status string value '{$currentStepValue}' for family ID {$family->id}. Error: " . $e->getMessage());
                            $errors[] = "خانواده {$family->family_code}: وضعیت فعلی ('{$currentStepValue}') نامعتبر است.";
                            $cantMoveCount++;
                            continue;
                        }
                    } elseif ($currentStepValue instanceof InsuranceWizardStep) {
                        $currentStepEnum = $currentStepValue;
                        Log::debug('✅ moveToPreviousStep: Current step is already an enum instance.', [
                            'family_id' => $family->id,
                            'current_step_enum' => $currentStepEnum->value
                        ]);
                    } else {
                        Log::error("❌ moveToPreviousStep: Unknown or empty wizard_status for family ID {$family->id}.", ['value_type' => gettype($currentStepValue), 'value' => print_r($currentStepValue, true)]);
                        $errors[] = "خانواده {$family->family_code}: وضعیت فعلی تعریف نشده یا خالی است.";
                        $cantMoveCount++;
                        continue;
                    }

                    $previousStepEnum = $currentStepEnum->previousStep();
                    Log::debug('🔄 moveToPreviousStep: Previous step determined.', [
                        'family_id' => $family->id,
                        'current_step_for_previous_logic' => $currentStepEnum->value, // Log the exact value used for previousStep()
                        'previous_step_result' => $previousStepEnum ? $previousStepEnum->value : 'null'
                    ]);

                    if ($previousStepEnum) {
                        try {
                            $family->wizard_status = $previousStepEnum->value;
                            
                            // به‌روزرسانی وضعیت قدیمی
                            switch ($previousStepEnum->value) {
                                case InsuranceWizardStep::PENDING->value:
                                    $family->setAttribute('status', 'pending');
                                    break;
                                case InsuranceWizardStep::REVIEWING->value:
                                    $family->setAttribute('status', 'reviewing');
                                    break;
                                case InsuranceWizardStep::SHARE_ALLOCATION->value:
                                case InsuranceWizardStep::APPROVED->value:
                                    $family->setAttribute('status', 'approved');
                                    break;
                                case InsuranceWizardStep::EXCEL_UPLOAD->value:
                                case InsuranceWizardStep::INSURED->value:
                                    $family->setAttribute('status', 'insured');
                                    break;
                                case InsuranceWizardStep::RENEWAL->value:
                                    $family->setAttribute('status', 'renewal');
                                    break;
                            }
                            
                            $family->save();
                            
                            Log::info('✅ moveToPreviousStep: Family status updated in DB.', [
                                'family_id' => $family->id,
                                'from_status' => $currentStepEnum->value,
                                'to_status' => $previousStepEnum->value,
                                'legacy_status' => $family->status
                            ]);

                            FamilyStatusLog::create([
                                'family_id' => $family->id,
                                'user_id' => Auth::id(),
                                'from_status' => $currentStepEnum->value,
                                'to_status' => $previousStepEnum->value,
                                'comments' => 'بازگشت به مرحله قبل توسط کاربر: ' . Auth::user()?->name,
                                'batch_id' => $batchId,
                            ]);
                            
                            Log::info('📝 moveToPreviousStep: Family status log created.', [
                                'family_id' => $family->id,
                                'batch_id' => $batchId
                            ]);
                            
                            $movedCount++;
                        } catch (\Exception $e) {
                            Log::error('❌ moveToPreviousStep: Error updating family status in DB.', [
                                'family_id' => $family->id,
                                'error' => $e->getMessage(),
                                'trace_snippet' => substr($e->getTraceAsString(), 0, 500)
                            ]);
                            $errors[] = "خطا در به‌روزرسانی وضعیت خانواده {$family->family_code}: " . $e->getMessage();
                            $cantMoveCount++;
                        }
                    } else {
                        Log::warning('⚠️ moveToPreviousStep: Cannot move family back - already at first step or no previous step defined.', [
                            'family_id' => $family->id,
                            'current_step' => $currentStepEnum->value,
                            'current_step_label' => $currentStepEnum->label()
                        ]);
                        $errors[] = "خانواده {$family->family_code} در اولین مرحله ({$currentStepEnum->label()}) قرار دارد یا مرحله قبلی برای آن تعریف نشده است.";
                        $cantMoveCount++;
                    }
                }

                Log::info('📊 moveToPreviousStep: Finished processing families.', [
                    'moved_count' => $movedCount,
                    'failed_count' => $cantMoveCount,
                    'errors_count' => count($errors)
                ]);

                if ($movedCount > 0) {
                    $successMessages[] = "{$movedCount} خانواده با موفقیت به مرحله قبل منتقل شدند.";
                    Log::info('✅ moveToPreviousStep: ' . $successMessages[0]);
                }

                Log::info('✅ moveToPreviousStep: Committing transaction.');
                DB::commit();

                // UI Updates after successful commit
                if (method_exists($this, 'clearFamiliesCache')) {
                    Log::info('🧹 moveToPreviousStep: Clearing families cache.');
                    $this->clearFamiliesCache();
                }
                
                // Refresh the current tab's data
                Log::info('🔄 moveToPreviousStep: Refreshing current tab data.', ['active_tab' => $this->activeTab]);
                $this->setTab($this->activeTab, false); // false to not reset selections here, as we do it next

                // Reset selections
                $this->selected = [];
                $this->selectAll = false;
                Log::info('🔄 moveToPreviousStep: Dispatching reset-checkboxes event.');
                $this->dispatch('reset-checkboxes');

                // Display messages
                if (!empty($successMessages) && empty($errors)) {
                    session()->flash('message', implode(' ', $successMessages));
                    Log::info('✅ moveToPreviousStep: Success message flashed: ' . implode(' ', $successMessages));
                } elseif (!empty($errors)) {
                    $finalMessage = implode(' ', array_merge($successMessages, $errors));
                    // Use persistent error for combined messages if any error occurred
                    $this->dispatch('show-persistent-error', message: $finalMessage);
                    Log::warning('⚠️ moveToPreviousStep: Persistent error/warning message dispatched: ' . $finalMessage);
                }

            } catch (\Exception $e) {
                Log::error('❌ moveToPreviousStep: Error within transaction, rolling back.', [
                    'error' => $e->getMessage(),
                    'trace_snippet' => substr($e->getTraceAsString(), 0, 500)
                ]);
                DB::rollback();
                $errorMsg = 'خطا در سیستم هنگام انتقال خانواده‌ها به مرحله قبل: ' . $e->getMessage();
                $this->dispatch('show-persistent-error', message: $errorMsg);
                Log::error('❌ moveToPreviousStep: Transaction failed and rolled back.', [
                    'original_error' => $e->getMessage(),
                    'selected_ids' => $this->selected
                ]);
            }
        } catch (\Exception $e) {
            $errorMsg = 'خطای سیستمی: ' . $e->getMessage();
            $this->dispatch('show-persistent-error', message: $errorMsg);
            Log::error('❌ moveToPreviousStep: Fatal error outside transaction.', [
                'error' => $e->getMessage(),
                'trace_snippet' => substr($e->getTraceAsString(), 0, 500),
                'selected_ids' => $this->selected
            ]);
        }
        
        Log::info('🏁 moveToPreviousStep: Method execution completed.');
    }

    public function openDeleteModal()
    {
        // تنظیم مستقیم متغیر showDeleteModal
        $this->showDeleteModal = true;
        
        // ارسال رویداد به جاوااسکریپت - استفاده از dispatch به جای dispatchBrowserEvent در Livewire 3
        $this->dispatch('showDeleteModal');
        
        Log::info('✅ Delete modal should be shown now, showDeleteModal = true');
    }
    
    /**
     * بستن مودال حذف
     */
    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        
        // ارسال رویداد به جاوااسکریپت - استفاده از dispatch به جای dispatchBrowserEvent در Livewire 3
        $this->dispatch('closeDeleteModal');
        
        Log::info('🔒 Delete modal closed');
    }

    /**
     * نمایش تایید حذف برای یک خانواده خاص
     */
    public function showDeleteSingleConfirmation($familyId)
    {
        Log::info('📢 showDeleteSingleConfirmation method called for family ID: ' . $familyId);
        
        // تنظیم آرایه selected با یک آیدی خانواده
        $this->selected = [(string)$familyId];
        
        // استفاده از متد باز کردن مودال
        $this->openDeleteModal();
        
        Log::info('✅ Delete modal should be shown now for family ID: ' . $familyId);
    }

    /**
     * متدهای مربوط به صفحه‌بندی
     */
    // Pagination is handled by WithPagination trait

    /**
     * نمایش مودال حذف برای خانواده‌های انتخاب شده
     */
    public function showDeleteConfirmation()
    {
        Log::info('📢 showDeleteConfirmation method called for ' . count($this->selected) . ' selected families');
        
        // بررسی انتخاب حداقل یک خانواده
        if (empty($this->selected)) {
            session()->flash('error', 'لطفاً حداقل یک خانواده را انتخاب کنید');
            Log::warning('⚠️ No families selected for deletion');
            return;
        }
        
        // استفاده از متد باز کردن مودال
        $this->openDeleteModal();
        
        Log::info('✅ Delete modal opened for ' . count($this->selected) . ' selected families');
    }

    public function handlePageRefresh()
    {
        $this->clearFamiliesCache();
        Log::info('🔄 Page refreshed - Cache cleared');
    }

    /**
     * Get current filters for Alpine.js download functionality
     */
    public function getFilters()
    {
        return [
            'activeTab' => $this->activeTab, // اضافه کردن تب فعال به فیلترها
            'search' => $this->search ?? null,
            'province_id' => $this->province_id ?? null,
            'city_id' => $this->city_id ?? null,
            'district_id' => $this->district_id ?? null,
            'region_id' => $this->region_id ?? null,
            'organization_id' => $this->organization_id ?? null,
            'charity_id' => $this->charity_id ?? null,
            // 'selected' را حذف کردیم چون در body به صورت جداگانه ارسال می‌شود
        ];
    }
    
    /**
     * دریافت تعداد کل خانواده‌های نمایش داده شده در تب فعلی
     */
    public function getCurrentViewCount()
    {
        return $this->getFamiliesProperty()->total();
    }
}
