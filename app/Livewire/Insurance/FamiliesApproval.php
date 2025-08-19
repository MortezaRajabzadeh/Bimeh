<?php

namespace App\Livewire\Insurance;

use Livewire\Component;
use App\Models\Family;
use App\Models\RankSetting;
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
use App\Models\FamilyFundingAllocation;
use App\Services\InsuranceShareService;
use App\Models\FamilyStatusLog;

use Carbon\Carbon;
use App\Exports\DynamicDataExport;
use App\Repositories\FamilyRepository;

use App\Enums\FamilyStatus as FamilyStatusEnum;
use App\Enums\InsuranceWizardStep;
use App\Services\InsuranceImportLogger;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use App\QueryFilters\FamilyRankingFilter;
use App\QuerySorts\FamilyRankingSort;
use App\Helpers\ProblemTypeHelper;
use App\Models\SavedFilter;
use App\Helpers\DateHelper;

class FamiliesApproval extends Component
{
    use WithFileUploads, WithPagination;

    protected FamilyRepository $familyRepository;

    public function boot(FamilyRepository $familyRepository)
    {
        $this->familyRepository = $familyRepository;
    }

    public string $activeTab = 'pending';
    public bool $selectAll = false;
    public array $selected = [];
    public $tab = 'pending'; // اضافه کردن متغیر tab

    // متغیرهای جدید برای مودال‌ها
    public bool $showDeleteModal = false;
    public bool $showExcelUploadModal = false;
    public ?string $deleteReason = null;

    public $cached_tab = null;
    public $is_loading = false;
    public $expandedFamily = null;
    public $insuranceExcelFile;
    public $perPage = 15;

    // متغیرهای مورد نیاز برای فیلترها
    public $tempFilters = [];
    public $activeFilters = [];
    public $showRankModal = false;

    // متغیرهای مورد نیاز برای فیلتر مودال
    public $provinces = [];
    public $cities = [];
    public $regions = [];
    public $organizations = [];
    public $rankSettings;

    // متغیرهای مورد نیاز برای مودال تنظیمات رتبه‌بندی
    public $editingRankSettingId = null;
    public $isCreatingNew = false;
    public $editingRankSetting = [
        'name' => '',
        'weight' => 5,
        'description' => '',
        'requires_document' => true,
        'color' => '#60A5FA'
    ];

    // متغیرهای فرم rank setting
    public $rankSettingName = '';
    public $rankSettingDescription = '';
    public $rankSettingWeight = 5;
    public $rankSettingColor = '#60A5FA';
    public $rankSettingNeedsDoc = true;
    public $rankingSchemes = [];
    public $availableCriteria = [];
    public $selectedSchemeId = null;
    public array $schemeWeights = [];
    public $newSchemeName = '';
    public $newSchemeDescription = '';
    public $appliedSchemeId = null;
    public $selectedCriteria = [];
    public $criteriaRequireDocument = [];

    public $searchTerm = '';
    public $sortField = 'created_at';
    public $sortDirection = 'asc';
    public $sortByProblemType = ''; // برای ذخیره نوع مشکل انتخاب شده برای مرتب‌سازی


    // لیست انواع مشکلات برای منوی کشویی
    public $problemTypes = [
        'addiction' => 'اعتیاد',
        'unemployment' => 'بیکاری',
        'disability' => 'معلولیت',
        'chronic_illness' => 'بیماری مزمن',
        'single_parent' => 'سرپرست خانوار زن',
        'elderly' => 'سالمندی',
        'other' => 'سایر'
    ];

    // متغیرهای تمدید بیمه
    public $renewalPeriod = 12;
    public $renewalDate = null;
    public $renewalNote = '';

    // متغیرهای جستجو و فیلتر
    public $search = '';
    public $status = '';
    public $province_id = null;
    public $city_id = null;
    public $district_id = null;
    public $region_id = null;
    public $organization_id = null;
    public $charity_id = null;

    // Add this line to fix the error
    public $charity = '';

    // متغیرهای فیلتر رتبه
    public $province = '';

    /**
     * تعیین اینکه آیا ستون تاریخ پایان بیمه باید نمایش داده شود یا خیر
     *
     * @return bool
     */
    public function showInsuranceEndDate()
    {
        // نمایش ستون فقط در تب "بیمه‌شده‌ها"
        return $this->activeTab === 'insured';
    }
    public $city = '';
    public $deprivation_rank = '';
    public $family_rank_range = '';
    public $specific_criteria = '';
    public $availableRankSettings = [];

    // متغیر برای نگهداری شماره صفحه پیجینیشن
    public $page = 1;

    protected $paginationTheme = 'tailwind';

    // تعریف متغیرهای queryString
    protected $queryString = [
        'search' => ['except' => ''],
        'province_id' => ['except' => ''],
        'city_id' => ['except' => ''],
        'status' => ['except' => ''],
        'sortField' => ['except' => 'id'],
        'sortDirection' => ['except' => 'desc'],
        'specific_criteria' => ['except' => ''],
        'page' => ['except' => 1],
        'activeTab' => ['except' => 'pending'],
        'family_rank_range' => ['except' => ''],
    ];

    // ایجاد لیستنر برای ذخیره سهم‌بندی
    protected function getListeners()
    {
        return [
            'sharesAllocated' => 'handleSharesAllocated',
            'reset-checkboxes' => 'onResetCheckboxes',
            'switchToReviewingTab' => 'switchToReviewingTab',
            'updateFamiliesStatus' => 'handleUpdateFamiliesStatus',
            'refreshFamiliesList' => 'refreshFamiliesList',
            'closeShareModal' => 'onCloseShareModal',
            'selectForRenewal' => 'selectForRenewal',
            'renewInsurance' => 'renewInsurance',
            'pageRefreshed' => 'handlePageRefresh' // اضافه کردن listener جدید
        ];
    }


    /**
     * مدیریت رویداد پس از تخصیص موفق سهم‌ها
     * این متد به صورت خودکار پس از تخصیص سهم‌ها فراخوانی می‌شود و خانواده‌ها را به مرحله بعد منتقل می‌کند
     *
     * @param array $data اطلاعات ارسالی از رویداد شامل 'family_ids'
     */
    public function handleSharesAllocated(array $data = [])
    {
        // 1. لاگ دریافت رویداد
        Log::info('FamiliesApproval::handleSharesAllocated - رویداد تخصیص سهم دریافت شد', [
            'selected_count' => count($this->selected),
            'selected_ids' => $this->selected,
            'active_tab' => $this->activeTab,
            'data' => $data,
            'time' => now()->format('Y-m-d H:i:s.u'),
        ]);

        // 2. دریافت ID خانواده‌ها از رویداد اگر ارسال شده باشد
        $familyIds = $data['family_ids'] ?? [];

        // 3. اگر ID خانواده‌ها از طریق رویداد ارسال شده باشد، آنها را به selected اضافه می‌کنیم
        if (!empty($familyIds)) {
            $this->selected = $familyIds;
            Log::info('FamiliesApproval::handleSharesAllocated - IDهای خانواده از رویداد دریافت شدند', [
                'family_ids' => $familyIds
            ]);
        }

        // 4. اگر هیچ خانواده‌ای انتخاب نشده باشد، پیام خطا نمایش می‌دهیم
        if (empty($this->selected)) {
            Log::warning('handleSharesAllocated called with no selected families.');
            session()->flash('error', 'هیچ خانواده‌ای برای انتقال انتخاب نشده است.');
            return;
        }

        // 5. انتقال خانواده‌ها به مرحله بعد
        $this->moveSelectedToNextWizardStep();

        // 6. هدایت کاربر به تب بعدی (approved)
        $this->setTab('approved');

        // 7. نمایش پیام موفقیت
        session()->flash('message', 'سهم‌های بیمه با موفقیت تخصیص داده شدند و خانواده‌ها به مرحله بعد منتقل شدند.');

        // 8. رویدادی برای ریست کردن چک‌باکس‌ها در view
        $this->dispatch('reset-checkboxes');
    }

    /**
     * اصلاح وضعیت خانواده‌های گیر کرده در مرحله تخصیص سهمیه
     * این متد به صورت دستی فراخوانی می‌شود تا خانواده‌هایی که در وضعیت share_allocation مانده‌اند را به approved منتقل کند
     */
    public function fixShareAllocationFamilies()
    {
        try {
            // یافتن خانواده‌هایی که در وضعیت share_allocation گیر کرده‌اند
            $stuckFamilies = Family::where('wizard_status', InsuranceWizardStep::SHARE_ALLOCATION->value)->get();

            $count = 0;
            $batchId = 'fix_stuck_families_' . time();

            Log::info('FamiliesApproval::fixShareAllocationFamilies - شروع اصلاح وضعیت خانواده‌های گیر کرده', [
                'total_stuck' => $stuckFamilies->count(),
                'time' => now()->format('Y-m-d H:i:s.u'),
            ]);

            DB::beginTransaction();

            foreach ($stuckFamilies as $family) {
                // تغییر وضعیت به approved
                $currentStep = InsuranceWizardStep::SHARE_ALLOCATION;
                $nextStep = InsuranceWizardStep::APPROVED;

                // استفاده از setAttribute به جای دسترسی مستقیم برای رفع خطای لینت
                $family->setAttribute('wizard_status', $nextStep->value);
                $family->setAttribute('status', 'approved');
                $family->save();

                // ثبت لاگ
                FamilyStatusLog::create([
                    'family_id' => $family->id,
                    'user_id' => Auth::id(),
                    'from_status' => $currentStep->value,
                    'to_status' => $nextStep->value,
                    'comments' => 'اصلاح دستی وضعیت پس از تخصیص سهمیه',
                    'batch_id' => $batchId,
                ]);

                $count++;
            }

            DB::commit();

            $this->clearFamiliesCache();
            $this->setTab('approved');

            Log::info('FamiliesApproval::fixShareAllocationFamilies - پایان اصلاح وضعیت خانواده‌های گیر کرده', [
                'success_count' => $count,
                'time' => now()->format('Y-m-d H:i:s.u'),
            ]);

            session()->flash('message', "وضعیت {$count} خانواده با موفقیت از 'تخصیص سهمیه' به 'در انتظار حمایت' اصلاح شد.");
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('FamiliesApproval::fixShareAllocationFamilies - خطا در اصلاح وضعیت خانواده‌ها', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'time' => now()->format('Y-m-d H:i:s.u'),
            ]);

            session()->flash('error', 'خطا در اصلاح وضعیت خانواده‌ها: ' . $e->getMessage());
        }
    }

    private function getCriteriaMapping(): array
    {
        return [
            'addiction' => 'اعتیاد',
            'unemployment' => 'بیکاری',
            'special_disease' => 'بیماری خاص',
            'disability' => 'معلولیت',
            'single_parent' => 'سرپرست خانوار زن',
            'elderly' => 'سالمندی',
            'chronic_illness' => 'بیماری مزمن',
            'work_disability' => 'ازکارافتادگی',
            'other' => 'سایر'
        ];
    }

    public function onSharesAllocated(array $data)
    {
        // 1. دریافت ID خانواده‌ها از رویداد
        $familyIds = $data['family_ids'] ?? [];

        if (empty($familyIds)) {
            Log::warning('onSharesAllocated called with no family_ids.');
            session()->flash('error', 'هیچ خانواده‌ای برای انتقال یافت نشد.');
            return;
        }

        Log::info('onSharesAllocated: Processing family IDs for status update.', ['family_ids' => $familyIds]);

        DB::beginTransaction();
        try {
            $batchId = 'batch_shares_allocated_' . time();
            $count = 0;

            // ما فقط خانواده‌هایی را آپدیت می‌کنیم که در مرحله تخصیص سهم بوده‌اند
            $familiesToUpdate = Family::whereIn('id', $familyIds)
                                      ->whereIn('wizard_status', [
                                          InsuranceWizardStep::REVIEWING->value,
                                          InsuranceWizardStep::SHARE_ALLOCATION->value
                                      ])
                                      ->get();

            foreach ($familiesToUpdate as $family) {
                $currentStepValue = $family->wizard_status?->value ?? 'unknown';
                $nextStep = InsuranceWizardStep::APPROVED; // مرحله بعد از تخصیص سهم

                // به‌روزرسانی وضعیت wizard
                $family->wizard_status = $nextStep->value;
                // به‌روزرسانی وضعیت قدیمی (legacy status) برای سازگاری
                $family->status = 'approved';
                $family->save();

                // ثبت لاگ دقیق
                FamilyStatusLog::create([
                    'family_id' => $family->id,
                    'user_id' => Auth::id(),
                    'from_status' => $currentStepValue,
                    'to_status' => $nextStep->value,
                    'comments' => 'انتقال خودکار پس از تخصیص سهمیه',
                    'batch_id' => $batchId,
                ]);

                $count++;
            }

            DB::commit();

            // 3. پاکسازی و اطلاع‌رسانی به کاربر
            $this->selected = [];
            $this->selectAll = false;
            $this->clearFamiliesCache(); // بروزرسانی لیست

            session()->flash('message', "{$count} خانواده با موفقیت به مرحله 'در انتظار حمایت' منتقل شدند.");

            // انتقال خودکار به تب بعدی برای مشاهده نتیجه
            $this->changeTab('approved');

            // رویدادی برای ریست کردن چک‌باکس‌ها در view
            $this->dispatch('reset-checkboxes');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error in onSharesAllocated: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            session()->flash('error', 'خطا در انتقال خانواده‌ها پس از تخصیص سهمیه.');
        }
    }
