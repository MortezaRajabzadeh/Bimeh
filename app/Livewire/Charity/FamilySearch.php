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
use App\Models\SavedFilter;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Illuminate\Support\Facades\Cache;
use App\QueryFilters\FamilyRankingFilter;
use App\QuerySorts\RankingSort;
use App\Helpers\ProblemTypeHelper;
use App\Helpers\DateHelper;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

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
    public $charity_id = null;
    public $organization_id = null;

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
    public $filters = [];

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
                'charity_id',
                'organization_id',
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

    /**
     * حذف فیلتر از لیست فیلترهای موقت
     * @param int $index
     * @return void
     */
    public function removeFilter($index)
    {
        if (isset($this->tempFilters[$index])) {
            unset($this->tempFilters[$index]);
            // بازنویسی ایندکس‌ها برای حفظ ترتیب
            $this->tempFilters = array_values($this->tempFilters);

            // پاک کردن کش برای بارگیری مجدد نتایج
            $this->clearFamiliesCache();

            Log::info('🗑️ Filter removed', [
                'index' => $index,
                'remaining_filters_count' => count($this->tempFilters),
                'user_id' => Auth::id()
            ]);
        }
    }

    // New ranking properties
    public $showRankModal = false;
    public $rankFilters = [];

    // اضافه کردن متغیرهای فرم معیار جدید
    public $rankSettingName = '';
    public $rankSettingDescription = '';
    public $rankSettingWeight = 5;
    public $rankSettingColor = '#60A5FA';
    public $rankSettingNeedsDoc = true;

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
        'organization_id' => ['except' => null],
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
                'per_page' => $this->perPage,
                'active_filters' => $this->activeFilters,
                'temp_filters' => $this->tempFilters
            ]);

            // استفاده از کش برای بهبود عملکرد
            $cacheKey = $this->getCacheKey();

            $families = Cache::remember($cacheKey, 300, function () {
                $queryBuilder = $this->buildFamiliesQuery();

                // لاگ SQL نهایی درست قبل از paginate
                $finalSql = $queryBuilder->toSql();
                $finalBindings = $queryBuilder->getBindings();
                Log::info('🔥 Final SQL before paginate', [
                    'sql' => $finalSql,
                    'bindings' => $finalBindings,
                    'count_query' => str_replace('select `families`.*', 'select count(*) as aggregate', $finalSql)
                ]);

                // اطمینان از paginate فقط روی QueryBuilder/Eloquent
                if ($queryBuilder instanceof \Illuminate\Database\Eloquent\Builder ||
                    $queryBuilder instanceof \Illuminate\Database\Eloquent\Relations\Relation ||
                    $queryBuilder instanceof \Spatie\QueryBuilder\QueryBuilder) {
                    // تعداد رکوردها را بررسی کن
                    $count = $queryBuilder->count();
                    Log::info('📊 Total records found', [
                        'count' => $count,
                        'with_filters' => $this->hasActiveFilters(),
                        'filters' => $this->activeFilters
                    ]);

                    return $queryBuilder->paginate($this->perPage);
                } else {
                    // ایجاد paginator خالی برای Collection ها
                    return new LengthAwarePaginator(
                        collect([]),  // items
                        0,           // total
                        $this->perPage,  // perPage
                        $this->page,     // currentPage
                        [
                            'path' => request()->url(),
                            'pageName' => 'page',
                        ]
                    );
                }
            });

            // لاگ برای دیباگ فیلتر
            Log::info('🎬 Rendering view with families', [
                'total_items' => $families->total(),
                'current_page' => $families->currentPage(),
                'per_page' => $families->perPage(),
                'has_filters' => $this->hasActiveFilters(),
                'cache_key' => $this->getCacheKey()
            ]);

            return view('livewire.charity.family-search', [
                'families' => $families,
                'totalMembersInCurrentPage' => $this->getTotalMembersInCurrentPageProperty()
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
            $emptyPaginator = new LengthAwarePaginator(
                collect([]),  // items
                0,           // total
                $this->perPage,  // perPage
                $this->page,     // currentPage
                [
                    'path' => request()->url(),
                    'pageName' => 'page',
                ]
            );

            return view('livewire.charity.family-search', [
                'families' => $emptyPaginator,
                'totalMembersInCurrentPage' => 0
            ]);
        }
    }

    /**
     * دریافت تعداد کل اعضای خانواده‌های صفحه فعلی
     *
     * @return int
     */
    public function getTotalMembersInCurrentPageProperty()
    {
        try {
            $cacheKey = $this->getCacheKey();
            $families = Cache::get($cacheKey);

            if (!$families) {
                $queryBuilder = $this->buildFamiliesQuery();
                $families = $queryBuilder->paginate($this->perPage);
            }

            if (!$families || $families->isEmpty()) {
                return 0;
            }

            return $families->sum('members_count');
        } catch (\Exception $e) {
            Log::error('❌ Error calculating total members in current page', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            return 0;
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
                    'finalInsurances.fundingSource' => fn($q) => $q->where('is_active', true),
                    'finalInsurances.shares.fundingSource' // added to avoid N+1 when reading shares in view
                ])
                ->withCount('members')
                ->groupBy('families.id');

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
                AllowedFilter::custom('ranking', new FamilyRankingFilter()),
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
                ->allowedSorts($allowedSorts);
                // ->defaultSort('families.created_at'); // حذف چون در applyComponentFilters هم sort اعمال می‌شود

            // اعمال فیلترهای کامپوننت
            $this->applyComponentFilters($queryBuilder);

            // اعمال فیلترهای مودال
            $queryBuilder = $this->convertModalFiltersToQueryBuilder($queryBuilder);

            // لاگ SQL برای debug
            $sql = $queryBuilder->toSql();
            $bindings = $queryBuilder->getBindings();

            Log::info('\ud83d\udd0d FamilySearch QueryBuilder initialized successfully', [
                'search' => $this->search,
                'status' => $this->status,
                'has_modal_filters' => !empty($this->activeFilters ?? $this->tempFilters ?? $this->filters ?? []),
                'filters_count' => count(request()->query()),
                'sql' => $sql,
                'bindings' => $bindings
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
                ->groupBy('families.id')
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

            // فیلتر معیار خاص (اصلاح شده مانند FamiliesApproval)
            if (!empty($this->specific_criteria)) {
                $criteriaIds = array_map('trim', explode(',', $this->specific_criteria));
                // اگر مقدار رشته‌ای است (مثلاً نام معیار)، آن را به id تبدیل کن
                if (!is_numeric($criteriaIds[0])) {
                    $criteriaIds = \App\Models\RankSetting::whereIn('name', $criteriaIds)->pluck('id')->toArray();
                }
                if (!empty($criteriaIds)) {
                    $rankSettingNames = \App\Models\RankSetting::whereIn('id', $criteriaIds)->pluck('name')->toArray();
                    $queryBuilder->where(function($q) use ($criteriaIds, $rankSettingNames) {
                        // سیستم جدید: family_criteria
                        $q->whereHas('familyCriteria', function($subquery) use ($criteriaIds) {
                            $subquery->whereIn('rank_setting_id', $criteriaIds)
                                     ->where('has_criteria', true);
                        });
                        // سیستم قدیمی: rank_criteria
                        foreach ($rankSettingNames as $name) {
                            $q->orWhere('rank_criteria', 'LIKE', '%' . $name . '%');
                        }
                    });
                    Log::debug('✅ Specific criteria filter applied (by id)', ['criteria_ids' => $criteriaIds]);
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
                'raw_filters' => $modalFilters,
                'user_id' => Auth::id()
            ]);

            // جداسازی فیلترها بر اساس عملگر منطقی
            $andFilters = [];
            $orFilters = [];

            foreach ($modalFilters as $filter) {
                // بررسی اعتبار فیلتر
                if (empty($filter['type'])) {
                    continue;
                }

                $operator = $filter['operator'] ?? 'and';

                // برای exists و not_exists نیازی به value نداریم
                if ($operator !== 'exists' && $operator !== 'not_exists' && empty($filter['value'])) {
                    continue;
                }

                // تعیین نوع شرط منطقی
                if ($operator === 'or') {
                    $orFilters[] = $filter;
                } else {
                    $andFilters[] = $filter;
                }
            }

            Log::debug('🔍 Final processed filters', [
                'and_filters' => $andFilters,
                'or_filters' => $orFilters,
                'user_id' => Auth::id()
            ]);

            // **بررسی و پردازش فیلترهای special_disease چندگانه با AND logic**
            $queryBuilder = $this->applySpecialDiseaseAndLogic($queryBuilder, $andFilters);

            // اعمال فیلترهای AND غیر special_disease
            foreach ($andFilters as $filter) {
                if (!in_array($filter['type'], ['special_disease', 'معیار پذیرش'])) {
                    Log::debug('🔧 Applying AND filter', ['filter' => $filter]);
                    $queryBuilder = $this->applySingleFilter($queryBuilder, $filter, 'and');
                }
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
     * پردازش فیلترهای special_disease چندگانه با منطق AND
     * @param \Spatie\QueryBuilder\QueryBuilder $queryBuilder
     * @param array $andFilters
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    protected function applySpecialDiseaseAndLogic($queryBuilder, $andFilters)
    {
        try {
            // فیلتر فیلترهای special_disease
            $specialDiseaseFilters = array_filter($andFilters, function($filter) {
                return in_array($filter['type'], ['special_disease', 'معیار پذیرش']) && !empty($filter['value']);
            });

            if (empty($specialDiseaseFilters)) {
                return $queryBuilder;
            }

            Log::debug('📊 Processing special_disease filters with AND logic', [
                'filters_count' => count($specialDiseaseFilters),
                'filters' => $specialDiseaseFilters
            ]);

            // **پردازش رشته comma-separated و تبدیل به آرایه**
            $allSelectedValues = [];
            foreach ($specialDiseaseFilters as $filter) {
                $filterValue = $filter['value'];

                // اگر رشته حاوی ویرگول باشد، تقسیم کن
                if (str_contains($filterValue, ',')) {
                    $values = array_map('trim', explode(',', $filterValue));
                    foreach ($values as $value) {
                        if (!empty($value) && !in_array($value, $allSelectedValues)) {
                            $allSelectedValues[] = $value;
                        }
                    }
                } else {
                    if (!empty($filterValue) && !in_array($filterValue, $allSelectedValues)) {
                        $allSelectedValues[] = $filterValue;
                    }
                }
            }

            if (empty($allSelectedValues)) {
                return $queryBuilder;
            }

            Log::debug('🔎 Parsed special_disease values for AND logic', [
                'values' => $allSelectedValues,
                'count' => count($allSelectedValues)
            ]);

            // برای هر مقدار جداگانه، یک whereHas اعمال کن (منطق AND)
            foreach ($allSelectedValues as $value) {
                Log::debug('🔎 Applying AND whereHas for special_disease value', ['value' => $value]);

                $queryBuilder = $queryBuilder->whereHas('members', function($memberQuery) use ($value) {
                    // تبدیل به مقادیر مختلف (فارسی و انگلیسی)
                    $persianValue = \App\Helpers\ProblemTypeHelper::englishToPersian($value);
                    $englishValue = \App\Helpers\ProblemTypeHelper::persianToEnglish($value);

                    $memberQuery->where(function($q) use ($value, $persianValue, $englishValue) {
                        $q->whereJsonContains('problem_type', $value)
                          ->orWhereJsonContains('problem_type', $persianValue)
                          ->orWhereJsonContains('problem_type', $englishValue);
                    });
                });
            }

            Log::info('✅ Special_disease AND logic applied successfully', [
                'values_applied' => $allSelectedValues,
                'filters_processed' => count($specialDiseaseFilters)
            ]);

            return $queryBuilder;

        } catch (\Exception $e) {
            Log::error('❌ Error applying special_disease AND logic', [
                'error' => $e->getMessage(),
                'filters' => $specialDiseaseFilters ?? [],
                'trace' => $e->getTraceAsString()
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

            // پردازش operators جدید
            $logicalOperator = $filter['logical_operator'] ?? 'and';
            $existenceOperator = $filter['existence_operator'] ?? 'equals';

            // تعیین operator نهایی بر اساس شرط‌های جدید
            $operator = $existenceOperator;
            if ($existenceOperator === 'equals') {
                // اگر مقدار خاص است، از logical operator استفاده کن
                $operator = 'equals';
            }

            // سازگاری با operator قدیمی
            if (isset($filter['operator']) && in_array($filter['operator'], ['exists', 'not_exists', 'equals', 'and', 'or'])) {
                $operator = $filter['operator'];
                if ($operator === 'and' || $operator === 'or') {
                    $operator = 'equals';
                }
            }

            // تعیین نوع متد بر اساس عملگر منطقی نهایی
            $finalLogicalMethod = ($logicalOperator === 'or' || $method === 'or') ? 'or' : 'and';
            $whereMethod = $finalLogicalMethod === 'or' ? 'orWhere' : 'where';
            $whereHasMethod = $finalLogicalMethod === 'or' ? 'orWhereHas' : 'whereHas';
            $whereDoesntHaveMethod = $finalLogicalMethod === 'or' ? 'orWhereDoesntHave' : 'whereDoesntHave';

            switch ($filterType) {
                case 'status':
                    if ($operator === 'equals') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.status', $filterValue);
                    } elseif ($operator === 'not_equals') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.status', '!=', $filterValue);
                    } elseif ($operator === 'exists') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.status', '!=', null);
                    } elseif ($operator === 'not_exists') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.status', null);
                    }
                    break;

                case 'province':
                    if ($operator === 'equals') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.province_id', $filterValue);
                    } elseif ($operator === 'not_equals') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.province_id', '!=', $filterValue);
                    } elseif ($operator === 'exists') {
                        if (!empty($filterValue)) {
                            // فیلتر برای استان خاص: families با province_id برابر با مقدار انتخابی
                            $queryBuilder = $queryBuilder->$whereMethod('families.province_id', $filterValue);
                        } else {
                            // فیلتر برای وجود هر استان: families که province_id دارند
                            $queryBuilder = $queryBuilder->$whereMethod('families.province_id', '!=', null);
                        }
                    } elseif ($operator === 'not_exists') {
                        if (!empty($filterValue)) {
                            // فیلتر برای عدم انتخاب استان خاص: families که province_id آن‌ها برابر با مقدار انتخابی نباشد
                            $queryBuilder = $queryBuilder->$whereMethod('families.province_id', '!=', $filterValue);
                        } else {
                            // فیلتر برای عدم وجود استان: families که province_id ندارند
                            $queryBuilder = $queryBuilder->$whereMethod('families.province_id', null);
                        }
                    }
                    break;

                case 'city':
                    if ($operator === 'equals') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.city_id', $filterValue);
                    } elseif ($operator === 'not_equals') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.city_id', '!=', $filterValue);
                    } elseif ($operator === 'exists') {
                        if (!empty($filterValue)) {
                            // فیلتر برای شهر خاص: families با city_id برابر با مقدار انتخابی
                            $queryBuilder = $queryBuilder->$whereMethod('families.city_id', $filterValue);
                        } else {
                            // فیلتر برای وجود هر شهر: families که city_id دارند
                            $queryBuilder = $queryBuilder->$whereMethod('families.city_id', '!=', null);
                        }
                    } elseif ($operator === 'not_exists') {
                        if (!empty($filterValue)) {
                            // فیلتر برای عدم انتخاب شهر خاص: families که city_id آن‌ها برابر با مقدار انتخابی نباشد
                            $queryBuilder = $queryBuilder->$whereMethod('families.city_id', '!=', $filterValue);
                        } else {
                            // فیلتر برای عدم وجود شهر: families که city_id ندارند
                            $queryBuilder = $queryBuilder->$whereMethod('families.city_id', null);
                        }
                    }
                    break;

                case 'charity':
                    if ($operator === 'equals') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.charity_id', $filterValue);
                    } elseif ($operator === 'not_equals') {
                        $queryBuilder = $queryBuilder->$whereMethod('families.charity_id', '!=', $filterValue);
                    } elseif ($operator === 'exists') {
                        if (!empty($filterValue)) {
                            // فیلتر برای خیریه خاص: families با charity_id برابر با مقدار انتخابی
                            $queryBuilder = $queryBuilder->$whereMethod('families.charity_id', $filterValue);
                        } else {
                            // فیلتر برای وجود هر خیریه: families که charity_id دارند
                            $queryBuilder = $queryBuilder->$whereMethod('families.charity_id', '!=', null);
                        }
                    } elseif ($operator === 'not_exists') {
                        if (!empty($filterValue)) {
                            // فیلتر برای عدم انتخاب خیریه خاص: families که charity_id آن‌ها برابر با مقدار انتخابی نباشد
                            $queryBuilder = $queryBuilder->$whereMethod('families.charity_id', '!=', $filterValue);
                        } else {
                            // فیلتر برای عدم وجود خیریه: families که charity_id ندارند
                            $queryBuilder = $queryBuilder->$whereMethod('families.charity_id', null);
                        }
                    }
                    break;

                case 'members_count':
                    Log::debug('🔢 Processing members_count filter', [
                        'operator' => $operator,
                        'value' => $filterValue,
                        'method' => $method
                    ]);
                    $queryBuilder = $this->applyNumericFilter($queryBuilder, 'members_count', $operator, $filterValue, $method, $filter);
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
                    if ($operator === 'exists') {
                        // خانواده‌هایی که حداقل یک عضو دارای معیار پذیرش باشد
                        $queryBuilder = $queryBuilder->$whereHasMethod('members', function($memberQuery) {
                            $memberQuery->whereNotNull('problem_type')
                                       ->where('problem_type', '!=', '[]')
                                       ->where('problem_type', '!=', 'null');
                        });
                    } elseif ($operator === 'not_exists') {
                        // خانواده‌هایی که هیچ عضوی دارای معیار پذیرش نباشد
                        $queryBuilder = $queryBuilder->$whereDoesntHaveMethod('members', function($memberQuery) {
                            $memberQuery->whereNotNull('problem_type')
                                       ->where('problem_type', '!=', '[]')
                                       ->where('problem_type', '!=', 'null');
                        });
                    } elseif (!empty($filterValue)) {
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
    protected function applyNumericFilter($queryBuilder, $field, $operator, $value, $method = 'and', $filter = [])
    {
        $whereMethod = $method === 'or' ? 'orWhere' : 'where';
        $whereBetweenMethod = $method === 'or' ? 'orWhereBetween' : 'whereBetween';
        $whereNotNullMethod = $method === 'or' ? 'orWhereNotNull' : 'whereNotNull';
        $whereNullMethod = $method === 'or' ? 'orWhereNull' : 'whereNull';
        $whereHasMethod = $method === 'or' ? 'orWhereHas' : 'whereHas';
        $whereDoesntHaveMethod = $method === 'or' ? 'orWhereDoesntHave' : 'whereDoesntHave';
        $havingMethod = $method === 'or' ? 'orHaving' : 'having';
        $havingBetweenMethod = $method === 'or' ? 'orHavingBetween' : 'havingBetween';

        // برای فیلد members_count که فیلد محاسباتی است، باید از HAVING یا relation استفاده کنیم
        if ($field === 'members_count') {
            Log::debug('🔧 applyNumericFilter for members_count', [
                'field' => $field,
                'operator' => $operator,
                'value' => $value,
                'method' => $method
            ]);

            switch ($operator) {
                case 'exists':
                    Log::debug('✅ Applying whereHas for members_count exists', ['value' => $value, 'filter' => $filter]);
                    return $this->applyMembersCountFilter($queryBuilder, $filter, $havingMethod, $whereHasMethod);
                case 'not_exists':
                    Log::debug('✅ Applying whereDoesntHave for members_count not_exists', ['value' => $value, 'filter' => $filter]);
                    return $this->applyMembersCountFilter($queryBuilder, $filter, $havingMethod, $whereHasMethod, true);
                case 'equals':
                    Log::debug('✅ Applying having equals for members_count');
                    return $queryBuilder->$havingMethod('members_count', '=', $value);
                case 'not_equals':
                    return $queryBuilder->$havingMethod('members_count', '!=', $value);
                case 'greater_than':
                    return $queryBuilder->$havingMethod('members_count', '>', $value);
                case 'less_than':
                    return $queryBuilder->$havingMethod('members_count', '<', $value);
                case 'greater_than_or_equal':
                    return $queryBuilder->$havingMethod('members_count', '>=', $value);
                case 'less_than_or_equal':
                    return $queryBuilder->$havingMethod('members_count', '<=', $value);
                case 'between':
                    if (is_array($value) && count($value) === 2) {
                        return $queryBuilder->$havingBetweenMethod('members_count', $value);
                    }
                    break;
                default:
                    Log::debug('⚠️ Using default having for members_count');
                    return $queryBuilder->$havingMethod('members_count', $value);
            }
        }

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
            case 'exists':
                return $queryBuilder->$whereNotNullMethod($field);
            case 'not_exists':
                return $queryBuilder->$whereNullMethod($field);
            default:
                return $queryBuilder->$whereMethod($field, $value);
        }

        return $queryBuilder;
    }

    /**
     * اعمال فیلتر تعداد اعضا با پشتیبانی از بازه
     *
     * @param \Spatie\QueryBuilder\QueryBuilder $queryBuilder
     * @param array $filter
     * @param string $havingMethod
     * @param string $whereHasMethod
     * @param bool $isNegative آیا شرط منفی است (not_exists)
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    protected function applyMembersCountFilter($queryBuilder, $filter, $havingMethod, $whereHasMethod, $isNegative = false)
    {
        $whereDoesntHaveMethod = str_replace('whereHas', 'whereDoesntHave', $whereHasMethod);

        // بررسی بازه
        if (!empty($filter['min_members']) || !empty($filter['max_members'])) {
            $minMembers = !empty($filter['min_members']) ? (int)$filter['min_members'] : null;
            $maxMembers = !empty($filter['max_members']) ? (int)$filter['max_members'] : null;

            if ($minMembers && $maxMembers) {
                // بازه کامل: مین تا مکس
                if ($isNegative) {
                    return $queryBuilder->$havingMethod('members_count', '<', $minMembers)
                                       ->orHaving('members_count', '>', $maxMembers);
                } else {
                    return $queryBuilder->$havingMethod('members_count', '>=', $minMembers)
                                       ->having('members_count', '<=', $maxMembers);
                }
            } elseif ($minMembers) {
                // فقط حداقل
                return $queryBuilder->$havingMethod('members_count', $isNegative ? '<' : '>=', $minMembers);
            } elseif ($maxMembers) {
                // فقط حداکثر
                return $queryBuilder->$havingMethod('members_count', $isNegative ? '>' : '<=', $maxMembers);
            }
        }

        // تک عدد یا شرط عمومی
        if (!empty($filter['value'])) {
            $value = (int)$filter['value'];
            return $queryBuilder->$havingMethod('members_count', $isNegative ? '!=' : '=', $value);
        } else {
            // بدون مقدار: فقط وجود/عدم وجود عضو
            return $queryBuilder->{$isNegative ? $whereDoesntHaveMethod : $whereHasMethod}('members');
        }
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
        $whereNotNullMethod = $method === 'or' ? 'orWhereNotNull' : 'whereNotNull';
        $whereNullMethod = $method === 'or' ? 'orWhereNull' : 'whereNull';

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
            case 'exists':
                return $queryBuilder->$whereNotNullMethod($field);
            case 'not_exists':
                return $queryBuilder->$whereNullMethod($field);
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

                // اجبار به refresh کامپوننت
                $this->dispatch('refresh-component');
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
               !empty($this->activeFilters) ||
               !empty($this->tempFilters);
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
        if (!empty($this->tempFilters)) {
            // شمارش فیلترهای فعال در tempFilters
            foreach ($this->tempFilters as $filter) {
                if (!empty($filter['type']) &&
                    (!empty($filter['value']) || !empty($filter['min_members']) ||
                     !empty($filter['max_members']) || !empty($filter['start_date']) ||
                     !empty($filter['end_date']))) {
                    $count++;
                }
            }
        }

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
        Log::info('📋 STEP 2: Loading rank settings', [
            'user_id' => Auth::id(),
            'timestamp' => now()
        ]);
        $this->rankSettings = RankSetting::orderBy('sort_order')->get();
        $this->rankingSchemes = \App\Models\RankingScheme::orderBy('name')->get();
        $this->availableCriteria = RankSetting::where('is_active', true)->orderBy('sort_order')->get();
        // Update available rank settings for display
        $this->availableRankSettings = $this->rankSettings;
        // اصلاح count برای آرایه/کالکشن
        $rankSettingsCount = is_array($this->rankSettings) ? count($this->rankSettings) : $this->rankSettings->count();
        $rankingSchemesCount = is_array($this->rankingSchemes) ? count($this->rankingSchemes) : $this->rankingSchemes->count();
        $availableCriteriaCount = is_array($this->availableCriteria) ? count($this->availableCriteria) : $this->availableCriteria->count();
        $activeCriteria = $this->availableCriteria instanceof \Illuminate\Support\Collection ? $this->availableCriteria->pluck('name', 'id')->toArray() : [];
        Log::info('✅ STEP 2 COMPLETED: Rank settings loaded', [
            'rankSettings_count' => $rankSettingsCount,
            'rankingSchemes_count' => $rankingSchemesCount,
            'availableCriteria_count' => $availableCriteriaCount,
            'active_criteria' => $activeCriteria,
            'user_id' => Auth::id()
        ]);
        // نمایش پیام مناسب برای باز شدن تنظیمات
        $this->dispatch('notify', [
            'message' => 'تنظیمات معیارهای رتبه‌بندی بارگذاری شد - ' . $rankSettingsCount . ' معیار',
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
     * یک معیار را برای ویرایش انتخاب می‌کند.
     * @param int $id
     */
    public function edit($id)
    {
        $this->isCreatingNew = false;
        $this->editingRankSettingId = $id;
        $setting = RankSetting::find($id);
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
                $maxOrder = RankSetting::max('sort_order') ?? 0;
                $this->editingRankSetting['sort_order'] = $maxOrder + 10;
                $this->editingRankSetting['is_active'] = true;
                $this->editingRankSetting['slug'] = \Illuminate\Support\Str::slug($this->editingRankSetting['name']);
            }

            // ذخیره
            $setting = RankSetting::updateOrCreate(
                ['id' => $this->editingRankSettingId],
                $this->editingRankSetting
            );

            // بازنشانی فرم
            $this->resetForm();

            // بارگذاری مجدد تنظیمات
            $this->loadRankSettings();

            // پاک کردن کش لیست خانواده‌ها
            $this->clearFamiliesCache();

            $this->dispatch('notify', [
                'message' => 'معیار با موفقیت ذخیره شد',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
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
            $setting = RankSetting::find($id);
            if ($setting) {
                // بررسی استفاده شدن معیار
                $usageCount = \App\Models\FamilyCriterion::where('rank_setting_id', $id)->count();
                if ($usageCount > 0) {
                    $this->dispatch('notify', [
                        'message' => "این معیار در {$usageCount} خانواده استفاده شده و قابل حذف نیست. به جای حذف می‌توانید آن را غیرفعال کنید.",
                        'type' => 'error'
                    ]);
                    return;
                }

                $setting->delete();
                $this->loadRankSettings();

                // پاک کردن کش لیست خانواده‌ها
                $this->clearFamiliesCache();

                $this->dispatch('notify', [
                    'message' => 'معیار با موفقیت حذف شد',
                    'type' => 'success'
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', [
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
        $this->dispatch('notify', [
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
     * باز کردن مودال تنظیمات رتبه
     */
    public function openRankModal()
    {
        Log::info('🎯 STEP 1: Opening rank modal', [
            'user_id' => Auth::id(),
            'timestamp' => now()
        ]);
        $this->loadRankSettings();
        $this->showRankModal = true;
        $rankSettingsCount = is_array($this->rankSettings) ? count($this->rankSettings) : $this->rankSettings->count();
        Log::info('✅ STEP 1 COMPLETED: Rank modal opened', [
            'showRankModal' => $this->showRankModal,
            'rankSettings_count' => $rankSettingsCount,
            'user_id' => Auth::id()
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
                $this->dispatch('notify', [
                    'message' => 'فیلتر و سورت معیارها پاک شد',
                    'type' => 'info'
                ]);
                return;
            }

            // ذخیره id معیارها برای فیلتر (مانند FamiliesApproval)
            $this->specific_criteria = implode(',', $selectedRankSettingIds);

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

            $criteriaList = implode('، ', $selectedRankSettingIds);

            $this->dispatch('notify', [
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
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => 'خطا در اعمال سورت رتبه‌بندی: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
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
        $this->rankSettingColor = '#60A5FA';
        $this->rankSettingNeedsDoc = true;
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
                $this->availableRankSettings = RankSetting::active()->ordered()->get();
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
     * ذخیره معیار رتبه‌بندی
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
                $setting = RankSetting::find($this->editingRankSettingId);
                if ($setting) {
                    $setting->weight = $this->rankSettingWeight;
                    $setting->save();

                    $this->dispatch('notify', [
                        'message' => 'وزن معیار با موفقیت به‌روزرسانی شد: ' . $setting->name,
                        'type' => 'success'
                    ]);
                }
            } else {
                // ایجاد معیار جدید
                RankSetting::create([
                    'name' => $this->rankSettingName,
                    'weight' => $this->rankSettingWeight,
                    'description' => $this->rankSettingDescription,
                    'requires_document' => (bool)$this->rankSettingNeedsDoc,
                    'slug' => \Illuminate\Support\Str::slug($this->rankSettingName) ?: 'rank-' . \Illuminate\Support\Str::random(6),
                    'is_active' => true,
                    'sort_order' => RankSetting::max('sort_order') + 1,
                ]);

                $this->dispatch('notify', [
                    'message' => 'معیار جدید با موفقیت ایجاد شد: ' . $this->rankSettingName,
                    'type' => 'success'
                ]);
            }

            // بارگذاری مجدد تنظیمات
            $this->availableRankSettings = RankSetting::active()->ordered()->get();
            $this->clearFamiliesCache();
            $this->resetRankSettingForm();

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'message' => 'خطا در ذخیره معیار: ' . $e->getMessage(),
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
                $this->dispatch('notify', [
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

            $this->dispatch('notify', [
                'message' => 'فیلتر تنظیمات رتبه "' . $filter->name . '" با موفقیت بارگذاری شد',
                'type' => 'success'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error loading rank filter: ' . $e->getMessage());
            $this->dispatch('notify', [
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
                $this->dispatch('notify', [
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
                $this->dispatch('notify', [
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

            $this->dispatch('notify', [
                'message' => 'فیلتر تنظیمات رتبه "' . $name . '" با موفقیت ذخیره شد',
                'type' => 'success'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error saving rank filter: ' . $e->getMessage());
            $this->dispatch('notify', [
                'message' => 'خطا در ذخیره فیلتر رتبه‌بندی: ' . $e->getMessage(),
                'type' => 'error'
            ]);
            return false;
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
     * اعمال سورت به query builder
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
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            // در صورت خطا، سورت بر اساس تاریخ ایجاد
            $queryBuilder->getEloquentBuilder()->orderBy('families.created_at', 'desc');
        }
    }

    /**
     * اعمال سورت وزنی بر اساس معیارهای انتخاب شده
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
     * Loads member data for editing. The problem_type array is passed to the MultiSelect component via wire:model binding.
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

            // بررسی مجوز ویرایش
            $family = $member->family;
            try {
                Gate::authorize('updateMembers', $family);
            } catch (AuthorizationException $e) {
                // ساخت پیام خطا بر اساس وضعیت wizard_status
                $statusMessage = $this->getAuthorizationErrorMessage($family);

                $this->dispatch('notify', [
                    'message' => $statusMessage,
                    'type' => 'error'
                ]);

                Log::warning('Unauthorized member edit attempt', [
                    'user_id' => Auth::id(),
                    'member_id' => $memberId,
                    'family_id' => $family->id,
                    'wizard_status' => $family->wizard_status
                ]);

                return;
            }

            $this->editingMemberId = $memberId;

            // دریافت آرایه معیارهای پذیرش برای dropdown
            $problemTypesArray = $member->getProblemTypesArray(); // English keys for the dropdown

            // حذف تکراری‌ها (بدون sort برای حفظ ترتیب insertion order)
            if (is_array($problemTypesArray)) {
                $problemTypesArray = array_unique($problemTypesArray);
                // sort() حذف شد: ترتیب insertion order حفظ می‌شود
            }

            $this->editingMemberData = [
                'relationship' => $member->relationship ?? '',
                'occupation' => $member->occupation ?? '',
                'job_type' => $member->job_type ?? '',
                'problem_type' => is_array($problemTypesArray) ? array_values($problemTypesArray) : []
            ];

            Log::info('Member edit started', [
                'member_id' => $memberId,
                'problem_types_count' => count($this->editingMemberData['problem_type']),
                'problem_types' => $this->editingMemberData['problem_type']
            ]);
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
            $member = Member::find($this->editingMemberId);
            if (!$member) {
                $this->dispatch('notify', [
                    'message' => 'عضو خانواده یافت نشد',
                    'type' => 'error'
                ]);
                return;
            }

            // بررسی مجوز ویرایش قبل از validation
            $family = $member->family;
            try {
                Gate::authorize('updateMembers', $family);
            } catch (AuthorizationException $e) {
                $statusMessage = $this->getAuthorizationErrorMessage($family);

                $this->dispatch('notify', [
                    'message' => $statusMessage,
                    'type' => 'error'
                ]);

                Log::warning('Unauthorized member save attempt', [
                    'user_id' => Auth::id(),
                    'member_id' => $this->editingMemberId,
                    'family_id' => $family->id,
                    'wizard_status' => $family->wizard_status
                ]);

                // لغو حالت ویرایش
                $this->editingMemberId = null;
                $this->editingMemberData = [
                    'relationship' => '',
                    'occupation' => '',
                    'job_type' => '',
                    'problem_type' => []
                ];

                return;
            }

            $this->validate([
                'editingMemberData.relationship' => 'required|string|max:255',
                'editingMemberData.occupation' => 'required|string|max:255',
                'editingMemberData.job_type' => 'nullable|string|max:255',
                'editingMemberData.problem_type' => 'nullable|array'
            ], [
                'editingMemberData.relationship.required' => 'نسبت الزامی است',
                'editingMemberData.occupation.required' => 'شغل الزامی است',
                'editingMemberData.problem_type.max' => 'معیار پذیرش نمی‌تواند بیش از 1000 کاراکتر باشد',
            ]);

            // آماده‌سازی داده‌ها برای ذخیره
            $updateData = [
                'relationship' => $this->editingMemberData['relationship'],
                'relationship_fa' => $this->editingMemberData['relationship'], // ذخیره مقدار فارسی برای relationship_fa
                'occupation' => $this->editingMemberData['occupation'],
            ];

            // مدیریت نوع شغل
            if ($this->editingMemberData['occupation'] === 'شاغل') {
                $updateData['job_type'] = $this->editingMemberData['job_type'] ?? null;
            } else {
                $updateData['job_type'] = null;
            }

            // مدیریت معیار پذیرش (problem_type) - پیشرفته و بهبود یافته
            // The problem_type array comes from the MultiSelect component via wire:model.live binding. It contains English keys.
            $problemTypeArray = null;
            $problemTypeInput = $this->editingMemberData['problem_type'] ?? '';

            Log::info('Processing problem_type input', [
                'member_id' => $this->editingMemberId,
                'input_type' => gettype($problemTypeInput),
                'input_value_persian' => $problemTypeInput
            ]);

            // پردازش مستقیم آرایه از dropdown
            if (is_array($problemTypeInput)) {
                // فیلتر کردن مقادیر خالی و null و تکراری‌ها
                $problemTypesForStorage = array_filter($problemTypeInput, function($item) {
                    return !is_null($item) && trim((string)$item) !== '';
                });

                // حذف مقادیر تکراری (بدون sort برای حفظ insertion order)
                $problemTypesForStorage = array_unique(array_values($problemTypesForStorage));
                // sort() حذف شد: chip‌ها به ترتیب اضافه شدن نمایش داده می‌شوند

                // بررسی اضافی برای حذف مقادیر مشابه
                $finalArray = [];
                foreach ($problemTypesForStorage as $item) {
                    $trimmedItem = trim((string)$item);
                    if (!empty($trimmedItem) && !in_array($trimmedItem, $finalArray)) {
                        $finalArray[] = $trimmedItem;
                    }
                }

                if (!empty($finalArray)) {
                    $problemTypeArray = $finalArray;
                } else {
                    // آرایه خالی است یا هیچ مقدار معتبری ندارد
                    $problemTypeArray = null;
                }
            } else if (!empty($problemTypeInput) && trim($problemTypeInput) !== '') {
                // اگر رشته باشد (برای سازگاری با روش قبلی)
                $problemTypeString = trim((string) $problemTypeInput);

                // تقسیم رشته با کاما
                $problemTypes = array_map('trim', explode(',', $problemTypeString));

                // فیلتر کردن مقادیر خالی
                $problemTypes = array_filter($problemTypes, function($item) {
                    return !empty(trim($item));
                });

                // تبدیل فارسی به انگلیسی
                $problemTypesForStorage = [];
                foreach ($problemTypes as $problemType) {
                    $englishValue = \App\Helpers\ProblemTypeHelper::persianToEnglish(trim($problemType));
                    if (!in_array($englishValue, $problemTypesForStorage)) {
                        $problemTypesForStorage[] = $englishValue;
                    }
                }

                // حذف مقادیر تکراری و مرتب‌سازی
                $problemTypesForStorage = array_unique($problemTypesForStorage);
                sort($problemTypesForStorage);

                if (!empty($problemTypesForStorage)) {
                    $problemTypeArray = array_values($problemTypesForStorage);
                }
            }

            // اگر آرایه خالی باشد، null ذخیره کن (نه آرایه خالی)
            $updateData['problem_type'] = empty($problemTypeArray) ? null : $problemTypeArray;

            // لاگ نتیجه تبدیل (همیشه لاگ کن)
            Log::info('Problem_type conversion completed', [
                'member_id' => $this->editingMemberId,
                'input_raw' => $problemTypeInput,
                'input_type' => gettype($problemTypeInput),
                'input_is_empty' => empty($problemTypeInput),
                'input_is_empty_array' => is_array($problemTypeInput) && empty($problemTypeInput),
                'processed_array' => $problemTypeArray,
                'will_store_in_db' => $updateData['problem_type']
            ]);

            // لاگ کامل برای دیباگ و ردیابی مشکلات
            Log::info('Updating member data - BEFORE UPDATE:', [
                'member_id' => $this->editingMemberId,
                'family_id' => $member->family_id,
                'member_name' => $member->first_name . ' ' . $member->last_name,
                'original_data' => [
                    'relationship' => $member->relationship,
                    'occupation' => $member->occupation,
                    'job_type' => $member->job_type,
                    'problem_type_original' => $member->problem_type
                ],
                'input_data' => [
                    'relationship' => $this->editingMemberData['relationship'],
                    'occupation' => $this->editingMemberData['occupation'],
                    'job_type' => $this->editingMemberData['job_type'],
                    'problem_type_input' => $this->editingMemberData['problem_type']
                ],
                'processed_update_data' => $updateData
            ]);

            // لاگ داده‌های relationship برای debug
            Log::info('Relationship data being saved', [
                'member_id' => $this->editingMemberId,
                'relationship' => $updateData['relationship'],
                'relationship_fa' => $updateData['relationship_fa']
            ]);

            $member->update($updateData);

            // لاگ بعد از آپدیت برای تأیید تغییرات
            Log::info('Member data updated successfully - AFTER UPDATE:', [
                'member_id' => $member->id,
                'updated_data' => $updateData,
                'fresh_data_from_db' => [
                    'relationship' => $member->fresh()->relationship,
                    'occupation' => $member->fresh()->occupation,
                    'job_type' => $member->fresh()->job_type,
                    'problem_type' => $member->fresh()->problem_type
                ]
            ]);

            // همگام‌سازی معیارهای پذیرش خانواده بر اساس معیارهای اعضا
            $family = $member->family;
            $family->load('members'); // اطمینان از بارگیری اعضای به‌روزرسانی شده
            $family->syncAcceptanceCriteriaFromMembers();

            // به‌روزرسانی فوری داده‌های محلی برای نمایش بلافاصله
            if ($this->expandedFamily === $member->family_id && !empty($this->familyMembers)) {
                foreach ($this->familyMembers as $key => $familyMember) {
                    if ($familyMember->id == $member->id) {
                        // به‌روزرسانی داده‌های عضو
                        $this->familyMembers[$key]->relationship = $updateData['relationship'];
                        $this->familyMembers[$key]->occupation = $updateData['occupation'];
                        $this->familyMembers[$key]->job_type = $updateData['job_type'];
                        $this->familyMembers[$key]->problem_type = $updateData['problem_type'];
                        $this->familyMembers[$key]->relationship_fa = $updateData['relationship_fa']; // اصلاح: استفاده از relationship_fa نه relationship

                        Log::info('Member data updated locally for immediate display', [
                            'member_id' => $member->id,
                            'updated_fields' => array_keys($updateData)
                        ]);
                        break;
                    }
                }

                // به‌روزرسانی اطلاعات خانواده در familyMembers برای نمایش فوری
                // برای نمایش فوری، خانواده را به‌روزرسانی می‌کنیم
                $freshFamily = $family->fresh(['members']); // بارگیری مجدد خانوادع به‌روزرسانی شده
                $this->familyMembers = $this->familyMembers->map(function($familyMember) use ($freshFamily) {
                    if ($familyMember->family_id === $freshFamily->id) {
                        $familyMember->family = $freshFamily; // به‌روزرسانی اطلاعات خانواده
                    }
                    return $familyMember;
                });

                Log::info('Family acceptance_criteria updated locally for immediate display', [
                    'family_id' => $freshFamily->id,
                    'updated_acceptance_criteria' => $freshFamily->acceptance_criteria
                ]);
            }

            // پاک کردن کش‌های مختلف برای اطمینان از نمایش داده‌های جدید
            $this->clearFamiliesCache();

            // پاک کردن کش رتبه‌بندی خانواده
            \Cache::forget('family_rank_' . $family->id);

            // اجبار به ریفرش کامپوننت برای نمایش تغییرات
            $this->refreshFamilyInList($family->id);

            // به‌روزرسانی لیست اصلی خانواده‌ها برای نمایش فوری تغییرات
            $this->updateFamilyInMainList($family->id);

            $this->dispatch('family-data-updated', [
                'family_id' => $family->id,
                'acceptance_criteria' => $family->acceptance_criteria
            ]);

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
     * بهروزرسانی خانواده مشخص در لیست families برای نمایش فوری تغییرات
     * @param int $familyId
     * @return void
     */
    protected function refreshFamilyInList($familyId)
    {
        // اگر لیست families در کامپوننت وجود دارد، آن خانواده را به‌روزرسانی کن
        try {
            // این method برای refresh کردن داده‌های کش شده کامپوننت است
            $this->clearCache();

            Log::info('Family refreshed in component list', [
                'family_id' => $familyId,
                'component' => 'FamilySearch'
            ]);
        } catch (\Exception $e) {
            Log::error('Error refreshing family in list', [
                'family_id' => $familyId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * دریافت پیام خطای Authorization بر اساس wizard_status خانواده
     * @param Family $family
     * @return string
     */
    protected function getAuthorizationErrorMessage($family)
    {
        $wizardStatus = $family->wizard_status;

        // استفاده از enum برای دریافت برچسب فارسی
        try {
            if ($wizardStatus) {
                // بررسی اینکه آیا قبلاً یک enum instance است یا خیر
                if ($wizardStatus instanceof \App\Enums\InsuranceWizardStep) {
                    $statusEnum = $wizardStatus;
                    $wizardStatusValue = $wizardStatus->value;
                } else {
                    $statusEnum = \App\Enums\InsuranceWizardStep::from($wizardStatus);
                    $wizardStatusValue = $wizardStatus;
                }
                $statusLabel = $statusEnum->label();

                // پیام‌های مختلف بر اساس وضعیت
                return match($wizardStatusValue) {
                    'pending' => 'خطای غیرمنتظره: شما باید بتوانید این خانواده را ویرایش کنید',
                    'reviewing' => "این خانواده در مرحله {$statusLabel} است و فقط ادمین می‌تواند ویرایش کند",
                    'share_allocation' => "این خانواده در مرحله {$statusLabel} است و فقط ادمین می‌تواند ویرایش کند",
                    'approved' => "این خانواده تایید شده ({$statusLabel}) و فقط ادمین می‌تواند ویرایش کند",
                    'excel_upload' => "این خانواده در انتظار صدور بیمه ({$statusLabel}) و فقط ادمین می‌تواند ویرایش کند",
                    'insured' => "این خانواده بیمه شده ({$statusLabel}) و فقط ادمین می‌تواند ویرایش کند",
                    'renewal' => "این خانواده در مرحله تمدید ({$statusLabel}) و فقط ادمین می‌تواند ویرایش کند",
                    'rejected' => "این خانواده رد شده ({$statusLabel}) و فقط ادمین می‌تواند ویرایش کند",
                    default => "این خانواده در مرحله {$statusLabel} است و فقط ادمین می‌تواند ویرایش کند"
                };
            }
        } catch (\Exception $e) {
            Log::error('Error getting wizard status label', [
                'wizard_status' => $wizardStatus,
                'error' => $e->getMessage()
            ]);
        }

        // پیام پیش‌فرض اگر wizard_status خالی یا نامعتبر باشد
        return 'شما مجوز ویرایش این خانواده را ندارید. فقط ادمین می‌تواند ویرایش کند';
    }

    /**
     * بهروزرسانی خانواده خاص در لیست اصلی خانواده‌ها
     * @param int $familyId
     * @return void
     */
    protected function updateFamilyInMainList($familyId)
    {
        try {
            // بازیابی داده‌های جدید خانواده با تمام روابط
            $updatedFamily = Family::with([
                'head', 'province', 'city', 'district', 'region', 'charity', 'organization', 'members'
            ])->find($familyId);

            if (!$updatedFamily) {
                Log::warning('Family not found for update', ['family_id' => $familyId]);
                return;
            }

            // وادار کردن خانواده به refresh از دیتابیس تا داده‌های جدید بارگیری شوند
            $updatedFamily->refresh();
            $updatedFamily->load(['members', 'head', 'province', 'city', 'district', 'region', 'charity', 'organization']);

            // اگر property families وجود دارد، آن را به‌روزرسانی کن
            if (property_exists($this, 'families') && !empty($this->families)) {
                $this->families = $this->families->map(function($family) use ($updatedFamily) {
                    if ($family->id === $updatedFamily->id) {
                        Log::info('Replacing family in collection', [
                            'family_id' => $updatedFamily->id,
                            'old_acceptance_criteria' => $family->acceptance_criteria ?? 'NULL',
                            'new_acceptance_criteria' => $updatedFamily->acceptance_criteria ?? 'NULL'
                        ]);
                        return $updatedFamily;
                    }
                    return $family;
                });
            }

            // به‌روزرسانی familyMembers اگر خانواده باز است
            if ($this->expandedFamily === $familyId && !empty($this->familyMembers)) {
                $this->familyMembers = $updatedFamily->members;
                Log::info('Family members updated in expanded view', [
                    'family_id' => $familyId,
                    'members_count' => $this->familyMembers->count()
                ]);
            }

            // اجبار به ریرندر مجدد کامپوننت برای نمایش تغییرات
            $this->dispatch('family-updated', [
                'familyId' => $familyId,
                'acceptanceCriteria' => $updatedFamily->acceptance_criteria
            ]);

            // ریفرش مجدد کامپوننت برای نمایش تغییرات
            $this->skipRender = false; // اطمینان از ریرندر مجدد

            Log::info('Family updated in main list', [
                'family_id' => $familyId,
                'updated_acceptance_criteria' => $updatedFamily->acceptance_criteria,
                'forced_refresh' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating family in main list', [
                'family_id' => $familyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
            'همسر' => 'همسر',
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
            // بررسی وجود فیلترهای مودال یا معیارهای انتخاب شده
            $currentFilters = $this->tempFilters ?? $this->activeFilters ?? [];
            $hasModalFilters = !empty($currentFilters);
            $hasSelectedCriteria = !empty($this->selectedCriteria) && count(array_filter($this->selectedCriteria)) > 0;

            if (!$hasModalFilters && !$hasSelectedCriteria) {
                $this->dispatch('notify', [
                    'message' => 'هیچ فیلتر یا معیاری برای ذخیره وجود ندارد',
                    'type' => 'warning'
                ]);
                return;
            }

            // ایجاد فیلتر ذخیره شده
            $savedFilter = SavedFilter::create([
                'name' => trim($name),
                'description' => $description ? trim($description) : null,
                'filters_config' => [
                    'filters' => $currentFilters,
                    'component_filters' => [
                        'search' => $this->search,
                        'status' => $this->status,
                        'province' => $this->province,
                        'city' => $this->city,
                        'deprivation_rank' => $this->deprivation_rank,
                        'family_rank_range' => $this->family_rank_range,
                        'specific_criteria' => $this->specific_criteria,
                        'charity' => $this->charity
                    ],
                    'rank_settings' => [
                        'selected_criteria' => $this->selectedCriteria ?? [],
                        'applied_scheme_id' => $this->appliedSchemeId
                    ],
                    'sort' => [
                        'field' => $this->sortField,
                        'direction' => $this->sortDirection
                    ]
                ],
                'filter_type' => 'family_search',
                'user_id' => Auth::id(),
                'organization_id' => auth()->user()->organization_id ?? null,
                'usage_count' => 0
            ]);

            Log::info('Filter saved successfully', [
                'filter_id' => $savedFilter->id,
                'name' => $name,
                'modal_filters_count' => count($currentFilters),
                'selected_criteria_count' => count(array_filter($this->selectedCriteria ?? [])),
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => "فیلتر '{$name}' با موفقیت ذخیره شد",
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving filter', [
                'name' => $name,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => 'خطا در ذخیره فیلتر: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * حذف فیلتر ذخیره شده
     * @param int $filterId
     * @return void
     */
    public function deleteSavedFilter($filterId)
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

            // بررسی دسترسی - فقط صاحب فیلتر می‌تواند آن را حذف کند
            if ($savedFilter->user_id !== Auth::id()) {
                $this->dispatch('notify', [
                    'message' => 'شما مجاز به حذف این فیلتر نیستید',
                    'type' => 'error'
                ]);
                return;
            }

            // حذف فیلتر
            $filterName = $savedFilter->name;
            $savedFilter->delete();

            Log::info('🗑️ Saved filter deleted successfully', [
                'filter_id' => $filterId,
                'filter_name' => $filterName,
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => 'فیلتر با موفقیت حذف شد',
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error deleting saved filter', [
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

    /**
     * بارگذاری فیلترهای ذخیره شده کاربر
     * @param string $filterType نوع فیلتر - 'family_search' یا 'rank_settings'
     * @return array
     */
    public function loadSavedFilters($filterType = 'family_search')
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return [];
            }

            // تعیین نوع فیلتر بر اساس پارامتر ورودی
            $actualFilterType = $filterType;

            // تبدیل نام‌های متداول به نوع فیلتر واقعی
            switch ($filterType) {
                case 'rank_modal':
                    $actualFilterType = 'rank_settings';
                    break;
                case 'family_search':
                case 'rank_settings':
                    $actualFilterType = $filterType;
                    break;
                default:
                    $actualFilterType = 'family_search';
                    break;
            }

            // فیلترهای قابل دسترس برای کاربر
            $query = SavedFilter::where('filter_type', $actualFilterType)
                ->where(function ($q) use ($user) {
                    // فیلترهای خود کاربر
                    $q->where('user_id', $user->id);

                    // اگر کاربر بیمه است، می‌تواند همه فیلترهای کاربران سازمانش را ببیند
                    if ($user->isInsurance() && $user->organization_id) {
                        $q->orWhereHas('user', function($userQuery) use ($user) {
                            $userQuery->where('organization_id', $user->organization_id);
                        });
                    }
                    // اگر کاربر خیریه است، فقط فیلترهای خودش را می‌بیند (که در بالا اضافه شده)
                })
                ->orderBy('usage_count', 'desc')
                ->orderBy('name')
                ->get()
                ->map(function ($filter) {
                    return [
                        'id' => $filter->id,
                        'name' => $filter->name,
                        'description' => $filter->description,
                        'visibility' => $filter->visibility,
                        'usage_count' => $filter->usage_count,
                        'created_at' => DateHelper::toJalali($filter->created_at, 'Y/m/d'),
                        'is_owner' => $filter->user_id === Auth::id()
                    ];
                });

            Log::debug('Loaded saved filters', [
                'requested_type' => $filterType,
                'actual_type' => $actualFilterType,
                'count' => count($query),
                'user_id' => Auth::id()
            ]);

            return $query->toArray();

        } catch (\Exception $e) {
            Log::error('Error loading saved filters', [
                'filter_type' => $filterType,
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

            // بررسی دسترسی بر اساس user_id و organization_id
            $user = Auth::user();
            $hasAccess = false;

            // فیلترهای خود کاربر
            if ($savedFilter->user_id === $user->id) {
                $hasAccess = true;
            }
            // فیلترهای سازمانی (اگر کاربر عضو همان سازمان باشد)
            elseif ($savedFilter->organization_id && $savedFilter->organization_id === $user->organization_id) {
                $hasAccess = true;
            }

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
                $this->filters = $filterData['filters'];
            }

            // اعمال فیلترهای کامپوننت
            if (isset($filterData['component_filters'])) {
                $componentFilters = $filterData['component_filters'];
                $this->search = $componentFilters['search'] ?? '';
                $this->status = $componentFilters['status'] ?? '';
                $this->province = $componentFilters['province'] ?? '';
                $this->city = $componentFilters['city'] ?? '';
                $this->deprivation_rank = $componentFilters['deprivation_rank'] ?? '';
                $this->family_rank_range = $componentFilters['family_rank_range'] ?? '';
                $this->specific_criteria = $componentFilters['specific_criteria'] ?? '';
                $this->charity = $componentFilters['charity'] ?? '';
            }

            // اعمال تنظیمات رتبه‌بندی
            if (isset($filterData['rank_settings'])) {
                $rankSettings = $filterData['rank_settings'];
                $this->selectedCriteria = $rankSettings['selected_criteria'] ?? [];
                $this->appliedSchemeId = $rankSettings['applied_scheme_id'] ?? null;
            }

            // اعمال تنظیمات سورت
            if (isset($filterData['sort'])) {
                $this->sortField = $filterData['sort']['field'] ?? 'created_at';
                $this->sortDirection = $filterData['sort']['direction'] ?? 'desc';
            }

            // افزایش شمارنده استفاده
            $savedFilter->increment('usage_count');
            $savedFilter->update(['last_used_at' => now()]);

            // بازنشانی صفحه و پاک کردن کش
            $this->resetPage();
            $this->clearCache();

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

    /**
     * به‌روزرسانی فیلتر ذخیره شده
     * @param int $filterId
     * @param string $name
     * @param string|null $description
     * @param string $visibility
     * @return void
     */
    public function updateFilter($filterId, $name, $description = null, $visibility = 'private')
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

            // فقط صاحب فیلتر می‌تواند آن را به‌روزرسانی کند
            if ($savedFilter->user_id !== Auth::id()) {
                $this->dispatch('notify', [
                    'message' => 'شما فقط می‌توانید فیلترهای خود را ویرایش کنید',
                    'type' => 'error'
                ]);
                return;
            }

            // به‌روزرسانی داده‌های فیلتر با فیلترهای فعلی
            $currentFilters = $this->tempFilters ?? $this->activeFilters ?? [];

            $savedFilter->update([
                'name' => trim($name),
                'description' => $description ? trim($description) : null,
                'visibility' => $visibility,
                'filters_config' => [
                    'filters' => $currentFilters,
                    'component_filters' => [
                        'search' => $this->search,
                        'status' => $this->status,
                        'province' => $this->province,
                        'city' => $this->city,
                        'deprivation_rank' => $this->deprivation_rank,
                        'family_rank_range' => $this->family_rank_range,
                        'specific_criteria' => $this->specific_criteria,
                        'charity' => $this->charity
                    ],
                    'rank_settings' => [
                        'selected_criteria' => $this->selectedCriteria ?? [],
                        'applied_scheme_id' => $this->appliedSchemeId
                    ],
                    'sort' => [
                        'field' => $this->sortField,
                        'direction' => $this->sortDirection
                    ]
                ]
            ]);

            Log::info('Filter updated successfully', [
                'filter_id' => $filterId,
                'name' => $name,
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => "فیلتر '{$name}' با موفقیت به‌روزرسانی شد",
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating filter', [
                'filter_id' => $filterId,
                'name' => $name,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => 'خطا در به‌روزرسانی فیلتر: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * کپی فیلتر برای کاربر جاری
     * @param int $filterId
     * @return void
     */
    public function duplicateFilter($filterId)
    {
        try {
            $originalFilter = SavedFilter::find($filterId);
            if (!$originalFilter) {
                $this->dispatch('notify', [
                    'message' => 'فیلتر مورد نظر یافت نشد',
                    'type' => 'error'
                ]);
                return;
            }

            // بررسی دسترسی
            $user = Auth::user();
            $hasAccess = false;

            if ($originalFilter->visibility === 'private' && $originalFilter->user_id === $user->id) {
                $hasAccess = true;
            } elseif ($originalFilter->visibility === 'organization' &&
                     $originalFilter->organization_id === $user->organization_id) {
                $hasAccess = true;
            } elseif ($originalFilter->visibility === 'public') {
                $hasAccess = true;
            }

            if (!$hasAccess) {
                $this->dispatch('notify', [
                    'message' => 'شما به این فیلتر دسترسی ندارید',
                    'type' => 'error'
                ]);
                return;
            }

            // ایجاد کپی از فیلتر
            $newFilterName = $originalFilter->name . ' (کپی)';
            $duplicatedFilter = SavedFilter::create([
                'name' => $newFilterName,
                'description' => $originalFilter->description,
                'filters_config' => $originalFilter->filters_config,
                'filter_type' => $originalFilter->filter_type,
                'visibility' => 'private', // کپی‌ها همیشه خصوصی هستند
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'usage_count' => 0
            ]);

            Log::info('Filter duplicated successfully', [
                'original_filter_id' => $filterId,
                'new_filter_id' => $duplicatedFilter->id,
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => "کپی فیلتر '{$newFilterName}' با موفقیت ایجاد شد",
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('Error duplicating filter', [
                'filter_id' => $filterId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => 'خطا در کپی کردن فیلتر: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * حذف یک معیار پذیرش از لیست آرایه
     * برای استفاده در multi-select dropdown
     *
     * @deprecated This method is no longer used after refactoring to MultiSelect component.
     *             The component handles toggling internally. Kept for backward compatibility.
     */
    public function removeProblemType($key)
    {
        Log::info('Removing problem type', [
            'key_to_remove' => $key,
            'current_array' => $this->editingMemberData['problem_type'] ?? 'not_set',
            'member_id' => $this->editingMemberId
        ]);

        if (isset($this->editingMemberData['problem_type']) && is_array($this->editingMemberData['problem_type'])) {
            // حذف کلید مشخص
            $this->editingMemberData['problem_type'] = array_filter(
                $this->editingMemberData['problem_type'],
                function($item) use ($key) {
                    return (string)$item !== (string)$key; // اطمینان از مقایسه رشته‌ای
                }
            );

            // بازنشانی کلیدهای آرایه و حذف تکراری‌ها
            $this->editingMemberData['problem_type'] = array_unique(array_values($this->editingMemberData['problem_type']));

            Log::info('Problem type removed successfully', [
                'remaining_array' => $this->editingMemberData['problem_type'],
                'count' => count($this->editingMemberData['problem_type'])
            ]);
        } else {
            Log::warning('Cannot remove problem type - array not found or invalid', [
                'editingMemberData' => $this->editingMemberData ?? 'not_set'
            ]);
        }
    }

    /**
     * اضافه کردن معیار پذیرش جدید با بررسی تکرار
     *
     * @deprecated This method is no longer used after refactoring to MultiSelect component.
     *             The component handles toggling internally. Kept for backward compatibility.
     * @param string $key
     * @return void
     */
    public function addProblemType($key)
    {
        if (!isset($this->editingMemberData['problem_type'])) {
            $this->editingMemberData['problem_type'] = [];
        }

        // بررسی تکرار قبل از اضافه کردن
        if (!in_array($key, $this->editingMemberData['problem_type'])) {
            $this->editingMemberData['problem_type'][] = $key;

            // حذف احتمالی تکراری‌ها (بدون sort برای حفظ insertion order)
            $this->editingMemberData['problem_type'] = array_unique($this->editingMemberData['problem_type']);
            // sort() حذف شد: ترتیب اضافه شدن حفظ می‌شود

            Log::info('Problem type added successfully', [
                'added_key' => $key,
                'current_array' => $this->editingMemberData['problem_type'],
                'member_id' => $this->editingMemberId
            ]);
        }
    }

    /**
     * به‌روزرسانی خودکار problem_type برای حذف تکراری‌ها در زمان واقعی
     * This hook fires when the MultiSelect component updates the parent's editingMemberData.problem_type via wire:model.
     * Provides automatic deduplication.
     * @param mixed $value
     * @return void
     */
    public function updatedEditingMemberDataProblemType($value)
    {
        if (is_array($value)) {
            // حذف مقادیر خالی و تکراری
            $cleanedArray = array_filter($value, function($item) {
                return !is_null($item) && trim((string)$item) !== '';
            });

            $cleanedArray = array_unique($cleanedArray);
            $cleanedArray = array_values($cleanedArray);
            // sort() حذف شد: ترتیب insertion order حفظ می‌شود (به جای comparison sorted)

            // Comparison بدون sort - بررسی count و مقادیر
            $reindexedOriginal = array_values($value);

            if ($cleanedArray !== $reindexedOriginal) {
                $this->editingMemberData['problem_type'] = $cleanedArray;

                Log::info('Problem type array cleaned automatically', [
                    'original_count' => count($value),
                    'cleaned_count' => count($cleanedArray),
                    'removed_duplicates' => count($value) - count($cleanedArray),
                    'member_id' => $this->editingMemberId
                ]);
            }

            // Dispatch event برای Alpine.js
            $this->dispatch('problem-types-updated', [
                'count' => count($this->editingMemberData['problem_type'])
            ]);
        }
    }

}
