<?php

namespace App\Livewire\Charity;

use App\Models\Family;
use App\Models\Region;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Province;
use App\Models\City;
use App\Models\RankSetting;
use App\Models\FamilyCriterion;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Illuminate\Support\Facades\Cache;
use App\QueryFilters\RankingFilter;
use App\QuerySorts\RankingSort;
use App\Helpers\ProblemTypeHelper;

class FamilySearch extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $region = '';
    public $charity = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $expandedFamily = null;
    public $familyMembers = [];
    public $regions = [];
    public $provinces = [];
    public $cities = [];
    public $organizations = [];
    public $selectedHead = null;
    public $perPage = 15;
    public $province = '';
    public $city = '';

    // اضافه کردن property های مورد نیاز برای فیلترهای جغرافیایی
    public $province_id = null;
    public $city_id = null;
    public $district_id = null;
    public $region_id = null;
    public $organization_id = null;
    public $charity_id = null;

    public $deprivation_rank = '';
    public $family_rank_range = '';
    public $specific_criteria = '';
    public $availableRankSettings = [];
    public $page = 1; // متغیر مورد نیاز برای پیجینیشن لیوایر
    public $isEditingMode = false; // متغیر برای کنترل حالت ویرایش فرم

    // Properties for editing family members
    public $editingMemberId = null;
    public $editingMemberData = [
        'relationship' => '',
        'occupation' => '',
        'job_type' => '',
        'problem_type' => []
    ];

    // Properties for new Rank Settings Modal
    public $rankSettings = [];
    public $editingRankSettingId = null;
    public $editingRankSetting = [
        'name' => '',
        'weight' => 5,
        'description' => '',
        'requires_document' => true,
        'color' => '#60A5FA'
    ];
    public $isCreatingNew = false;

    // اضافه کردن پراپرتی‌های مورد نیاز
    public $rankingSchemes = [];
    public $availableCriteria = [];

    // پراپرتی‌های جدید سیستم رتبه‌بندی پویا
    public $selectedSchemeId = null;
    public array $schemeWeights = [];
    public $newSchemeName = '';
    public $newSchemeDescription = '';
    public $appliedSchemeId = null;

    // مدیریت فیلترهای پیشرفته
    public $tempFilters = [];
    public $activeFilters = [];

    /**
     * Reset all filters to their default values
     */
    public function resetToDefault()
    {
        try {
            $this->reset([
                'search',
                'status',
                'region',
                'charity',
                'province',
                'city',
                'province_id',
                'city_id',
                'district_id',
                'region_id',
                'organization_id',
                'charity_id',
                'deprivation_rank',
                'family_rank_range',
                'specific_criteria',
                'tempFilters',
                'activeFilters',
                'selectedSchemeId',
                'appliedSchemeId'
            ]);

            $this->resetPage();
            
            // Reset any custom filters if needed
            if (method_exists($this, 'resetFilters')) {
                $this->resetFilters();
            }
            
            // Dispatch event to update any client-side components
            $this->dispatch('filters-reset');
            
        } catch (\Exception $e) {
            Log::error('Error resetting filters: ' . $e->getMessage());
            $this->dispatch('error', 'خطا در بازنشانی فیلترها');
        }
    }

    /**
     * Clear all filters - alias for resetToDefault
     */
    public function clearAllFilters()
    {
        return $this->resetToDefault();
    }

    // New ranking properties
    public $showRankModal = false;
    public $rankFilters = [];

    // اضافه کردن متغیرهای فرم معیار جدید
    public $rankSettingName = '';
    public $rankSettingDescription = '';
    public $rankSettingWeight = 5;
    public $rankSettingColor = 'bg-green-100';
    public $rankSettingNeedsDoc = 1;

    // متغیرهای مورد نیاز برای مودال رتبه‌بندی جدید
    public $selectedCriteria = [];
    public $criteriaRequireDocument = [];

    protected $paginationTheme = 'tailwind';

    // Define Livewire event listeners to enable frontend component interactions
    protected $listeners = [
        'openRankModal',
        'closeRankModal',
        'applyCriteria',
        'editRankSetting',
        'saveRankSetting',
        'deleteRankSetting',
        'resetToDefaults',
        'applyAndClose',
        'copyText',
        'editMember',
        'saveMember',
        'cancelMemberEdit',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'region' => ['except' => ''],
        'charity' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'family_rank_range' => ['except' => ''],
        'specific_criteria' => ['except' => ''],
        'province' => ['except' => ''],
        'city' => ['except' => ''],
        'deprivation_rank' => ['except' => ''],
        'page' => ['except' => 1],
        'perPage' => ['except' => 15],
    ];

    public function mount()
    {
        $this->regions = cache()->remember('regions_list', 3600, function () {
            return Region::all();
        });
        $this->provinces = cache()->remember('provinces_list', 3600, function () {
            return Province::orderBy('name')->get();
        });
        $this->cities = cache()->remember('cities_list', 3600, function () {
            return City::orderBy('name')->get();
        });
        $this->organizations = cache()->remember('organizations_list', 3600, function () {
            return Organization::where('type', 'charity')->orderBy('name')->get();
        });

        // بارگذاری معیارهای رتبه‌بندی در ابتدای لود صفحه
        $this->loadRankSettings();

        // مقداردهی اولیه متغیرهای رتبه‌بندی
        $this->rankingSchemes = \App\Models\RankingScheme::orderBy('name')->get();
        $this->availableCriteria = \App\Models\RankSetting::where('is_active', true)->orderBy('sort_order')->get();

        // مقداردهی اولیه فیلترهای مودالی - حتماً آرایه خالی
        $this->tempFilters = [];
        $this->activeFilters = [];

        // مقداردهی اولیه فرم معیار جدید
        $this->resetRankSettingForm();

        // اگر session موفقیت آپلود وجود دارد، کش را پاک کن
        if (session('success') && session('results')) {
            $this->clearFamiliesCache();
            cache()->forget('families_query_' . Auth::id());
        }

        // تست ارسال نوتیفیکیشن
        $this->dispatch('notify', [
            'message' => 'صفحه جستجوی خانواده‌ها با موفقیت بارگذاری شد',
            'type' => 'success'
        ]);
    }

    /**
     * پاک کردن کش جستجوی خانواده‌ها
     */
    public function clearFamiliesCache()
    {
        try {
            // کش فعلی را پاک می‌کنیم
            cache()->forget($this->getCacheKey());

        } catch (\Exception $e) {
        }
    }
    public function render()
    {
        try {
            Log::debug('🎬 FamilySearch render started', [
                'search' => $this->search,
                'status' => $this->status,
                'page' => $this->page,
                'per_page' => $this->perPage
            ]);

            // استفاده از کش برای بهبود عملکرد
            $cacheKey = $this->getCacheKey();

            $families = Cache::remember($cacheKey, 300, function () { // 5 دقیقه کش
                $queryBuilder = $this->buildFamiliesQuery();

                // Dynamic Ranking Logic - اگر طرح رتبه‌بندی اعمال شده باشد
                if ($this->appliedSchemeId) {
                    $schemeCriteria = \App\Models\RankingSchemeCriterion::where('ranking_scheme_id', $this->appliedSchemeId)
                        ->pluck('weight', 'rank_setting_id');

                    if ($schemeCriteria->isNotEmpty()) {
                        $cases = [];
                        foreach ($schemeCriteria as $rank_setting_id => $weight) {
                            $cases[] = "CASE WHEN EXISTS (SELECT 1 FROM family_criteria fc WHERE fc.family_id = families.id AND fc.rank_setting_id = {$rank_setting_id} AND fc.has_criteria = true) THEN {$weight} ELSE 0 END";
                        }

                        $caseQuery = implode(' + ', $cases);

                        // تبدیل QueryBuilder به Eloquent برای selectRaw
                        $eloquentQuery = $queryBuilder->getEloquentBuilder();
                        $eloquentQuery->selectRaw("families.*, ({$caseQuery}) as calculated_score")
                                     ->orderBy('calculated_score', 'desc');

                        return $eloquentQuery->paginate($this->perPage);
                    }
                }

                // استفاده از QueryBuilder برای پیجینیشن معمولی
                return $queryBuilder->paginate($this->perPage);
            });

            // نمایش تعداد خانواده‌های فیلتر شده
            if ($this->hasActiveFilters() && request()->has(['status', 'province', 'city', 'deprivation_rank', 'family_rank_range', 'specific_criteria', 'charity', 'region'])) {
                $totalCount = $families->total();
                $activeFiltersCount = $this->getActiveFiltersCount();
                $this->dispatch('notify', [
                    'message' => "نمایش {$totalCount} خانواده براساس {$activeFiltersCount} فیلتر فعال",
                    'type' => 'info'
                ]);
            }

            // بارگذاری اعضای خانواده برای نمایش جزئیات
            if ($this->expandedFamily) {
                $this->familyMembers = Member::where('family_id', $this->expandedFamily)
                    ->orderBy('is_head', 'desc')
                    ->orderBy('created_at')
                    ->get();
            }

            Log::info('✅ FamilySearch render completed successfully', [
                'families_count' => $families->count(),
                'total_families' => $families->total(),
                'cache_key' => $cacheKey
            ]);

            return view('livewire.charity.family-search', [
                'families' => $families,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error in FamilySearch render', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'search' => $this->search,
                'status' => $this->status,
                'user_id' => Auth::id()
            ]);

            // بازگشت به نمایش خالی در صورت خطا
            return view('livewire.charity.family-search', [
                'families' => collect()->paginate($this->perPage),
            ]);
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
        // پاک کردن کش هنگام تغییر فیلترها
        $this->clearFamiliesCache();
    }

    public function updatingStatus()
    {
        $this->resetPage();
        // پاک کردن کش هنگام تغییر فیلترها
        $this->clearFamiliesCache();
    }

    public function updatingRegion()
    {
        $this->resetPage();
    }

    public function updatingCharity()
    {
        $this->resetPage();
    }

    public function updatingProvince()
    {
        $this->resetPage();
    }

    public function updatingCity()
    {
        $this->resetPage();
    }

    public function updatingDeprivationRank()
    {
        $this->resetPage();
    }

    public function updatingFamilyRankRange()
    {
        $this->resetPage();
    }

    public function updatingSpecificCriteria()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
        $this->clearCache();
    }

    /**
     * رفتن به صفحه بعدی
     * @return void
     */
    public function nextPage()
    {
        $this->setPage($this->page + 1);
        $this->clearCache();
    }

    /**
     * رفتن به صفحه قبلی
     * @return void
     */
    public function previousPage()
    {
        $this->setPage(max(1, $this->page - 1));
        $this->clearCache();
    }

    /**
     * رفتن به صفحه مشخص
     * @param int $page
     * @return void
     */
    public function gotoPage($page)
    {
        $this->setPage($page);
        $this->clearCache();
    }

    /**
     * ساخت کوئری خانواده‌ها با استفاده از QueryBuilder
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    protected function buildFamiliesQuery()
    {
        try {
            Log::debug('🏗️ Building FamilySearch QueryBuilder', [
                'search' => $this->search,
                'status' => $this->status,
                'has_active_filters' => $this->hasActiveFilters()
            ]);

            // ساخت base query با relations مورد نیاز
            $baseQuery = Family::query()
                ->with([
                    'province',
                    'city',
                    'district',
                    'region',
                    'organization',
                    'charity',
                    'members' => fn($q) => $q->orderBy('is_head', 'desc'),
                    'familyCriteria.rankSetting',
                    'insurances' => fn($q) => $q->orderBy('created_at', 'desc'),
                    'finalInsurances' => fn($q) => $q->where('status', 'insured')->orderBy('created_at', 'desc'),
                    'finalInsurances.fundingSource' => fn($q) => $q->where('is_active', true)
                ])
                ->withCount('members');

            // فیلترهای مجاز برای QueryBuilder
            $allowedFilters = [
                AllowedFilter::exact('family_code'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('province_id'),
                AllowedFilter::exact('city_id'),
                AllowedFilter::exact('district_id'),
                AllowedFilter::exact('region_id'),
                AllowedFilter::exact('organization_id'),
                AllowedFilter::exact('charity_id'),
                AllowedFilter::exact('wizard_status'),
                AllowedFilter::exact('is_insured'),
                // فیلتر سفارشی رتبه‌بندی و وزن‌دهی
                AllowedFilter::custom('ranking', new RankingFilter()),
                AllowedFilter::exact('ranking_scheme'),
                AllowedFilter::exact('ranking_weights'),
                // فیلتر برای جستجوی نام سرپرست
                AllowedFilter::callback('head_name', function ($query, $value) {
                    $query->whereHas('head', function ($q) use ($value) {
                        $q->where('first_name', 'like', "%{$value}%")
                          ->orWhere('last_name', 'like', "%{$value}%");
                    });
                }),
                // فیلتر تعداد اعضا
                AllowedFilter::callback('members_count', function ($query, $value) {
                    if (str_contains($value, '-')) {
                        [$min, $max] = explode('-', $value);
                        $query->havingRaw('members_count BETWEEN ? AND ?', [$min, $max]);
                    } elseif (is_numeric($value)) {
                        $query->havingRaw('members_count = ?', [$value]);
                    }
                }),
                // فیلتر رتبه محاسبه شده
                AllowedFilter::callback('calculated_rank_range', function ($query, $value) {
                    if (str_contains($value, '-')) {
                        [$min, $max] = explode('-', $value);
                        $query->whereBetween('calculated_rank', [$min, $max]);
                    } elseif (is_numeric($value)) {
                        $query->where('calculated_rank', '>=', $value);
                    }
                }),
                // فیلتر محدوده تاریخ عضویت
                AllowedFilter::callback('created_from', function ($query, $value) {
                    $query->where('families.created_at', '>=', $value);
                }),
                AllowedFilter::callback('created_to', function ($query, $value) {
                    $query->where('families.created_at', '<=', $value);
                }),
            ];

            // سورت‌های مجاز
            $allowedSorts = [
                AllowedSort::field('created_at', 'families.created_at'),
                AllowedSort::field('updated_at', 'families.updated_at'),
                AllowedSort::field('family_code', 'families.family_code'),
                AllowedSort::field('status', 'families.status'),
                AllowedSort::field('wizard_status', 'families.wizard_status'),
                AllowedSort::field('members_count', 'members_count'),
                AllowedSort::field('calculated_rank', 'families.calculated_rank'),
                // سورت سفارشی رتبه‌بندی وزن‌دار
                AllowedSort::custom('weighted_rank', new RankingSort()),
                // سورت بر اساس نام سرپرست خانوار
                AllowedSort::callback('head_name', function ($query, $descending) {
                    $direction = $descending ? 'desc' : 'asc';
                    $query->leftJoin('people as head_person', 'families.head_id', '=', 'head_person.id')
                          ->orderBy('head_person.first_name', $direction)
                          ->orderBy('head_person.last_name', $direction);
                }),
            ];

            // ساخت QueryBuilder
            $queryBuilder = QueryBuilder::for($baseQuery)
                ->allowedFilters($allowedFilters)
                ->allowedSorts($allowedSorts)
                ->defaultSort('families.created_at');

            // اعمال فیلترهای کامپوننت
            $this->applyComponentFilters($queryBuilder);

            // اعمال فیلترهای مودال
            $queryBuilder = $this->convertModalFiltersToQueryBuilder($queryBuilder);

            Log::info('🔍 FamilySearch QueryBuilder initialized successfully', [
                'search' => $this->search,
                'status' => $this->status,
                'has_modal_filters' => !empty($this->activeFilters ?? $this->tempFilters ?? $this->filters ?? []),
                'filters_count' => count(request()->query())
            ]);

            return $queryBuilder;

        } catch (\Exception $e) {
            Log::error('❌ Error in FamilySearch buildFamiliesQuery', [
                'search' => $this->search,
                'status' => $this->status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            // بازگشت به query ساده در صورت خطا
            return Family::query()
                ->with([
                    'province', 'city', 'district', 'region', 'organization', 'charity',
                    'members' => fn($q) => $q->orderBy('is_head', 'desc')
                ])
                ->withCount('members')
                ->orderBy('families.created_at', 'desc');
        }
    }

    /**
     * اعمال فیلترهای کامپوننت به QueryBuilder
     * @param \Spatie\QueryBuilder\QueryBuilder $queryBuilder
     * @return void
     */
    protected function applyComponentFilters($queryBuilder)
    {
        try {
            Log::debug('🎛️ Applying FamilySearch component filters', [
                'search' => $this->search,
                'status' => $this->status,
                'province' => $this->province,
                'city' => $this->city
            ]);

            // فیلتر جستجوی عمومی - جستجو در تمام فیلدهای خانواده و اعضا
            if (!empty($this->search)) {
                $queryBuilder->where(function ($query) {
                    $query->where('family_code', 'like', '%' . $this->search . '%')
                          ->orWhere('address', 'like', '%' . $this->search . '%')
                          ->orWhere('additional_info', 'like', '%' . $this->search . '%')
                          ->orWhereHas('members', function ($memberQuery) {
                              $memberQuery->where(function($q) {
                                  $q->whereRaw("CONCAT(first_name, ' ', last_name) like ?", ['%' . $this->search . '%'])
                                    ->orWhereRaw("CONCAT(last_name, ' ', first_name) like ?", ['%' . $this->search . '%'])
                                    ->orWhere('first_name', 'like', '%' . $this->search . '%')
                                    ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                    ->orWhere('national_code', 'like', '%' . $this->search . '%')
                                    ->orWhere('mobile', 'like', '%' . $this->search . '%')
                                    ->orWhere('phone', 'like', '%' . $this->search . '%')
                                    ->orWhere('relationship', 'like', '%' . $this->search . '%')
                                    ->orWhere('occupation', 'like', '%' . $this->search . '%')
                                    ->orWhere('job_type', 'like', '%' . $this->search . '%')
                                    ->orWhereRaw("JSON_SEARCH(problem_type, 'one', ?) IS NOT NULL", ["%{$this->search}%"]);
                              });
                          })
                          ->orWhereHas('province', function ($provinceQuery) {
                              $provinceQuery->where('name', 'like', '%' . $this->search . '%');
                          })
                          ->orWhereHas('city', function ($cityQuery) {
                              $cityQuery->where('name', 'like', '%' . $this->search . '%');
                          })
                          ->orWhereHas('district', function ($districtQuery) {
                              $districtQuery->where('name', 'like', '%' . $this->search . '%');
                          })
                          ->orWhereHas('region', function ($regionQuery) {
                              $regionQuery->where('name', 'like', '%' . $this->search . '%');
                          })
                          ->orWhereHas('organization', function ($orgQuery) {
                              $orgQuery->where('name', 'like', '%' . $this->search . '%');
                          })
                          ->orWhereHas('charity', function ($charityQuery) {
                              $charityQuery->where('name', 'like', '%' . $this->search . '%');
                          });
                });
                Log::debug('✅ Enhanced search filter applied', ['search' => $this->search]);
            }

            // فیلتر وضعیت
            if (!empty($this->status)) {
                if ($this->status === 'insured') {
                    $queryBuilder->where(function($q) {
                        $q->where('is_insured', true)
                          ->orWhere('status', 'insured');
                    });
                } elseif ($this->status === 'uninsured') {
                    $queryBuilder->where('is_insured', false)
                                 ->where('status', '!=', 'insured');
                } elseif ($this->status === 'special_disease') {
                    $queryBuilder->whereHas('members', function($q) {
                        // جستجو با تمام مقادیر ممکن (فارسی و انگلیسی)
                        $q->whereJsonContains('problem_type', 'بیماری های خاص')
                          ->orWhereJsonContains('problem_type', 'بیماری خاص')
                          ->orWhereJsonContains('problem_type', 'special_disease')
                          ->orWhereJsonContains('problem_type', 'addiction')
                          ->orWhereJsonContains('problem_type', 'اعتیاد')
                          ->orWhereJsonContains('problem_type', 'work_disability')
                          ->orWhereJsonContains('problem_type', 'از کار افتادگی')
                          ->orWhereJsonContains('problem_type', 'unemployment')
                          ->orWhereJsonContains('problem_type', 'بیکاری');
                    });
                } else {
                    $queryBuilder->where('status', $this->status);
                }
                Log::debug('✅ Status filter applied', ['status' => $this->status]);
            }

            // فیلتر استان
            if (!empty($this->province)) {
                $queryBuilder->where('province_id', $this->province);
                Log::debug('✅ Province filter applied', ['province' => $this->province]);
            }

            // فیلتر شهر
            if (!empty($this->city)) {
                $queryBuilder->where('city_id', $this->city);
                Log::debug('✅ City filter applied', ['city' => $this->city]);
            }

            // فیلتر رتبه محرومیت استان
            if (!empty($this->deprivation_rank)) {
                $queryBuilder->whereHas('province', function ($q) {
                    switch ($this->deprivation_rank) {
                        case 'high':
                            $q->where('deprivation_rank', '<=', 3);
                            break;
                        case 'medium':
                            $q->whereBetween('deprivation_rank', [4, 6]);
                            break;
                        case 'low':
                            $q->where('deprivation_rank', '>=', 7);
                            break;
                    }
                });
                Log::debug('✅ Deprivation rank filter applied', ['deprivation_rank' => $this->deprivation_rank]);
            }

            // فیلتر بازه رتبه محرومیت خانواده
            if (!empty($this->family_rank_range)) {
                switch ($this->family_rank_range) {
                    case 'very_high':
                        $queryBuilder->where('calculated_rank', '>=', 80)
                                     ->where('calculated_rank', '<=', 100);
                        break;
                    case 'high':
                        $queryBuilder->where('calculated_rank', '>=', 60)
                                     ->where('calculated_rank', '<', 80);
                        break;
                    case 'medium':
                        $queryBuilder->where('calculated_rank', '>=', 40)
                                     ->where('calculated_rank', '<', 60);
                        break;
                    case 'low':
                        $queryBuilder->where('calculated_rank', '>=', 20)
                                     ->where('calculated_rank', '<', 40);
                        break;
                    case 'very_low':
                        $queryBuilder->where('calculated_rank', '>=', 0)
                                     ->where('calculated_rank', '<', 20);
                        break;
                }
                Log::debug('✅ Family rank range filter applied', ['family_rank_range' => $this->family_rank_range]);
            }

            // فیلتر معیار خاص
            if (!empty($this->specific_criteria)) {
                $rankSetting = RankSetting::find($this->specific_criteria);
                if ($rankSetting) {
                    $queryBuilder->where(function($q) use ($rankSetting) {
                        // تبدیل نام معیار به فارسی و انگلیسی برای جستجو
                        $persianName = ProblemTypeHelper::englishToPersian($rankSetting->name);
                        $englishName = ProblemTypeHelper::persianToEnglish($rankSetting->name);
                        
                        $q->whereJsonContains('acceptance_criteria', $persianName)
                          ->orWhereJsonContains('acceptance_criteria', $rankSetting->name)
                          ->orWhereJsonContains('acceptance_criteria', $englishName)
                          ->orWhereHas('members', function($memberQuery) use ($persianName, $rankSetting, $englishName) {
                              $memberQuery->whereJsonContains('problem_type', $persianName)
                                        ->orWhereJsonContains('problem_type', $rankSetting->name)
                                        ->orWhereJsonContains('problem_type', $englishName);
                          })
                          ->orWhereHas('familyCriteria', function ($subQ) use ($rankSetting) {
                              $subQ->where('rank_setting_id', $rankSetting->id)
                                   ->where('has_criteria', true);
                          });
                    });
                    Log::debug('✅ Specific criteria filter applied', ['criteria_id' => $this->specific_criteria]);
                }
            }

            // فیلتر خیریه معرف
            if (!empty($this->charity)) {
                $queryBuilder->where('charity_id', $this->charity);
                Log::debug('✅ Charity filter applied', ['charity' => $this->charity]);
            }

            // اعمال سورت
            if (!empty($this->sortField) && !empty($this->sortDirection)) {
                $validSorts = ['created_at', 'updated_at', 'family_code', 'status', 'wizard_status', 'members_count', 'head_name'];
                if (in_array($this->sortField, $validSorts)) {
                    $direction = in_array($this->sortDirection, ['asc', 'desc']) ? $this->sortDirection : 'desc';

                    if ($this->sortField === 'head_name') {
                        // سورت خاص برای نام سرپرست
                        $queryBuilder->leftJoin('people as head_person', 'families.head_id', '=', 'head_person.id')
                                     ->orderBy('head_person.first_name', $direction)
                                     ->orderBy('head_person.last_name', $direction);
                    } else {
                        $fieldName = $this->sortField === 'members_count' ? 'members_count' : 'families.' . $this->sortField;
                        $queryBuilder->orderBy($fieldName, $direction);
                    }

                    Log::debug('🔧 Component sort applied', [
                        'sort_field' => $this->sortField,
                        'sort_direction' => $direction
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('❌ Error applying FamilySearch component filters', [
                'search' => $this->search,
                'status' => $this->status,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
        }
    }

    /**
     * تبدیل فیلترهای مودال به QueryBuilder constraints با پشتیبانی از عملگرهای AND/OR
     * @param \Spatie\QueryBuilder\QueryBuilder $queryBuilder
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    protected function convertModalFiltersToQueryBuilder($queryBuilder)
    {
        try {
            // استفاده از activeFilters که توسط متد applyFilters قدیمی پر شده
            $modalFilters = $this->activeFilters ?? $this->tempFilters ?? $this->filters ?? [];

            if (empty($modalFilters)) {
                return $queryBuilder;
            }

            Log::debug('🎯 Converting FamilySearch modal filters to QueryBuilder with AND/OR logic', [
                'filters_count' => count($modalFilters),
                'user_id' => Auth::id()
            ]);

            // جداسازی فیلترها بر اساس عملگر منطقی
            $andFilters = [];
            $orFilters = [];

            foreach ($modalFilters as $filter) {
                if (empty($filter['type']) || empty($filter['value'])) {
                    continue;
                }

                $logicalOperator = $filter['logical_operator'] ?? 'and';

                if ($logicalOperator === 'or') {
                    $orFilters[] = $filter;
                } else {
                    $andFilters[] = $filter;
                }
            }

            // اعمال فیلترهای AND
            foreach ($andFilters as $filter) {
                $queryBuilder = $this->applySingleFilter($queryBuilder, $filter, 'and');
            }

            // اعمال فیلترهای OR در یک گروه
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

            Log::info('✅ FamilySearch modal filters applied successfully', [
                'and_filters_count' => count($andFilters),
                'or_filters_count' => count($orFilters),
                'user_id' => Auth::id()
            ]);

            return $queryBuilder;

        } catch (\Exception $e) {
            Log::error('❌ Error applying FamilySearch modal filters', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return $queryBuilder;
        }
    }

    /**
     * اعمال یک فیلتر منفرد
     * @param \Spatie\QueryBuilder\QueryBuilder $queryBuilder
     * @param array $filter
     * @param string $method
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    protected function applySingleFilter($queryBuilder, $filter, $method = 'and')
    {
        try {
            $filterType = $filter['type'];
            $filterValue = $filter['value'];
            $operator = $filter['operator'] ?? 'equals';

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
                            $queryBuilder = $queryBuilder->orWhere('families.calculated_rank', '>=', $filter['min']);
                        } else {
                            $queryBuilder = $queryBuilder->where('families.calculated_rank', '>=', $filter['min']);
                        }
                    }
                    if (!empty($filter['max'])) {
                        if ($method === 'or') {
                            $queryBuilder = $queryBuilder->orWhere('families.calculated_rank', '<=', $filter['max']);
                        } else {
                            $queryBuilder = $queryBuilder->where('families.calculated_rank', '<=', $filter['max']);
                        }
                    }
                    break;
            }

            return $queryBuilder;

        } catch (\Exception $e) {
            Log::error('❌ Error applying single filter in FamilySearch', [
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
     * @param \Spatie\QueryBuilder\QueryBuilder $queryBuilder
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @param string $method
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    protected function applyNumericFilter($queryBuilder, $field, $operator, $value, $method = 'and')
    {
        $whereMethod = $method === 'or' ? 'orWhere' : 'where';
        $whereBetweenMethod = $method === 'or' ? 'orWhereBetween' : 'whereBetween';

        switch ($operator) {
            case 'equals':
                return $queryBuilder->$whereMethod($field, '=', $value);
            case 'not_equals':
                return $queryBuilder->$whereMethod($field, '!=', $value);
            case 'greater_than':
                return $queryBuilder->$whereMethod($field, '>', $value);
            case 'less_than':
                return $queryBuilder->$whereMethod($field, '<', $value);
            case 'greater_than_or_equal':
                return $queryBuilder->$whereMethod($field, '>=', $value);
            case 'less_than_or_equal':
                return $queryBuilder->$whereMethod($field, '<=', $value);
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    return $queryBuilder->$whereBetweenMethod($field, $value);
                }
                break;
            default:
                return $queryBuilder->$whereMethod($field, $value);
        }

        return $queryBuilder;
    }

    /**
     * اعمال فیلتر تاریخ
     * @param \Spatie\QueryBuilder\QueryBuilder $queryBuilder
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @param string $method
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    protected function applyDateFilter($queryBuilder, $field, $operator, $value, $method = 'and')
    {
        $whereMethod = $method === 'or' ? 'orWhereDate' : 'whereDate';
        $whereBetweenMethod = $method === 'or' ? 'orWhereBetween' : 'whereBetween';

        switch ($operator) {
            case 'equals':
                return $queryBuilder->$whereMethod($field, '=', $value);
            case 'after':
            case 'greater_than':
                return $queryBuilder->$whereMethod($field, '>', $value);
            case 'before':
            case 'less_than':
                return $queryBuilder->$whereMethod($field, '<', $value);
            case 'after_or_equal':
                return $queryBuilder->$whereMethod($field, '>=', $value);
            case 'before_or_equal':
                return $queryBuilder->$whereMethod($field, '<=', $value);
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    return $queryBuilder->$whereBetweenMethod($field, $value);
                }
                break;
            default:
                return $queryBuilder->$whereMethod($field, $value);
        }

        return $queryBuilder;
    }

    /**
     * اعمال فیلترهای مودال
     * @return void
     */
    public function applyFilters()
    {
        try {
            Log::debug('🎯 FamilySearch applyFilters called', [
                'temp_filters' => $this->tempFilters,
                'active_filters' => $this->activeFilters ?? []
            ]);

            // کپی فیلترهای موقت به فیلترهای فعال
            $this->activeFilters = $this->tempFilters;

            // همگام‌سازی با فیلترهای اصلی برای سازگاری با کدهای قدیمی
            $this->filters = $this->tempFilters;

            // بازنشانی صفحه به ۱
            $this->resetPage();

            // پاک کردن کش
            $this->clearCache();

            $filterCount = count($this->activeFilters ?? []);

            if ($filterCount > 0) {
                Log::info('✅ FamilySearch filters applied successfully', [
                    'filters_count' => $filterCount,
                    'has_modal_filters' => true
                ]);

                session()->flash('message', "فیلترها با موفقیت اعمال شدند ({$filterCount} فیلتر فعال)");
                session()->flash('type', 'success');
            } else {
                Log::info('⚠️ FamilySearch no filters to apply');
                session()->flash('message', 'هیچ فیلتری برای اعمال وجود ندارد');
                session()->flash('type', 'warning');
            }

        } catch (\Exception $e) {
            Log::error('❌ Error applying FamilySearch filters', [
                'error' => $e->getMessage(),
                'temp_filters' => $this->tempFilters ?? [],
                'user_id' => Auth::id()
            ]);

            session()->flash('message', 'خطا در اعمال فیلترها: ' . $e->getMessage());
            session()->flash('type', 'error');
        }
    }

    /**
     * تست فیلترهای مودال
     * @return void
     */
    public function testFilters()
    {
        try {
            Log::debug('🧪 FamilySearch testFilters called', [
                'temp_filters' => $this->tempFilters
            ]);

            // شبیه‌سازی اعمال فیلترها برای تست
            $testFilters = $this->tempFilters;

            if (empty($testFilters)) {
                session()->flash('message', 'هیچ فیلتری برای تست وجود ندارد');
                session()->flash('type', 'warning');
                return;
            }

            // ایجاد کوئری تست
            $queryBuilder = $this->buildFamiliesQuery();

            // شبیه‌سازی اعمال فیلترهای مودال
            $originalActiveFilters = $this->activeFilters;
            $this->activeFilters = $testFilters;

            $queryBuilder = $this->convertModalFiltersToQueryBuilder($queryBuilder);
            $testCount = $queryBuilder->count();

            // بازگردانی فیلترهای اصلی
            $this->activeFilters = $originalActiveFilters;

            Log::info('✅ FamilySearch filters test completed', [
                'test_count' => $testCount,
                'filters_count' => count($testFilters)
            ]);

            session()->flash('message', "تست فیلترها: {$testCount} خانواده یافت شد");
            session()->flash('type', 'info');

        } catch (\Exception $e) {
            Log::error('❌ Error testing FamilySearch filters', [
                'error' => $e->getMessage(),
                'temp_filters' => $this->tempFilters ?? [],
                'user_id' => Auth::id()
            ]);

            session()->flash('message', 'خطا در تست فیلترها: ' . $e->getMessage());
            session()->flash('type', 'error');
        }
    }

    /**
     * بازنشانی فیلترها به حالت پیشفرض
     * @return void
     */
    public function resetFilters()
    {
        try {
            Log::debug('🔄 FamilySearch resetFilters called');

            // پاک کردن تمام فیلترها
            $this->tempFilters = [];
            $this->activeFilters = [];
            $this->filters = [];

            // پاک کردن فیلترهای کامپوننت
            $this->search = '';
            $this->status = '';
            $this->province = '';
            $this->city = '';
            $this->deprivation_rank = '';
            $this->family_rank_range = '';
            $this->specific_criteria = '';
            $this->charity = '';

            // بازنشانی سورت
            $this->sortField = 'created_at';
            $this->sortDirection = 'desc';

            // بازنشانی صفحه
            $this->resetPage();

            // پاک کردن کش
            $this->clearCache();

            Log::info('✅ FamilySearch filters reset successfully');

            session()->flash('message', 'فیلترها با موفقیت بازنشانی شدند');
            session()->flash('type', 'success');

        } catch (\Exception $e) {
            Log::error('❌ Error resetting FamilySearch filters', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            session()->flash('message', 'خطا در بازنشانی فیلترها: ' . $e->getMessage());
            session()->flash('type', 'error');
        }
    }

    /**
     * بررسی وجود فیلترهای فعال
     * @return bool
     */
    public function hasActiveFilters(): bool
    {
        return !empty($this->search) ||
               !empty($this->status) ||
               !empty($this->province) ||
               !empty($this->city) ||
               !empty($this->deprivation_rank) ||
               !empty($this->family_rank_range) ||
               !empty($this->specific_criteria) ||
               !empty($this->charity) ||
               !empty($this->activeFilters);
    }

    /**
     * شمارش فیلترهای فعال
     * @return int
     */
    public function getActiveFiltersCount(): int
    {
        $count = 0;

        if (!empty($this->search)) $count++;
        if (!empty($this->status)) $count++;
        if (!empty($this->province)) $count++;
        if (!empty($this->city)) $count++;
        if (!empty($this->deprivation_rank)) $count++;
        if (!empty($this->family_rank_range)) $count++;
        if (!empty($this->specific_criteria)) $count++;
        if (!empty($this->charity)) $count++;
        if (!empty($this->activeFilters)) $count += count($this->activeFilters);

        return $count;
    }

    /**
     * تولید کلید کش
     * @return string
     */
    protected function getCacheKey(): string
    {
        $filterData = [
            'search' => $this->search,
            'status' => $this->status,
            'province' => $this->province,
            'city' => $this->city,
            'deprivation_rank' => $this->deprivation_rank,
            'family_rank_range' => $this->family_rank_range,
            'specific_criteria' => $this->specific_criteria,
            'charity' => $this->charity,
            'active_filters' => $this->activeFilters ?? [],
            'sort_field' => $this->sortField,
            'sort_direction' => $this->sortDirection,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'applied_scheme_id' => $this->appliedSchemeId
        ];

        return 'family_search_' . md5(json_encode($filterData)) . '_' . Auth::id();
    }

    /**
     * پاک کردن کش
     * @return void
     */
    protected function clearCache(): void
    {
        try {
            // پاک کردن کش‌های مرتبط با این کاربر
            $pattern = 'family_search_*_' . Auth::id();

            // Laravel Cache doesn't support pattern deletion directly,
            // so we'll just forget the current cache key
            $currentKey = $this->getCacheKey();
            Cache::forget($currentKey);

            Log::debug('🧹 FamilySearch cache cleared', ['cache_key' => $currentKey]);

        } catch (\Exception $e) {
            Log::warning('⚠️ Error clearing FamilySearch cache', [
                'error' => $e->getMessage()
            ]);
        }
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

            // بارگذاری کامل اعضای خانواده با تمام اطلاعات و مرتب‌سازی مناسب
            $family = Family::with(['members' => function($query) {
                // مرتب‌سازی: ابتدا سرپرست و سپس به ترتیب ID
                $query->orderBy('is_head', 'desc')
                      ->orderBy('id', 'asc');
            }])->findOrFail($familyId);

            // تهیه کالکشن کامل اعضای خانواده
            $this->familyMembers = $family->members;

            // تنظیم selectedHead به ID سرپرست فعلی
            foreach ($this->familyMembers as $member) {
                if ($member->is_head) {
                    $this->selectedHead = $member->id;
                    break;
                }
            }

            // ارسال رویداد برای اسکرول به موقعیت خانواده باز شده
            $this->dispatch('family-expanded', $familyId);
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
        try {
            $family = Family::findOrFail($familyId);

            // فقط اگر خانواده تایید نشده باشد، اجازه تغییر سرپرست را بدهیم
            if ($family->verified_at) {
                $this->dispatch('show-toast', [
                    'message' => '❌ امکان تغییر سرپرست برای خانواده‌های تایید شده وجود ندارد',
                    'type' => 'error'
                ]);
                return;
            }

            // بررسی اینکه عضو انتخاب شده متعلق به همین خانواده است
            $member = Member::where('id', $memberId)->where('family_id', $familyId)->first();
            if (!$member) {
                $this->dispatch('show-toast', [
                    'message' => '❌ عضو انتخاب شده در این خانواده یافت نشد',
                    'type' => 'error'
                ]);
                return;
            }

                // تنظیم متغیر انتخاب شده
                $this->selectedHead = $memberId;

                // مدیریت تراکنش برای اطمینان از صحت داده‌ها
                DB::beginTransaction();

            // به‌روزرسانی پایگاه داده - فقط یک نفر سرپرست
                Member::where('family_id', $familyId)->update(['is_head' => false]);
                Member::where('id', $memberId)->update(['is_head' => true]);

                DB::commit();

                // به‌روزرسانی نمایش بدون بارگیری مجدد کامل
                if ($this->expandedFamily === $familyId && !empty($this->familyMembers)) {
                    // به‌روزرسانی state داخلی بدون بارگیری مجدد
                foreach ($this->familyMembers as $familyMember) {
                        // فقط وضعیت is_head را تغییر می‌دهیم
                    $familyMember->is_head = ($familyMember->id == $memberId);
                    }
                }

                // نمایش پیام موفقیت
                $this->dispatch('show-toast', [
                'message' => '✅ سرپرست خانواده با موفقیت تغییر یافت',
                    'type' => 'success'
                ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('show-toast', [
                'message' => '❌ خطا در به‌روزرسانی اطلاعات: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    public function verifyFamily($familyId)
    {
        // بررسی دسترسی کاربر
        if (!Auth::check() || !Gate::allows('verify-family')) {
            $this->dispatch('show-toast', [
                'message' => '🚫 شما اجازه تایید خانواده را ندارید',
                'type' => 'error'
            ]);
            return;
        }

        $family = Family::findOrFail($familyId);

        // اگر قبلاً تایید شده، اطلاع بدهیم
        if ($family->verified_at) {
            $this->dispatch('show-toast', [
                'message' => '⚠️ این خانواده قبلاً تایید شده است',
                'type' => 'warning'
            ]);
            return;
        }

        // بررسی اینکه یک سرپرست انتخاب شده باشد
        $headsCount = Member::where('family_id', $familyId)->where('is_head', true)->count();

        if ($headsCount === 0) {
            $this->dispatch('show-toast', [
                'message' => '❌ لطفاً قبل از تایید، یک سرپرست برای خانواده انتخاب کنید',
                'type' => 'error'
            ]);
            return;
        }

        if ($headsCount > 1) {
            $this->dispatch('show-toast', [
                'message' => '⚠️ خطا: بیش از یک سرپرست انتخاب شده است. لطفاً فقط یک نفر را انتخاب کنید',
                'type' => 'error'
            ]);
            // اصلاح خودکار - فقط اولین سرپرست را نگه می‌داریم
            $firstHead = Member::where('family_id', $familyId)->where('is_head', true)->first();
            Member::where('family_id', $familyId)->update(['is_head' => false]);
            $firstHead->update(['is_head' => true]);
            return;
        }

        // بررسی حداقل یک عضو در خانواده
        $membersCount = Member::where('family_id', $familyId)->count();
        if ($membersCount === 0) {
            $this->dispatch('show-toast', [
                'message' => '❌ این خانواده هیچ عضوی ندارد و قابل تایید نیست',
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
            'message' => '✅ خانواده با موفقیت تایید شد و آماده ارسال به بیمه می‌باشد',
            'type' => 'success'
        ]);
    }

    public function copyText($text)
    {
        $this->dispatch('copy-text', $text);
        $this->dispatch('show-toast', [
            'message' => '📋 متن با موفقیت کپی شد: ' . $text,
            'type' => 'success'
        ]);
    }



    /**
     * بازگشت به تنظیمات پیشفرض
     */
    public function resetToDefaultSettings()
    {
        // پاک کردن معیارهای انتخاب شده
        $this->selectedCriteria = [];
        $this->criteriaRequireDocument = [];

        // مقداردهی مجدد با مقادیر پیشفرض
        foreach ($this->availableCriteria as $criterion) {
            $this->selectedCriteria[$criterion->id] = false;
            $this->criteriaRequireDocument[$criterion->id] = true;
        }

        $this->dispatch('notify', ['message' => 'تنظیمات به حالت پیشفرض بازگشت.', 'type' => 'info']);
    }

    //======================================================================
    //== متدهای سیستم رتبه‌بندی پویا
    //======================================================================

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

        $this->dispatch('notify', ['message' => 'الگو با موفقیت ذخیره شد.', 'type' => 'success']);
    }

    /**
     * الگوی انتخاب‌شده را برای فیلتر کردن و مرتب‌سازی اعمال می‌کند.
     */
    public function applyRankingScheme()
    {
        if (!$this->selectedSchemeId) {
             $this->dispatch('notify', ['message' => 'لطفا ابتدا یک الگو را انتخاب یا ذخیره کنید.', 'type' => 'error']);
             return;
        }
        $this->appliedSchemeId = $this->selectedSchemeId;
        $this->sortBy('calculated_score');
        $this->resetPage();
        $this->showRankModal = false;

        // دریافت نام الگوی انتخاب شده برای نمایش در پیام
        $schemeName = \App\Models\RankingScheme::find($this->selectedSchemeId)->name ?? '';
        $this->dispatch('notify', [
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
        $this->dispatch('notify', ['message' => 'فیلتر رتبه‌بندی حذف شد.', 'type' => 'info']);
    }
    public function applyAndClose()
    {
        try {
            // اطمینان از ذخیره همه تغییرات
            $this->loadRankSettings();

            // بروزرسانی لیست معیارهای در دسترس
            $this->availableRankSettings = \App\Models\RankSetting::active()->ordered()->get();

            // اعمال تغییرات به خانواده‌ها
            if ($this->appliedSchemeId) {
                // اگر یک طرح رتبه‌بندی انتخاب شده باشد، دوباره آن را اعمال می‌کنیم
                $this->applyRankingScheme();

                $this->sortBy('calculated_score');
            }

            // بستن مودال و نمایش پیام
            $this->showRankModal = false;
            $this->dispatch('notify', [
                'message' => 'تغییرات با موفقیت اعمال شد.',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            // خطا در اعمال تغییرات
            $this->dispatch('notify', [
                'message' => 'خطا در اعمال تغییرات: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    public function loadRankSettings()
    {
        // استفاده از آبجکت کالکشن بدون تبدیل به آرایه
        $this->rankSettings = RankSetting::orderBy('sort_order')->get();

        // نمایش پیام مناسب برای باز شدن تنظیمات
        $this->dispatch('notify', [
            'message' => 'تنظیمات معیارهای رتبه‌بندی بارگذاری شد - ' . count($this->rankSettings) . ' معیار',
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

        $this->dispatch('notify', [
            'message' => 'فرم ایجاد معیار جدید آماده شد',
            'type' => 'info'
        ]);
    }

    /**
     * باز کردن مودال تنظیمات رتبه
     */
    public function openRankModal()
    {
        // بارگذاری مجدد معیارهای رتبه‌بندی با اسکوپ active و ordered
        // با لود کردن به صورت collection (بدون ->toArray())
        $this->availableRankSettings = RankSetting::active()->ordered()->get();

        // ثبت در لاگ برای اشکال‌زدایی - با استفاده از متد count() کالکشن
        Log::info('Rank settings loaded:', [
            'loaded_criteria_count' => count($this->availableRankSettings)
        ]);

        // مقداردهی اولیه فیلدهای فرم معیار جدید
        $this->resetRankSettingForm();

        // Initialize selectedCriteria from specific_criteria if set
        if ($this->specific_criteria) {
            $this->selectedCriteria = explode(',', $this->specific_criteria);
        } else {
            $this->selectedCriteria = [];
        }

        $this->showRankModal = true;
        $this->dispatch('show-rank-modal');

        // نمایش پیام برای کاربر - با استفاده از متد count() کالکشن
        $this->dispatch('notify', [
            'message' => count($this->availableRankSettings) . ' معیار رتبه‌بندی بارگذاری شد',
            'type' => 'info'
        ]);
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
    public function applyCriteria()
    {
        if (!empty($this->selectedCriteria)) {
            $this->specific_criteria = implode(',', $this->selectedCriteria);
        } else {
            $this->specific_criteria = null;
        }

        $this->resetPage();
        $this->closeRankModal();

        // Clear cache to ensure fresh data
        if (Auth::check()) {
            cache()->forget('families_query_' . Auth::id());
        }

        $this->dispatch('notify', [
            'message' => 'معیارهای انتخاب‌شده با موفقیت اعمال شدند',
            'type' => 'success'
        ]);
    }

    /**
     * ویرایش تنظیمات رتبه
     */
    public function editRankSetting($id)
    {
        try {
            $setting = RankSetting::find($id);
            if ($setting) {
                // پر کردن فرم با مقادیر معیار موجود - با پشتیبانی از هر دو نام فیلد
                $this->rankSettingName = $setting->name;
                $this->rankSettingDescription = $setting->description;
                $this->rankSettingWeight = $setting->weight;

                // پشتیبانی از هر دو نام فیلد رنگ
                if (isset($setting->bg_color)) {
                    $this->rankSettingColor = $setting->bg_color;
                } elseif (isset($setting->color)) {
                    $this->rankSettingColor = $setting->color;
                } else {
                    $this->rankSettingColor = 'bg-green-100';
                }

                // پشتیبانی از هر دو نام فیلد نیاز به مدرک
                if (isset($setting->requires_document)) {
                    $this->rankSettingNeedsDoc = $setting->requires_document ? 1 : 0;
                } elseif (isset($setting->needs_doc)) {
                    $this->rankSettingNeedsDoc = $setting->needs_doc ? 1 : 0;
                } else {
                    $this->rankSettingNeedsDoc = 1;
                }

                $this->editingRankSettingId = $id;
                $this->isEditingMode = true; // مشخص می‌کند که در حال ویرایش هستیم نه افزودن

                // ثبت در لاگ
                Log::info('Editing rank setting:', [
                    'id' => $setting->id,
                    'name' => $setting->name
                ]);

                $this->dispatch('notify', [
                    'message' => 'در حال ویرایش معیار: ' . $setting->name,
                    'type' => 'info'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error loading rank setting:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            $this->dispatch('notify', [
                'message' => 'خطا در بارگذاری اطلاعات معیار: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * ریست کردن فرم معیار - متد عمومی
     */
    public function resetRankSettingForm()
    {
        $this->rankSettingName = '';
        $this->rankSettingDescription = '';
        $this->rankSettingWeight = 5;
        $this->rankSettingColor = 'bg-green-100';
        $this->rankSettingNeedsDoc = 1;
        $this->editingRankSettingId = null;
        $this->isEditingMode = false; // مشخص می‌کند که در حال افزودن هستیم نه ویرایش

        // اطلاع‌رسانی به کاربر در صورتی که این متد مستقیماً از UI فراخوانی شده باشد
        if (request()->hasHeader('x-livewire')) {
            $this->dispatch('notify', [
                'message' => 'فرم معیار بازنشانی شد',
                'type' => 'info'
            ]);
        }
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
        $this->closeRankModal();

        // پاک کردن کش برای اطمینان از به‌روزرسانی داده‌ها
        if (Auth::check()) {
            cache()->forget('families_query_' . Auth::id());
        }

        $this->dispatch('notify', [
            'message' => 'تنظیمات رتبه با موفقیت به حالت پیشفرض بازگردانده شد',
            'type' => 'success'
        ]);
    }

    /**
     * حذف معیار
     */
    public function deleteRankSetting($id)
    {
        try {
            $setting = RankSetting::find($id);
            if ($setting) {
                $name = $setting->name;
                $setting->delete();

                $this->dispatch('notify', [
                    'message' => "معیار «{$name}» با موفقیت حذف شد",
                    'type' => 'warning'
                ]);

                // بارگذاری مجدد لیست
        $this->availableRankSettings = RankSetting::active()->ordereclearCacheAndRefreshd()->get();
            }
        } catch (\Exception $e) {
            Log::error('Error deleting rank setting:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            $this->dispatch('notify', [
                'message' => 'خطا در حذف معیار: ' . $e->getMessage(),
                'type' => 'error'
        ]);
        }
    }

    /**
     * اضافه کردن فیلتر بیماری خاص
     */
    public function filterBySpecialDisease()
    {
        $this->status = 'special_disease';
        $this->resetPage();
        $this->dispatch('notify', [
            'message' => 'فیلتر بیماری خاص اعمال شد',
            'type' => 'success'
        ]);
    }

    /**
     * دانلود فایل اکسل برای خانواده‌های موجود در صفحه
     */
    public function downloadPageExcel()
    {
        $query = Family::query()->with([
            'province', 'city', 'district', 'region', 'members', 'head', 'charity', 'organization'
        ]);

        // اعمال فیلترهای موجود
        if ($this->search) {
            $query->where(function($q) {
                $q->where('family_code', 'like', '%' . $this->search . '%')
                  ->orWhereHas('head', function($headQuery) {
                      $headQuery->where('full_name', 'like', '%' . $this->search . '%')
                               ->orWhere('national_code', 'like', '%' . $this->search . '%');
                  });
            });
        }

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

        // اعمال مرتب‌سازی
        if ($this->sortField && $this->sortDirection) {
            $query->orderBy($this->sortField, $this->sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // محدود کردن به خانواده‌های صفحه فعلی
        $offset = ($this->page - 1) * $this->perPage;
        $families = $query->skip($offset)->take($this->perPage)->get();

        if ($families->isEmpty()) {
            session()->flash('error', 'هیچ خانواده‌ای برای دانلود یافت نشد.');
            return;
        }

        $filename = 'families-page-' . $this->page . '-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

        return Excel::download(new \App\Exports\FamiliesExport($families->toArray()), $filename);
    }

    /**
     * شروع ویرایش عضو خانواده
     * @param int $memberId
     * @return void
     */
    public function editMember($memberId)
    {
        try {
            $member = Member::find($memberId);
            if (!$member) {
                $this->dispatch('notify', [
                    'message' => 'عضو خانواده یافت نشد',
                    'type' => 'error'
                ]);
                return;
            }

            $this->editingMemberId = $memberId;

            // تبدیل آرایه problem_type به رشته برای نمایش در فرم
            $problemTypeString = '';
            if (is_array($member->problem_type) && !empty($member->problem_type)) {
                $problemTypeString = implode(', ', $member->problem_type);
            }

            $this->editingMemberData = [
                'relationship' => $member->relationship ?? '',
                'occupation' => $member->occupation ?? '',
                'job_type' => $member->job_type ?? '',
                'problem_type' => $problemTypeString
            ];
        } catch (\Exception $e) {
            Log::error('Error starting member edit:', [
                'member_id' => $memberId,
                'error' => $e->getMessage()
            ]);

            $this->dispatch('notify', [
                'message' => 'خطا در شروع ویرایش: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * ذخیره تغییرات عضو خانواده
     * @return void
     */
    public function saveMember()
    {
        try {
            $this->validate([
                'editingMemberData.relationship' => 'required|string|max:255',
                'editingMemberData.occupation' => 'required|string|max:255',
                'editingMemberData.job_type' => 'nullable|string|max:255',
                'editingMemberData.problem_type' => 'nullable|string|max:1000'
            ], [
                'editingMemberData.relationship.required' => 'نسبت الزامی است',
                'editingMemberData.occupation.required' => 'شغل الزامی است',
                'editingMemberData.problem_type.max' => 'معیار پذیرش نمی‌تواند بیش از 1000 کاراکتر باشد',
            ]);

            $member = Member::find($this->editingMemberId);
            if (!$member) {
                $this->dispatch('notify', [
                    'message' => 'عضو خانواده یافت نشد',
                    'type' => 'error'
                ]);
                return;
            }

            // آماده‌سازی داده‌ها برای ذخیره
            $updateData = [
                'relationship' => $this->editingMemberData['relationship'],
                'occupation' => $this->editingMemberData['occupation'],
            ];

            // مدیریت نوع شغل
            if ($this->editingMemberData['occupation'] === 'شاغل') {
                $updateData['job_type'] = $this->editingMemberData['job_type'] ?? null;
            } else {
                $updateData['job_type'] = null;
            }

            // مدیریت معیار پذیرش (problem_type)
            $problemTypeArray = null;
            $problemTypeInput = $this->editingMemberData['problem_type'] ?? '';

            // تبدیل آرایه به رشته اگر لازم باشد
            if (is_array($problemTypeInput)) {
                $problemTypeString = implode(', ', array_filter($problemTypeInput, function($item) {
                    return !empty(trim($item));
                }));
            } else {
                $problemTypeString = (string) $problemTypeInput;
            }

            if (!empty($problemTypeString) && trim($problemTypeString) !== '') {
                $problemTypeString = trim($problemTypeString);
                // تقسیم رشته با کاما و حذف فضاهای اضافی
                $problemTypes = array_map('trim', explode(',', $problemTypeString));
                $problemTypes = array_filter($problemTypes, function($item) {
                    return !empty(trim($item));
                });

                if (!empty($problemTypes)) {
                    $problemTypeArray = array_values($problemTypes); // reset array keys
                }
            }

            $updateData['problem_type'] = $problemTypeArray;

            // لاگ برای دیباگ
            Log::info('Updating member data:', [
                'member_id' => $this->editingMemberId,
                'original_problem_type' => $this->editingMemberData['problem_type'],
                'processed_problem_type' => $problemTypeArray,
                'job_type' => $updateData['job_type'],
                'occupation' => $updateData['occupation']
            ]);

            $member->update($updateData);

            // پاک کردن کش برای به‌روزرسانی داده‌ها
            $this->clearFamiliesCache();

            // بستن حالت ویرایش
            $this->cancelMemberEdit();

            $this->dispatch('notify', [
                'message' => 'اطلاعات عضو خانواده با موفقیت به‌روزرسانی شد',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving member:', [
                'member_id' => $this->editingMemberId,
                'data' => $this->editingMemberData,
                'error' => $e->getMessage()
            ]);

            $this->dispatch('notify', [
                'message' => 'خطا در ذخیره اطلاعات: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * لغو ویرایش عضو خانواده
     * @return void
     */
    public function cancelMemberEdit()
    {
        $this->editingMemberId = null;
        $this->editingMemberData = [
            'relationship' => '',
            'occupation' => '',
            'job_type' => '',
            'problem_type' => []
        ];
    }

    /**
     * دریافت گزینه‌های نسبت
     * @return array
     */
    public function getRelationshipOptions()
    {
        return [
            'مادر' => 'مادر',
            'پدر' => 'پدر',
            'زن' => 'زن',
            'شوهر' => 'شوهر',
            'پسر' => 'پسر',
            'دختر' => 'دختر',
            'مادربزرگ' => 'مادربزرگ',
            'پدربزرگ' => 'پدربزرگ',
            'سایر' => 'سایر'
        ];
    }

    /**
     * دریافت گزینه‌های شغل
     * @return array
     */
    public function getOccupationOptions()
    {
        return [
            'شاغل' => 'شاغل',
            'بیکار' => 'بیکار',
            'محصل' => 'محصل',
            'دانشجو' => 'دانشجو',
            'از کار افتاده' => 'از کار افتاده',
            'ترک تحصیل' => 'ترک تحصیل',
            'خانه‌دار' => 'خانه‌دار'
        ];
    }
}