private function getCriteriaWeights(): array
{
    try {
        // اول سعی کن از دیتابیس بگیری
        $rankSettings = \App\Models\RankSetting::where('is_active', true)
            ->pluck('weight', 'name')
            ->toArray();

        if (!empty($rankSettings)) {
            return $rankSettings;
        }

        // fallback به مقادیر ثابت
        return [
            'اعتیاد' => 10,
            'بیماری خاص' => 6,
            'بیکاری' => 5,
            'معلولیت' => 8,
            'سرپرست خانوار زن' => 7,
            'سالمندی' => 4,
            'بیماری مزمن' => 6,
            'ازکارافتادگی' => 9,
            'سایر' => 2
        ];
    } catch (\Exception $e) {
        Log::error('Error getting criteria weights', ['error' => $e->getMessage()]);

        // fallback امن
        return [
            'اعتیاد' => 10,
            'بیماری خاص' => 6,
            'بیکاری' => 5,
        ];
    }
}

    public function saveFamilyCriteria()
    {
        if (!$this->editingFamily) return;

        $this->editingFamily->criteria()->sync($this->familyCriteria);

        $this->editingFamily->calculateRank();

        $this->dispatch('toast', [
            'message' => 'معیارهای خانواده با موفقیت به‌روزرسانی شد.',
            'type' => 'success'
        ]);

        $this->closeCriteriaModal();

        $this->clearFamiliesCache();
    }
    // تعریف ویژگی wizard_status
    protected $wizard_status = null;

    public function mount()
    {
        // پیش‌فرض تنظیم تب فعال
        $this->activeTab = $this->tab;

        // پاکسازی کش هنگام لود اولیه صفحه
        $this->clearFamiliesCache();

        // بارگذاری داده‌های مورد نیاز برای فیلترها با استفاده از کش
        $this->provinces = cache()->remember('provinces_list', 3600, function () {
            return \App\Models\Province::orderBy('name')->get();
        });

        $this->cities = cache()->remember('cities_list', 3600, function () {
            return \App\Models\City::orderBy('name')->get();
        });

        $this->regions = cache()->remember('regions_list', 3600, function () {
            return \App\Models\Region::all();
        });

        $this->organizations = cache()->remember('organizations_list', 3600, function () {
            return \App\Models\Organization::where('type', 'charity')->orderBy('name')->get();
        });

        // بارگذاری کامل تنظیمات رتبه‌بندی
        $this->loadRankSettings();

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

    public function debugCriteria()
{
    try {
        Log::info('=== Debug Criteria ===');

        // چک کردن selectedCriteria
        Log::info('selectedCriteria', [
            'value' => $this->selectedCriteria,
            'type' => gettype($this->selectedCriteria)
        ]);

        // چک کردن specific_criteria
        Log::info('specific_criteria', [
            'value' => $this->specific_criteria,
            'type' => gettype($this->specific_criteria)
        ]);

        // چک کردن یک خانواده نمونه
        $sampleFamily = Family::first();
        if ($sampleFamily) {
            Log::info('Sample family acceptance_criteria', [
                'value' => $sampleFamily->acceptance_criteria,
                'type' => gettype($sampleFamily->acceptance_criteria),
                'is_json' => is_string($sampleFamily->acceptance_criteria) ? 'yes' : 'no'
            ]);
        }

    } catch (\Exception $e) {
        Log::error('Debug error: ' . $e->getMessage());
    }
}
    public function updatedSelected()
    {
        // $families = $this->getFamiliesProperty();
        // $oldSelectAll = $this->selectAll;
        // $this->selectAll = count($this->selected) > 0 && count($this->selected) === $families->count();
        // $this->skipRender();

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

                Log::info('🔍 Current step for family ' . $familyId . ': ' . $currentStep->value . ' (type: ' . gettype($currentStep) . ')');

                // استفاده از nextStep method موجود در enum
                $nextStep = $currentStep->nextStep();

                if ($nextStep) {
                    Log::info('⏩ Moving family ' . $familyId . ' from ' . $currentStep->value . ' to ' . $nextStep->value);
                } else {
                    Log::warning('⚠️ No next step available for family ' . $familyId . ' with current step: ' . $currentStep->value);
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
        // 1. اعتبارسنجی ساده
        $this->validate([
            'deleteReason' => 'required|string|min:3',
            'selected' => 'required|array|min:1'
        ], [
            'deleteReason.required' => 'لطفاً دلیل حذف را انتخاب کنید.',
            'selected.required' => 'هیچ خانواده‌ای برای حذف انتخاب نشده است.'
        ]);

        $familyIds = $this->selected;

        DB::beginTransaction();
        try {
            $batchId = 'delete_' . time();
            $families = Family::whereIn('id', $familyIds)->get();

            if ($families->isEmpty()) {
                $this->dispatch('toast', message: 'خانواده‌های انتخاب شده یافت نشدند.', type: 'error');
                DB::rollBack();
                return;
            }

            // 2. ایجاد لاگ‌ها به صورت گروهی (بهینه‌تر)
            $logs = [];
            foreach ($families as $family) {
                $logs[] = [
                    'family_id' => $family->id,
                    'user_id' => Auth::id(),
                    'from_status' => $family->wizard_status?->value ?? $family->status,
                    'to_status' => 'deleted',
                    'comments' => $this->deleteReason,
                    'batch_id' => $batchId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($logs)) {
                FamilyStatusLog::insert($logs);
            }

            // 3. آپدیت گروهی وضعیت خانواده‌ها
            Family::whereIn('id', $familyIds)->update([
                'status' => 'deleted',
                'wizard_status' => null, // وضعیت ویزارد را پاک می‌کنیم
            ]);

            // 4. اجرای Soft Delete به صورت گروهی
            Family::destroy($familyIds);

            DB::commit();

            // 5. بازخورد به کاربر و پاکسازی UI
            $this->dispatch('toast', message: count($familyIds) . ' خانواده با موفقیت به لیست حذف‌شده‌ها منتقل شدند.');
            $this->closeDeleteModal();
            $this->selected = [];
            $this->selectAll = false;
            $this->clearFamiliesCache(); // برای رفرش شدن لیست

            // اگر در تب حذف شده‌ها نیستیم، به آنجا منتقل شویم
            if ($this->activeTab !== 'deleted') {
                $this->changeTab('deleted');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error during soft-deleting families: ' . $e->getMessage(), [
                'family_ids' => $familyIds,
                'reason' => $this->deleteReason,
            ]);
            $this->dispatch('toast', message: 'خطا در عملیات حذف خانواده‌ها.', type: 'error');
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
            // پاک کردن کش فعلی
            $currentKey = $this->getCacheKey();
            Cache::forget($currentKey);

            // پاک کردن کش‌های مرتبط با pattern
            $pattern = 'families_*_user_' . Auth::id();

            // اگر از Redis استفاده می‌کنید
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Cache::getRedis()->keys($pattern);
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
            }

            Log::info("🧹 Families cache cleared", [
                'current_key' => $currentKey,
                'pattern' => $pattern
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error clearing cache: ' . $e->getMessage());
        }
    }
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
        $this->activeTab = $tab;

        // همگام‌سازی تب‌های قدیمی با مراحل wizard
        if ($tab === 'pending') {
            $this->loadFamiliesByWizardStatus(InsuranceWizardStep::PENDING);
        } elseif ($tab === 'reviewing') {
            $this->loadFamiliesByWizardStatus(InsuranceWizardStep::REVIEWING);
        } elseif ($tab === 'approved') {
            $this->loadFamiliesByWizardStatus([InsuranceWizardStep::SHARE_ALLOCATION, InsuranceWizardStep::APPROVED, InsuranceWizardStep::EXCEL_UPLOAD]);
        } elseif ($tab === 'excel') {
            // تب excel باید خانواده‌های در انتظار صدور بیمه را نمایش دهد
            $this->loadFamiliesByWizardStatus(InsuranceWizardStep::EXCEL_UPLOAD);
        } elseif ($tab === 'insured') {
            $this->loadFamiliesByWizardStatus(InsuranceWizardStep::INSURED);
        } elseif ($tab === 'renewal') {
            $this->loadFamiliesByWizardStatus(InsuranceWizardStep::RENEWAL);
        } elseif ($tab === 'deleted') {
            $this->wizard_status = null;
        }

        $this->resetPage();

        if ($resetSelections) {
            $this->selected = [];
            $this->selectAll = false;
        }

        // برای تب excel کش پاک کردن لازم نیست
        if ($tab !== 'excel') {
            $this->clearFamiliesCache();
        }

        $this->is_loading = false;
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

    public function toggleFamily($familyId)
    {
        $this->expandedFamily = $this->expandedFamily === $familyId ? null : $familyId;
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
                $query->whereIn('wizard_status', [
                    InsuranceWizardStep::SHARE_ALLOCATION->value,
                    InsuranceWizardStep::APPROVED->value,
                    InsuranceWizardStep::EXCEL_UPLOAD->value
                ])->where('status', '!=', 'deleted');
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
                $q->where('family_code', 'like', '%' . $this->search . '%')
                  ->orWhere('address', 'like', '%' . $this->search . '%')
                  ->orWhere('additional_info', 'like', '%' . $this->search . '%')
                  ->orWhereHas('members', function ($memberQuery) {
                      $memberQuery->where('first_name', 'like', '%' . $this->search . '%')
                                 ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                 ->orWhere('national_code', 'like', '%' . $this->search . '%');
                  });
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
            return;
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
        // اگر خانواده‌ای انتخاب شده باشد، فقط آنها را دانلود کن، وگرنه همه خانواده‌های صفحه را دانلود کن
        if (!empty($this->selected)) {
            // دانلود خانواده‌های انتخاب شده
            $families = Family::whereIn('id', $this->selected)
                ->with(['head', 'province', 'city', 'district', 'region', 'charity', 'organization', 'members', 'finalInsurances'])
                ->get();

            if ($families->isEmpty()) {
                $this->dispatch('toast', ['message' => 'خانواده‌های انتخاب شده یافت نشدند.', 'type' => 'error']);
                return null;
            }

            $downloadType = 'انتخاب-شده';
        } else {
            // دانلود همه خانواده‌های صفحه فعلی
            $families = $this->getFamiliesProperty();

            if ($families->isEmpty()) {
                $this->dispatch('toast', ['message' => 'داده‌ای برای دانلود وجود ندارد.', 'type' => 'error']);
                return null;
            }

            $downloadType = $this->activeTab;
        }

        // ایجاد کالکشن برای داده‌های اکسل
        $excelData = collect();

        foreach ($families as $family) {
            // اضافه کردن سرپرست خانواده به عنوان یک ردیف
            $excelData->push([
                'family_code' => $family->family_code,
                'head_name' => $family->head ? $family->head->first_name . ' ' . $family->head->last_name : 'نامشخص',
                'head_national_id' => $family->head ? $family->head->national_code : 'نامشخص',
                'is_head' => 'بله',
                'member_name' => $family->head ? $family->head->first_name . ' ' . $family->head->last_name : 'نامشخص',
                'member_national_id' => $family->head ? $family->head->national_code : 'نامشخص',
                'member_relationship' => $family->head && $family->head->relationship ? $family->head->relationship : 'سرپرست خانوار',
                'member_birth_date' => $family->head && $family->head->birth_date ? \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($family->head->birth_date))->format('Y/m/d') : null,
                'member_gender' => $this->translateGender($family->head ? $family->head->gender : null),
                'province' => $family->province ? $family->province->name : 'نامشخص',
                'city' => $family->city ? $family->city->name : 'نامشخص',
                'district' => $family->district ? $family->district->name : 'نامشخص',
                'region' => $family->region ? $family->region->name : 'نامشخص',
                'organization' => $family->organization ? $family->organization->name : 'نامشخص',
                'insurance_type' => $family->finalInsurances->first() ? $family->finalInsurances->first()->insurance_type : 'نامشخص',
                'insurance_amount' => $family->finalInsurances->first() ? $family->finalInsurances->first()->insurance_amount : 0,
                'start_date' => $family->finalInsurances->first() && $family->finalInsurances->first()->start_date ? \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($family->finalInsurances->first()->start_date))->format('Y/m/d') : null,
                'end_date' => $family->finalInsurances->first() && $family->finalInsurances->first()->end_date ? \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($family->finalInsurances->first()->end_date))->format('Y/m/d') : null,
            ]);

            // اضافه کردن اعضای خانواده (غیر از سرپرست)
            $nonHeadMembers = $family->members->where('is_head', false);
            foreach ($nonHeadMembers as $member) {
                $excelData->push([
                    'family_code' => $family->family_code,
                    'head_name' => $family->head ? $family->head->first_name . ' ' . $family->head->last_name : 'نامشخص',
                    'head_national_id' => $family->head ? $family->head->national_code : 'نامشخص',
                    'is_head' => 'خیر',
                    'member_name' => $member->first_name . ' ' . $member->last_name,
                    'member_national_id' => $member->national_code,
                    'member_relationship' => $member->relationship ? $member->relationship : 'نامشخص',
                    'member_birth_date' => $member->birth_date ? \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($member->birth_date))->format('Y/m/d') : null,
                    'member_gender' => $this->translateGender($member->gender),
                    'province' => $family->province ? $family->province->name : 'نامشخص',
                    'city' => $family->city ? $family->city->name : 'نامشخص',
                    'district' => $family->district ? $family->district->name : 'نامشخص',
                    'region' => $family->region ? $family->region->name : 'نامشخص',
                    'organization' => $family->organization ? $family->organization->name : 'نامشخص',
                    'insurance_type' => $family->finalInsurances->first() ? $family->finalInsurances->first()->insurance_type : 'نامشخص',
                    'insurance_amount' => $family->finalInsurances->first() ? $family->finalInsurances->first()->insurance_amount : 0,
                    'start_date' => $family->finalInsurances->first() && $family->finalInsurances->first()->start_date ? \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($family->finalInsurances->first()->start_date))->format('Y/m/d') : null,
                    'end_date' => $family->finalInsurances->first() && $family->finalInsurances->first()->end_date ? \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($family->finalInsurances->first()->end_date))->format('Y/m/d') : null,
                ]);
            }
        }

        // تعریف هدرهای جدید (بدون ستون‌های اضافی)
        $headings = [
            'کد خانوار',
            'کد ملی سرپرست',
            'سرپرست',
            'نام عضو',
            'کد ملی عضو',
            'نسبت',
            'تاریخ تولد',
            'جنسیت',
            'استان',
            'شهرستان',
            'منطقه',
            'ناحیه',
            'سازمان',
            'نوع بیمه',
            'مبلغ بیمه',
            'تاریخ شروع',
            'تاریخ پایان',
        ];

        // کلیدهای داده جدید (هماهنگ با داده‌های واقعی)
        $dataKeys = [
            'family_code',
            'head_national_id',
            'is_head',
            'member_name',
            'member_national_id',
            'member_relationship',
            'member_birth_date',
            'member_gender',
            'province',
            'city',
            'district',
            'region',
            'organization',
            'insurance_type',
            'insurance_amount',
            'start_date',
            'end_date',
        ];

        // ایجاد نام فایل
        $fileName = 'families-' . $this->activeTab . '-' . now()->format('Y-m-d') . '.xlsx';

        // استفاده از Excel::download برای ارسال مستقیم فایل به مرورگر
        return Excel::download(new DynamicDataExport($excelData, $headings, $dataKeys), $fileName);
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

/**
     * آپلود فایل اکسل بیمه با مدیریت تکرار
     */
    public function uploadInsuranceExcel()
    {


        // اعتبارسنجی فایل
        $this->validate([
            'insuranceExcelFile' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);


        try {
            // ذخیره فایل
            $filename = time() . '_' . $this->insuranceExcelFile->getClientOriginalName();

            $path = $this->insuranceExcelFile->storeAs('excel_imports', $filename, 'public');
            $fullPath = storage_path('app/public/' . $path);


            // بررسی وجود فایل
            if (!file_exists($fullPath)) {
                Log::error('❌ فایل آپلود شده وجود ندارد: ' . $fullPath);
                throw new \Exception('فایل آپلود شده قابل دسترسی نیست. لطفاً دوباره تلاش کنید.');
            }

            Log::info('✅ فایل با موفقیت آپلود شد و قابل دسترسی است');

            // تفویض به سرویس
            $insuranceService = new \App\Services\InsuranceShareService();
            $result = $insuranceService->completeInsuranceFromExcel($fullPath);

            // ✅ بررسی تکرار و نمایش پیام مناسب
            if (isset($result['is_duplicate']) && $result['is_duplicate']) {
                $this->handleDuplicateUpload($result);
                return;
            }

            // نمایش پیام موفقیت
            $this->handleSuccessfulUpload($result);

            // پاک کردن فایل آپلود شده
            $this->reset('insuranceExcelFile');

            // بازگشت به تب excel برای نمایش خانواده‌های باقی‌مانده
            $this->setTab('excel');
            $this->clearFamiliesCache();
            $this->dispatch('refreshFamiliesList');

            Log::info('🔄 Successfully redirected to excel tab after Excel upload');

        } catch (\Exception $e) {
            Log::error('❌ خطا در پردازش فایل اکسل: ' . $e->getMessage());
            Log::error('❌ جزئیات خطا: ' . $e->getTraceAsString());

            session()->flash('error', 'خطا در پردازش فایل اکسل: ' . $e->getMessage());
        }
    }

    /**
     * ✅ مدیریت آپلود تکراری
     */
    private function handleDuplicateUpload(array $result): void
    {
        Log::warning('⚠️ آپلود تکراری شناسایی شد', [
            'duplicate_type' => $result['duplicate_type'],
            'existing_log_id' => $result['existing_log_id'] ?? null
        ]);

        $duplicateMessages = [
            'exact_file' => [
                'title' => '🔄 فایل تکراری',
                'message' => 'این فایل قبلاً آپلود شده است',
                'type' => 'warning'
            ],
            'similar_content' => [
                'title' => '📋 محتوای مشابه',
                'message' => 'محتوای مشابه قبلاً پردازش شده است',
                'type' => 'warning'
            ],
            'high_overlap' => [
                'title' => '👥 تداخل خانواده‌ها',
                'message' => 'بیشتر خانواده‌های این فایل قبلاً پردازش شده‌اند',
                'type' => 'warning'
            ],
            'idempotency' => [
                'title' => '🔒 عملیات تکراری',
                'message' => 'این عملیات قبلاً انجام شده است',
                'type' => 'info'
            ]
        ];

        $duplicateType = $result['duplicate_type'] ?? 'unknown';
        $messageConfig = $duplicateMessages[$duplicateType] ?? $duplicateMessages['idempotency'];

        // نمایش پیام تکرار
        $errorMessage = $messageConfig['title'] . "\n\n";
        $errorMessage .= $messageConfig['message'] . "\n";
        if (!empty($result['errors'][0])) {
            $errorMessage .= "جزئیات: " . $result['errors'][0] . "\n";
        }
        $errorMessage .= "\n⚠️ هیچ تغییری در دیتابیس اعمال نشد.";

        if (isset($result['existing_log_id'])) {
            $errorMessage .= "\n📋 شناسه لاگ قبلی: " . $result['existing_log_id'];
        }

        session()->flash('error', $errorMessage);

        // ارسال رویداد مخصوص تکرار برای نمایش نوتیفیکیشن
        $this->dispatch('duplicate-upload-detected', [
            'type' => $duplicateType,
            'message' => $messageConfig['message'],
            'existing_log_id' => $result['existing_log_id'] ?? null
        ]);

        // نوتیفیکیشن toast برای نمایش سریع
        $this->dispatch('toast', [
            'message' => $messageConfig['title'] . ': ' . $messageConfig['message'],
            'type' => 'warning',
            'duration' => 5000
        ]);

        Log::info('✅ پیام تکرار نمایش داده شد', [
            'duplicate_type' => $duplicateType,
            'message_type' => $messageConfig['type']
        ]);
    }

    /**
     * ✅ مدیریت آپلود موفق
     */
    private function handleSuccessfulUpload(array $result): void
    {
        $successMessage = "✅ عملیات ایمپورت با موفقیت انجام شد:\n";
        $successMessage .= "🆕 رکوردهای جدید: {$result['created']}\n";
        $successMessage .= "🔄 رکوردهای به‌روزرسانی شده: {$result['updated']}\n";
        $successMessage .= "❌ خطاها: {$result['skipped']}\n";
        $successMessage .= "💰 مجموع مبلغ بیمه: " . number_format($result['total_insurance_amount']) . " ریال";

        if (!empty($result['errors'])) {
            $errorCount = count($result['errors']);
            $successMessage .= "\n\n⚠️ جزئیات خطاها ({$errorCount} مورد):\n";
            $successMessage .= implode("\n", array_slice($result['errors'], 0, 5));
            if ($errorCount > 5) {
                $successMessage .= "\n... و " . ($errorCount - 5) . " خطای دیگر";
            }

            // نمایش خطاها در flash message جداگانه
            session()->flash('warning', "جزئیات خطاها:\n" . implode("\n", array_slice($result['errors'], 0, 10)));
        }

        session()->flash('message', $successMessage);

        // ارسال رویداد موفقیت
        $this->dispatch('upload-completed-successfully', [
            'created' => $result['created'],
            'updated' => $result['updated'],
            'skipped' => $result['skipped'],
            'total_amount' => $result['total_insurance_amount'],
            'errors_count' => count($result['errors'])
        ]);

        // نوتیفیکیشن toast برای نمایش سریع موفقیت
        $toastMessage = "✅ آپلود موفق: {$result['created']} رکورد جدید، {$result['updated']} به‌روزرسانی";
        if ($result['skipped'] > 0) {
            $toastMessage .= "، {$result['skipped']} خطا";
        }

        $this->dispatch('toast', [
            'message' => $toastMessage,
            'type' => 'success',
            'duration' => 6000
        ]);

        Log::info('✅ پیام موفقیت نمایش داده شد', [
            'created' => $result['created'],
            'updated' => $result['updated'],
            'total_amount' => $result['total_insurance_amount']
        ]);
    }

    /**
     * ✅ نمایش تاریخچه آپلودهای قبلی (اختیاری)
     */
    public function showUploadHistory()
    {
        try {
            $recentUploads = \App\Models\ShareAllocationLog::where('user_id', Auth::id())
                ->whereJsonContains('shares_data->upload_method', 'excel')
                ->where('created_at', '>=', now()->subDays(30))
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(['id', 'created_at', 'families_count', 'total_amount', 'status', 'shares_data']);

            $historyData = $recentUploads->map(function($log) {
                $sharesData = json_decode($log->shares_data, true);
                return [
                    'id' => $log->id,
                    'date' => $log->created_at->format('Y-m-d H:i'),
                    'families_count' => $log->families_count,
                    'total_amount' => number_format($log->total_amount),
                    'status' => $log->status,
                    'created' => $sharesData['created'] ?? 0,
                    'updated' => $sharesData['updated'] ?? 0,
                    'skipped' => $sharesData['skipped'] ?? 0,
                ];
            });

            $this->dispatch('show-upload-history', $historyData->toArray());

        } catch (\Exception $e) {
            Log::error('❌ خطا در نمایش تاریخچه آپلود', ['error' => $e->getMessage()]);
            $this->dispatch('toast', [
                'message' => 'خطا در بارگذاری تاریخچه آپلود',
                'type' => 'error'
            ]);
        }
    }

    /**
     * بارگذاری فیلتر رتبه‌بندی و اعمال آن
     *
     * @param int $filterId شناسه فیلتر
     * @return bool
     */
    public function loadRankFilter($filterId)
    {
        try {
            $user = auth()->user();

            // فقط فیلترهای رتبه‌بندی را جستجو کن
            $filter = SavedFilter::where('filter_type', 'rank_settings')
                ->where(function ($q) use ($user) {
                    // فیلترهای خود کاربر
                    $q->where('user_id', $user->id)
                      // یا فیلترهای سازمانی (اگر کاربر عضو سازمان باشد)
                      ->orWhere('organization_id', $user->organization_id);
                })
                ->find($filterId);

            if (!$filter) {
                $this->dispatch('toast', [
                    'message' => 'فیلتر رتبه‌بندی یافت نشد یا مخصوص این بخش نیست',
                    'type' => 'warning'
                ]);
                return false;
            }

            // اعمال تنظیمات فیلتر
            $config = $filter->filters_config;

            $this->selectedCriteria = $config['selectedCriteria'] ?? [];
            $this->family_rank_range = $config['family_rank_range'] ?? '';
            $this->specific_criteria = $config['specific_criteria'] ?? '';

            // بازنشانی صفحه‌بندی
            $this->resetPage();

            // افزایش تعداد استفاده و به‌روزرسانی آخرین زمان استفاده
            $filter->increment('usage_count');
            $filter->update(['last_used_at' => now()]);

            // پاک کردن کش
            $this->clearFamiliesCache();

            $this->dispatch('toast', [
                'message' => 'فیلتر تنظیمات رتبه "' . $filter->name . '" با موفقیت بارگذاری شد',
                'type' => 'success'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error loading rank filter: ' . $e->getMessage());
            $this->dispatch('toast', [
                'message' => 'خطا در بارگذاری فیلتر رتبه‌بندی: ' . $e->getMessage(),
                'type' => 'error'
            ]);
            return false;
        }
    }

    /**
     * ذخیره فیلتر تنظیمات رتبه
     *
     * @param string $name نام فیلتر
     * @param string $description توضیحات فیلتر
     * @return bool
     */
    public function saveRankFilter($name, $description = '')
    {
        try {
            // اعتبارسنجی ورودی
            if (empty(trim($name))) {
                $this->dispatch('toast', [
                    'message' => 'نام فیلتر الزامی است',
                    'type' => 'error'
                ]);
                return false;
            }

            // تهیه پیکربندی فیلتر فعلی برای تنظیمات رتبه
            $filtersConfig = [
                'selectedCriteria' => $this->selectedCriteria,
                'family_rank_range' => $this->family_rank_range,
                'specific_criteria' => $this->specific_criteria,
                // می‌توانید فیلدهای دیگر مربوط به رتبه‌بندی را اضافه کنید
            ];

            // بررسی اینکه فیلتری با همین نام برای این کاربر و نوع فیلتر وجود ندارد
            $existingFilter = SavedFilter::where('user_id', auth()->id())
                                        ->where('name', trim($name))
                                        ->where('filter_type', 'rank_settings')
                                        ->first();

            if ($existingFilter) {
                $this->dispatch('toast', [
                    'message' => 'فیلتری با این نام قبلاً ذخیره شده است',
                    'type' => 'error'
                ]);
                return false;
            }

            // ایجاد فیلتر جدید
            SavedFilter::create([
                'name' => trim($name),
                'description' => trim($description),
                'user_id' => auth()->id(),
                'organization_id' => auth()->user()->organization_id,
                'filter_type' => 'rank_settings',
                'filters_config' => $filtersConfig,
                'usage_count' => 0
            ]);

            $this->dispatch('toast', [
                'message' => 'فیلتر تنظیمات رتبه "' . $name . '" با موفقیت ذخیره شد',
                'type' => 'success'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error saving rank filter: ' . $e->getMessage());
            $this->dispatch('toast', [
                'message' => 'خطا در ذخیره فیلتر رتبه‌بندی: ' . $e->getMessage(),
                'type' => 'error'
            ]);
            return false;
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
            $family->setAttribute('is_insured', true);
            $family->save();

            // پاک کردن کش برای نمایش فوری تغییرات
        $this->clearFamiliesCache();

        // اضافه کردن این خط برای به‌روزرسانی فوری UI
        $this->dispatch('refreshFamiliesList');


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

                    // به‌روزرسانی وضعیت قدیمی
                    switch ($targetWizardStep->value) {
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

                    $family->save();

                    // ثبت لاگ تغییر وضعیت
                    FamilyStatusLog::logTransition(
                        $family,
                        $currentWizardStep,
                        $targetWizardStep,
                        "تغییر وضعیت به {$targetWizardStep->label()} توسط کاربر",
                        ['batch_id' => $batchId]
                    );

                    $count++;
                }
            }

            DB::commit();

            session()->flash('message', "{$count} خانواده با موفقیت به‌روزرسانی شدند.");

            // به‌روزرسانی کش
            $this->clearFamiliesCache();

            // اضافه کردن این خط برای به‌روزرسانی فوری UI
            $this->dispatch('refreshFamiliesList');

            // ریست کردن انتخاب‌ها و رفرش صفحه
            $this->selected = [];
            $this->selectAll = false;
            $this->resetPage();
            $this->dispatch('reset-checkboxes');

            // به‌روزرسانی UI
            // $this->dispatch('wizardUpdated', $result);

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

            // پاک کردن متغیرها
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
    /**
     * مرتب‌سازی بر اساس فیلد مشخص شده
     */
    public function sortBy($field)
    {
        // اعتبارسنجی جهت مرتب‌سازی
        if (!in_array($this->sortDirection, ['asc', 'desc'])) {
            $this->sortDirection = 'desc';
        }

        // بررسی فیلدهای رتبه‌بندی
        $rankingFields = ['weighted_rank', 'criteria_count', 'priority_score'];

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;

            // تنظیم جهت پیش‌فرض بر اساس نوع فیلد
            if (in_array($field, $rankingFields)) {
                // برای فیلدهای رتبه‌بندی، پیش‌فرض نزولی (امتیاز بالاتر اول)
                $this->sortDirection = 'desc';
            } elseif ($field === 'created_at') {
                // برای تاریخ ایجاد، پیش‌فرض صعودی (قدیمی‌تر اول)
                $this->sortDirection = 'asc';
            } else {
                // برای سایر فیلدها، پیش‌فرض نزولی
                $this->sortDirection = 'desc';
            }
        }

        // اطمینان از مقدار معتبر
        if (!in_array($this->sortDirection, ['asc', 'desc'])) {
            $this->sortDirection = 'desc';
        }

        // ریست کردن صفحه بندی
        $this->resetPage();

        // پاکسازی کش
        $this->clearFamiliesCache();

        Log::info('🔀 Sorting applied', [
            'field' => $field,
            'direction' => $this->sortDirection,
            'is_ranking_field' => in_array($field, $rankingFields)
        ]);
    }
    /**
     * مرتب‌سازی بر اساس تعداد اعضای دارای مشکل خاص
     *
     * @param string $problemType
     * @return void
     */
    public function sortByProblemType($problemType = null)
    {
        if ($problemType) {
            $this->sortByProblemType = $problemType;
            $this->sortField = 'problem_type.' . $problemType;
            $this->sortDirection = 'desc'; // به صورت پیش‌فرض نزولی مرتب می‌شود
        } else {
            $this->sortByProblemType = '';
            $this->sortField = 'created_at';
            $this->sortDirection = 'desc';
        }

        // ریست کردن صفحه بندی
        $this->resetPage();

        // پاکسازی کش
        $this->clearFamiliesCache();
    }




    /**
     * پاک کردن تمام فیلترها
     */
    public function clearAllFilters()
    {
        $this->search = '';
        $this->status = ''; // اضافه کردن پاک کردن status
        $this->province_id = null;
        $this->city_id = null;
        $this->district_id = null;
        $this->region_id = null;
        $this->organization_id = null;
        $this->charity_id = null;
        $this->tempFilters = [];
        $this->activeFilters = [];

        // پاک کردن فیلترهای رتبه
        $this->province_id = null;
        $this->city_id = null;
        $this->district_id = null; // منطقه/ناحیه
        $this->deprivation_rank = '';
        $this->family_rank_range = '';
        $this->specific_criteria = '';
        $this->charity_id = null;
        $this->specific_criteria = null;
        $this->selectedCriteria = [];

        $this->tempFilters = [];
        $this->activeFilters = [];
        $this->resetPage();
        $this->clearFamiliesCache();

        $this->dispatch('toast', [
            'message' => 'تمام فیلترها پاک شدند',
            'type' => 'info'
        ]);
    }

    /**
     * باز کردن مودال رتبه‌بندی
     */
    public function openRankModal()
    {
        Log::info('🎯 STEP 1: Opening rank modal', [
            'user_id' => Auth::id(),
            'timestamp' => now(),
            'current_tab' => $this->activeTab
        ]);

        $this->loadRankSettings();
        $this->showRankModal = true;

        Log::info('✅ STEP 1 COMPLETED: Rank modal opened', [
            'showRankModal' => $this->showRankModal,
            'rankSettings_count' => $this->rankSettings->count() ?? 0,
            'user_id' => Auth::id()
        ]);
    }

    /**
     * بارگذاری تنظیمات رتبه‌بندی
     */
    public function loadRankSettings()
    {
        Log::info('📋 STEP 2: Loading rank settings', [
            'user_id' => Auth::id(),
            'timestamp' => now()
        ]);

        $this->rankSettings = \App\Models\RankSetting::orderBy('sort_order')->get();
        $this->rankingSchemes = \App\Models\RankingScheme::orderBy('name')->get();
        $this->availableCriteria = \App\Models\RankSetting::where('is_active', true)->orderBy('sort_order')->get();

        // Update available rank settings for display
        $this->availableRankSettings = $this->rankSettings;

        Log::info('✅ STEP 2 COMPLETED: Rank settings loaded', [
            'rankSettings_count' => $this->rankSettings->count(),
            'rankingSchemes_count' => $this->rankingSchemes->count(),
            'availableCriteria_count' => $this->availableCriteria->count(),
            'active_criteria' => $this->availableCriteria->pluck('name', 'id')->toArray(),
            'user_id' => Auth::id()
        ]);

        // نمایش پیام مناسب برای باز شدن تنظیمات
        $this->dispatch('toast', [
            'message' => 'تنظیمات معیارهای رتبه‌بندی بارگذاری شد - ' . $this->rankSettings->count() . ' معیار',
            'type' => 'info'
        ]);
    }

    /**
     * فرم افزودن معیار جدید را نمایش می‌دهد.
     */
    public function showCreateForm()
    {
        $this->reset('editingRankSettingId');
        $this->isCreatingNew = true;
        $this->editingRankSetting = [
            'name' => '',
            'weight' => 5,
            'description' => '',
            'requires_document' => true,
            'color' => '#'.substr(str_shuffle('ABCDEF0123456789'), 0, 6)
        ];
    }

    /**
     * یک معیار را برای ویرایش انتخاب می‌کند.
     * @param int $id
     */
    public function edit($id)
    {
        $this->isCreatingNew = false;
        $this->editingRankSettingId = $id;
        $setting = \App\Models\RankSetting::find($id);
        if ($setting) {
            $this->editingRankSetting = $setting->toArray();
        }
    }

    /**
     * تغییرات را ذخیره می‌کند (هم برای افزودن جدید و هم ویرایش).
     */
    public function save()
    {
        $this->validate([
            'editingRankSetting.name' => 'required|string|max:255',
            'editingRankSetting.weight' => 'required|integer|min:0|max:10',
            'editingRankSetting.description' => 'nullable|string',
            'editingRankSetting.requires_document' => 'boolean',
            'editingRankSetting.color' => 'nullable|string',
        ]);

        try {
            // محاسبه sort_order برای رکورد جدید
            if (!$this->editingRankSettingId) {
                $maxOrder = \App\Models\RankSetting::max('sort_order') ?? 0;
                $this->editingRankSetting['sort_order'] = $maxOrder + 10;
                $this->editingRankSetting['is_active'] = true;
                $this->editingRankSetting['slug'] = \Illuminate\Support\Str::slug($this->editingRankSetting['name']);
            }

            // ذخیره
            $setting = \App\Models\RankSetting::updateOrCreate(
                ['id' => $this->editingRankSettingId],
                $this->editingRankSetting
            );

            // بازنشانی فرم
            $this->resetForm();

            // بارگذاری مجدد تنظیمات
            $this->loadRankSettings();

            // پاک کردن کش لیست خانواده‌ها
            $this->clearFamiliesCache();

            $this->dispatch('toast', [
                'message' => 'معیار با موفقیت ذخیره شد',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'message' => 'خطا در ذخیره معیار: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * حذف یک معیار رتبه‌بندی
     * @param int $id
     */
    public function delete($id)
    {
        try {
            $setting = \App\Models\RankSetting::find($id);
            if ($setting) {
                // بررسی استفاده شدن معیار
                $usageCount = \App\Models\FamilyCriterion::where('rank_setting_id', $id)->count();
                if ($usageCount > 0) {
                    $this->dispatch('toast', [
                        'message' => "این معیار در {$usageCount} خانواده استفاده شده و قابل حذف نیست. به جای حذف می‌توانید آن را غیرفعال کنید.",
                        'type' => 'error'
                    ]);
                    return;
                }

                $setting->delete();
                $this->loadRankSettings();

                // پاک کردن کش لیست خانواده‌ها
                $this->clearFamiliesCache();

                $this->dispatch('toast', [
                    'message' => 'معیار با موفقیت حذف شد',
                    'type' => 'success'
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'message' => 'خطا در حذف معیار: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * انصراف از ویرایش/افزودن و بازنشانی فرم
     */
    public function cancel()
    {
        $this->resetForm();
        $this->dispatch('toast', [
            'message' => 'عملیات لغو شد',
            'type' => 'info'
        ]);
    }

    /**
     * بازنشانی فرم ویرایش/افزودن
     */
    private function resetForm()
    {
        $this->editingRankSettingId = null;
        $this->isCreatingNew = false;
        $this->editingRankSetting = [
            'name' => '',
            'weight' => 5,
            'description' => '',
            'requires_document' => true,
            'color' => '#60A5FA'
        ];
    }


    /**
     */
    public function saveRankSetting()
    {
        try {
            // اعتبارسنجی
            if ($this->editingRankSettingId) {
                // در حالت ویرایش فقط وزن قابل تغییر است
                $this->validate([
                    'rankSettingWeight' => 'required|integer|min:0|max:10',
                ]);
            } else {
                // در حالت افزودن معیار جدید همه فیلدها الزامی هستند
                $this->validate([
                    'rankSettingName' => 'required|string|max:255',
                    'rankSettingWeight' => 'required|integer|min:0|max:10',
                    'rankSettingDescription' => 'nullable|string',
                    'rankSettingNeedsDoc' => 'required|boolean',
                ]);
            }

            if ($this->editingRankSettingId) {
                // ویرایش معیار موجود - فقط وزن
                $setting = \App\Models\RankSetting::find($this->editingRankSettingId);
                if ($setting) {
                    $setting->weight = $this->rankSettingWeight;
                    $setting->save();

                    $this->dispatch('toast', [
                        'message' => 'وزن معیار با موفقیت به‌روزرسانی شد: ' . $setting->name,
                        'type' => 'success'
                    ]);
                }
            } else {
                // ایجاد معیار جدید
                \App\Models\RankSetting::create([
                    'name' => $this->rankSettingName,
                    'weight' => $this->rankSettingWeight,
                    'description' => $this->rankSettingDescription,
                    'requires_document' => (bool)$this->rankSettingNeedsDoc,
                    'slug' => \Illuminate\Support\Str::slug($this->rankSettingName) ?: 'rank-' . \Illuminate\Support\Str::random(6),
                    'is_active' => true,
                    'sort_order' => \App\Models\RankSetting::max('sort_order') + 1,
                ]);

                $this->dispatch('toast', [
                    'message' => 'معیار جدید با موفقیت ایجاد شد: ' . $this->rankSettingName,
                    'type' => 'success'
                ]);
            }

            // بارگذاری مجدد تنظیمات
            $this->loadRankSettings();
            $this->clearFamiliesCache();
            $this->resetRankSettingForm();

        } catch (\Exception $e) {
            $this->dispatch('toast', [
                'message' => 'خطا در ذخیره معیار: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * ریست کردن فرم معیار
     */
    public  function resetRankSettingForm()
    {
        $this->rankSettingName = '';
        $this->rankSettingDescription = '';
        $this->rankSettingWeight = 5;
        $this->rankSettingColor = '#60A5FA';
        $this->rankSettingNeedsDoc = true;
        $this->editingRankSettingId = null;
    }

    /**
     * بازگشت به تنظیمات پیشفرض
     */
    public function resetToDefaults()
    {
        // پاک کردن فیلترهای رتبه
        $this->family_rank_range = null;
        $this->specific_criteria = null;
        $this->selectedCriteria = [];

        // بازنشانی صفحه‌بندی و به‌روزرسانی لیست
        $this->resetPage();
        $this->showRankModal = false;

        // پاک کردن کش برای اطمینان از به‌روزرسانی داده‌ها
        if (Auth::check()) {
            cache()->forget('families_query_' . Auth::id());
        }

        $this->dispatch('toast', [
            'message' => 'تنظیمات رتبه با موفقیت به حالت پیشفرض بازگردانده شد',
            'type' => 'success'
        ]);
    }

    /**
     * وزن‌های یک الگوی رتبه‌بندی ذخیره‌شده را بارگیری می‌کند.
     */
    public function loadScheme($schemeId)
    {
        if (empty($schemeId)) {
            $this->reset(['selectedSchemeId', 'schemeWeights', 'newSchemeName', 'newSchemeDescription']);
            return;
        }

        $this->selectedSchemeId = $schemeId;
        $scheme = \App\Models\RankingScheme::with('criteria')->find($schemeId);

        if ($scheme) {
            $this->newSchemeName = $scheme->name;
            $this->newSchemeDescription = $scheme->description;
            $this->schemeWeights = $scheme->criteria->pluck('pivot.weight', 'id')->toArray();
        }
    }

    /**
     * یک الگوی رتبه‌بندی جدید را ذخیره یا یک الگوی موجود را به‌روزرسانی می‌کند.
     */
    public function saveScheme()
    {
        $this->validate([
            'newSchemeName' => 'required|string|max:255',
            'newSchemeDescription' => 'nullable|string',
            'schemeWeights' => 'required|array',
            'schemeWeights.*' => 'nullable|integer|min:0'
        ]);

        $scheme = \App\Models\RankingScheme::updateOrCreate(
            ['id' => $this->selectedSchemeId],
            [
                'name' => $this->newSchemeName,
                'description' => $this->newSchemeDescription,
                'user_id' => \Illuminate\Support\Facades\Auth::id()
            ]
        );

        $weightsToSync = [];
        foreach ($this->schemeWeights as $criterionId => $weight) {
            if (!is_null($weight) && $weight > 0) {
                $weightsToSync[$criterionId] = ['weight' => $weight];
            }
        }

        $scheme->criteria()->sync($weightsToSync);

        $this->rankingSchemes = \App\Models\RankingScheme::orderBy('name')->get();
        $this->selectedSchemeId = $scheme->id;

        $this->dispatch('toast', ['message' => 'الگو با موفقیت ذخیره شد.', 'type' => 'success']);
    }

    /**
     * الگوی انتخاب‌شده را برای فیلتر کردن و مرتب‌سازی اعمال می‌کند.
     */
    public function applyRankingScheme()
    {
        if (!$this->selectedSchemeId) {
             $this->dispatch('toast', ['message' => 'لطفا ابتدا یک الگو را انتخاب یا ذخیره کنید.', 'type' => 'error']);
             return;
        }
        $this->appliedSchemeId = $this->selectedSchemeId;
        $this->sortBy('calculated_score'); // مرتب‌سازی بر اساس امتیاز
        $this->resetPage();
        $this->showRankModal = false;

        // دریافت نام الگوی انتخاب شده برای نمایش در پیام
        $schemeName = \App\Models\RankingScheme::find($this->selectedSchemeId)->name ?? '';
        $this->dispatch('toast', [
            'message' => "الگوی رتبه‌بندی «{$schemeName}» با موفقیت اعمال شد.",
            'type' => 'success'
        ]);
    }

    /**
     * رتبه‌بندی اعمال‌شده را پاک می‌کند.
     */
    public function clearRanking()
    {
        $this->appliedSchemeId = null;
        $this->sortBy('created_at');
        $this->resetPage();
        $this->showRankModal = false;
        $this->dispatch('toast', ['message' => 'فیلتر رتبه‌بندی حذف شد.', 'type' => 'info']);
    }

    /**
     * اعمال تغییرات و بستن مودال
     */
    public function applyAndClose()
    {
        try {
            // اطمینان از ذخیره همه تغییرات
            $this->loadRankSettings();

            // بروزرسانی لیست معیارهای در دسترس
            $this->availableCriteria = \App\Models\RankSetting::active()->ordered()->get();

            // اعمال تغییرات به خانواده‌ها
            if ($this->appliedSchemeId) {
                // اگر یک طرح رتبه‌بندی انتخاب شده باشد، دوباره آن را اعمال می‌کنیم
                $this->applyRankingScheme();

                $this->sortBy('calculated_score');
            }

            // بستن مودال و نمایش پیام
            $this->showRankModal = false;
            $this->dispatch('toast', [
                'message' => 'تغییرات با موفقیت اعمال شد.',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            // خطا در اعمال تغییرات
            $this->dispatch('toast', [
                'message' => 'خطا در اعمال تغییرات: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * بستن مودال تنظیمات رتبه
     */
    public function closeRankModal()
    {
        $this->showRankModal = false;
    }

    /**
     * اعمال معیارهای انتخاب شده
     */
    // public function applyCriteria()
    // {
    //     // 1️⃣ استخراج IDهای انتخاب شده (مثل قبل)
    //     $criteriaIds = array_keys(array_filter($this->selectedCriteria,
    //         function($value) { return $value === true; }
    //     ));

    //     // 2️⃣ ذخیره برای فیلتر (اختیاری)
    //     $this->specific_criteria = implode(',', $criteriaIds);

    //     // 3️⃣ گرفتن خانواده‌های صفحه فعلی
    //     $familyIds = $this->getFamiliesProperty()->pluck('id');

    //     // 4️⃣ برای هر خانواده - فقط یک عملیات!
    //     foreach ($familyIds as $familyId) {
    //         $family = Family::find($familyId);

    //         // ✅ فقط از رابطه criteria استفاده می‌کنیم
    //         $family->criteria()->sync($criteriaIds);

    //         // ✅ محاسبه رتبه فقط از یک منبع
    //         $family->calculateRank();
    //     }

    //     // 5️⃣ بستن مودال و رفرش
    //     $this->showRankModal = false;
    //     $this->clearFamiliesCache();

    //     $this->dispatch('toast', [
    //         'message' => 'معیارهای انتخاب‌شده با موفقیت اعمال شدند',
    //         'type' => 'success'
    //     ]);
    // }


    // public function applyCriteria()
    // {
    //     try {
    //         Log::info('Starting applyCriteria', [
    //             'selectedCriteria' => $this->selectedCriteria
    //         ]);

    //         // استخراج IDهای انتخاب شده
    //         $criteriaIds = array_keys(array_filter($this->selectedCriteria,
    //             fn($value) => $value === true
    //         ));

    //         Log::info('Extracted criteria IDs', [
    //             'criteriaIds' => $criteriaIds,
    //             'count' => count($criteriaIds)
    //         ]);

    //         if (empty($criteriaIds)) {
    //             throw new \Exception('لطفاً حداقل یک معیار را انتخاب کنید');
    //         }

    //         // ذخیره برای فیلتر
    //         $this->specific_criteria = implode(',', $criteriaIds);
    //         Log::info('Specific criteria set', ['specific_criteria' => $this->specific_criteria]);

    //         // دریافت خانواده‌های صفحه فعلی
    //         $families = $this->getFamiliesProperty();
    //         $familyIds = $families->pluck('id');

    //         Log::info('Processing families', [
    //             'total_families' => $families->total(),
    //             'current_page_families' => $familyIds->toArray()
    //         ]);

    //         // استفاده از تراکنش
    //         DB::transaction(function () use ($familyIds, $criteriaIds) {
    //             $updatedCount = 0;
    //             $families = Family::whereIn('id', $familyIds)->get();
    //             foreach ($families as $family) {
    //                 $family->criteria()->sync($criteriaIds);
    //                 $family->calculateRank();
    //             }
    //             Log::info('Updated families criteria', [
    //                 'updated_count' => $updatedCount,
    //                 'criteria_applied' => $criteriaIds
    //             ]);
    //         });

    //         // پاک کردن کش
    //         $this->clearFamiliesCache();
    //         Log::info('Cache cleared after applying criteria');

    //         $this->dispatch('toast', [
    //             'message' => 'معیارهای انتخاب‌شده با موفقیت اعمال شدند',
    //             'type' => 'success'
    //         ]);

    //     } catch (\Exception $e) {
    //         Log::error('Error in applyCriteria: ' . $e->getMessage(), [
    //             'exception' => $e,
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         $this->dispatch('toast', [
    //             'message' => 'خطا در اعمال معیارها: ' . $e->getMessage(),
    //             'type' => 'error'
    //         ]);
    //     }
    // }



    public function applyCriteria()
    {
        try {
            Log::info('🎯 STEP 3: Starting applyCriteria with ranking sort', [
                'selectedCriteria' => $this->selectedCriteria,
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            // استخراج ID معیارهای انتخاب شده
            $selectedRankSettingIds = array_keys(array_filter($this->selectedCriteria,
                fn($value) => $value === true
            ));

            Log::info('📊 STEP 3.1: Selected criteria analysis', [
                'selectedRankSettingIds' => $selectedRankSettingIds,
                'selectedRankSettingIds_count' => count($selectedRankSettingIds),
                'user_id' => Auth::id()
            ]);

            if (empty($selectedRankSettingIds)) {
                Log::warning('❌ STEP 3 FAILED: No criteria selected for ranking', [
                    'user_id' => Auth::id()
                ]);

                // پاک کردن فیلتر و سورت
                $this->specific_criteria = null;
                $this->sortField = 'created_at';
                $this->sortDirection = 'desc';
                $this->resetPage();
                $this->clearFamiliesCache();

                // بستن مودال
                $this->showRankModal = false;

                $this->dispatch('toast', [
                    'message' => 'فیلتر و سورت معیارها پاک شد',
                    'type' => 'info'
                ]);
                return;
            }

            // دریافت نام‌های فارسی معیارها از RankSettings
            $selectedCriteriaNames = \App\Models\RankSetting::whereIn('id', $selectedRankSettingIds)
                ->pluck('name')
                ->toArray();

            Log::info('📋 STEP 3.2: Criteria names retrieved', [
                'criteria_ids' => $selectedRankSettingIds,
                'criteria_names' => $selectedCriteriaNames,
                'user_id' => Auth::id()
            ]);

            // اطمینان از اینکه آرایه داریم
            if (empty($selectedCriteriaNames)) {
                Log::warning('❌ STEP 3 FAILED: No criteria names found for IDs', [
                    'ids' => $selectedRankSettingIds,
                    'user_id' => Auth::id()
                ]);
                return;
            }

            // ذخیره نام‌های فارسی برای فیلتر
            $this->specific_criteria = implode(',', $selectedCriteriaNames);

            // تنظیم سورت بر اساس رتبه‌بندی
            $this->sortField = 'weighted_rank';
            $this->sortDirection = 'desc'; // امتیاز بالاتر اول

            Log::info('⚙️ STEP 3.3: Sort parameters set', [
                'sortField' => $this->sortField,
                'sortDirection' => $this->sortDirection,
                'specific_criteria' => $this->specific_criteria,
                'user_id' => Auth::id()
            ]);

            // Reset صفحه و cache
            $this->resetPage();
            $this->clearFamiliesCache();

            $criteriaList = implode('، ', $selectedCriteriaNames);

            $this->dispatch('toast', [
                'message' => "سورت بر اساس معیارها اعمال شد: {$criteriaList}",
                'type' => 'success'
            ]);

            // بستن مودال
            $this->showRankModal = false;

            Log::info('✅ STEP 3 COMPLETED: Ranking sort applied successfully', [
                'criteria_ids' => $selectedRankSettingIds,
                'sort_field' => $this->sortField,
                'sort_direction' => $this->sortDirection,
                'user_id' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ STEP 3 ERROR: Error in ranking sort: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'user_id' => Auth::id()
            ]);

            $this->dispatch('toast', [
                'message' => 'خطا در اعمال سورت رتبه‌بندی: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * اعمال فیلتر رتبه‌بندی با استفاده از QueryBuilder
     */
    public function applyRankingFilter($criteriaIds = null, $schemeId = null)
    {
        try {
            $filters = [];

            if ($criteriaIds) {
                $filters['ranking'] = is_array($criteriaIds) ? implode(',', $criteriaIds) : $criteriaIds;
            }

            if ($schemeId) {
                $filters['ranking_scheme'] = $schemeId;
            }

            // اعمال فیلترها به درخواست
            request()->merge(['filter' => $filters]);

            // پاک کردن کش
            $this->clearFamiliesCache();

            $this->dispatch('toast', [
                'message' => 'فیلتر رتبه‌بندی اعمال شد',
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error applying ranking filter', [
                'error' => $e->getMessage(),
                'criteria_ids' => $criteriaIds,
                'scheme_id' => $schemeId
            ]);

            $this->dispatch('toast', [
                'message' => 'خطا در اعمال فیلتر رتبه‌بندی',
                'type' => 'error'
            ]);
        }
    }

    /**
 * محاسبه امتیاز خانواده بعد از بارگذاری داده‌ها
 */
public function calculateDisplayScore($family): int
{
    try {
        $score = 0;
        $weights = $this->getCriteriaWeights();

        // بررسی acceptance_criteria
        if (!empty($family->acceptance_criteria)) {
            $familyCriteria = null;

            if (is_string($family->acceptance_criteria)) {
                $familyCriteria = json_decode($family->acceptance_criteria, true);
            } elseif (is_array($family->acceptance_criteria)) {
                $familyCriteria = $family->acceptance_criteria;
            }

            if (is_array($familyCriteria)) {
                foreach ($familyCriteria as $criteria) {
                    if (is_string($criteria) && isset($weights[$criteria])) {
                        $score += $weights[$criteria];
                    }
                }
            }
        }

        return $score;
    } catch (\Exception $e) {
        Log::error('Error calculating display score', [
            'family_id' => $family->id ?? 'unknown',
            'error' => $e->getMessage()
        ]);
        return 0;
    }
}
    /**
     * ویرایش تنظیمات رتبه
     */
    public function editRankSetting($id)
    {
        $setting = \App\Models\RankSetting::find($id);
        if ($setting) {
            // پر کردن فرم با مقادیر معیار موجود
            $this->rankSettingName = $setting->name;
            $this->rankSettingDescription = $setting->description;
            $this->rankSettingWeight = $setting->weight;
            $this->rankSettingNeedsDoc = $setting->requires_document ? 1 : 0;
            $this->editingRankSettingId = $id;
            $this->isCreatingNew = false;

            $this->dispatch('toast', [
                'message' => 'در حال ویرایش معیار: ' . $setting->name,
                'type' => 'info'
            ]);
        }
    }

    /**
     * دریافت کلید منحصر به فرد برای کش کوئری
     *
     * @return string
     */
    protected function getCacheKey()
    {
        // ایجاد یک آرایه از همه پارامترهای فیلتر
        $filterParams = [
            'tab' => $this->activeTab,
            'search' => $this->search,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'district_id' => $this->district_id,
            'region_id' => $this->region_id,
            'charity_id' => $this->charity_id,
            'status' => $this->status,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'page' => $this->page,
            'perPage' => $this->perPage,
            'specific_criteria' => $this->specific_criteria,
            'family_rank_range' => $this->family_rank_range,
            'deprivation_rank' => $this->deprivation_rank,
            'selectedCriteria' => $this->selectedCriteria,
            'activeFilters' => $this->activeFilters,
            'tempFilters' => $this->tempFilters,
        ];

        // حذف مقادیر null یا empty
        $filterParams = array_filter($filterParams, function($value) {
            return !is_null($value) && $value !== '' && $value !== [];
        });

        // ساخت کلید یکتا
        $cacheKey = 'families_' . md5(serialize($filterParams)) . '_user_' . Auth::id();

        Log::debug('Cache key generated', [
            'key' => $cacheKey,
            'params' => $filterParams
        ]);

        return $cacheKey;
    }

    /**
     * دریافت لیست خانواده‌ها با توجه به فیلترها و مرتب‌سازی اعمال شده
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getFamiliesProperty()
    {
        if ($this->activeTab === 'excel') {
            // برای این تب، تمام خانواده‌های واجد شرایط را بدون صفحه‌بندی دریافت می‌کنیم
            $familiesCollection = $this->buildFamiliesQuery()->get();

            // برای حفظ سازگاری با view، نتایج را در یک Paginator قرار می‌دهیم که فقط یک صفحه دارد.
            // این کار باعث می‌شود متدهایی مثل total() همچنان کار کنند ولی hasPages() مقدار false برگرداند.
            return new \Illuminate\Pagination\LengthAwarePaginator(
                $familiesCollection,
                $familiesCollection->count(),
                max(1, $familiesCollection->count()), // تعداد در هر صفحه برابر با کل نتایج
                1,
                ['path' => request()->url()]
            );
        }
        $cacheKey = $this->getCacheKey();
        $cacheDuration = now()->addMinutes(5);

        try {
            return Cache::remember($cacheKey, $cacheDuration, function () {
                $families = $this->buildFamiliesQuery()->paginate($this->perPage);

                // اگر فیلتر معیار فعال باشه، مرتب‌سازی بر اساس امتیاز کامل
                if (!empty($this->specific_criteria)) {
                    $familiesArray = $families->items();

                    // محاسبه امتیاز برای هر خانواده
                    $familiesWithScores = collect($familiesArray)->map(function($family) {
                        $scoreData = $this->calculateFamilyTotalScore($family);
                        $family->calculated_total_score = $scoreData['total_score'];
                        $family->score_details = $scoreData['details'];
                        return $family;
                    });

                    // جداسازی خانواده‌ها به دو گروه
                    $familiesWithCriteria = $familiesWithScores->filter(function($family) {
                        return $family->calculated_total_score > 0;
                    });

                    $familiesWithoutCriteria = $familiesWithScores->filter(function($family) {
                        return $family->calculated_total_score == 0;
                    });

                    // مرتب‌سازی هر گروه
                    $sortedFamiliesWithCriteria = $familiesWithCriteria->sortByDesc('calculated_total_score');
                    $sortedFamiliesWithoutCriteria = $familiesWithoutCriteria->sortBy('created_at'); // قدیمی‌ترین اول

                    // ترکیب: ابتدا خانواده‌های با معیار، سپس بدون معیار
                    $sortedFamilies = $sortedFamiliesWithCriteria->concat($sortedFamiliesWithoutCriteria)->values();

                    // جایگذاری مجدد در pagination
                    $families->setCollection($sortedFamilies);

                    Log::info('Families sorted by criteria and score', [
                        'with_criteria_count' => $familiesWithCriteria->count(),
                        'without_criteria_count' => $familiesWithoutCriteria->count(),
                        'top_scores' => $sortedFamiliesWithCriteria->take(3)->pluck('calculated_total_score', 'id')->toArray()
                    ]);
                }

                return $families;
            });
        } catch (\Exception $e) {
            Log::error('Error in getFamiliesProperty with scoring', [
                'error' => $e->getMessage()
            ]);
            return $this->buildFamiliesQuery()->paginate($this->perPage);
        }
    }

    /**
     * ساخت کوئری پایه برای دریافت خانواده‌ها با بهینه‌سازی کارایی
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
// protected function buildFamiliesQuery()
// {
//     $query = Family::query()
//         ->select(['families.*']);

//     // ... سایر with ها

//     // ✅ اعمال فیلتر معیارها بر اساس JSON field
//     if (!empty($this->specific_criteria)) {
//         $selectedCriteriaNames = explode(',', $this->specific_criteria);

//         Log::info('Applying JSON criteria filter', [
//             'criteria_names' => $selectedCriteriaNames
//         ]);

//         // محاسبه امتیاز وزنی برای هر خانواده
//         $weights = $this->getCriteriaWeights();

//         $query->addSelect([
//             'criteria_score' => function($subQuery) use ($selectedCriteriaNames, $weights) {
//                 $subQuery->selectRaw('
//                     CASE
//                         WHEN acceptance_criteria IS NULL THEN 0
//                         ELSE (
//                             ' . implode(' + ', array_map(function($criteria) use ($weights) {
//                                 $weight = $weights[$criteria] ?? 1;
//                                 return "CASE WHEN JSON_CONTAINS(acceptance_criteria, JSON_QUOTE('{$criteria}')) THEN {$weight} ELSE 0 END";
//                             }, $selectedCriteriaNames)) . '
//                         )
//                     END
//                 ')
//                 ->from('families as f')
//                 ->whereColumn('f.id', 'families.id');
//             }
//         ]);

//         // فیلتر: فقط خانواده‌هایی که حداقل یکی از معیارها رو در acceptance_criteria دارن
//         $query->where(function($subQuery) use ($selectedCriteriaNames) {
//             foreach ($selectedCriteriaNames as $criteria) {
//                 $subQuery->orWhereRaw("JSON_CONTAINS(acceptance_criteria, JSON_QUOTE(?))", [$criteria]);
//             }
//         });

//         // مرتب‌سازی بر اساس امتیاز وزنی (بالاترین امتیاز اول)
//         $query->orderBy('criteria_score', 'desc');
//     }

//     // سایر فیلترها...

//     // مرتب‌سازی عادی فقط اگر فیلتر معیار نداشته باشیم
//     if (empty($this->specific_criteria)) {
//         if ($this->sortField) {
//             $query->orderBy("families.{$this->sortField}", $this->sortDirection);
//         } else {
//             $query->orderBy('families.created_at', 'desc');
//         }
//     }

//     return $query;
// }
protected function buildFamiliesQuery()
{
    try {
        // ایجاد query اولیه
        $baseQuery = Family::query()->select(['families.*']);

        // اعمال فیلتر wizard_status بر اساس تب انتخاب شده
        $this->applyTabStatusFilter($baseQuery);

        // بارگذاری روابط مورد نیاز

        // ساختن query parameters برای spatie QueryBuilder
        $queryParams = [];

        // اضافه کردن فیلتر criteria به query parameters
        if (!empty($this->specific_criteria)) {
            $queryParams['filter']['specific_criteria'] = $this->specific_criteria;

            Log::info('🎯 STEP 2: Adding criteria to query params', [
                'specific_criteria' => $this->specific_criteria,
                'user_id' => Auth::id()
            ]);
        }

        // اضافه کردن سایر فیلترها
        if (!empty($this->search)) {
            $queryParams['filter']['search'] = $this->search;
        }
        if (!empty($this->province_id)) {
            $queryParams['filter']['province_id'] = $this->province_id;
        }
        if (!empty($this->city_id)) {
            $queryParams['filter']['city_id'] = $this->city_id;
        }
        if (!empty($this->charity_id)) {
            $queryParams['filter']['charity_id'] = $this->charity_id;
        }

        // تنظیم query parameters در request
        if (!empty($queryParams)) {
            request()->merge($queryParams);
        }

        // اضافه کردن weighted ranking subquery اگر معیارهای رتبه‌بندی انتخاب شده
        if (!empty($this->specific_criteria)) {
            Log::info('🎯 STEP 3: Adding weighted ranking subquery', [
                'specific_criteria' => $this->specific_criteria,
                'user_id' => Auth::id()
            ]);

            $criteriaArray = is_string($this->specific_criteria)
                ? explode(',', $this->specific_criteria)
                : (array)$this->specific_criteria;
            $criteriaArray = array_filter($criteriaArray);

            if (!empty($criteriaArray)) {
                // دریافت وزن‌های معیارها
                $criteriaWeights = $this->getCriteriaWeights();

                // ساختن weighted score به عنوان یک field جداگانه با LEFT JOIN
                $weightedScoreSubquery = "COALESCE(";
                $scoreParts = [];

                foreach ($criteriaArray as $criteria) {
                    $criteria = trim($criteria);
                    $weight = $criteriaWeights[$criteria] ?? 1;

                    // امتیاز از acceptance_criteria خانواده
                    $scoreParts[] = "(
                        CASE WHEN JSON_CONTAINS(families.acceptance_criteria, JSON_QUOTE('{$criteria}'))
                        THEN {$weight} ELSE 0 END
                    )";

                    // امتیاز از تعداد اعضای مبتلا
                    $scoreParts[] = "(
                        {$weight} * (
                            SELECT COUNT(*) FROM members
                            WHERE members.family_id = families.id
                            AND JSON_CONTAINS(members.problem_type, JSON_QUOTE('{$criteria}'))
                            AND members.deleted_at IS NULL
                        )
                    )";
                }

                $weightedScoreSubquery .= implode(' + ', $scoreParts) . ", 0) as weighted_score";

                $baseQuery->selectRaw('families.*, ' . $weightedScoreSubquery);

                Log::info('📊 STEP 3.1: Weighted ranking subquery added', [
                    'criteria_count' => count($criteriaArray),
                    'criteria' => $criteriaArray,
                    'user_id' => Auth::id()
                ]);
            }
        }

        Log::info('🔍 STEP 4: Starting QueryBuilder creation', [
            'has_criteria' => !empty($this->specific_criteria),
            'query_params' => $queryParams,
            'user_id' => Auth::id()
        ]);

        // استفاده از QueryBuilder با فیلترهای مجاز
        $queryBuilder = QueryBuilder::for($baseQuery)
            ->allowedFilters([
                AllowedFilter::partial('search'),
                AllowedFilter::exact('province_id'),
                AllowedFilter::exact('city_id'),
                AllowedFilter::exact('district_id'),
                AllowedFilter::exact('region_id'),
                AllowedFilter::exact('charity_id'),
                AllowedFilter::exact('organization_id'),
                AllowedFilter::exact('status'),

                AllowedFilter::callback('members_count', function ($query, $value) {
                    if (is_numeric($value)) {
                        return $query->having('members_count', '=', (int)$value);
                    }
                    return $query;
                }),

                AllowedFilter::callback('specific_criteria', function ($query, $value, $property) {
                    Log::info('🎯 CRITERIA FILTER ACTIVATED: Processing specific_criteria', [
                        'value' => $value,
                        'property' => $property,
                        'value_type' => gettype($value),
                        'user_id' => Auth::id()
                    ]);

                    if (!empty($value)) {
                        // تبدیل رشته معیارها به آرایه
                        $criteriaArray = is_string($value) ? explode(',', $value) : (array)$value;
                        $criteriaArray = array_filter(array_map('trim', $criteriaArray)); // حذف مقادیر خالی و spaces

                        Log::info('🔍 CRITERIA FILTER: Parsed criteria array', [
                            'original_value' => $value,
                            'parsed_array' => $criteriaArray,
                            'count' => count($criteriaArray),
                            'user_id' => Auth::id()
                        ]);

                        if (!empty($criteriaArray)) {
                            $query->where(function($mainQuery) use ($criteriaArray) {
                                foreach ($criteriaArray as $criteria) {
                                    if (!empty($criteria)) {
                                        Log::info('🎯 Adding criteria condition', [
                                            'criteria' => $criteria,
                                            'user_id' => Auth::id()
                                        ]);

                                        $mainQuery->orWhere(function($subQuery) use ($criteria) {
                                            // شرط 1: معیار در acceptance_criteria خانواده باشد
                                            $subQuery->orWhereRaw("JSON_CONTAINS(acceptance_criteria, JSON_QUOTE(?))", [$criteria])
                                                     // شرط 2: یا حداقل یک عضو این مشکل را داشته باشد
                                                     ->orWhereHas('members', function($memberQuery) use ($criteria) {
                                                         $memberQuery->whereRaw("JSON_CONTAINS(problem_type, JSON_QUOTE(?))", [$criteria]);
                                                     });
                                        });
                                    }
                                }
                            });

                            Log::info('✅ CRITERIA FILTER: Applied successfully', [
                                'applied_criteria' => $criteriaArray,
                                'user_id' => Auth::id()
                            ]);
                        }
                    }

                    return $query;
                }),

                AllowedFilter::callback('membership_date_from', function ($query, $value) {
                    if (!empty($value)) {
                        return $query->whereDate('created_at', '>=', $value);
                    }
                    return $query;
                }),

                AllowedFilter::callback('membership_date_to', function ($query, $value) {
                    if (!empty($value)) {
                        return $query->whereDate('created_at', '<=', $value);
                    }
                    return $query;
                }),

                AllowedFilter::callback('weighted_score_min', function ($query, $value) {
                    if (is_numeric($value)) {
                        return $query->where('weighted_score', '>=', (float)$value);
                    }
                    return $query;
                }),

                AllowedFilter::callback('weighted_score_max', function ($query, $value) {
                    if (is_numeric($value)) {
                        return $query->where('weighted_score', '<=', (float)$value);
                    }
                    return $query;
                }),

                AllowedFilter::callback('insurance_end_date', function ($query, $value) {
                    if (!empty($value)) {
                        return $query->whereHas('finalInsurances', function($q) use ($value) {
                            $q->whereDate('end_date', '=', $value);
                        });
                    }
                    return $query;
                })
            ])
            ->allowedSorts([
                AllowedSort::field('created_at'),
                AllowedSort::field('updated_at'),
                AllowedSort::field('family_code'),
                AllowedSort::field('weighted_score'),
                'members_count'
            ]);

        // اعمال فیلترهای مودال پیشرفته
        $this->applyAdvancedModalFilters($queryBuilder);

        Log::info('🎯 STEP 5: About to apply custom sort', [
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'user_id' => Auth::id(),
            'timestamp' => now()
        ]);

        // اعمال سورت سفارشی
        $this->applySortToQueryBuilder($queryBuilder);

        // اعمال مرتب‌سازی پیش‌فرض اگر سورت سفارشی اعمال نشده
        if (empty($this->sortField) && !request()->has('sort')) {
            Log::info('🔄 STEP 5: Applying default sort (no custom sort)', [
                'user_id' => Auth::id()
            ]);
            $queryBuilder->getEloquentBuilder()->orderBy('families.created_at', 'asc');
        }

        Log::info('✅ STEP 5 COMPLETED: Query building finished', [
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'final_query_params' => request()->get('filter', []),
            'user_id' => Auth::id()
        ]);

        Log::info('✅ Families query built successfully', [
            'tab' => $this->activeTab,
            'filters_applied' => $this->hasActiveFilters(),
            'active_filters_count' => $this->getActiveFiltersCount(),
            'user_id' => Auth::id()
        ]);

        return $queryBuilder;

    } catch (\Exception $e) {
        Log::error('❌ Critical error in buildFamiliesQuery', [
            'tab' => $this->activeTab,
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'user_id' => Auth::id()
        ]);

        // بازگشت به query ساده در صورت خطای غیرمنتظره
        return QueryBuilder::for(
            Family::query()
                ->select(['families.*'])
                ->with(['head', 'province', 'city', 'district', 'region', 'charity', 'organization', 'members'])
                ->withCount('members')
                ->orderBy('families.created_at', 'asc')
        );
    }
}

/**
 * محاسبه امتیاز کامل خانواده با در نظر گیری تعداد اعضای متأثر
 */
public function calculateFamilyTotalScore($family): array
{
    $baseWeights = $this->getCriteriaWeights();
    $mapping = $this->getCriteriaMapping();
    $totalScore = 0;
    $details = [];

    // 1️⃣ امتیاز از acceptance_criteria خانواده (امتیاز پایه)
    if (!empty($family->acceptance_criteria)) {
        $familyCriteria = is_string($family->acceptance_criteria)
            ? json_decode($family->acceptance_criteria, true)
            : $family->acceptance_criteria;

        if (is_array($familyCriteria)) {
            foreach ($familyCriteria as $criteria) {
                if (isset($baseWeights[$criteria])) {
                    $baseScore = $baseWeights[$criteria];
                    $totalScore += $baseScore;
                    $details[] = [
                        'type' => 'family_criteria',
                        'name' => $criteria,
                        'base_score' => $baseScore,
                        'multiplier' => 1,
                        'final_score' => $baseScore
                    ];
                }
            }
        }
    }

    // 2️⃣ امتیاز اضافی بر اساس تعداد اعضای متأثر
    if ($family->members) {
        $memberProblems = [];

        // شمارش تعداد اعضایی که هر مشکل رو دارن
        foreach ($family->members as $member) {
            if (!empty($member->problem_type)) {
                $memberProblemTypes = is_string($member->problem_type)
                    ? json_decode($member->problem_type, true)
                    : $member->problem_type;

                if (is_array($memberProblemTypes)) {
                    foreach ($memberProblemTypes as $problem) {
                        $persianName = $mapping[$problem] ?? $problem;
                        if (!isset($memberProblems[$persianName])) {
                            $memberProblems[$persianName] = 0;
                        }
                        $memberProblems[$persianName]++;
                    }
                }
            }
        }

        // محاسبه امتیاز بر اساس تعداد اعضای متأثر
        foreach ($memberProblems as $problemName => $affectedCount) {
            if (isset($baseWeights[$problemName])) {
                $baseScore = $baseWeights[$problemName];

                // ضریب تشدید بر اساس تعداد اعضای متأثر
                $intensityMultiplier = $this->calculateIntensityMultiplier($affectedCount, $family->members->count());

                $additionalScore = $baseScore * $intensityMultiplier;
                $totalScore += $additionalScore;

                $details[] = [
                    'type' => 'member_problems',
                    'name' => $problemName,
                    'base_score' => $baseScore,
                    'affected_count' => $affectedCount,
                    'total_members' => $family->members->count(),
                    'multiplier' => $intensityMultiplier,
                    'final_score' => $additionalScore
                ];
            }
        }
    }

    return [
        'total_score' => round($totalScore, 1),
        'details' => $details
    ];
}

/**
 * محاسبه ضریب تشدید بر اساس درصد اعضای متأثر
 */
private function calculateIntensityMultiplier(int $affectedCount, int $totalMembers): float
{
    if ($totalMembers === 0) return 0;

    $affectedPercentage = ($affectedCount / $totalMembers) * 100;

    // ضریب بر اساس درصد اعضای متأثر
    if ($affectedPercentage >= 75) {
        return 2.0;  // بیش از 75% اعضا متأثر → ضریب 2
    } elseif ($affectedPercentage >= 50) {
        return 1.5;  // 50-75% اعضا متأثر → ضریب 1.5
    } elseif ($affectedPercentage >= 25) {
        return 1.2;  // 25-50% اعضا متأثر → ضریب 1.2
    } else {
        return 0.8;  // کمتر از 25% اعضا متأثر → ضریب 0.8
    }
}

/**
 * محاسبه امتیاز کل یک خانواده بر اساس معیارها و مشکلات اعضا
 */
public function calculateFamilyScore($family): int
{
    $score = 0;
    $weights = $this->getCriteriaWeights();
    $mapping = $this->getCriteriaMapping();

    // امتیاز از acceptance_criteria خانواده
    if (!empty($family->acceptance_criteria)) {
        $familyCriteria = is_string($family->acceptance_criteria)
            ? json_decode($family->acceptance_criteria, true)
            : $family->acceptance_criteria;

        if (is_array($familyCriteria)) {
            foreach ($familyCriteria as $criteria) {
                $score += $weights[$criteria] ?? 1;
            }
        }
    }

    // امتیاز اضافی از problem_type اعضای خانواده
    if ($family->members) {
        foreach ($family->members as $member) {
            if (!empty($member->problem_type)) {
                $memberProblems = is_string($member->problem_type)
                    ? json_decode($member->problem_type, true)
                    : $member->problem_type;

                if (is_array($memberProblems)) {
                    foreach ($memberProblems as $problem) {
                        // تبدیل نام انگلیسی به فارسی
                        $persianName = $mapping[$problem] ?? $problem;
                        $score += ($weights[$persianName] ?? 1) * 0.5; // نصف وزن برای اعضا
                    }
                }
            }
        }
    }

    return (int) $score;
}
    /**
 * دریافت نام معیارها برای نمایش
 */
private function getCriteriaNames(array $criteriaIds): array
{
    try {
        // Cache کردن نام معیارها برای بهبود کارایی
        return Cache::remember("criteria_names_" . implode('_', $criteriaIds), 3600, function() use ($criteriaIds) {
            return \App\Models\RankSetting::whereIn('id', $criteriaIds)
                ->pluck('name', 'id')
                ->toArray();
        });
    } catch (\Exception $e) {
        Log::warning('Could not fetch criteria names', ['error' => $e->getMessage()]);
        // fallback: استفاده از ID ها
        return array_map(fn($id) => "معیار #{$id}", $criteriaIds);
    }
}
    /**
 * اضافه کردن فیلتر معیارها به لیست فیلترهای فعال
 */
private function getCriteriaWithWeights(array $criteriaIds): array
{
    try {
        return Cache::remember("criteria_weights_" . implode('_', $criteriaIds), 3600, function() use ($criteriaIds) {
            return \App\Models\RankSetting::whereIn('id', $criteriaIds)
                ->select('id', 'name', 'weight')
                ->orderBy('weight', 'desc')  // مرتب‌سازی بر اساس وزن
                ->get()
                ->toArray();
        });
    } catch (\Exception $e) {
        Log::warning('Could not fetch criteria with weights', ['error' => $e->getMessage()]);
        // fallback
        return array_map(fn($id) => [
            'id' => $id,
            'name' => "معیار #{$id}",
            'weight' => 1
        ], $criteriaIds);
    }
}

/**
 * اضافه کردن فیلتر معیارها با جزئیات وزن
 */
private function addCriteriaToActiveFilters(array $criteriaInfo, int $totalWeight): void
{
    // حذف فیلتر معیارهای قبلی
    $this->activeFilters = collect($this->activeFilters ?? [])
        ->filter(fn($filter) => $filter['type'] !== 'criteria')
        ->values()
        ->toArray();

    // اضافه کردن فیلتر جدید معیارها با وزن
    if (!empty($criteriaInfo)) {
        $label = 'معیارها (مرتب شده بر اساس وزن): ';
        $details = array_map(function($criteria) {
            return "{$criteria['name']} ({$criteria['weight']})";
        }, $criteriaInfo);

        $this->activeFilters[] = [
            'type' => 'criteria',
            'value' => implode(',', array_column($criteriaInfo, 'id')),
            'label' => $label . implode('، ', $details),
            'total_weight' => $totalWeight
        ];
    }
}

/**
 * اعمال فیلترهای مودال پیشرفته بر روی QueryBuilder
 *
 * @param \Spatie\QueryBuilder\QueryBuilder $queryBuilder
 * @return \Spatie\QueryBuilder\QueryBuilder
 */
protected function applyAdvancedModalFilters($queryBuilder)
{
    try {
        $filtersToApply = $this->tempFilters ?? $this->filters ?? [];

        if (empty($filtersToApply)) {
            Log::info('🔧 No advanced modal filters to apply', [
                'tempFilters_count' => count($this->tempFilters ?? []),
                'filters_count' => count($this->filters ?? []),
                'user_id' => Auth::id()
            ]);
            return $queryBuilder;
        }

        Log::info('🚀 Applying advanced modal filters', [
            'filters_count' => count($filtersToApply),
            'user_id' => Auth::id()
        ]);

        // تفکیک فیلترها به گروه‌های AND و OR
        $andFilters = collect($filtersToApply)->filter(function($filter) {
            return ($filter['logical_operator'] ?? 'and') === 'and';
        });

        $orFilters = collect($filtersToApply)->filter(function($filter) {
            return ($filter['logical_operator'] ?? 'and') === 'or';
        });

        $eloquentQuery = $queryBuilder->getEloquentBuilder();

        // اعمال فیلترهای AND
        if ($andFilters->isNotEmpty()) {
            foreach ($andFilters as $filter) {
                $this->applySingleAdvancedFilter($eloquentQuery, $filter, 'and');
            }
        }

        // اعمال فیلترهای OR در یک گروه
        if ($orFilters->isNotEmpty()) {
            $eloquentQuery->where(function($query) use ($orFilters) {
                foreach ($orFilters as $filter) {
                    $this->applySingleAdvancedFilter($query, $filter, 'or');
                }
            });
        }

        Log::info('✅ Advanced modal filters applied successfully', [
            'and_filters_count' => $andFilters->count(),
            'or_filters_count' => $orFilters->count(),
            'user_id' => Auth::id()
        ]);

        return $queryBuilder;

    } catch (\Exception $e) {
        Log::error('❌ Error applying advanced modal filters', [
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'filters_data' => $filtersToApply ?? [],
            'user_id' => Auth::id()
        ]);

        return $queryBuilder;
    }
}

/**
 * اعمال یک فیلتر پیشرفته بر روی کوئری
 *
 * @param \Illuminate\Database\Eloquent\Builder $query
 * @param array $filter
 * @param string $method
 * @return \Illuminate\Database\Eloquent\Builder
 */
protected function applySingleAdvancedFilter($query, $filter, $method = 'and')
{
    try {
        $filterType = $filter['type'] ?? null;
        $filterValue = $filter['value'] ?? null;
        $operator = $filter['operator'] ?? 'equals';

        if (empty($filterType) || $filterValue === null || $filterValue === '') {
            return $query;
        }

        Log::debug('🔍 Applying single advanced filter', [
            'type' => $filterType,
            'value' => $filterValue,
            'operator' => $operator,
            'method' => $method
        ]);

        $queryMethod = $method === 'or' ? 'orWhere' : 'where';
        $queryMethodHas = $method === 'or' ? 'orWhereHas' : 'whereHas';

        switch ($filterType) {
            case 'province':
                return $query->{$queryMethod}('families.province_id', $this->getOperatorQuery($operator), $filterValue);

            case 'city':
                return $query->{$queryMethod}('families.city_id', $this->getOperatorQuery($operator), $filterValue);

            case 'charity':
                return $query->{$queryMethod}('families.charity_id', $this->getOperatorQuery($operator), $filterValue);

            case 'members_count':
                if (is_numeric($filterValue)) {
                    $havingMethod = $method === 'or' ? 'orHaving' : 'having';
                    return $query->{$havingMethod}('members_count', $this->getOperatorQuery($operator), (int)$filterValue);
                }
                break;

            case 'special_disease':
                return $query->{$queryMethodHas}('members', function($memberQuery) use ($filterValue) {
                    $memberQuery->where(function($q) use ($filterValue) {
                        $q->whereJsonContains('problem_type', $filterValue)
                          ->orWhereJsonContains('problem_type', \App\Helpers\ProblemTypeHelper::englishToPersian($filterValue))
                          ->orWhereJsonContains('problem_type', \App\Helpers\ProblemTypeHelper::persianToEnglish($filterValue));
                    });
                });

            case 'acceptance_criteria':
                return $query->{$queryMethod}(function($q) use ($filterValue) {
                    $q->whereJsonContains('acceptance_criteria', $filterValue);
                });

            case 'membership_date':
                if ($operator === 'range' && is_array($filterValue) && count($filterValue) === 2) {
                    return $query->{$queryMethod}(function($q) use ($filterValue) {
                        $q->whereDate('families.created_at', '>=', $filterValue[0])
                          ->whereDate('families.created_at', '<=', $filterValue[1]);
                    });
                } else {
                    return $query->{$queryMethod}('families.created_at', $this->getOperatorQuery($operator), $filterValue);
                }
                break;

            case 'weighted_score':
                if (is_numeric($filterValue)) {
                    return $query->{$queryMethod}('families.weighted_score', $this->getOperatorQuery($operator), (float)$filterValue);
                }
                break;

            case 'insurance_end_date':
                return $query->{$queryMethodHas}('finalInsurances', function($q) use ($filterValue, $operator) {
                    $q->whereDate('end_date', $this->getOperatorQuery($operator), $filterValue);
                });

            case 'created_at':
                return $query->{$queryMethod}('families.created_at', $this->getOperatorQuery($operator), $filterValue);

            default:
                Log::warning('⚠️ Unknown filter type', [
                    'filter_type' => $filterType,
                    'available_types' => ['province', 'city', 'charity', 'members_count', 'special_disease', 'acceptance_criteria', 'membership_date', 'weighted_score', 'insurance_end_date', 'created_at']
                ]);
                break;
        }

        return $query;

    } catch (\Exception $e) {
        Log::error('❌ Error applying single advanced filter', [
            'filter_type' => $filter['type'] ?? 'unknown',
            'method' => $method,
            'error_message' => $e->getMessage(),
            'user_id' => Auth::id()
        ]);

        return $query;
    }
}

/**
 * تبدیل عملگر فیلتر به عملگر SQL
 *
 * @param string $operator
 * @return string
 */
protected function getOperatorQuery($operator)
{
    return match($operator) {
        'equals' => '=',
        'not_equals' => '!=',
        'greater_than' => '>',
        'greater_than_or_equal' => '>=',
        'less_than' => '<',
        'less_than_or_equal' => '<=',
        'like' => 'LIKE',
        'not_like' => 'NOT LIKE',
        'in' => 'IN',
        'not_in' => 'NOT IN',
        default => '='
    };
}

/**
 * پاک کردن فیلتر معیارها
 */
public function clearCriteriaFilter()
{
    $this->specific_criteria = null;
    $this->selectedCriteria = [];

    // حذف از فیلترهای فعال
    $this->activeFilters = collect($this->activeFilters ?? [])
        ->filter(fn($filter) => $filter['type'] !== 'criteria')
        ->values()
        ->toArray();

    $this->resetPage();
    $this->clearFamiliesCache();

    $this->dispatch('toast', [
        'message' => 'فیلتر معیارها پاک شد',
        'type' => 'info'
    ]);
}
    /**
     * دریافت تعداد کل اعضای خانواده‌های انتخاب شده
     *
     * @return int
     */
    public function getTotalMembersProperty()
    {
        if (empty($this->selected)) {
            return 0;
        }

        return \App\Models\Member::whereIn('family_id', $this->selected)->count();
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


        if (empty($this->selected)) {
            $errorMsg = 'هیچ خانواده‌ای برای بازگشت به مرحله قبل انتخاب نشده است. لطفاً ابتدا خانواده‌های مورد نظر را انتخاب کنید.';
            $this->dispatch('show-persistent-error', message: $errorMsg);
            return;
        }

        try {


            $families = Family::whereIn('id', $this->selected)->get();



            if ($families->isEmpty()) {
                $errorMsg = 'خانواده‌های انتخاب شده یافت نشدند یا مشکلی در دریافت آن‌ها وجود دارد.';

                $this->dispatch('show-persistent-error', message: $errorMsg);
                return;
            }

            $batchId = 'move_prev_step_' . time() . '_' . uniqid();
            $movedCount = 0;
            $cantMoveCount = 0;
            $errors = [];
            $successMessages = [];

            DB::beginTransaction();

            try {


                foreach ($families as $family) {


                    $currentStepValue = $family->wizard_status;
                    $currentStepEnum = null;

                    if (is_string($currentStepValue) && !empty($currentStepValue)) {
                        try {
                            $currentStepEnum = InsuranceWizardStep::from($currentStepValue);

                        } catch (\ValueError $e) {
                            $errors[] = "خانواده {$family->family_code}: وضعیت فعلی ('{$currentStepValue}') نامعتبر است.";
                            $cantMoveCount++;
                            continue;
                        }
                    } elseif ($currentStepValue instanceof InsuranceWizardStep) {
                        $currentStepEnum = $currentStepValue;

                    } else {
                        $errors[] = "خانواده {$family->family_code}: وضعیت فعلی تعریف نشده یا خالی است.";
                        $cantMoveCount++;
                        continue;
                    }

                    $previousStepEnum = $currentStepEnum->previousStep();


                    if ($previousStepEnum) {
                        try {
                            // استفاده از setAttribute به جای تغییر مستقیم wizard_status
                            $family->setAttribute('wizard_status', $previousStepEnum->value);

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



                            FamilyStatusLog::create([
                                'family_id' => $family->id,
                                'user_id' => Auth::id(),
                                'from_status' => $currentStepEnum->value,
                                'to_status' => $previousStepEnum->value,
                                'comments' => 'بازگشت به مرحله قبل توسط کاربر: ' . Auth::user()?->name,
                                'batch_id' => $batchId,
                            ]);


                            $movedCount++;
                        } catch (\Exception $e) {

                            $errors[] = "خطا در به‌روزرسانی وضعیت خانواده {$family->family_code}: " . $e->getMessage();
                            $cantMoveCount++;
                        }
                    } else {

                        $errors[] = "خانواده {$family->family_code} در اولین مرحله ({$currentStepEnum->label()}) قرار دارد یا مرحله قبلی برای آن تعریف نشده است.";
                        $cantMoveCount++;
                    }
                }



                if ($movedCount > 0) {
                    $successMessages[] = "{$movedCount} خانواده با موفقیت به مرحله قبل منتقل شدند.";
                }

                DB::commit();

                // UI Updates after successful commit
                if (method_exists($this, 'clearFamiliesCache')) {
                    $this->clearFamiliesCache();

                    // اضافه کردن این خط برای به‌روزرسانی فوری UI
                    $this->dispatch('refreshFamiliesList');
                }

                // Refresh the current tab's data
                $this->setTab($this->activeTab, false); // false to not reset selections here, as we do it next

                // Reset selections
                $this->selected = [];
                $this->selectAll = false;
                $this->dispatch('reset-checkboxes');

                // Display messages
                if (!empty($successMessages) && empty($errors)) {
                    session()->flash('message', implode(' ', $successMessages));
                } elseif (!empty($errors)) {
                    $finalMessage = implode(' ', array_merge($successMessages, $errors));
                    // Use persistent error for combined messages if any error occurred
                    $this->dispatch('show-persistent-error', message: $finalMessage);
                }

            } catch (\Exception $e) {

                DB::rollback();
                $errorMsg = 'خطا در سیستم هنگام انتقال خانواده‌ها به مرحله قبل: ' . $e->getMessage();
                $this->dispatch('show-persistent-error', message: $errorMsg);

            }
        } catch (\Exception $e) {
            $errorMsg = 'خطای سیستمی: ' . $e->getMessage();
            $this->dispatch('show-persistent-error', message: $errorMsg);

        }

    }

    //endregion

    //region Excel Upload Modal

    /**
     * Opens the Excel upload modal.
     */
    public function openExcelUploadModal()
    {
        $this->showExcelUploadModal = true;
        $this->dispatch('showExcelUploadModal');
        Log::info('✅ Excel upload modal should be shown now, showExcelUploadModal = true');
    }

    /**
     * Closes the Excel upload modal.
     */
    public function closeExcelUploadModal()
    {
        $this->showExcelUploadModal = false;
        $this->dispatch('closeExcelUploadModal');
        Log::info('🔒 Excel upload modal closed');
    }

    //endregion

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

        // تنظیم آرایه selected با یک آیدی خانواده
        $this->selected = [(string)$familyId];

        // استفاده از متد باز کردن مودال
        $this->openDeleteModal();

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

        // بررسی انتخاب حداقل یک خانواده
        if (empty($this->selected)) {
            session()->flash('error', 'لطفاً حداقل یک خانواده را انتخاب کنید');
            return;
        }

        // استفاده از متد باز کردن مودال
        $this->openDeleteModal();

    }

    public function handlePageRefresh()
    {
        $this->clearFamiliesCache();
    }

    /**
     * Get current filters for Alpine.js download functionality
     */
    public function getFilters()
    {
        return [
            'search' => $this->search,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'district_id' => $this->district_id,
            'region_id' => $this->region_id,
            'organization_id' => $this->organization_id,
            'charity_id' => $this->charity_id,
            'status' => $this->status,
            'family_rank_range' => $this->family_rank_range,
            'specific_criteria' => $this->specific_criteria,
        ];
    }

    /**
     * دریافت تعداد کل خانواده‌های نمایش داده شده در تب فعلی
     */
    public function getCurrentViewCount()
    {
        return $this->getFamiliesProperty()->total();
    }

    /**
     * بررسی وجود فیلترهای فعال
     */
    public function hasActiveFilters()
    {
        return !empty($this->search) ||
               !empty($this->province_id) ||
               !empty($this->city_id) ||
               !empty($this->district_id) ||
               !empty($this->region_id) ||
               !empty($this->organization_id) ||
               !empty($this->charity_id) ||
               !empty($this->activeFilters) ||
               !empty($this->status) ||
               !empty($this->province) ||
               !empty($this->city) ||
               !empty($this->deprivation_rank) ||
               !empty($this->family_rank_range) ||
               !empty($this->specific_criteria) ||
               !empty($this->charity) ||
               !empty($this->region);
    }

    /**
     * شمارش فیلترهای فعال
     */
    public function getActiveFiltersCount()
    {
        $count = 0;
        if (!empty($this->search)) $count++;
        if (!empty($this->province_id)) $count++;
        if (!empty($this->city_id)) $count++;
        if (!empty($this->district_id)) $count++;
        if (!empty($this->region_id)) $count++;
        if (!empty($this->organization_id)) $count++;
        if (!empty($this->charity_id)) $count++;
        if (!empty($this->activeFilters)) $count += count($this->activeFilters);
        if (!empty($this->status)) $count++;
        if (!empty($this->province)) $count++;
        if (!empty($this->city)) $count++;
        if (!empty($this->deprivation_rank)) $count++;
        if (!empty($this->family_rank_range)) $count++;
        if (!empty($this->specific_criteria)) $count++;
        if (!empty($this->charity)) $count++;
        if (!empty($this->region)) $count++;
        return $count;
    }


    public function getProvincesProperty()
    {
        return cache()->remember('provinces_list', 3600, function () {
            return \App\Models\Province::orderBy('name')->get();
        });
    }

    /**
     * دریافت آمارهای سایدبار با استفاده از کش
     *
     * @return array
     */
    public function getSidebarStatsProperty()
    {
        // تشخیص نوع کاربر و دسترسی‌های آن
        $user = Auth::user();
        $userType = $user ? $user->type : 'guest';
        $charityId = $user && isset($user->charity_id) ? $user->charity_id : null;

        // ساخت کلید کش منحصر به فرد بر اساس نوع کاربر و خیریه
        $cacheKey = "sidebar-stats-{$userType}-" . ($charityId ?? 'all');

        // کش کردن آمار به مدت ۵ دقیقه
        return Cache::remember($cacheKey, 300, function () use ($charityId, $userType) {
            try {
                $query = Family::query();

                // اگر کاربر مدیر خیریه است، فقط خانواده‌های مربوط به آن خیریه را ببیند
                if ($charityId && $userType === 'charity_admin') {
                    $query->where('charity_id', $charityId);
                }

                // بهینه‌سازی N+1: استفاده از یک کوئری برای محاسبه تمام آمارها
                $result = $query->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = "reviewing" THEN 1 ELSE 0 END) as reviewing_count,
                    SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected_count,
                    SUM(CASE WHEN is_insured = 1 THEN 1 ELSE 0 END) as insured_count
                ')->first();

                // ساخت آرایه آمار
                return [
                    'total' => $result->total ?? 0,
                    'pending' => $result->pending_count ?? 0,
                    'reviewing' => $result->reviewing_count ?? 0,
                    'approved' => $result->approved_count ?? 0,
                    'rejected' => $result->rejected_count ?? 0,
                    'insured' => $result->insured_count ?? 0,
                    'uninsured' => ($result->total ?? 0) - ($result->insured_count ?? 0),
                ];
            } catch (\Exception $e) {
                // در صورت بروز خطا، لاگ کرده و آمار خالی برمی‌گردانیم
                Log::error('Error in sidebar stats calculation', [
                    'error' => $e->getMessage(),
                    'user_type' => $userType,
                    'charity_id' => $charityId
                ]);

                return [
                    'total' => 0,
                    'pending' => 0,
                    'reviewing' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                    'insured' => 0,
                    'uninsured' => 0,
                ];
            }
        });
    }


    /**
     * دریافت لیست انواع مشکلات موجود در سیستم
     *
     * @return array
     */
    public function getProblemTypesProperty()
    {
        return [
            'addiction' => 'اعتیاد',
            'unemployment' => 'بیکاری',
            'disability' => 'معلولیت',
            'special_disease' => 'بیماری خاص',
            'work_disability' => 'از کار افتادگی',
            'single_parent' => 'سرپرست خانوار',
            'elderly' => 'کهولت سن',
            'other' => 'سایر'
        ];
    }

    public function getCitiesProperty()
    {
        // حل مشکل: استفاده از کش
        return cache()->remember('cities_list', 3600, function () {
            return \App\Models\City::orderBy('name')->get();
        });
    }

    public function getOrganizationsProperty()
    {
        // حل مشکل: استفاده از کش
        return cache()->remember('organizations_list', 3600, function () {
            return \App\Models\Organization::where('type', 'charity')->orderBy('name')->get();
        });
    }

    /**
     * بارگذاری شهرهای یک استان به صورت lazy loading
     */
    public function loadCitiesByProvince($provinceId)
    {
        if (empty($provinceId)) {
            return [];
        }

        return cache()->remember("cities_province_{$provinceId}", 1800, function () use ($provinceId) {
            return \App\Models\City::where('province_id', $provinceId)
                                  ->orderBy('name')
                                  ->get(['id', 'name']);
        });
    }


    public function downloadSampleTemplate()
    {
        try {
            // دریافت خانواده‌های انتخاب شده یا همه خانواده‌های صفحه
            if (!empty($this->selected)) {
                // اگر خانواده‌ای انتخاب شده، فقط اونها
                $families = Family::whereIn('id', $this->selected)
                    ->with(['head', 'province', 'city', 'district', 'charity', 'organization', 'members', 'finalInsurances'])
                    ->get();
                $downloadType = 'selected_families';
            } else {
                // اگر هیچ خانواده‌ای انتخاب نشده، همه خانواده‌های صفحه فعلی
                $families = $this->getFamiliesProperty();
                $downloadType = 'all_page_families';
            }

            if ($families->isEmpty()) {
                session()->flash('error', 'هیچ خانواده‌ای برای تولید فایل نمونه یافت نشد.');
                return null;
            }

            // ایجاد کالکشن برای داده‌های اکسل (مشابه متد export)
            $excelData = collect();

            foreach ($families as $family) {
                // محاسبه تاریخ عضویت
                $membershipDate = $family->created_at ?
                    \Morilog\Jalali\Jalalian::fromCarbon($family->created_at)->format('Y/m/d') :
                    'نامشخص';

                // محاسبه درصد مشارکت و نام مشارکت کننده (اصلاح شده)
                $participationPercentage = '';
                $participantName = '';

                if ($this->activeTab === 'approved') {
                    // اول جستجو در FamilyFundingAllocation برای این خانواده (برای سازگاری با سیستم قدیمی)
                    $latestAllocation = FamilyFundingAllocation::where('family_id', $family->id)
                        ->orderBy('created_at', 'desc')
                        ->with(['fundingSource', 'importLog.user'])
                        ->first();

                    if ($latestAllocation) {
                        // اگر داده‌ای در FamilyFundingAllocation پیدا شد
                        $participationPercentage = $latestAllocation->percentage . '%';

                        // تلاش برای یافتن نام مشارکت‌کننده
                        if ($latestAllocation->fundingSource) {
                            // اگر منبع بانک باشد، نام بانک را نمایش بده
                            if ($latestAllocation->fundingSource->type === 'bank') {
                                $participantName = $latestAllocation->fundingSource->name; // نام بانک
                            } else {
                                $participantName = $latestAllocation->fundingSource->name;
                            }
                        } elseif ($latestAllocation->importLog && $latestAllocation->importLog->user) {
                            $participantName = $latestAllocation->importLog->user->name;
                        } else {
                            $participantName = 'نامشخص';
                        }
                    } else {
                        // اگر داده‌ای در FamilyFundingAllocation نبود، از insurance_shares جستجو کن (سیستم جدید)
                        $latestInsuranceShare = \App\Models\InsuranceShare::whereHas('familyInsurance', function($q) use ($family) {
                                $q->where('family_id', $family->id);
                            })
                            ->orderBy('created_at', 'desc')
                            ->first();

                        if ($latestInsuranceShare) {
                            // درصد مشارکت از insurance_share
                            $participationPercentage = $latestInsuranceShare->percentage . '%';

                            // نام مشارکت کننده - از متد getPayerNameAttribute که منطق کامل دارد
                            $participantName = $latestInsuranceShare->payer_name;
                        } else {
                            // اگر هیچ داده‌ای پیدا نشد، از ShareAllocationLog جستجو کن (سیستم قدیمی)
                            $latestShareLog = \App\Models\ShareAllocationLog::whereJsonContains('family_ids', [$family->id])
                                ->orWhere(function($q) use ($family) {
                                    $q->whereJsonContains('shares_data->families', $family->id)
                                      ->orWhereJsonContains('shares_data->allocated_families', $family->id);
                                })
                                ->with('user')
                                ->orderBy('created_at', 'desc')
                                ->first();

                            if ($latestShareLog) {
                                // استخراج درصد مشارکت از داده‌های JSON
                                $sharesData = is_string($latestShareLog->shares_data)
                                    ? json_decode($latestShareLog->shares_data, true)
                                    : $latestShareLog->shares_data;

                                // تلاش برای یافتن درصد این خانواده
                                if (isset($sharesData['family_percentages'][$family->id])) {
                                    $participationPercentage = $sharesData['family_percentages'][$family->id] . '%';
                                } elseif (isset($sharesData['default_percentage'])) {
                                    $participationPercentage = $sharesData['default_percentage'] . '%';
                                } else {
                                    $participationPercentage = '50%'; // درصد پیش‌فرض
                                }

                                // نام مشارکت کننده از کاربر
                                if ($latestShareLog->user) {
                                    $participantName = $latestShareLog->user->name;
                                } elseif (isset($sharesData['funding_source_name'])) {
                                    $participantName = $sharesData['funding_source_name'];
                                } else {
                                    $participantName = 'مجموعه خیریه';
                                }
                            } else {
                                // اگر هیچ داده‌ای پیدا نشد، مقادیر پیش‌فرض
                                $participationPercentage = '50%';
                                $participantName = 'مجموعه خیریه';
                            }
                        }
                    }
                }

                // اضافه کردن سرپرست خانواده به عنوان یک ردیف
                if ($family->head) {
                    $headAcceptanceCriteria = $this->getMemberAcceptanceCriteria($family->head);
                    $headHasDocuments = $this->checkMemberHasDocuments($family->head);

                    $excelData->push([
                        'family_code' => $family->family_code,
                        'head_name' => $family->head->first_name . ' ' . $family->head->last_name,
                        'head_national_id' => $family->head->national_code,
                        'is_head' => 'بله',
                        'member_name' => $family->head->first_name . ' ' . $family->head->last_name,
                        'member_national_id' => $family->head->national_code,
                        'member_relationship' => $family->head->relationship_fa ?? 'سرپرست خانوار',
                        'member_birth_date' => $family->head->birth_date ? \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($family->head->birth_date))->format('Y/m/d') : null,
                        'member_gender' => $this->translateGender($family->head->gender),
                        'acceptance_criteria' => $headAcceptanceCriteria,
                        'has_documents' => $headHasDocuments,
                        'membership_date' => $membershipDate,
                        'participation_percentage' => $participationPercentage,
                        'participant_name' => $participantName,
                        'province' => $family->province ? $family->province->name : 'نامشخص',
                        'city' => $family->city ? $family->city->name : 'نامشخص',
                        'dehestan' => $family->district ? $family->district->name : 'نامشخص',
                        'organization' => $family->organization ? $family->organization->name : 'نامشخص',
                        'insurance_type' => '', // ادمین باید پر کند
                        'insurance_amount' => 0, // ادمین باید پر کند
                        'start_date' => null, // ادمین باید پر کند
                        'end_date' => null, // ادمین باید پر کند
                    ]);
                }

                // اضافه کردن اعضای خانواده (غیر از سرپرست)
                $nonHeadMembers = $family->members->where('is_head', false);
                foreach ($nonHeadMembers as $member) {
                    $memberAcceptanceCriteria = $this->getMemberAcceptanceCriteria($member);
                    $memberHasDocuments = $this->checkMemberHasDocuments($member);

                    $excelData->push([
                        'family_code' => $family->family_code,
                        'head_name' => $family->head ? $family->head->first_name . ' ' . $family->head->last_name : 'نامشخص',
                        'head_national_id' => $family->head ? $family->head->national_code : 'نامشخص',
                        'is_head' => 'خیر',
                        'member_name' => $member->first_name . ' ' . $member->last_name,
                        'member_national_id' => $member->national_code,
                        'member_relationship' => $member->relationship_fa ?? 'نامشخص',
                        'member_birth_date' => $member->birth_date ? \Morilog\Jalali\Jalalian::fromCarbon(\Carbon\Carbon::parse($member->birth_date))->format('Y/m/d') : null,
                        'member_gender' => $this->translateGender($member->gender),
                        'acceptance_criteria' => $memberAcceptanceCriteria,
                        'has_documents' => $memberHasDocuments,
                        'membership_date' => $membershipDate,
                        'participation_percentage' => $participationPercentage,
                        'participant_name' => $participantName,
                        'province' => $family->province ? $family->province->name : 'نامشخص',
                        'city' => $family->city ? $family->city->name : 'نامشخص',
                        'dehestan' => $family->district ? $family->district->name : 'نامشخص',
                        'organization' => $family->organization ? $family->organization->name : 'نامشخص',
                        'insurance_type' => '', // ادمین باید پر کند
                        'insurance_amount' => 0, // ادمین باید پر کند
                        'start_date' => null, // ادمین باید پر کند
                        'end_date' => null, // ادمین باید پر کند
                    ]);
                }
            }

            // تعریف هدرهای جدید (شامل ستون‌های جدید)
            $headings = [
                'کد خانوار',
                'کد ملی سرپرست',
                'سرپرست',
                'نام عضو',
                'کد ملی عضو',
                'نسبت',
                'تاریخ تولد',
                'جنسیت',
                'معیار پذیرش',
                'مدرک',
                'تاریخ عضویت',
            ];

            // اضافه کردن ستون‌های درصد مشارکت و نام مشارکت کننده فقط برای تب "در انتظار حمایت"
            if ($this->activeTab === 'approved') {
                $headings[] = 'درصد مشارکت';
                $headings[] = 'نام مشارکت کننده';
            }

            $headings = array_merge($headings, [
                'استان',
                'شهرستان',
                'دهستان',
                'سازمان',
                'نوع بیمه',
                'مبلغ بیمه',
                'تاریخ شروع',
                'تاریخ پایان',
            ]);

            // کلیدهای داده جدید (هماهنگ با داده‌های واقعی)
            $dataKeys = [
                'family_code',
                'head_national_id',
                'is_head',
                'member_name',
                'member_national_id',
                'member_relationship',
                'member_birth_date',
                'member_gender',
                'acceptance_criteria',
                'has_documents',
                'membership_date',
            ];

            // اضافه کردن کلیدهای درصد مشارکت و نام مشارکت کننده فقط برای تب "در انتظار حمایت"
            if ($this->activeTab === 'approved') {
                $dataKeys[] = 'participation_percentage';
                $dataKeys[] = 'participant_name';
            }

            $dataKeys = array_merge($dataKeys, [
                'province',
                'city',
                'dehestan',
                'organization',
                'insurance_type',
                'insurance_amount',
                'start_date',
                'end_date',
            ]);

            // دانلود فایل
            $fileName = 'sample-families-' . $this->activeTab . '-' . now()->format('Y-m-d') . '.xlsx';
            $response = Excel::download(
                new DynamicDataExport($excelData, $headings, $dataKeys),
                $fileName
            );

            // ✅ بعد از دانلود موفق، انتقال به تب "در انتظار صدور"
            $this->dispatch('file-downloaded-successfully', [
                'message' => 'فایل نمونه با موفقیت دانلود شد. این فایل شامل تمام ستون‌های مورد نیاز برای بیمه خانواده‌ها است.',
                'families_count' => $families->count()
            ]);

            // تغییر تب به "در انتظار صدور"
            $this->setTab('excel');

            // نمایش پیام راهنما
            session()->flash('message', 'فایل نمونه شامل ' . $families->count() . ' خانواده دانلود شد. نوع بیمه، مبلغ بیمه، تاریخ شروع و پایان خالی گذاشته شده تا ادمین پر کند. درصد مشارکت و نام مشارکت‌کننده از سهمیه‌بندی قبلی گرفته شده است.');

            return $response;

        } catch (\Exception $e) {
            Log::error('خطا در دانلود قالب بیمه: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => Auth::id()
            ]);

            session()->flash('error', 'خطا در دانلود قالب: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * اعمال فیلتر wizard_status بر اساس تب انتخاب شده
     */
    protected function applyTabStatusFilter($query)
    {
        try {
            switch ($this->tab) {
                case 'pending':
                    $query->where('wizard_status', InsuranceWizardStep::PENDING->value);
                    break;

                case 'reviewing':
                    $query->where('wizard_status', InsuranceWizardStep::REVIEWING->value);
                    break;

                case 'approved':
                    $query->where(function($q) {
                        $q->where('wizard_status', 'approved')
                          ->orWhere('wizard_status', 'excel_upload');
                    })->where('status', '!=', 'deleted');
                    break;

                case 'rejected':
                    $query->where('wizard_status', InsuranceWizardStep::REJECTED->value);
                    break;

                case 'excel':
                    $query->where('wizard_status', InsuranceWizardStep::EXCEL_UPLOAD->value);
                    break;

                case 'renewal':
                    $query->whereHas('finalInsurances', function ($q) {
                        $q->where('end_date', '<', now());
                    })->whereIn('wizard_status', [
                        InsuranceWizardStep::INSURED->value,
                        InsuranceWizardStep::RENEWAL->value
                    ]);
                    break;

                case 'deleted':
                    $query->onlyTrashed();
                    break;
            }

            Log::debug('📋 Tab status filter applied', [
                'tab' => $this->tab,
                'wizard_status_filter' => $this->tab
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error applying tab status filter', [
                'tab' => $this->tab,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * اعمال فیلتر معیارهای خاص بر اساس JSON field
     */
    protected function applyCriteriaFilter($query)
    {
        try {
            $selectedCriteriaNames = explode(',', $this->specific_criteria);

            $query->where(function($mainQuery) use ($selectedCriteriaNames) {
                // شرط 1: معیار در acceptance_criteria خانواده باشه
                foreach ($selectedCriteriaNames as $criteria) {
                    $mainQuery->orWhereRaw("JSON_CONTAINS(acceptance_criteria, JSON_QUOTE(?))", [$criteria]);
                }

                // شرط 2: اعضای خانواده این مشکلات رو داشته باشن
                $mainQuery->orWhereHas('members', function($memberQuery) use ($selectedCriteriaNames) {
                    $mapping = $this->getCriteriaMapping();
                    $englishProblems = [];

                    foreach ($selectedCriteriaNames as $persianCriteria) {
                        $englishProblem = array_search($persianCriteria, $mapping);
                        if ($englishProblem) {
                            $englishProblems[] = $englishProblem;
                        }
                    }

                    if (!empty($englishProblems)) {
                        foreach ($englishProblems as $problem) {
                            // تبدیل مشکل به فارسی و انگلیسی برای جستجو
                        $persianProblem = ProblemTypeHelper::englishToPersian($problem);
                        $englishProblem = ProblemTypeHelper::persianToEnglish($problem);

                        $memberQuery->orWhereRaw("JSON_CONTAINS(problem_type, JSON_QUOTE(?))", [$persianProblem])
                                   ->orWhereRaw("JSON_CONTAINS(problem_type, JSON_QUOTE(?))", [$problem])
                                   ->orWhereRaw("JSON_CONTAINS(problem_type, JSON_QUOTE(?))", [$englishProblem])
                                   ->orWhereRaw("JSON_CONTAINS(problem_type, JSON_QUOTE(?))", ['بیماری های خاص'])
                                   ->orWhereRaw("JSON_CONTAINS(problem_type, JSON_QUOTE(?))", ['بیماری خاص'])
                                   ->orWhereRaw("JSON_CONTAINS(problem_type, JSON_QUOTE(?))", ['special_disease'])
                                   ->orWhereRaw("JSON_CONTAINS(problem_type, JSON_QUOTE(?))", ['اعتیاد'])
                                   ->orWhereRaw("JSON_CONTAINS(problem_type, JSON_QUOTE(?))", ['addiction'])
                                   ->orWhereRaw("JSON_CONTAINS(problem_type, JSON_QUOTE(?))", ['از کار افتادگی'])
                                   ->orWhereRaw("JSON_CONTAINS(problem_type, JSON_QUOTE(?))", ['work_disability'])
                                   ->orWhereRaw("JSON_CONTAINS(problem_type, JSON_QUOTE(?))", ['بیکاری'])
                                   ->orWhereRaw("JSON_CONTAINS(problem_type, JSON_QUOTE(?))", ['unemployment']);
                        }
                    }
                });
            });

            // مرتب‌سازی بر اساس امتیاز محاسبه شده (بعداً در Collection)
            $query->orderBy('families.created_at', 'asc');

            Log::debug('🎯 Criteria filter applied', [
                'criteria_count' => count($selectedCriteriaNames),
                'criteria' => $selectedCriteriaNames
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error applying criteria filter', [
                'specific_criteria' => $this->specific_criteria,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * اعمال فیلترهای اضافی از request
     */
    protected function applyRequestFilters($queryBuilder)
    {
        try {
            // اعمال فیلتر و سورت بر اساس پارامترهای فعلی کامپوننت
            if (!empty($this->specific_criteria)) {
                // در صورت وجود معیارهای خاص، سورت پیش‌فرض قدیمی‌ترین اول
                return $queryBuilder;
            }

            // اعمال سورت بر اساس تنظیمات کامپوننت
            $validDirection = in_array($this->sortDirection, ['asc', 'desc']) ? $this->sortDirection : 'asc';

            if ($this->sortField) {
                $sortField = match($this->sortField) {
                    'created_at' => 'families.created_at',
                    'updated_at' => 'families.updated_at',
                    'family_code' => 'families.family_code',
                    'status' => 'families.status',
                    'wizard_status' => 'families.wizard_status',
                    'members_count' => 'members_count',
                    default => 'families.created_at'
                };

                // بازنویسی سورت پیش‌فرض
                $queryBuilder->getEloquentBuilder()->reorder($sortField, $validDirection);
            }

            Log::debug('🔧 Request filters applied', [
                'sort_field' => $this->sortField,
                'sort_direction' => $this->sortDirection,
                'valid_direction' => $validDirection
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error applying request filters', [
                'sort_field' => $this->sortField,
                'sort_direction' => $this->sortDirection,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * اعمال فیلترهای مودال به QueryBuilder
     */
    public function applyFilters()
    {
        try {
            Log::info('🔧 Applying modal filters', [
                'filters_count' => count($this->filters ?? []),
                'user_id' => Auth::id()
            ]);

            // بازنشانی query parameters فعلی
            $this->resetPage();

            // آپدیت لیست خانواده‌ها با فیلترهای جدید
            // این کار باعث می‌شود buildFamiliesQuery دوباره اجرا شود
            $this->dispatch('filters-updated');

            session()->flash('message', 'فیلترها با موفقیت اعمال شدند.');

            Log::info('✅ Modal filters applied successfully', [
                'user_id' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error applying modal filters', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            session()->flash('error', 'خطا در اعمال فیلترها: ' . $e->getMessage());
        }
    }

    /**
     * اعمال سورت بر اساس متغیرهای کلاس
     */
    protected function applySortToQueryBuilder($queryBuilder)
    {
        try {
            Log::info('🎯 STEP 4: Starting applySortToQueryBuilder', [
                'sortField' => $this->sortField,
                'sortDirection' => $this->sortDirection,
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            if (empty($this->sortField)) {
                Log::info('🔄 STEP 4: No sort field specified, using default', [
                    'user_id' => Auth::id()
                ]);
                return $queryBuilder;
            }

            // تعریف فیلدهای قابل سورت و نگاشت آنها
            $sortMappings = [
                'created_at' => 'families.created_at',
                'updated_at' => 'families.updated_at',
                'family_code' => 'families.family_code',
                'status' => 'families.status',
                'wizard_status' => 'families.wizard_status',
                'members_count' => 'members_count',
                'final_insurances_count' => 'final_insurances_count',
                'calculated_rank' => 'families.calculated_rank',
                'deprivation_rank' => 'families.deprivation_rank',
                'weighted_score' => 'families.weighted_score'
            ];

            $sortDirection = $this->sortDirection === 'desc' ? 'desc' : 'asc';

            Log::info('⚙️ STEP 4.1: Sort parameters prepared', [
                'sortField' => $this->sortField,
                'sortDirection' => $sortDirection,
                'sortMappings' => array_keys($sortMappings),
                'user_id' => Auth::id()
            ]);

            // اعمال سورت بر اساس نوع فیلد
            switch ($this->sortField) {
                case 'head_name':
                    Log::info('📋 STEP 4.2: Applying head_name sort');
                    // سورت خاص برای نام سرپرست
                    $queryBuilder->getEloquentBuilder()
                        ->leftJoin('people as head_person', 'families.head_id', '=', 'head_person.id')
                        ->orderBy('head_person.first_name', $sortDirection)
                        ->orderBy('head_person.last_name', $sortDirection);
                    break;

                case 'final_insurances_count':
                    Log::info('📋 STEP 4.2: Applying final_insurances_count sort');
                    // سورت بر اساس تعداد بیمه‌های نهایی
                    $queryBuilder->getEloquentBuilder()
                        ->withCount('finalInsurances')
                        ->orderBy('final_insurances_count', $sortDirection);
                    break;

                case 'calculated_rank':
                    Log::info('📋 STEP 4.2: Applying calculated_rank sort');
                    // سورت بر اساس رتبه محاسبه شده
                    if ($sortDirection === 'desc') {
                        $queryBuilder->getEloquentBuilder()->orderByRaw('families.calculated_rank IS NULL, families.calculated_rank DESC');
                    } else {
                        $queryBuilder->getEloquentBuilder()->orderByRaw('families.calculated_rank IS NULL, families.calculated_rank ASC');
                    }
                    break;

                case 'weighted_rank':
                    Log::info('📋 STEP 4.2: Applying weighted_rank sort');
                    // سورت بر اساس امتیاز وزنی معیارهای انتخاب شده
                    $this->applyWeightedRankSort($queryBuilder, $sortDirection);
                    break;

                default:
                    Log::info('📋 STEP 4.2: Applying default sort');
                    // سورت معمولی برای سایر فیلدها
                    if (isset($sortMappings[$this->sortField])) {
                        $fieldName = $sortMappings[$this->sortField];
                        $queryBuilder->getEloquentBuilder()->orderBy($fieldName, $sortDirection);
                    } else {
                        Log::warning('⚠️ STEP 4 WARNING: Unknown sort field', [
                            'sort_field' => $this->sortField,
                            'user_id' => Auth::id()
                        ]);
                        // بازگشت به سورت پیش‌فرض
                        $queryBuilder->getEloquentBuilder()->orderBy('families.created_at', 'desc');
                    }
                    break;
            }

            Log::info('✅ STEP 4 COMPLETED: Sort applied successfully', [
                'sort_field' => $this->sortField,
                'sort_direction' => $sortDirection,
                'user_id' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ STEP 4 ERROR: Error applying sort', [
                'error' => $e->getMessage(),
                'sort_field' => $this->sortField,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * اعمال سورت بر اساس امتیاز وزنی معیارهای انتخاب شده
     */
    protected function applyWeightedRankSort($queryBuilder, $sortDirection)
    {
        try {
            Log::info('🎯 STEP 5: Starting applyWeightedRankSort', [
                'sortDirection' => $sortDirection,
                'selectedCriteria' => $this->selectedCriteria ?? [],
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            // دریافت معیارهای انتخاب شده
            $selectedCriteriaIds = array_keys(array_filter($this->selectedCriteria ?? [], fn($value) => $value === true));

            Log::info('📊 STEP 5.1: Selected criteria analysis', [
                'selectedCriteriaIds' => $selectedCriteriaIds,
                'selectedCriteriaIds_count' => count($selectedCriteriaIds),
                'user_id' => Auth::id()
            ]);

            if (empty($selectedCriteriaIds)) {
                Log::warning('❌ STEP 5 FAILED: No criteria selected for weighted sort', [
                    'user_id' => Auth::id()
                ]);
                // اگر معیاری انتخاب نشده، سورت بر اساس تاریخ ایجاد
                $queryBuilder->getEloquentBuilder()->orderBy('families.created_at', 'desc');
                return;
            }

            // ایجاد subquery برای محاسبه امتیاز وزنی با ضرب وزن در تعداد موارد
            $criteriaIds = implode(',', $selectedCriteriaIds);
            $weightedScoreSubquery = "
                (
                    SELECT COALESCE(SUM(
                        rs.weight * (
                            -- شمارش موارد معیار در acceptance_criteria (0 یا 1)
                            CASE
                                WHEN JSON_CONTAINS(families.acceptance_criteria, CAST(rs.id AS JSON))
                                THEN 1
                                ELSE 0
                            END +
                            -- شمارش تعداد اعضای دارای این معیار در problem_type
                            (
                                SELECT COUNT(*)
                                FROM members fm
                                WHERE fm.family_id = families.id
                                AND JSON_CONTAINS(fm.problem_type, CAST(rs.id AS JSON))
                                AND fm.deleted_at IS NULL
                            )
                        )
                    ), 0)
                    FROM rank_settings rs
                    WHERE rs.id IN ({$criteriaIds})
                    AND rs.is_active = 1
                )
            ";

            Log::info('⚙️ STEP 5.2: Weighted score subquery created', [
                'criteriaIds' => $criteriaIds,
                'weightedScoreSubquery_length' => strlen($weightedScoreSubquery),
                'user_id' => Auth::id()
            ]);

            // اضافه کردن امتیاز محاسبه شده به select
            $queryBuilder->getEloquentBuilder()
                ->addSelect(DB::raw("({$weightedScoreSubquery}) as weighted_score"))
                ->orderBy('weighted_score', $sortDirection)
                ->orderBy('families.created_at', 'desc'); // سورت ثانویه

            Log::info('✅ STEP 5 COMPLETED: Weighted rank sort applied successfully', [
                'criteria_ids' => $selectedCriteriaIds,
                'sort_direction' => $sortDirection,
                'user_id' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ STEP 5 ERROR: Error applying weighted rank sort', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            // در صورت خطا، سورت بر اساس تاریخ ایجاد
            $queryBuilder->getEloquentBuilder()->orderBy('families.created_at', 'desc');
        }
    }

    /**
     * تست سورت وزنی - برای اشکال‌زدایی
     */
    public function testWeightedSort()
    {
        try {
            Log::info('🧪 Testing weighted sort', [
                'selectedCriteria' => $this->selectedCriteria ?? [],
                'sortField' => $this->sortField,
                'sortDirection' => $this->sortDirection,
                'user_id' => Auth::id()
            ]);

            // تست محاسبه امتیاز برای چند خانواده
            $testFamilies = Family::with(['members'])->limit(5)->get();

            foreach ($testFamilies as $family) {
                $score = $this->calculateFamilyScore($family);
                Log::info('📊 Family score test', [
                    'family_id' => $family->id,
                    'family_code' => $family->family_code,
                    'acceptance_criteria' => $family->acceptance_criteria,
                    'members_count' => $family->members->count(),
                    'calculated_score' => $score
                ]);
            }

            $this->dispatch('toast', [
                'message' => 'تست سورت وزنی انجام شد - لاگ‌ها را بررسی کنید',
                'type' => 'info'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error in weighted sort test', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            $this->dispatch('toast', [
                'message' => 'خطا در تست سورت: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * تست فیلتر استان و کش - برای اشکال‌زدایی
     */
    public function testProvinceFilter($provinceId = null)
    {
        try {
            Log::info('🧪 Testing province filter and cache', [
                'province_id' => $provinceId,
                'current_filters' => $this->filters ?? [],
                'user_id' => Auth::id()
            ]);

            // تست کلیدهای کش فعلی
            $cacheKey = $this->getCacheKey();
            $cacheExists = Cache::has($cacheKey);

            Log::info('📊 Cache status before filter test', [
                'cache_key' => $cacheKey,
                'cache_exists' => $cacheExists
            ]);

            // اگر province_id داده شده، تست فیلتر استان
            if ($provinceId) {
                // پاک کردن کش قدیمی
                $this->clearFamiliesCache();

                // تست فیلتر استان
                $testQuery = Family::query()
                    ->select(['families.*'])
                    ->with(['province', 'head'])
                    ->where('families.province_id', $provinceId)
                    ->limit(5)
                    ->get();

                Log::info('✅ Province filter test result', [
                    'province_id' => $provinceId,
                    'families_found' => $testQuery->count(),
                    'sample_family_codes' => $testQuery->pluck('family_code')->toArray()
                ]);

                $this->dispatch('toast', [
                    'message' => "تست فیلتر استان: {$testQuery->count()} خانواده یافت شد",
                    'type' => 'info'
                ]);
            }

            // تست کش جدید
            $newCacheKey = $this->getCacheKey();
            Log::info('📊 Cache status after test', [
                'old_cache_key' => $cacheKey,
                'new_cache_key' => $newCacheKey,
                'keys_different' => $cacheKey !== $newCacheKey
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error in province filter test', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            $this->dispatch('toast', [
                'message' => 'خطا در تست فیلتر: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * تست فیلترها بدون اعمال
     */
    public function testFilters()
    {
        try {
            Log::info('🧪 Testing filters', [
                'tempFilters_count' => count($this->tempFilters ?? []),
                'filters_count' => count($this->filters ?? []),
                'tempFilters_data' => $this->tempFilters,
                'user_id' => Auth::id()
            ]);

            $validFilters = [];
            $invalidFilters = [];

            foreach ($this->tempFilters ?? [] as $index => $filter) {
                if (empty($filter['type'])) {
                    $invalidFilters[] = $index + 1;
                    continue;
                }

                // برای فیلترهای تاریخ عضویت، بررسی start_date و end_date
                if ($filter['type'] === 'membership_date') {
                    if (empty($filter['start_date']) && empty($filter['end_date'])) {
                        $invalidFilters[] = $index + 1;
                        continue;
                    }
                } else {
                    // برای سایر فیلترها، بررسی value
                    if (empty($filter['value'])) {
                        $invalidFilters[] = $index + 1;
                        continue;
                    }
                }

                // بررسی اعتبار نوع فیلتر
                $allowedTypes = ['status', 'province', 'city', 'deprivation_rank', 'charity', 'members_count', 'created_at', 'weighted_score', 'special_disease', 'membership_date'];
                if (!in_array($filter['type'], $allowedTypes)) {
                    $invalidFilters[] = $index + 1;
                    continue;
                }

                $validFilters[] = $index + 1;
            }

            $message = sprintf(
                'نتیجه تست: %d فیلتر معتبر، %d فیلتر نامعتبر',
                count($validFilters),
                count($invalidFilters)
            );

            if (!empty($invalidFilters)) {
                $message .= ' (فیلترهای نامعتبر: ' . implode(', ', $invalidFilters) . ')';
            }

            session()->flash('message', $message);

            Log::info('✅ Filter test completed', [
                'valid_filters' => count($validFilters),
                'invalid_filters' => count($invalidFilters),
                'user_id' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error testing filters', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            session()->flash('error', 'خطا در تست فیلترها: ' . $e->getMessage());
        }
    }

    /**
     * بازگشت به تنظیمات پیش‌فرض
     */
    public function resetToDefault()
    {
        try {
            Log::info('🔄 Resetting filters to default', [
                'user_id' => Auth::id()
            ]);

            // بازنشانی فیلترها
            $this->activeFilters = [];
            $this->tempFilters = [];
            $this->specific_criteria = '';
            $this->sortField = 'created_at';
            $this->sortDirection = 'desc';

            // بازنشانی صفحه‌بندی
            $this->resetPage();

            session()->flash('message', 'فیلترها به حالت پیش‌فرض بازگشتند.');

            Log::info('✅ Filters reset to default successfully', [
                'user_id' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error resetting filters', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            session()->flash('error', 'خطا در بازنشانی فیلترها: ' . $e->getMessage());
        }
    }

    /**
     * تبدیل فیلترهای مودال به فرمت QueryBuilder
     */
    protected function convertModalFiltersToQueryBuilder($queryBuilder)
    {
        try {
            // استفاده از tempFilters به جای filters (مشکل اصلی فیلتر استان)
            $filtersToApply = $this->tempFilters ?? $this->filters ?? [];

            if (empty($filtersToApply)) {
                Log::info('🔧 No modal filters to apply', [
                    'tempFilters_count' => count($this->tempFilters ?? []),
                    'filters_count' => count($this->filters ?? []),
                    'user_id' => Auth::id()
                ]);
                return $queryBuilder;
            }

                        Log::info('🔧 Applying modal filters with AND/OR operators', [
                'filters_count' => count($filtersToApply),
                'filters_data' => $filtersToApply,
                'user_id' => Auth::id()
            ]);

            // گروه‌بندی فیلترها بر اساس عملگر منطقی (AND/OR)
            $andFilters = [];
            $orFilters = [];

            foreach ($filtersToApply as $filter) {
                if (empty($filter['type'])) {
                    continue;
                }

                // برای فیلترهای تاریخ عضویت، بررسی start_date و end_date
                if ($filter['type'] === 'membership_date') {
                    if (empty($filter['start_date']) && empty($filter['end_date'])) {
                        continue;
                    }
                } else {
                    // برای سایر فیلترها، بررسی value
                    if (empty($filter['value'])) {
                        continue;
                    }
                }

                $logicalOperator = $filter['logical_operator'] ?? 'and';

                if ($logicalOperator === 'or') {
                    $orFilters[] = $filter;
                } else {
                    $andFilters[] = $filter;
                }
            }

            // اعمال فیلترهای AND
            if (!empty($andFilters)) {
                foreach ($andFilters as $filter) {
                    $queryBuilder = $this->applySingleFilter($queryBuilder, $filter, 'and');
                }
            }

            // اعمال فیلترهای OR
            if (!empty($orFilters)) {
                $queryBuilder = $queryBuilder->where(function($query) use ($orFilters) {
                    foreach ($orFilters as $index => $filter) {
                        if ($index === 0) {
                            // اولین فیلتر OR با where معمولی
                            $query = $this->applySingleFilter($query, $filter, 'where');
                        } else {
                            // بقیه فیلترها با orWhere
                            $query = $this->applySingleFilter($query, $filter, 'or');
                        }
                    }
                    return $query;
                });
            }

            Log::info('✅ Modal filters applied successfully', [
                'and_filters_count' => count($andFilters),
                'or_filters_count' => count($orFilters),
                'user_id' => Auth::id()
            ]);

            return $queryBuilder;

        } catch (\Exception $e) {
            Log::error('❌ Error applying modal filters', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return $queryBuilder;
        }
    }

    /**
     * اعمال یک فیلتر منفرد
     */
    protected function applySingleFilter($queryBuilder, $filter, $method = 'and')
    {
        try {
            $filterType = $filter['type'];
            $filterValue = $filter['value'];
            $operator = $filter['operator'] ?? 'equals';

            Log::info('🔍 Processing filter', [
                'type' => $filterType,
                'value' => $filterValue,
                'operator' => $operator,
                'method' => $method,
                'full_filter' => $filter
            ]);

            // تعیین نوع متد بر اساس عملگر منطقی
            $whereMethod = $method === 'or' ? 'orWhere' : 'where';
            $whereHasMethod = $method === 'or' ? 'orWhereHas' : 'whereHas';
            $whereDoesntHaveMethod = $method === 'or' ? 'orWhereDoesntHave' : 'whereDoesntHave';

            switch ($filterType) {
                case 'status':
                    if ($operator === 'equals') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.status', $filterValue);
                    } elseif ($operator === 'not_equals') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.status', '!=', $filterValue);
                    }
                    break;

                case 'province':
                    if ($operator === 'equals') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.province_id', $filterValue);
                    } elseif ($operator === 'not_equals') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.province_id', '!=', $filterValue);
                    }
                    break;

                case 'city':
                    if ($operator === 'equals') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.city_id', $filterValue);
                    } elseif ($operator === 'not_equals') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.city_id', '!=', $filterValue);
                    }
                    break;

                case 'charity':
                    if ($operator === 'equals') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.organization_id', $filterValue);
                    } elseif ($operator === 'not_equals') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.organization_id', '!=', $filterValue);
                    }
                    break;

                case 'members_count':
                    $queryBuilder = $this->applyNumericFilter($queryBuilder, 'members_count', $operator, $filterValue, $method);
                    break;

                case 'created_at':
                    $queryBuilder = $this->applyDateFilter($queryBuilder, 'families.created_at', $operator, $filterValue, $method);
                    break;

                case 'membership_date':
                    // فیلتر بر اساس بازه زمانی تاریخ عضویت
                    Log::info('🔍 Processing membership_date filter', [
                        'start_date' => $filter['start_date'] ?? 'empty',
                        'end_date' => $filter['end_date'] ?? 'empty',
                        'filter_data' => $filter
                    ]);

                    if (!empty($filter['start_date']) || !empty($filter['end_date'])) {
                        $queryBuilder = $queryBuilder->$whereMethod(function($q) use ($filter, $method) {
                            if (!empty($filter['start_date'])) {
                                $startDate = $this->parseJalaliOrGregorianDate($filter['start_date']);
                                Log::info('📅 Parsed start_date', [
                                    'original' => $filter['start_date'],
                                    'parsed' => $startDate
                                ]);
                                if ($startDate) {
                                    $q->where('families.created_at', '>=', $startDate);
                                }
                            }
                            if (!empty($filter['end_date'])) {
                                $endDate = $this->parseJalaliOrGregorianDate($filter['end_date']);
                                Log::info('📅 Parsed end_date', [
                                    'original' => $filter['end_date'],
                                    'parsed' => $endDate
                                ]);
                                if ($endDate) {
                                    $q->where('families.created_at', '<=', $endDate . ' 23:59:59');
                                }
                            }
                        });
                    }
                    break;

                case 'insurance_end_date':
                    // فیلتر بر اساس تاریخ پایان بیمه
                    $queryBuilder = $queryBuilder->$whereHasMethod('finalInsurances', function($q) use ($operator, $filterValue) {
                        $this->applyDateFilter($q, 'end_date', $operator, $filterValue);
                    });
                    break;

                case 'deprivation_rank':
                    // فیلتر بر اساس رتبه محرومیت
                    switch ($filterValue) {
                        case 'high':
                            if ($method === 'or') {
                                $queryBuilder = $queryBuilder->orWhere(function($q) {
                                    $q->whereBetween('families.deprivation_rank', [1, 3]);
                                });
                            } else {
                                $queryBuilder = $queryBuilder->whereBetween('families.deprivation_rank', [1, 3]);
                            }
                            break;
                        case 'medium':
                            if ($method === 'or') {
                                $queryBuilder = $queryBuilder->orWhere(function($q) {
                                    $q->whereBetween('families.deprivation_rank', [4, 6]);
                                });
                            } else {
                                $queryBuilder = $queryBuilder->whereBetween('families.deprivation_rank', [4, 6]);
                            }
                            break;
                        case 'low':
                            if ($method === 'or') {
                                $queryBuilder = $queryBuilder->orWhere(function($q) {
                                    $q->whereBetween('families.deprivation_rank', [7, 10]);
                                });
                            } else {
                                $queryBuilder = $queryBuilder->whereBetween('families.deprivation_rank', [7, 10]);
                            }
                            break;
                    }
                    break;

                                case 'special_disease':
                case 'معیار پذیرش':
                    // پشتیبانی از هر دو نام فیلتر برای سازگاری
                    if (!empty($filterValue)) {
                        $queryBuilder = $queryBuilder->$whereMethod(function($q) use ($filterValue) {
                            // جستجو در اعضای خانواده با problem_type - پشتیبانی از تمام مقادیر
                            $q->whereHas('members', function($memberQuery) use ($filterValue) {
                                // تبدیل به مقادیر مختلف
                                $persianValue = ProblemTypeHelper::englishToPersian($filterValue);
                                $englishValue = ProblemTypeHelper::persianToEnglish($filterValue);

                                $memberQuery->whereJsonContains('problem_type', $filterValue)
                                          ->orWhereJsonContains('problem_type', $persianValue)
                                          ->orWhereJsonContains('problem_type', $englishValue);
                            });
                        });
                    }
                    break;

                case 'weighted_score':
                    if (!empty($filter['min'])) {
                        if ($method === 'or') {
                            $queryBuilder = $queryBuilder->orWhere('families.weighted_score', '>=', $filter['min']);
                        } else {
                            $queryBuilder = $queryBuilder->where('families.weighted_score', '>=', $filter['min']);
                        }
                    }
                    if (!empty($filter['max'])) {
                        if ($method === 'or') {
                            $queryBuilder = $queryBuilder->orWhere('families.weighted_score', '<=', $filter['max']);
                        } else {
                            $queryBuilder = $queryBuilder->where('families.weighted_score', '<=', $filter['max']);
                        }
                    }
                    break;
            }

            return $queryBuilder;

        } catch (\Exception $e) {
            Log::error('❌ Error applying single filter', [
                'filter_type' => $filter['type'] ?? 'unknown',
                'method' => $method,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return $queryBuilder;
        }
    }

    /**
     * اعمال فیلتر عددی
     */
    protected function applyNumericFilter($queryBuilder, $field, $operator, $value, $method = 'and')
    {
        $whereMethod = $method === 'or' ? 'orWhere' : 'where';

        switch ($operator) {
            case 'equals':
                return $queryBuilder->$whereMethod($field, $value);
            case 'not_equals':
                return $queryBuilder->$whereMethod($field, '!=', $value);
            case 'greater_than':
                return $queryBuilder->$whereMethod($field, '>', $value);
            case 'less_than':
                return $queryBuilder->$whereMethod($field, '<', $value);
            default:
                return $queryBuilder->$whereMethod($field, $value);
        }
    }

    /**
     * اعمال فیلتر تاریخ
     */
    protected function applyDateFilter($queryBuilder, $field, $operator, $value, $method = 'and')
    {
        $whereMethod = $method === 'or' ? 'orWhereDate' : 'whereDate';

        switch ($operator) {
            case 'equals':
                return $queryBuilder->$whereMethod($field, $value);
            case 'greater_than':
                return $queryBuilder->$whereMethod($field, '>', $value);
            case 'less_than':
                return $queryBuilder->$whereMethod($field, '<', $value);
            default:
                return $queryBuilder->$whereMethod($field, $value);
        }
    }

    /**
     * تبدیل کد نسبت به فارسی
     *
     * @param string|null $relationship
     * @return string
     */
    private function translateRelationship($relationship)
    {
        if (empty($relationship)) {
            return 'نامشخص';
        }

        $relationshipMap = [
            'spouse' => 'همسر',
            'child' => 'فرزند',
            'son' => 'پسر',
            'daughter' => 'دختر',
            'father' => 'پدر',
            'mother' => 'مادر',
            'brother' => 'برادر',
            'sister' => 'خواهر',
            'grandfather' => 'پدربزرگ',
            'grandmother' => 'مادربزرگ',
            'uncle' => 'عمو/دایی',
            'aunt' => 'عمه/خاله',
            'nephew' => 'برادرزاده',
            'niece' => 'خواهرزاده',
            'cousin' => 'پسرعمو/دخترعمو',
            'son_in_law' => 'داماد',
            'daughter_in_law' => 'عروس',
            'father_in_law' => 'پدرشوهر/پدرزن',
            'mother_in_law' => 'مادرشوهر/مادرزن',
            'other' => 'سایر',
            // مقادیر فارسی برای سازگاری
            'همسر' => 'همسر',
            'فرزند' => 'فرزند',
            'پسر' => 'پسر',
            'دختر' => 'دختر',
            'پدر' => 'پدر',
            'مادر' => 'مادر',
            'برادر' => 'برادر',
            'خواهر' => 'خواهر',
            'سرپرست خانوار' => 'سرپرست خانوار',
            'سرپرست' => 'سرپرست خانوار'
        ];

        return $relationshipMap[$relationship] ?? $relationship;
    }

    /**
     * بررسی نیاز به مدرک برای نوع مشکل
     *
     * @param string $problemType
     * @return bool
     */
    private function checkDocumentRequirement($problemType)
    {
        // معیارهایی که نیاز به مدرک دارند
        $requiresDocumentation = [
            'disability' => true,
            'معلولیت' => true,
            'special_disease' => true,
            'بیماری خاص' => true,
            'بیماری های خاص' => true,
            'work_disability' => true,
            'از کار افتادگی' => true,
            'ازکارافتادگی' => true,
            'chronic_illness' => true,
            'بیماری مزمن' => true,
        ];

        return isset($requiresDocumentation[trim($problemType)]) && $requiresDocumentation[trim($problemType)];
    }

    /**
     * ترجمه انواع مشکلات
     *
     * @var array
     */
    private $problemTypeTranslations = [
        'addiction' => 'اعتیاد',
        'unemployment' => 'بیکاری',
        'disability' => 'معلولیت',
        'special_disease' => 'بیماری خاص',
        'work_disability' => 'ازکارافتادگی',
        'single_parent' => 'سرپرست خانوار زن',
        'elderly' => 'سالمندی',
        'chronic_illness' => 'بیماری مزمن',
        'other' => 'سایر',
        // Persian to Persian normalization
        'بیماری های خاص' => 'بیماری خاص',
        'از کار افتادگی' => 'ازکارافتادگی',
        'کهولت سن' => 'سالمندی'
    ];

    /**
     * تبدیل کد جنسیت به فارسی
     *
     * @param string|null $gender
     * @return string
     */
    private function translateGender($gender)
    {
        if (empty($gender)) {
            return 'نامشخص';
        }

        $genderMap = [
            'male' => 'مرد',
            'female' => 'زن',
            'm' => 'مرد',
            'f' => 'زن',
            '1' => 'مرد',
            '2' => 'زن',
            'man' => 'مرد',
            'woman' => 'زن'
        ];

        return $genderMap[strtolower($gender)] ?? $gender;
    }

    /**
     * دریافت معیارهای پذیرش یک عضو
     *
     * @param \App\Models\Member $member
     * @return string
     */
    private function getMemberAcceptanceCriteria($member)
    {
        if (!$member || !$member->problem_type) {
            return 'ندارد';
        }

        $problemTypes = is_array($member->problem_type)
            ? $member->problem_type
            : json_decode($member->problem_type, true) ?? [];

        if (empty($problemTypes)) {
            return 'ندارد';
        }

        $translatedTypes = [];
        foreach ($problemTypes as $type) {
            $translatedType = $this->problemTypeTranslations[trim($type)] ?? trim($type);
            if (!in_array($translatedType, $translatedTypes)) {
                $translatedTypes[] = $translatedType;
            }
        }

        return !empty($translatedTypes) ? implode('، ', $translatedTypes) : 'ندارد';
    }

    /**
     * بررسی اینکه عضو مدرک دارد یا نه
     *
     * @param \App\Models\Member $member
     * @return string
     */
    private function checkMemberHasDocuments($member)
    {
        if (!$member || !$member->problem_type) {
            return 'ندارد';
        }

        $problemTypes = is_array($member->problem_type)
            ? $member->problem_type
            : json_decode($member->problem_type, true) ?? [];

        if (empty($problemTypes)) {
            return 'ندارد';
        }

        $hasDocumentRequirement = false;
        foreach ($problemTypes as $type) {
            if ($this->checkDocumentRequirement($type)) {
                $hasDocumentRequirement = true;
                break;
            }
        }

        return $hasDocumentRequirement ? 'دارد' : 'ندارد';
    }

    //======================================================================
    //== متدهای سیستم ذخیره و بارگذاری فیلترها
    //======================================================================

    /**
     * ذخیره فیلتر فعلی با نام و تنظیمات مشخص
     * @param string $name
     * @param string|null $description
     * @return void
     */
    public function saveFilter($name, $description = null)
    {
        try {
            Log::info('🔍 Starting saveFilter method from', [
                'component' => 'FamiliesApproval',
                'showRankModal' => $this->showRankModal,
                'user_id' => Auth::id(),
                'name' => $name
            ]);

            // بررسی وجود فیلترهایی برای ذخیره
            $currentFilters = $this->tempFilters ?? $this->activeFilters ?? [];

            // در اینجا بررسی می‌کنیم که از کدام مودال درخواست ذخیره آمده است
            $isFromRankModal = $this->showRankModal;

            // اگر از مودال رتبه‌بندی نیست، فقط در صورت عدم وجود فیلتر خطا نمایش دهد
            if (!$isFromRankModal && empty($currentFilters)) {
                session()->flash('message', 'هیچ فیلتری برای ذخیره وجود ندارد');
                session()->flash('type', 'warning');
                return;
            }

            // تنظیم فیلترهای پایه
            $configData = [
                'filters' => $currentFilters,
                'component_filters' => [
                    'search' => $this->search,
                    'status' => $this->status,
                    'province_id' => $this->province_id,
                    'city_id' => $this->city_id,
                    'charity_id' => $this->charity_id,
                    'deprivation_rank' => $this->deprivation_rank,
                    'family_rank_range' => $this->family_rank_range,
                    'specific_criteria' => $this->specific_criteria
                ],
                'sort' => [
                    'field' => $this->sortField,
                    'direction' => $this->sortDirection
                ],
                'tab' => $this->activeTab
            ];

            // اگر از مودال رتبه‌بندی است، اطلاعات آن را هم اضافه کنیم
            if ($isFromRankModal) {
                $selectedCriteriaIds = array_keys(array_filter($this->selectedCriteria ?? [], fn($value) => $value === true));

                Log::info('💾 Saving rank settings filter', [
                    'selectedCriteriaIds' => $selectedCriteriaIds,
                    'selectedCriteriaIds_count' => count($selectedCriteriaIds),
                    'user_id' => Auth::id()
                ]);

                $configData['rank_settings'] = [
                    'selected_criteria' => $this->selectedCriteria ?? [],
                    'selected_criteria_ids' => $selectedCriteriaIds
                ];
            }

            // ایجاد فیلتر ذخیره شده
            $savedFilter = SavedFilter::create([
                'name' => trim($name),
                'description' => $description ? trim($description) : null,
                'filters_config' => $configData,
                'filter_type' => 'families_approval',
                'user_id' => Auth::id(),
                'organization_id' => auth()->user()->organization_id ?? null,
                'usage_count' => 0
            ]);

            Log::info('Filter saved successfully', [
                'filter_id' => $savedFilter->id,
                'name' => $name,
                'user_id' => Auth::id()
            ]);

            // ارسال پیام موفقیت به session برای نمایش در toast
            session()->flash('success', "فیلتر '{$name}' با موفقیت ذخیره شد");

        } catch (\Exception $e) {
            Log::error('Error saving filter', [
                'name' => $name,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            // ارسال پیام خطا به session برای نمایش در toast
            session()->flash('error', 'خطا در ذخیره فیلتر: ' . $e->getMessage());
        }
    }

    /**
     * بارگذاری فیلترهای ذخیره شده کاربر
     * @return array
     */
    public function loadSavedFilters()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return [];
            }

            // فیلترهای قابل دسترس برای کاربر
            $query = SavedFilter::where('filter_type', 'families_approval')
                ->where(function ($q) use ($user) {
                    // فیلترهای خود کاربر
                    $q->where('user_id', $user->id)
                      // یا فیلترهای سازمانی (اگر کاربر عضو سازمان باشد)
                      ->orWhere('organization_id', $user->organization_id);
                })
                ->orderBy('usage_count', 'desc')
                ->orderBy('name')
                ->get()
                ->map(function ($filter) {
                    return [
                        'id' => $filter->id,
                        'name' => $filter->name,
                        'description' => $filter->description,
                        'usage_count' => $filter->usage_count,
                        'created_at' => DateHelper::toJalali($filter->created_at, 'Y/m/d'),
                        'is_owner' => $filter->user_id === Auth::id()
                    ];
                });

            return $query->toArray();

        } catch (\Exception $e) {
            Log::error('Error loading saved filters', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return [];
        }
    }

    /**
     * بارگذاری و اعمال فیلتر ذخیره شده
     * @param int $filterId
     * @return void
     */
    public function loadFilter($filterId)
    {
        try {
            $savedFilter = SavedFilter::find($filterId);
            if (!$savedFilter) {
                $this->dispatch('notify', [
                    'message' => 'فیلتر مورد نظر یافت نشد',
                    'type' => 'error'
                ]);
                return;
            }

            // بررسی دسترسی
            $user = Auth::user();
            $hasAccess = ($savedFilter->user_id === $user->id) ||
                        ($savedFilter->organization_id === $user->organization_id);

            if (!$hasAccess) {
                $this->dispatch('notify', [
                    'message' => 'شما به این فیلتر دسترسی ندارید',
                    'type' => 'error'
                ]);
                return;
            }

            // بارگذاری داده‌های فیلتر
            $filterData = $savedFilter->filters_config;

            // اعمال فیلترهای مودال
            if (isset($filterData['filters']) && is_array($filterData['filters'])) {
                $this->tempFilters = $filterData['filters'];
                $this->activeFilters = $filterData['filters'];
            }

            // اعمال فیلترهای کامپوننت
            if (isset($filterData['component_filters'])) {
                $componentFilters = $filterData['component_filters'];
                $this->search = $componentFilters['search'] ?? '';
                $this->status = $componentFilters['status'] ?? '';
                $this->province_id = $componentFilters['province_id'] ?? null;
                $this->city_id = $componentFilters['city_id'] ?? null;
                $this->charity_id = $componentFilters['charity_id'] ?? null;
                $this->deprivation_rank = $componentFilters['deprivation_rank'] ?? '';
                $this->family_rank_range = $componentFilters['family_rank_range'] ?? '';
                $this->specific_criteria = $componentFilters['specific_criteria'] ?? '';
            }

            // اعمال تنظیمات رتبه‌بندی اگر در فیلتر ذخیره شده باشد
            if (isset($filterData['rank_settings'])) {
                $rankSettings = $filterData['rank_settings'];
                $this->selectedCriteria = $rankSettings['selected_criteria'] ?? [];

                Log::info('📋 Loaded rank settings from filter', [
                    'selected_criteria' => $this->selectedCriteria,
                    'selected_criteria_ids' => $rankSettings['selected_criteria_ids'] ?? [],
                    'user_id' => Auth::id()
                ]);
            }

            // اعمال تنظیمات سورت
            if (isset($filterData['sort'])) {
                $this->sortField = $filterData['sort']['field'] ?? 'created_at';
                $this->sortDirection = $filterData['sort']['direction'] ?? 'desc';
            }

            // اعمال تب مناسب
            if (isset($filterData['tab'])) {
                $this->setTab($filterData['tab']);
            }

            // افزایش شمارنده استفاده
            $savedFilter->increment('usage_count');
            $savedFilter->update(['last_used_at' => now()]);

            // بازنشانی صفحه و پاک کردن کش
            $this->resetPage();
            $this->clearFamiliesCache();

            Log::info('Filter loaded successfully', [
                'filter_id' => $filterId,
                'filter_name' => $savedFilter->name,
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => "فیلتر '{$savedFilter->name}' با موفقیت بارگذاری شد",
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading filter', [
                'filter_id' => $filterId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => 'خطا در بارگذاری فیلتر: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * حذف فیلتر ذخیره شده
     * @param int $filterId
     * @return void
     */
    public function deleteFilter($filterId)
    {
        try {
            $savedFilter = SavedFilter::find($filterId);
            if (!$savedFilter) {
                $this->dispatch('notify', [
                    'message' => 'فیلتر مورد نظر یافت نشد',
                    'type' => 'error'
                ]);
                return;
            }

            // فقط صاحب فیلتر می‌تواند آن را حذف کند
            if ($savedFilter->user_id !== Auth::id()) {
                $this->dispatch('notify', [
                    'message' => 'شما فقط می‌توانید فیلترهای خود را حذف کنید',
                    'type' => 'error'
                ]);
                return;
            }

            $filterName = $savedFilter->name;
            $savedFilter->delete();

            Log::info('Filter deleted successfully', [
                'filter_id' => $filterId,
                'filter_name' => $filterName,
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => "فیلتر '{$filterName}' با موفقیت حذف شد",
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting filter', [
                'filter_id' => $filterId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => 'خطا در حذف فیلتر: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }
}
