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

    // Ã˜Â§Ã˜Â¶Ã˜Â§Ã™ÂÃ™â€¡ ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  property Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã™Ë†Ã˜Â±Ã˜Â¯ Ã™â€ Ã›Å’Ã˜Â§Ã˜Â² Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â¬Ã˜ÂºÃ˜Â±Ã˜Â§Ã™ÂÃ›Å’Ã˜Â§Ã›Å’Ã›Å’
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
    public $page = 1; // Ã™â€¦Ã˜ÂªÃ˜ÂºÃ›Å’Ã˜Â± Ã™â€¦Ã™Ë†Ã˜Â±Ã˜Â¯ Ã™â€ Ã›Å’Ã˜Â§Ã˜Â² Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™Â¾Ã›Å’Ã˜Â¬Ã›Å’Ã™â€ Ã›Å’Ã˜Â´Ã™â€  Ã™â€žÃ›Å’Ã™Ë†Ã˜Â§Ã›Å’Ã˜Â±
    public $isEditingMode = false; // Ã™â€¦Ã˜ÂªÃ˜ÂºÃ›Å’Ã˜Â± Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ ÃšÂ©Ã™â€ Ã˜ÂªÃ˜Â±Ã™â€ž Ã˜Â­Ã˜Â§Ã™â€žÃ˜Âª Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ Ã™ÂÃ˜Â±Ã™â€¦

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

    // Ã˜Â§Ã˜Â¶Ã˜Â§Ã™ÂÃ™â€¡ ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã™Â¾Ã˜Â±Ã˜Â§Ã™Â¾Ã˜Â±Ã˜ÂªÃ›Å’Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã™Ë†Ã˜Â±Ã˜Â¯ Ã™â€ Ã›Å’Ã˜Â§Ã˜Â²
    public $rankingSchemes = [];
    public $availableCriteria = [];

    // Ã™Â¾Ã˜Â±Ã˜Â§Ã™Â¾Ã˜Â±Ã˜ÂªÃ›Å’Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯ Ã˜Â³Ã›Å’Ã˜Â³Ã˜ÂªÃ™â€¦ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã™Â¾Ã™Ë†Ã›Å’Ã˜Â§
    public $selectedSchemeId = null;
    public array $schemeWeights = [];
    public $newSchemeName = '';
    public $newSchemeDescription = '';
    public $appliedSchemeId = null;

    // Ã™â€¦Ã˜Â¯Ã›Å’Ã˜Â±Ã›Å’Ã˜Âª Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™Â¾Ã›Å’Ã˜Â´Ã˜Â±Ã™ÂÃ˜ÂªÃ™â€¡
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
            $this->dispatch('error', 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€ Ã˜Â´Ã˜Â§Ã™â€ Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§');
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
     * Ã˜Â­Ã˜Â°Ã™Â Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â§Ã˜Â² Ã™â€žÃ›Å’Ã˜Â³Ã˜Âª Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã™Ë†Ã™â€šÃ˜Âª
     * @param int $index
     * @return void
     */
    public function removeFilter($index)
    {
        if (isset($this->tempFilters[$index])) {
            unset($this->tempFilters[$index]);
            // Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€ Ã™Ë†Ã›Å’Ã˜Â³Ã›Å’ Ã˜Â§Ã›Å’Ã™â€ Ã˜Â¯ÃšÂ©Ã˜Â³Ã¢â‚¬Å’Ã™â€¡Ã˜Â§ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â­Ã™ÂÃ˜Â¸ Ã˜ÂªÃ˜Â±Ã˜ÂªÃ›Å’Ã˜Â¨
            $this->tempFilters = array_values($this->tempFilters);

            // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  ÃšÂ©Ã˜Â´ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã›Å’Ã˜Â±Ã›Å’ Ã™â€¦Ã˜Â¬Ã˜Â¯Ã˜Â¯ Ã™â€ Ã˜ÂªÃ˜Â§Ã›Å’Ã˜Â¬
            $this->clearFamiliesCache();

            Log::info('Ã°Å¸â€”â€˜Ã¯Â¸Â Filter removed', [
                'index' => $index,
                'remaining_filters_count' => count($this->tempFilters),
                'user_id' => Auth::id()
            ]);
        }
    }

    // New ranking properties
    public $showRankModal = false;
    public $rankFilters = [];

    // Ã˜Â§Ã˜Â¶Ã˜Â§Ã™ÂÃ™â€¡ ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã™â€¦Ã˜ÂªÃ˜ÂºÃ›Å’Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™ÂÃ˜Â±Ã™â€¦ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯
    public $rankSettingName = '';
    public $rankSettingDescription = '';
    public $rankSettingWeight = 5;
    public $rankSettingColor = '#60A5FA';
    public $rankSettingNeedsDoc = true;

    // Ã™â€¦Ã˜ÂªÃ˜ÂºÃ›Å’Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã™Ë†Ã˜Â±Ã˜Â¯ Ã™â€ Ã›Å’Ã˜Â§Ã˜Â² Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™â€¦Ã™Ë†Ã˜Â¯Ã˜Â§Ã™â€ž Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯
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

        // Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã˜Â¯Ã˜Â± Ã˜Â§Ã˜Â¨Ã˜ÂªÃ˜Â¯Ã˜Â§Ã›Å’ Ã™â€žÃ™Ë†Ã˜Â¯ Ã˜ÂµÃ™ÂÃ˜Â­Ã™â€¡
        $this->loadRankSettings();

        // Ã™â€¦Ã™â€šÃ˜Â¯Ã˜Â§Ã˜Â±Ã˜Â¯Ã™â€¡Ã›Å’ Ã˜Â§Ã™Ë†Ã™â€žÃ›Å’Ã™â€¡ Ã™â€¦Ã˜ÂªÃ˜ÂºÃ›Å’Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’
        $this->rankingSchemes = \App\Models\RankingScheme::orderBy('name')->get();
        $this->availableCriteria = \App\Models\RankSetting::where('is_active', true)->orderBy('sort_order')->get();

        // Ã™â€¦Ã™â€šÃ˜Â¯Ã˜Â§Ã˜Â±Ã˜Â¯Ã™â€¡Ã›Å’ Ã˜Â§Ã™Ë†Ã™â€žÃ›Å’Ã™â€¡ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã™Ë†Ã˜Â¯Ã˜Â§Ã™â€žÃ›Å’ - Ã˜Â­Ã˜ÂªÃ™â€¦Ã˜Â§Ã™â€¹ Ã˜Â¢Ã˜Â±Ã˜Â§Ã›Å’Ã™â€¡ Ã˜Â®Ã˜Â§Ã™â€žÃ›Å’
        $this->tempFilters = [];
        $this->activeFilters = [];

        // Ã™â€¦Ã™â€šÃ˜Â¯Ã˜Â§Ã˜Â±Ã˜Â¯Ã™â€¡Ã›Å’ Ã˜Â§Ã™Ë†Ã™â€žÃ›Å’Ã™â€¡ Ã™ÂÃ˜Â±Ã™â€¦ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯
        $this->resetRankSettingForm();

        // Ã˜Â§ÃšÂ¯Ã˜Â± session Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â¢Ã™Â¾Ã™â€žÃ™Ë†Ã˜Â¯ Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã˜Â¯Ã˜Â§Ã˜Â±Ã˜Â¯Ã˜Å’ ÃšÂ©Ã˜Â´ Ã˜Â±Ã˜Â§ Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã™â€ 
        if (session('success') && session('results')) {
            $this->clearFamiliesCache();
            cache()->forget('families_query_' . Auth::id());
        }

        // Ã˜ÂªÃ˜Â³Ã˜Âª Ã˜Â§Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ž Ã™â€ Ã™Ë†Ã˜ÂªÃ›Å’Ã™ÂÃ›Å’ÃšÂ©Ã›Å’Ã˜Â´Ã™â€ 
        $this->dispatch('notify', [
            'message' => 'Ã˜ÂµÃ™ÂÃ˜Â­Ã™â€¡ Ã˜Â¬Ã˜Â³Ã˜ÂªÃ˜Â¬Ã™Ë†Ã›Å’ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§ Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ Ã˜Â´Ã˜Â¯',
            'type' => 'success'
        ]);
    }

    /**
     * Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  ÃšÂ©Ã˜Â´ Ã˜Â¬Ã˜Â³Ã˜ÂªÃ˜Â¬Ã™Ë†Ã›Å’ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§
     */
    public function clearFamiliesCache()
    {
        try {
            // ÃšÂ©Ã˜Â´ Ã™ÂÃ˜Â¹Ã™â€žÃ›Å’ Ã˜Â±Ã˜Â§ Ã™Â¾Ã˜Â§ÃšÂ© Ã™â€¦Ã›Å’Ã¢â‚¬Å’ÃšÂ©Ã™â€ Ã›Å’Ã™â€¦
            cache()->forget($this->getCacheKey());

        } catch (\Exception $e) {
        }
    }
    public function render()
    {
        try {
            Log::debug('Ã°Å¸Å½Â¬ FamilySearch render started', [
                'search' => $this->search,
                'status' => $this->status,
                'page' => $this->page,
                'per_page' => $this->perPage,
                'active_filters' => $this->activeFilters,
                'temp_filters' => $this->tempFilters
            ]);

            // Ã˜Â§Ã˜Â³Ã˜ÂªÃ™ÂÃ˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â§Ã˜Â² ÃšÂ©Ã˜Â´ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â¨Ã™â€¡Ã˜Â¨Ã™Ë†Ã˜Â¯ Ã˜Â¹Ã™â€¦Ã™â€žÃšÂ©Ã˜Â±Ã˜Â¯
            $cacheKey = $this->getCacheKey();

            $families = Cache::remember($cacheKey, 300, function () {
                $queryBuilder = $this->buildFamiliesQuery();

                // Ã™â€žÃ˜Â§ÃšÂ¯ SQL Ã™â€ Ã™â€¡Ã˜Â§Ã›Å’Ã›Å’ Ã˜Â¯Ã˜Â±Ã˜Â³Ã˜Âª Ã™â€šÃ˜Â¨Ã™â€ž Ã˜Â§Ã˜Â² paginate
                $finalSql = $queryBuilder->toSql();
                $finalBindings = $queryBuilder->getBindings();
                Log::info('Ã°Å¸â€Â¥ Final SQL before paginate', [
                    'sql' => $finalSql,
                    'bindings' => $finalBindings,
                    'count_query' => str_replace('select `families`.*', 'select count(*) as aggregate', $finalSql)
                ]);

                // Ã˜Â§Ã˜Â·Ã™â€¦Ã›Å’Ã™â€ Ã˜Â§Ã™â€  Ã˜Â§Ã˜Â² paginate Ã™ÂÃ™â€šÃ˜Â· Ã˜Â±Ã™Ë†Ã›Å’ QueryBuilder/Eloquent
                if ($queryBuilder instanceof \Illuminate\Database\Eloquent\Builder ||
                    $queryBuilder instanceof \Illuminate\Database\Eloquent\Relations\Relation ||
                    $queryBuilder instanceof \Spatie\QueryBuilder\QueryBuilder) {
                    // Ã˜ÂªÃ˜Â¹Ã˜Â¯Ã˜Â§Ã˜Â¯ Ã˜Â±ÃšÂ©Ã™Ë†Ã˜Â±Ã˜Â¯Ã™â€¡Ã˜Â§ Ã˜Â±Ã˜Â§ Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ ÃšÂ©Ã™â€ 
                    $count = $queryBuilder->count();
                    Log::info('Ã°Å¸â€œÅ  Total records found', [
                        'count' => $count,
                        'with_filters' => $this->hasActiveFilters(),
                        'filters' => $this->activeFilters
                    ]);

                    return $queryBuilder->paginate($this->perPage);
                } else {
                    // Ã˜Â§Ã›Å’Ã˜Â¬Ã˜Â§Ã˜Â¯ paginator Ã˜Â®Ã˜Â§Ã™â€žÃ›Å’ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Collection Ã™â€¡Ã˜Â§
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

            // Ã™â€žÃ˜Â§ÃšÂ¯ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â¯Ã›Å’Ã˜Â¨Ã˜Â§ÃšÂ¯ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±
            Log::info('Ã°Å¸Å½Â¬ Rendering view with families', [
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
            Log::error('Ã¢ÂÅ’ Error in FamilySearch render', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'search' => $this->search,
                'status' => $this->status,
                'user_id' => Auth::id()
            ]);

            // Ã˜Â¨Ã˜Â§Ã˜Â²ÃšÂ¯Ã˜Â´Ã˜Âª Ã˜Â¨Ã™â€¡ Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã˜Â®Ã˜Â§Ã™â€žÃ›Å’ Ã˜Â¯Ã˜Â± Ã˜ÂµÃ™Ë†Ã˜Â±Ã˜Âª Ã˜Â®Ã˜Â·Ã˜Â§
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
     * Ã˜Â¯Ã˜Â±Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª Ã˜ÂªÃ˜Â¹Ã˜Â¯Ã˜Â§Ã˜Â¯ ÃšÂ©Ã™â€ž Ã˜Â§Ã˜Â¹Ã˜Â¶Ã˜Â§Ã›Å’ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã˜ÂµÃ™ÂÃ˜Â­Ã™â€¡ Ã™ÂÃ˜Â¹Ã™â€žÃ›Å’
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
            Log::error('Ã¢ÂÅ’ Error calculating total members in current page', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            return 0;
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
        // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  ÃšÂ©Ã˜Â´ Ã™â€¡Ã™â€ ÃšÂ¯Ã˜Â§Ã™â€¦ Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â± Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§
        $this->clearFamiliesCache();
    }

    public function updatingStatus()
    {
        $this->resetPage();
        // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  ÃšÂ©Ã˜Â´ Ã™â€¡Ã™â€ ÃšÂ¯Ã˜Â§Ã™â€¦ Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â± Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§
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
     * Ã˜Â±Ã™ÂÃ˜ÂªÃ™â€  Ã˜Â¨Ã™â€¡ Ã˜ÂµÃ™ÂÃ˜Â­Ã™â€¡ Ã˜Â¨Ã˜Â¹Ã˜Â¯Ã›Å’
     * @return void
     */
    public function nextPage()
    {
        $this->setPage($this->page + 1);
        $this->clearCache();
    }

    /**
     * Ã˜Â±Ã™ÂÃ˜ÂªÃ™â€  Ã˜Â¨Ã™â€¡ Ã˜ÂµÃ™ÂÃ˜Â­Ã™â€¡ Ã™â€šÃ˜Â¨Ã™â€žÃ›Å’
     * @return void
     */
    public function previousPage()
    {
        $this->setPage(max(1, $this->page - 1));
        $this->clearCache();
    }

    /**
     * Ã˜Â±Ã™ÂÃ˜ÂªÃ™â€  Ã˜Â¨Ã™â€¡ Ã˜ÂµÃ™ÂÃ˜Â­Ã™â€¡ Ã™â€¦Ã˜Â´Ã˜Â®Ã˜Âµ
     * @param int $page
     * @return void
     */
    public function gotoPage($page)
    {
        $this->setPage($page);
        $this->clearCache();
    }

    /**
     * Ã˜Â³Ã˜Â§Ã˜Â®Ã˜Âª ÃšÂ©Ã™Ë†Ã˜Â¦Ã˜Â±Ã›Å’ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§ Ã˜Â¨Ã˜Â§ Ã˜Â§Ã˜Â³Ã˜ÂªÃ™ÂÃ˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â§Ã˜Â² QueryBuilder
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    protected function buildFamiliesQuery()
    {
        try {
            Log::debug('Ã°Å¸Ââ€”Ã¯Â¸Â Building FamilySearch QueryBuilder', [
                'search' => $this->search,
                'status' => $this->status,
                'has_active_filters' => $this->hasActiveFilters()
            ]);

            // Ã˜Â³Ã˜Â§Ã˜Â®Ã˜Âª base query Ã˜Â¨Ã˜Â§ relations Ã™â€¦Ã™Ë†Ã˜Â±Ã˜Â¯ Ã™â€ Ã›Å’Ã˜Â§Ã˜Â²
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

            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã˜Â¬Ã˜Â§Ã˜Â² Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ QueryBuilder
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
                // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â³Ã™ÂÃ˜Â§Ã˜Â±Ã˜Â´Ã›Å’ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã™Ë† Ã™Ë†Ã˜Â²Ã™â€ Ã¢â‚¬Å’Ã˜Â¯Ã™â€¡Ã›Å’
                AllowedFilter::custom('ranking', new FamilyRankingFilter()),
                AllowedFilter::exact('ranking_scheme'),
                AllowedFilter::exact('ranking_weights'),
                // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â¬Ã˜Â³Ã˜ÂªÃ˜Â¬Ã™Ë†Ã›Å’ Ã™â€ Ã˜Â§Ã™â€¦ Ã˜Â³Ã˜Â±Ã™Â¾Ã˜Â±Ã˜Â³Ã˜Âª
                AllowedFilter::callback('head_name', function ($query, $value) {
                    $query->whereHas('head', function ($q) use ($value) {
                        $q->where('first_name', 'like', "%{$value}%")
                          ->orWhere('last_name', 'like', "%{$value}%");
                    });
                }),
                // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜ÂªÃ˜Â¹Ã˜Â¯Ã˜Â§Ã˜Â¯ Ã˜Â§Ã˜Â¹Ã˜Â¶Ã˜Â§
                AllowedFilter::callback('members_count', function ($query, $value) {
                    if (str_contains($value, '-')) {
                        [$min, $max] = explode('-', $value);
                        $query->havingRaw('members_count BETWEEN ? AND ?', [$min, $max]);
                    } elseif (is_numeric($value)) {
                        $query->havingRaw('members_count = ?', [$value]);
                    }
                }),
                // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡ Ã™â€¦Ã˜Â­Ã˜Â§Ã˜Â³Ã˜Â¨Ã™â€¡ Ã˜Â´Ã˜Â¯Ã™â€¡
                AllowedFilter::callback('calculated_rank_range', function ($query, $value) {
                    if (str_contains($value, '-')) {
                        [$min, $max] = explode('-', $value);
                        $query->whereBetween('calculated_rank', [$min, $max]);
                    } elseif (is_numeric($value)) {
                        $query->where('calculated_rank', '>=', $value);
                    }
                }),
                // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™â€¦Ã˜Â­Ã˜Â¯Ã™Ë†Ã˜Â¯Ã™â€¡ Ã˜ÂªÃ˜Â§Ã˜Â±Ã›Å’Ã˜Â® Ã˜Â¹Ã˜Â¶Ã™Ë†Ã›Å’Ã˜Âª
                AllowedFilter::callback('created_from', function ($query, $value) {
                    $query->where('families.created_at', '>=', $value);
                }),
                AllowedFilter::callback('created_to', function ($query, $value) {
                    $query->where('families.created_at', '<=', $value);
                }),
            ];

            // Ã˜Â³Ã™Ë†Ã˜Â±Ã˜ÂªÃ¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã˜Â¬Ã˜Â§Ã˜Â²
            $allowedSorts = [
                AllowedSort::field('created_at', 'families.created_at'),
                AllowedSort::field('updated_at', 'families.updated_at'),
                AllowedSort::field('family_code', 'families.family_code'),
                AllowedSort::field('status', 'families.status'),
                AllowedSort::field('wizard_status', 'families.wizard_status'),
                AllowedSort::field('members_count', 'members_count'),
                AllowedSort::field('calculated_rank', 'families.calculated_rank'),
                // Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã˜Â³Ã™ÂÃ˜Â§Ã˜Â±Ã˜Â´Ã›Å’ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã™Ë†Ã˜Â²Ã™â€ Ã¢â‚¬Å’Ã˜Â¯Ã˜Â§Ã˜Â±
                AllowedSort::custom('weighted_rank', new RankingSort()),
                // Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã™â€ Ã˜Â§Ã™â€¦ Ã˜Â³Ã˜Â±Ã™Â¾Ã˜Â±Ã˜Â³Ã˜Âª Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â±
                AllowedSort::callback('head_name', function ($query, $descending) {
                    $direction = $descending ? 'desc' : 'asc';
                    $query->leftJoin('people as head_person', 'families.head_id', '=', 'head_person.id')
                          ->orderBy('head_person.first_name', $direction)
                          ->orderBy('head_person.last_name', $direction);
                }),
            ];

            // Ã˜Â³Ã˜Â§Ã˜Â®Ã˜Âª QueryBuilder
            $queryBuilder = QueryBuilder::for($baseQuery)
                ->allowedFilters($allowedFilters)
                ->allowedSorts($allowedSorts);
                // ->defaultSort('families.created_at'); // Ã˜Â­Ã˜Â°Ã™Â Ãšâ€ Ã™Ë†Ã™â€  Ã˜Â¯Ã˜Â± applyComponentFilters Ã™â€¡Ã™â€¦ sort Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜Â´Ã™Ë†Ã˜Â¯

            // Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ ÃšÂ©Ã˜Â§Ã™â€¦Ã™Â¾Ã™Ë†Ã™â€ Ã™â€ Ã˜Âª
            $this->applyComponentFilters($queryBuilder);

            // Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã™Ë†Ã˜Â¯Ã˜Â§Ã™â€ž
            $queryBuilder = $this->convertModalFiltersToQueryBuilder($queryBuilder);

            // Ã™â€žÃ˜Â§ÃšÂ¯ SQL Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ debug
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
            Log::error('Ã¢ÂÅ’ Error in FamilySearch buildFamiliesQuery', [
                'search' => $this->search,
                'status' => $this->status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            // Ã˜Â¨Ã˜Â§Ã˜Â²ÃšÂ¯Ã˜Â´Ã˜Âª Ã˜Â¨Ã™â€¡ query Ã˜Â³Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¯Ã˜Â± Ã˜ÂµÃ™Ë†Ã˜Â±Ã˜Âª Ã˜Â®Ã˜Â·Ã˜Â§
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
     * Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ ÃšÂ©Ã˜Â§Ã™â€¦Ã™Â¾Ã™Ë†Ã™â€ Ã™â€ Ã˜Âª Ã˜Â¨Ã™â€¡ QueryBuilder
     * @param \Spatie\QueryBuilder\QueryBuilder $queryBuilder
     * @return void
     */
    protected function applyComponentFilters($queryBuilder)
    {
        try {
            Log::debug('Ã°Å¸Å½â€ºÃ¯Â¸Â Applying FamilySearch component filters', [
                'search' => $this->search,
                'status' => $this->status,
                'province' => $this->province,
                'city' => $this->city
            ]);

            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¬Ã˜Â³Ã˜ÂªÃ˜Â¬Ã™Ë†Ã›Å’ Ã˜Â¹Ã™â€¦Ã™Ë†Ã™â€¦Ã›Å’ - Ã˜Â¬Ã˜Â³Ã˜ÂªÃ˜Â¬Ã™Ë† Ã˜Â¯Ã˜Â± Ã˜ÂªÃ™â€¦Ã˜Â§Ã™â€¦ Ã™ÂÃ›Å’Ã™â€žÃ˜Â¯Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã™Ë† Ã˜Â§Ã˜Â¹Ã˜Â¶Ã˜Â§
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
                Log::debug('Ã¢Å“â€¦ Enhanced search filter applied', ['search' => $this->search]);
            }

            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™Ë†Ã˜Â¶Ã˜Â¹Ã›Å’Ã˜Âª
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
                        // Ã˜Â¬Ã˜Â³Ã˜ÂªÃ˜Â¬Ã™Ë† Ã˜Â¨Ã˜Â§ Ã˜ÂªÃ™â€¦Ã˜Â§Ã™â€¦ Ã™â€¦Ã™â€šÃ˜Â§Ã˜Â¯Ã›Å’Ã˜Â± Ã™â€¦Ã™â€¦ÃšÂ©Ã™â€  (Ã™ÂÃ˜Â§Ã˜Â±Ã˜Â³Ã›Å’ Ã™Ë† Ã˜Â§Ã™â€ ÃšÂ¯Ã™â€žÃ›Å’Ã˜Â³Ã›Å’)
                        $q->whereJsonContains('problem_type', 'Ã˜Â¨Ã›Å’Ã™â€¦Ã˜Â§Ã˜Â±Ã›Å’ Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â®Ã˜Â§Ã˜Âµ')
                          ->orWhereJsonContains('problem_type', 'Ã˜Â¨Ã›Å’Ã™â€¦Ã˜Â§Ã˜Â±Ã›Å’ Ã˜Â®Ã˜Â§Ã˜Âµ')
                          ->orWhereJsonContains('problem_type', 'special_disease')
                          ->orWhereJsonContains('problem_type', 'addiction')
                          ->orWhereJsonContains('problem_type', 'Ã˜Â§Ã˜Â¹Ã˜ÂªÃ›Å’Ã˜Â§Ã˜Â¯')
                          ->orWhereJsonContains('problem_type', 'work_disability')
                          ->orWhereJsonContains('problem_type', 'Ã˜Â§Ã˜Â² ÃšÂ©Ã˜Â§Ã˜Â± Ã˜Â§Ã™ÂÃ˜ÂªÃ˜Â§Ã˜Â¯ÃšÂ¯Ã›Å’')
                          ->orWhereJsonContains('problem_type', 'unemployment')
                          ->orWhereJsonContains('problem_type', 'Ã˜Â¨Ã›Å’ÃšÂ©Ã˜Â§Ã˜Â±Ã›Å’');
                    });
                } else {
                    $queryBuilder->where('status', $this->status);
                }
                Log::debug('Ã¢Å“â€¦ Status filter applied', ['status' => $this->status]);
            }

            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â§Ã˜Â³Ã˜ÂªÃ˜Â§Ã™â€ 
            if (!empty($this->province)) {
                $queryBuilder->where('province_id', $this->province);
                Log::debug('Ã¢Å“â€¦ Province filter applied', ['province' => $this->province]);
            }

            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â´Ã™â€¡Ã˜Â±
            if (!empty($this->city)) {
                $queryBuilder->where('city_id', $this->city);
                Log::debug('Ã¢Å“â€¦ City filter applied', ['city' => $this->city]);
            }

            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡ Ã™â€¦Ã˜Â­Ã˜Â±Ã™Ë†Ã™â€¦Ã›Å’Ã˜Âª Ã˜Â§Ã˜Â³Ã˜ÂªÃ˜Â§Ã™â€ 
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
                Log::debug('Ã¢Å“â€¦ Deprivation rank filter applied', ['deprivation_rank' => $this->deprivation_rank]);
            }

            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€¡ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡ Ã™â€¦Ã˜Â­Ã˜Â±Ã™Ë†Ã™â€¦Ã›Å’Ã˜Âª Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡
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
                Log::debug('Ã¢Å“â€¦ Family rank range filter applied', ['family_rank_range' => $this->family_rank_range]);
            }

            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â®Ã˜Â§Ã˜Âµ (Ã˜Â§Ã˜ÂµÃ™â€žÃ˜Â§Ã˜Â­ Ã˜Â´Ã˜Â¯Ã™â€¡ Ã™â€¦Ã˜Â§Ã™â€ Ã™â€ Ã˜Â¯ FamiliesApproval)
            if (!empty($this->specific_criteria)) {
                $criteriaIds = array_map('trim', explode(',', $this->specific_criteria));
                // Ã˜Â§ÃšÂ¯Ã˜Â± Ã™â€¦Ã™â€šÃ˜Â¯Ã˜Â§Ã˜Â± Ã˜Â±Ã˜Â´Ã˜ÂªÃ™â€¡Ã¢â‚¬Å’Ã˜Â§Ã›Å’ Ã˜Â§Ã˜Â³Ã˜Âª (Ã™â€¦Ã˜Â«Ã™â€žÃ˜Â§Ã™â€¹ Ã™â€ Ã˜Â§Ã™â€¦ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±)Ã˜Å’ Ã˜Â¢Ã™â€  Ã˜Â±Ã˜Â§ Ã˜Â¨Ã™â€¡ id Ã˜ÂªÃ˜Â¨Ã˜Â¯Ã›Å’Ã™â€ž ÃšÂ©Ã™â€ 
                if (!is_numeric($criteriaIds[0])) {
                    $criteriaIds = \App\Models\RankSetting::whereIn('name', $criteriaIds)->pluck('id')->toArray();
                }
                if (!empty($criteriaIds)) {
                    $rankSettingNames = \App\Models\RankSetting::whereIn('id', $criteriaIds)->pluck('name')->toArray();
                    $queryBuilder->where(function($q) use ($criteriaIds, $rankSettingNames) {
                        // Ã˜Â³Ã›Å’Ã˜Â³Ã˜ÂªÃ™â€¦ Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯: family_criteria
                        $q->whereHas('familyCriteria', function($subquery) use ($criteriaIds) {
                            $subquery->whereIn('rank_setting_id', $criteriaIds)
                                     ->where('has_criteria', true);
                        });
                        // Ã˜Â³Ã›Å’Ã˜Â³Ã˜ÂªÃ™â€¦ Ã™â€šÃ˜Â¯Ã›Å’Ã™â€¦Ã›Å’: rank_criteria
                        foreach ($rankSettingNames as $name) {
                            $q->orWhere('rank_criteria', 'LIKE', '%' . $name . '%');
                        }
                    });
                    Log::debug('Ã¢Å“â€¦ Specific criteria filter applied (by id)', ['criteria_ids' => $criteriaIds]);
                }
            }

            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â®Ã›Å’Ã˜Â±Ã›Å’Ã™â€¡ Ã™â€¦Ã˜Â¹Ã˜Â±Ã™Â
            if (!empty($this->charity)) {
                $queryBuilder->where('charity_id', $this->charity);
                Log::debug('Ã¢Å“â€¦ Charity filter applied', ['charity' => $this->charity]);
            }

            // Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª
            if (!empty($this->sortField) && !empty($this->sortDirection)) {
                $validSorts = ['created_at', 'updated_at', 'family_code', 'status', 'wizard_status', 'members_count', 'head_name'];
                if (in_array($this->sortField, $validSorts)) {
                    $direction = in_array($this->sortDirection, ['asc', 'desc']) ? $this->sortDirection : 'desc';

                    if ($this->sortField === 'head_name') {
                        // Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã˜Â®Ã˜Â§Ã˜Âµ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™â€ Ã˜Â§Ã™â€¦ Ã˜Â³Ã˜Â±Ã™Â¾Ã˜Â±Ã˜Â³Ã˜Âª
                        $queryBuilder->leftJoin('people as head_person', 'families.head_id', '=', 'head_person.id')
                                     ->orderBy('head_person.first_name', $direction)
                                     ->orderBy('head_person.last_name', $direction);
                    } else {
                        $fieldName = $this->sortField === 'members_count' ? 'members_count' : 'families.' . $this->sortField;
                        $queryBuilder->orderBy($fieldName, $direction);
                    }

                    Log::debug('Ã°Å¸â€Â§ Component sort applied', [
                        'sort_field' => $this->sortField,
                        'sort_direction' => $direction
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Ã¢ÂÅ’ Error applying FamilySearch component filters', [
                'search' => $this->search,
                'status' => $this->status,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
        }
    }

    /**
     * Ã˜ÂªÃ˜Â¨Ã˜Â¯Ã›Å’Ã™â€ž Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã™Ë†Ã˜Â¯Ã˜Â§Ã™â€ž Ã˜Â¨Ã™â€¡ QueryBuilder constraints Ã˜Â¨Ã˜Â§ Ã™Â¾Ã˜Â´Ã˜ÂªÃ›Å’Ã˜Â¨Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â§Ã˜Â² Ã˜Â¹Ã™â€¦Ã™â€žÃšÂ¯Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ AND/OR
     * @param \Spatie\QueryBuilder\QueryBuilder $queryBuilder
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    protected function convertModalFiltersToQueryBuilder($queryBuilder)
    {
        try {
            // Ã˜Â§Ã˜Â³Ã˜ÂªÃ™ÂÃ˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â§Ã˜Â² activeFilters ÃšÂ©Ã™â€¡ Ã˜ÂªÃ™Ë†Ã˜Â³Ã˜Â· Ã™â€¦Ã˜ÂªÃ˜Â¯ applyFilters Ã™â€šÃ˜Â¯Ã›Å’Ã™â€¦Ã›Å’ Ã™Â¾Ã˜Â± Ã˜Â´Ã˜Â¯Ã™â€¡
            $modalFilters = $this->activeFilters ?? $this->tempFilters ?? $this->filters ?? [];

            if (empty($modalFilters)) {
                return $queryBuilder;
            }

            Log::debug('Ã°Å¸Å½Â¯ Converting FamilySearch modal filters to QueryBuilder with AND/OR logic', [
                'filters_count' => count($modalFilters),
                'raw_filters' => $modalFilters,
                'user_id' => Auth::id()
            ]);

            // Ã˜Â¬Ã˜Â¯Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â²Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§ Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã˜Â¹Ã™â€¦Ã™â€žÃšÂ¯Ã˜Â± Ã™â€¦Ã™â€ Ã˜Â·Ã™â€šÃ›Å’
            $andFilters = [];
            $orFilters = [];

            foreach ($modalFilters as $filter) {
                // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã˜Â§Ã˜Â¹Ã˜ÂªÃ˜Â¨Ã˜Â§Ã˜Â± Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±
                if (empty($filter['type'])) {
                    continue;
                }

                $operator = $filter['operator'] ?? 'and';

                // Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ exists Ã™Ë† not_exists Ã™â€ Ã›Å’Ã˜Â§Ã˜Â²Ã›Å’ Ã˜Â¨Ã™â€¡ value Ã™â€ Ã˜Â¯Ã˜Â§Ã˜Â±Ã›Å’Ã™â€¦
                if ($operator !== 'exists' && $operator !== 'not_exists' && empty($filter['value'])) {
                    continue;
                }

                // Ã˜ÂªÃ˜Â¹Ã›Å’Ã›Å’Ã™â€  Ã™â€ Ã™Ë†Ã˜Â¹ Ã˜Â´Ã˜Â±Ã˜Â· Ã™â€¦Ã™â€ Ã˜Â·Ã™â€šÃ›Å’
                if ($operator === 'or') {
                    $orFilters[] = $filter;
                } else {
                    $andFilters[] = $filter;
                }
            }

            Log::debug('Ã°Å¸â€Â Final processed filters', [
                'and_filters' => $andFilters,
                'or_filters' => $orFilters,
                'user_id' => Auth::id()
            ]);

            // **Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã™Ë† Ã™Â¾Ã˜Â±Ã˜Â¯Ã˜Â§Ã˜Â²Ã˜Â´ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ special_disease Ãšâ€ Ã™â€ Ã˜Â¯ÃšÂ¯Ã˜Â§Ã™â€ Ã™â€¡ Ã˜Â¨Ã˜Â§ AND logic**
            $queryBuilder = $this->applySpecialDiseaseAndLogic($queryBuilder, $andFilters);

            // Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ AND Ã˜ÂºÃ›Å’Ã˜Â± special_disease
            foreach ($andFilters as $filter) {
                if (!in_array($filter['type'], ['special_disease', 'Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã™Â¾Ã˜Â°Ã›Å’Ã˜Â±Ã˜Â´'])) {
                    Log::debug('Ã°Å¸â€Â§ Applying AND filter', ['filter' => $filter]);
                    $queryBuilder = $this->applySingleFilter($queryBuilder, $filter, 'and');
                }
            }

            // Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ OR Ã˜Â¯Ã˜Â± Ã›Å’ÃšÂ© ÃšÂ¯Ã˜Â±Ã™Ë†Ã™â€¡
            if (!empty($orFilters)) {
                $queryBuilder = $queryBuilder->where(function($query) use ($orFilters) {
                    foreach ($orFilters as $index => $filter) {
                        if ($index === 0) {
                            // Ã˜Â§Ã™Ë†Ã™â€žÃ›Å’Ã™â€  Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± OR Ã˜Â¨Ã˜Â§ where Ã™â€¦Ã˜Â¹Ã™â€¦Ã™Ë†Ã™â€žÃ›Å’
                            $query = $this->applySingleFilter($query, $filter, 'where');
                        } else {
                            // Ã˜Â¨Ã™â€šÃ›Å’Ã™â€¡ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§ Ã˜Â¨Ã˜Â§ orWhere
                            $query = $this->applySingleFilter($query, $filter, 'or');
                        }
                    }
                    return $query;
                });
            }

            Log::info('Ã¢Å“â€¦ FamilySearch modal filters applied successfully', [
                'and_filters_count' => count($andFilters),
                'or_filters_count' => count($orFilters),
                'user_id' => Auth::id()
            ]);

            return $queryBuilder;

        } catch (\Exception $e) {
            Log::error('Ã¢ÂÅ’ Error applying FamilySearch modal filters', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return $queryBuilder;
        }
    }

    /**
     * Ã™Â¾Ã˜Â±Ã˜Â¯Ã˜Â§Ã˜Â²Ã˜Â´ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ special_disease Ãšâ€ Ã™â€ Ã˜Â¯ÃšÂ¯Ã˜Â§Ã™â€ Ã™â€¡ Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™â€ Ã˜Â·Ã™â€š AND
     * @param \Spatie\QueryBuilder\QueryBuilder $queryBuilder
     * @param array $andFilters
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    protected function applySpecialDiseaseAndLogic($queryBuilder, $andFilters)
    {
        try {
            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ special_disease
            $specialDiseaseFilters = array_filter($andFilters, function($filter) {
                return in_array($filter['type'], ['special_disease', 'Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã™Â¾Ã˜Â°Ã›Å’Ã˜Â±Ã˜Â´']) && !empty($filter['value']);
            });

            if (empty($specialDiseaseFilters)) {
                return $queryBuilder;
            }

            Log::debug('Ã°Å¸â€œÅ  Processing special_disease filters with AND logic', [
                'filters_count' => count($specialDiseaseFilters),
                'filters' => $specialDiseaseFilters
            ]);

            // **Ã™Â¾Ã˜Â±Ã˜Â¯Ã˜Â§Ã˜Â²Ã˜Â´ Ã˜Â±Ã˜Â´Ã˜ÂªÃ™â€¡ comma-separated Ã™Ë† Ã˜ÂªÃ˜Â¨Ã˜Â¯Ã›Å’Ã™â€ž Ã˜Â¨Ã™â€¡ Ã˜Â¢Ã˜Â±Ã˜Â§Ã›Å’Ã™â€¡**
            $allSelectedValues = [];
            foreach ($specialDiseaseFilters as $filter) {
                $filterValue = $filter['value'];

                // Ã˜Â§ÃšÂ¯Ã˜Â± Ã˜Â±Ã˜Â´Ã˜ÂªÃ™â€¡ Ã˜Â­Ã˜Â§Ã™Ë†Ã›Å’ Ã™Ë†Ã›Å’Ã˜Â±ÃšÂ¯Ã™Ë†Ã™â€ž Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯Ã˜Å’ Ã˜ÂªÃ™â€šÃ˜Â³Ã›Å’Ã™â€¦ ÃšÂ©Ã™â€ 
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

            Log::debug('Ã°Å¸â€Å½ Parsed special_disease values for AND logic', [
                'values' => $allSelectedValues,
                'count' => count($allSelectedValues)
            ]);

            // Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™â€¡Ã˜Â± Ã™â€¦Ã™â€šÃ˜Â¯Ã˜Â§Ã˜Â± Ã˜Â¬Ã˜Â¯Ã˜Â§ÃšÂ¯Ã˜Â§Ã™â€ Ã™â€¡Ã˜Å’ Ã›Å’ÃšÂ© whereHas Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž ÃšÂ©Ã™â€  (Ã™â€¦Ã™â€ Ã˜Â·Ã™â€š AND)
            foreach ($allSelectedValues as $value) {
                Log::debug('Ã°Å¸â€Å½ Applying AND whereHas for special_disease value', ['value' => $value]);

                $queryBuilder = $queryBuilder->whereHas('members', function($memberQuery) use ($value) {
                    // Ã˜ÂªÃ˜Â¨Ã˜Â¯Ã›Å’Ã™â€ž Ã˜Â¨Ã™â€¡ Ã™â€¦Ã™â€šÃ˜Â§Ã˜Â¯Ã›Å’Ã˜Â± Ã™â€¦Ã˜Â®Ã˜ÂªÃ™â€žÃ™Â (Ã™ÂÃ˜Â§Ã˜Â±Ã˜Â³Ã›Å’ Ã™Ë† Ã˜Â§Ã™â€ ÃšÂ¯Ã™â€žÃ›Å’Ã˜Â³Ã›Å’)
                    $persianValue = \App\Helpers\ProblemTypeHelper::englishToPersian($value);
                    $englishValue = \App\Helpers\ProblemTypeHelper::persianToEnglish($value);

                    $memberQuery->where(function($q) use ($value, $persianValue, $englishValue) {
                        $q->whereJsonContains('problem_type', $value)
                          ->orWhereJsonContains('problem_type', $persianValue)
                          ->orWhereJsonContains('problem_type', $englishValue);
                    });
                });
            }

            Log::info('Ã¢Å“â€¦ Special_disease AND logic applied successfully', [
                'values_applied' => $allSelectedValues,
                'filters_processed' => count($specialDiseaseFilters)
            ]);

            return $queryBuilder;

        } catch (\Exception $e) {
            Log::error('Ã¢ÂÅ’ Error applying special_disease AND logic', [
                'error' => $e->getMessage(),
                'filters' => $specialDiseaseFilters ?? [],
                'trace' => $e->getTraceAsString()
            ]);

            return $queryBuilder;
        }
    }

    /**
     * Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã›Å’ÃšÂ© Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™â€¦Ã™â€ Ã™ÂÃ˜Â±Ã˜Â¯
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

            // Ã™Â¾Ã˜Â±Ã˜Â¯Ã˜Â§Ã˜Â²Ã˜Â´ operators Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯
            $logicalOperator = $filter['logical_operator'] ?? 'and';
            $existenceOperator = $filter['existence_operator'] ?? 'equals';

            // Ã˜ÂªÃ˜Â¹Ã›Å’Ã›Å’Ã™â€  operator Ã™â€ Ã™â€¡Ã˜Â§Ã›Å’Ã›Å’ Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã˜Â´Ã˜Â±Ã˜Â·Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯
            $operator = $existenceOperator;
            if ($existenceOperator === 'equals') {
                // Ã˜Â§ÃšÂ¯Ã˜Â± Ã™â€¦Ã™â€šÃ˜Â¯Ã˜Â§Ã˜Â± Ã˜Â®Ã˜Â§Ã˜Âµ Ã˜Â§Ã˜Â³Ã˜ÂªÃ˜Å’ Ã˜Â§Ã˜Â² logical operator Ã˜Â§Ã˜Â³Ã˜ÂªÃ™ÂÃ˜Â§Ã˜Â¯Ã™â€¡ ÃšÂ©Ã™â€ 
                $operator = 'equals';
            }

            // Ã˜Â³Ã˜Â§Ã˜Â²ÃšÂ¯Ã˜Â§Ã˜Â±Ã›Å’ Ã˜Â¨Ã˜Â§ operator Ã™â€šÃ˜Â¯Ã›Å’Ã™â€¦Ã›Å’
            if (isset($filter['operator']) && in_array($filter['operator'], ['exists', 'not_exists', 'equals', 'and', 'or'])) {
                $operator = $filter['operator'];
                if ($operator === 'and' || $operator === 'or') {
                    $operator = 'equals';
                }
            }

            // Ã˜ÂªÃ˜Â¹Ã›Å’Ã›Å’Ã™â€  Ã™â€ Ã™Ë†Ã˜Â¹ Ã™â€¦Ã˜ÂªÃ˜Â¯ Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã˜Â¹Ã™â€¦Ã™â€žÃšÂ¯Ã˜Â± Ã™â€¦Ã™â€ Ã˜Â·Ã™â€šÃ›Å’ Ã™â€ Ã™â€¡Ã˜Â§Ã›Å’Ã›Å’
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
                            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â§Ã˜Â³Ã˜ÂªÃ˜Â§Ã™â€  Ã˜Â®Ã˜Â§Ã˜Âµ: families Ã˜Â¨Ã˜Â§ province_id Ã˜Â¨Ã˜Â±Ã˜Â§Ã˜Â¨Ã˜Â± Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™â€šÃ˜Â¯Ã˜Â§Ã˜Â± Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨Ã›Å’
                            $queryBuilder = $queryBuilder->$whereMethod('families.province_id', $filterValue);
                        } else {
                            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã™â€¡Ã˜Â± Ã˜Â§Ã˜Â³Ã˜ÂªÃ˜Â§Ã™â€ : families ÃšÂ©Ã™â€¡ province_id Ã˜Â¯Ã˜Â§Ã˜Â±Ã™â€ Ã˜Â¯
                            $queryBuilder = $queryBuilder->$whereMethod('families.province_id', '!=', null);
                        }
                    } elseif ($operator === 'not_exists') {
                        if (!empty($filterValue)) {
                            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â¹Ã˜Â¯Ã™â€¦ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â§Ã˜Â³Ã˜ÂªÃ˜Â§Ã™â€  Ã˜Â®Ã˜Â§Ã˜Âµ: families ÃšÂ©Ã™â€¡ province_id Ã˜Â¢Ã™â€ Ã¢â‚¬Å’Ã™â€¡Ã˜Â§ Ã˜Â¨Ã˜Â±Ã˜Â§Ã˜Â¨Ã˜Â± Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™â€šÃ˜Â¯Ã˜Â§Ã˜Â± Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨Ã›Å’ Ã™â€ Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯
                            $queryBuilder = $queryBuilder->$whereMethod('families.province_id', '!=', $filterValue);
                        } else {
                            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â¹Ã˜Â¯Ã™â€¦ Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã˜Â§Ã˜Â³Ã˜ÂªÃ˜Â§Ã™â€ : families ÃšÂ©Ã™â€¡ province_id Ã™â€ Ã˜Â¯Ã˜Â§Ã˜Â±Ã™â€ Ã˜Â¯
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
                            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â´Ã™â€¡Ã˜Â± Ã˜Â®Ã˜Â§Ã˜Âµ: families Ã˜Â¨Ã˜Â§ city_id Ã˜Â¨Ã˜Â±Ã˜Â§Ã˜Â¨Ã˜Â± Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™â€šÃ˜Â¯Ã˜Â§Ã˜Â± Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨Ã›Å’
                            $queryBuilder = $queryBuilder->$whereMethod('families.city_id', $filterValue);
                        } else {
                            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã™â€¡Ã˜Â± Ã˜Â´Ã™â€¡Ã˜Â±: families ÃšÂ©Ã™â€¡ city_id Ã˜Â¯Ã˜Â§Ã˜Â±Ã™â€ Ã˜Â¯
                            $queryBuilder = $queryBuilder->$whereMethod('families.city_id', '!=', null);
                        }
                    } elseif ($operator === 'not_exists') {
                        if (!empty($filterValue)) {
                            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â¹Ã˜Â¯Ã™â€¦ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â´Ã™â€¡Ã˜Â± Ã˜Â®Ã˜Â§Ã˜Âµ: families ÃšÂ©Ã™â€¡ city_id Ã˜Â¢Ã™â€ Ã¢â‚¬Å’Ã™â€¡Ã˜Â§ Ã˜Â¨Ã˜Â±Ã˜Â§Ã˜Â¨Ã˜Â± Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™â€šÃ˜Â¯Ã˜Â§Ã˜Â± Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨Ã›Å’ Ã™â€ Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯
                            $queryBuilder = $queryBuilder->$whereMethod('families.city_id', '!=', $filterValue);
                        } else {
                            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â¹Ã˜Â¯Ã™â€¦ Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã˜Â´Ã™â€¡Ã˜Â±: families ÃšÂ©Ã™â€¡ city_id Ã™â€ Ã˜Â¯Ã˜Â§Ã˜Â±Ã™â€ Ã˜Â¯
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
                            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â®Ã›Å’Ã˜Â±Ã›Å’Ã™â€¡ Ã˜Â®Ã˜Â§Ã˜Âµ: families Ã˜Â¨Ã˜Â§ charity_id Ã˜Â¨Ã˜Â±Ã˜Â§Ã˜Â¨Ã˜Â± Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™â€šÃ˜Â¯Ã˜Â§Ã˜Â± Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨Ã›Å’
                            $queryBuilder = $queryBuilder->$whereMethod('families.charity_id', $filterValue);
                        } else {
                            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã™â€¡Ã˜Â± Ã˜Â®Ã›Å’Ã˜Â±Ã›Å’Ã™â€¡: families ÃšÂ©Ã™â€¡ charity_id Ã˜Â¯Ã˜Â§Ã˜Â±Ã™â€ Ã˜Â¯
                            $queryBuilder = $queryBuilder->$whereMethod('families.charity_id', '!=', null);
                        }
                    } elseif ($operator === 'not_exists') {
                        if (!empty($filterValue)) {
                            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â¹Ã˜Â¯Ã™â€¦ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â®Ã›Å’Ã˜Â±Ã›Å’Ã™â€¡ Ã˜Â®Ã˜Â§Ã˜Âµ: families ÃšÂ©Ã™â€¡ charity_id Ã˜Â¢Ã™â€ Ã¢â‚¬Å’Ã™â€¡Ã˜Â§ Ã˜Â¨Ã˜Â±Ã˜Â§Ã˜Â¨Ã˜Â± Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™â€šÃ˜Â¯Ã˜Â§Ã˜Â± Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨Ã›Å’ Ã™â€ Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯
                            $queryBuilder = $queryBuilder->$whereMethod('families.charity_id', '!=', $filterValue);
                        } else {
                            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â¹Ã˜Â¯Ã™â€¦ Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã˜Â®Ã›Å’Ã˜Â±Ã›Å’Ã™â€¡: families ÃšÂ©Ã™â€¡ charity_id Ã™â€ Ã˜Â¯Ã˜Â§Ã˜Â±Ã™â€ Ã˜Â¯
                            $queryBuilder = $queryBuilder->$whereMethod('families.charity_id', null);
                        }
                    }
                    break;

                case 'members_count':
                    Log::debug('Ã°Å¸â€Â¢ Processing members_count filter', [
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
                    // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡ Ã™â€¦Ã˜Â­Ã˜Â±Ã™Ë†Ã™â€¦Ã›Å’Ã˜Âª
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
                case 'Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã™Â¾Ã˜Â°Ã›Å’Ã˜Â±Ã˜Â´':
                    // Ã™Â¾Ã˜Â´Ã˜ÂªÃ›Å’Ã˜Â¨Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â§Ã˜Â² Ã™â€¡Ã˜Â± Ã˜Â¯Ã™Ë† Ã™â€ Ã˜Â§Ã™â€¦ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â³Ã˜Â§Ã˜Â²ÃšÂ¯Ã˜Â§Ã˜Â±Ã›Å’
                    if ($operator === 'exists') {
                        // Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’Ã›Å’ ÃšÂ©Ã™â€¡ Ã˜Â­Ã˜Â¯Ã˜Â§Ã™â€šÃ™â€ž Ã›Å’ÃšÂ© Ã˜Â¹Ã˜Â¶Ã™Ë† Ã˜Â¯Ã˜Â§Ã˜Â±Ã˜Â§Ã›Å’ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã™Â¾Ã˜Â°Ã›Å’Ã˜Â±Ã˜Â´ Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯
                        $queryBuilder = $queryBuilder->$whereHasMethod('members', function($memberQuery) {
                            $memberQuery->whereNotNull('problem_type')
                                       ->where('problem_type', '!=', '[]')
                                       ->where('problem_type', '!=', 'null');
                        });
                    } elseif ($operator === 'not_exists') {
                        // Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’Ã›Å’ ÃšÂ©Ã™â€¡ Ã™â€¡Ã›Å’Ãšâ€  Ã˜Â¹Ã˜Â¶Ã™Ë†Ã›Å’ Ã˜Â¯Ã˜Â§Ã˜Â±Ã˜Â§Ã›Å’ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã™Â¾Ã˜Â°Ã›Å’Ã˜Â±Ã˜Â´ Ã™â€ Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯
                        $queryBuilder = $queryBuilder->$whereDoesntHaveMethod('members', function($memberQuery) {
                            $memberQuery->whereNotNull('problem_type')
                                       ->where('problem_type', '!=', '[]')
                                       ->where('problem_type', '!=', 'null');
                        });
                    } elseif (!empty($filterValue)) {
                        $queryBuilder = $queryBuilder->$whereMethod(function($q) use ($filterValue) {
                            // Ã˜Â¬Ã˜Â³Ã˜ÂªÃ˜Â¬Ã™Ë† Ã˜Â¯Ã˜Â± Ã˜Â§Ã˜Â¹Ã˜Â¶Ã˜Â§Ã›Å’ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã˜Â§ problem_type - Ã™Â¾Ã˜Â´Ã˜ÂªÃ›Å’Ã˜Â¨Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â§Ã˜Â² Ã˜ÂªÃ™â€¦Ã˜Â§Ã™â€¦ Ã™â€¦Ã™â€šÃ˜Â§Ã˜Â¯Ã›Å’Ã˜Â±
                            $q->whereHas('members', function($memberQuery) use ($filterValue) {
                                // Ã˜ÂªÃ˜Â¨Ã˜Â¯Ã›Å’Ã™â€ž Ã˜Â¨Ã™â€¡ Ã™â€¦Ã™â€šÃ˜Â§Ã˜Â¯Ã›Å’Ã˜Â± Ã™â€¦Ã˜Â®Ã˜ÂªÃ™â€žÃ™Â
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
            Log::error('Ã¢ÂÅ’ Error applying single filter in FamilySearch', [
                'filter_type' => $filter['type'] ?? 'unknown',
                'method' => $method,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return $queryBuilder;
        }
    }

    /**
     * Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¹Ã˜Â¯Ã˜Â¯Ã›Å’
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

        // Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜Â¯ members_count ÃšÂ©Ã™â€¡ Ã™ÂÃ›Å’Ã™â€žÃ˜Â¯ Ã™â€¦Ã˜Â­Ã˜Â§Ã˜Â³Ã˜Â¨Ã˜Â§Ã˜ÂªÃ›Å’ Ã˜Â§Ã˜Â³Ã˜ÂªÃ˜Å’ Ã˜Â¨Ã˜Â§Ã›Å’Ã˜Â¯ Ã˜Â§Ã˜Â² HAVING Ã›Å’Ã˜Â§ relation Ã˜Â§Ã˜Â³Ã˜ÂªÃ™ÂÃ˜Â§Ã˜Â¯Ã™â€¡ ÃšÂ©Ã™â€ Ã›Å’Ã™â€¦
        if ($field === 'members_count') {
            Log::debug('Ã°Å¸â€Â§ applyNumericFilter for members_count', [
                'field' => $field,
                'operator' => $operator,
                'value' => $value,
                'method' => $method
            ]);

            switch ($operator) {
                case 'exists':
                    Log::debug('Ã¢Å“â€¦ Applying whereHas for members_count exists', ['value' => $value, 'filter' => $filter]);
                    return $this->applyMembersCountFilter($queryBuilder, $filter, $havingMethod, $whereHasMethod);
                case 'not_exists':
                    Log::debug('Ã¢Å“â€¦ Applying whereDoesntHave for members_count not_exists', ['value' => $value, 'filter' => $filter]);
                    return $this->applyMembersCountFilter($queryBuilder, $filter, $havingMethod, $whereHasMethod, true);
                case 'equals':
                    Log::debug('Ã¢Å“â€¦ Applying having equals for members_count');
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
                    Log::debug('Ã¢Å¡Â Ã¯Â¸Â Using default having for members_count');
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
     * Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜ÂªÃ˜Â¹Ã˜Â¯Ã˜Â§Ã˜Â¯ Ã˜Â§Ã˜Â¹Ã˜Â¶Ã˜Â§ Ã˜Â¨Ã˜Â§ Ã™Â¾Ã˜Â´Ã˜ÂªÃ›Å’Ã˜Â¨Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â§Ã˜Â² Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€¡
     *
     * @param \Spatie\QueryBuilder\QueryBuilder $queryBuilder
     * @param array $filter
     * @param string $havingMethod
     * @param string $whereHasMethod
     * @param bool $isNegative Ã˜Â¢Ã›Å’Ã˜Â§ Ã˜Â´Ã˜Â±Ã˜Â· Ã™â€¦Ã™â€ Ã™ÂÃ›Å’ Ã˜Â§Ã˜Â³Ã˜Âª (not_exists)
     * @return \Spatie\QueryBuilder\QueryBuilder
     */
    protected function applyMembersCountFilter($queryBuilder, $filter, $havingMethod, $whereHasMethod, $isNegative = false)
    {
        $whereDoesntHaveMethod = str_replace('whereHas', 'whereDoesntHave', $whereHasMethod);

        // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€¡
        if (!empty($filter['min_members']) || !empty($filter['max_members'])) {
            $minMembers = !empty($filter['min_members']) ? (int)$filter['min_members'] : null;
            $maxMembers = !empty($filter['max_members']) ? (int)$filter['max_members'] : null;

            if ($minMembers && $maxMembers) {
                // Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€¡ ÃšÂ©Ã˜Â§Ã™â€¦Ã™â€ž: Ã™â€¦Ã›Å’Ã™â€  Ã˜ÂªÃ˜Â§ Ã™â€¦ÃšÂ©Ã˜Â³
                if ($isNegative) {
                    return $queryBuilder->$havingMethod('members_count', '<', $minMembers)
                                       ->orHaving('members_count', '>', $maxMembers);
                } else {
                    return $queryBuilder->$havingMethod('members_count', '>=', $minMembers)
                                       ->having('members_count', '<=', $maxMembers);
                }
            } elseif ($minMembers) {
                // Ã™ÂÃ™â€šÃ˜Â· Ã˜Â­Ã˜Â¯Ã˜Â§Ã™â€šÃ™â€ž
                return $queryBuilder->$havingMethod('members_count', $isNegative ? '<' : '>=', $minMembers);
            } elseif ($maxMembers) {
                // Ã™ÂÃ™â€šÃ˜Â· Ã˜Â­Ã˜Â¯Ã˜Â§ÃšÂ©Ã˜Â«Ã˜Â±
                return $queryBuilder->$havingMethod('members_count', $isNegative ? '>' : '<=', $maxMembers);
            }
        }

        // Ã˜ÂªÃšÂ© Ã˜Â¹Ã˜Â¯Ã˜Â¯ Ã›Å’Ã˜Â§ Ã˜Â´Ã˜Â±Ã˜Â· Ã˜Â¹Ã™â€¦Ã™Ë†Ã™â€¦Ã›Å’
        if (!empty($filter['value'])) {
            $value = (int)$filter['value'];
            return $queryBuilder->$havingMethod('members_count', $isNegative ? '!=' : '=', $value);
        } else {
            // Ã˜Â¨Ã˜Â¯Ã™Ë†Ã™â€  Ã™â€¦Ã™â€šÃ˜Â¯Ã˜Â§Ã˜Â±: Ã™ÂÃ™â€šÃ˜Â· Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯/Ã˜Â¹Ã˜Â¯Ã™â€¦ Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã˜Â¹Ã˜Â¶Ã™Ë†
            return $queryBuilder->{$isNegative ? $whereDoesntHaveMethod : $whereHasMethod}('members');
        }
    }

    /**
     * Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜ÂªÃ˜Â§Ã˜Â±Ã›Å’Ã˜Â®
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
     * Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã™Ë†Ã˜Â¯Ã˜Â§Ã™â€ž
     * @return void
     */
    public function applyFilters()
    {
        try {
            Log::debug('Ã°Å¸Å½Â¯ FamilySearch applyFilters called', [
                'temp_filters' => $this->tempFilters,
                'active_filters' => $this->activeFilters ?? []
            ]);

            // ÃšÂ©Ã™Â¾Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã™Ë†Ã™â€šÃ˜Âª Ã˜Â¨Ã™â€¡ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™ÂÃ˜Â¹Ã˜Â§Ã™â€ž
            $this->activeFilters = $this->tempFilters;

            // Ã™â€¡Ã™â€¦ÃšÂ¯Ã˜Â§Ã™â€¦Ã¢â‚¬Å’Ã˜Â³Ã˜Â§Ã˜Â²Ã›Å’ Ã˜Â¨Ã˜Â§ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â§Ã˜ÂµÃ™â€žÃ›Å’ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â³Ã˜Â§Ã˜Â²ÃšÂ¯Ã˜Â§Ã˜Â±Ã›Å’ Ã˜Â¨Ã˜Â§ ÃšÂ©Ã˜Â¯Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€šÃ˜Â¯Ã›Å’Ã™â€¦Ã›Å’
            $this->filters = $this->tempFilters;

            // Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€ Ã˜Â´Ã˜Â§Ã™â€ Ã›Å’ Ã˜ÂµÃ™ÂÃ˜Â­Ã™â€¡ Ã˜Â¨Ã™â€¡ Ã›Â±
            $this->resetPage();

            // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  ÃšÂ©Ã˜Â´
            $this->clearCache();

            $filterCount = count($this->activeFilters ?? []);

            if ($filterCount > 0) {
                Log::info('Ã¢Å“â€¦ FamilySearch filters applied successfully', [
                    'filters_count' => $filterCount,
                    'has_modal_filters' => true
                ]);

                session()->flash('message', "Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§ Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜Â´Ã˜Â¯Ã™â€ Ã˜Â¯ ({$filterCount} Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™ÂÃ˜Â¹Ã˜Â§Ã™â€ž)");
                session()->flash('type', 'success');

                // Ã˜Â§Ã˜Â¬Ã˜Â¨Ã˜Â§Ã˜Â± Ã˜Â¨Ã™â€¡ refresh ÃšÂ©Ã˜Â§Ã™â€¦Ã™Â¾Ã™Ë†Ã™â€ Ã™â€ Ã˜Âª
                $this->dispatch('refresh-component');
            } else {
                Log::info('Ã¢Å¡Â Ã¯Â¸Â FamilySearch no filters to apply');
                session()->flash('message', 'Ã™â€¡Ã›Å’Ãšâ€  Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã›Å’ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã™â€ Ã˜Â¯Ã˜Â§Ã˜Â±Ã˜Â¯');
                session()->flash('type', 'warning');
            }

        } catch (\Exception $e) {
            Log::error('Ã¢ÂÅ’ Error applying FamilySearch filters', [
                'error' => $e->getMessage(),
                'temp_filters' => $this->tempFilters ?? [],
                'user_id' => Auth::id()
            ]);

            session()->flash('message', 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§: ' . $e->getMessage());
            session()->flash('type', 'error');
        }
    }

    /**
     * Ã˜ÂªÃ˜Â³Ã˜Âª Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã™Ë†Ã˜Â¯Ã˜Â§Ã™â€ž
     * @return void
     */
    public function testFilters()
    {
        try {
            Log::debug('Ã°Å¸Â§Âª FamilySearch testFilters called', [
                'temp_filters' => $this->tempFilters
            ]);

            // Ã˜Â´Ã˜Â¨Ã›Å’Ã™â€¡Ã¢â‚¬Å’Ã˜Â³Ã˜Â§Ã˜Â²Ã›Å’ Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜ÂªÃ˜Â³Ã˜Âª
            $testFilters = $this->tempFilters;

            if (empty($testFilters)) {
                session()->flash('message', 'Ã™â€¡Ã›Å’Ãšâ€  Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã›Å’ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜ÂªÃ˜Â³Ã˜Âª Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã™â€ Ã˜Â¯Ã˜Â§Ã˜Â±Ã˜Â¯');
                session()->flash('type', 'warning');
                return;
            }

            // Ã˜Â§Ã›Å’Ã˜Â¬Ã˜Â§Ã˜Â¯ ÃšÂ©Ã™Ë†Ã˜Â¦Ã˜Â±Ã›Å’ Ã˜ÂªÃ˜Â³Ã˜Âª
            $queryBuilder = $this->buildFamiliesQuery();

            // Ã˜Â´Ã˜Â¨Ã›Å’Ã™â€¡Ã¢â‚¬Å’Ã˜Â³Ã˜Â§Ã˜Â²Ã›Å’ Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã™Ë†Ã˜Â¯Ã˜Â§Ã™â€ž
            $originalActiveFilters = $this->activeFilters;
            $this->activeFilters = $testFilters;

            $queryBuilder = $this->convertModalFiltersToQueryBuilder($queryBuilder);
            $testCount = $queryBuilder->count();

            // Ã˜Â¨Ã˜Â§Ã˜Â²ÃšÂ¯Ã˜Â±Ã˜Â¯Ã˜Â§Ã™â€ Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â§Ã˜ÂµÃ™â€žÃ›Å’
            $this->activeFilters = $originalActiveFilters;

            Log::info('Ã¢Å“â€¦ FamilySearch filters test completed', [
                'test_count' => $testCount,
                'filters_count' => count($testFilters)
            ]);

            session()->flash('message', "Ã˜ÂªÃ˜Â³Ã˜Âª Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§: {$testCount} Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª Ã˜Â´Ã˜Â¯");
            session()->flash('type', 'info');

        } catch (\Exception $e) {
            Log::error('Ã¢ÂÅ’ Error testing FamilySearch filters', [
                'error' => $e->getMessage(),
                'temp_filters' => $this->tempFilters ?? [],
                'user_id' => Auth::id()
            ]);

            session()->flash('message', 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜ÂªÃ˜Â³Ã˜Âª Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§: ' . $e->getMessage());
            session()->flash('type', 'error');
        }
    }

    /**
     * Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€ Ã˜Â´Ã˜Â§Ã™â€ Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§ Ã˜Â¨Ã™â€¡ Ã˜Â­Ã˜Â§Ã™â€žÃ˜Âª Ã™Â¾Ã›Å’Ã˜Â´Ã™ÂÃ˜Â±Ã˜Â¶
     * @return void
     */
    public function resetFilters()
    {
        try {
            Log::debug('Ã°Å¸â€â€ž FamilySearch resetFilters called');

            // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã˜ÂªÃ™â€¦Ã˜Â§Ã™â€¦ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§
            $this->tempFilters = [];
            $this->activeFilters = [];
            $this->filters = [];

            // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ ÃšÂ©Ã˜Â§Ã™â€¦Ã™Â¾Ã™Ë†Ã™â€ Ã™â€ Ã˜Âª
            $this->search = '';
            $this->status = '';
            $this->province = '';
            $this->city = '';
            $this->deprivation_rank = '';
            $this->family_rank_range = '';
            $this->specific_criteria = '';
            $this->charity = '';

            // Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€ Ã˜Â´Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª
            $this->sortField = 'created_at';
            $this->sortDirection = 'desc';

            // Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€ Ã˜Â´Ã˜Â§Ã™â€ Ã›Å’ Ã˜ÂµÃ™ÂÃ˜Â­Ã™â€¡
            $this->resetPage();

            // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  ÃšÂ©Ã˜Â´
            $this->clearCache();

            Log::info('Ã¢Å“â€¦ FamilySearch filters reset successfully');

            session()->flash('message', 'Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§ Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€ Ã˜Â´Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â´Ã˜Â¯Ã™â€ Ã˜Â¯');
            session()->flash('type', 'success');

        } catch (\Exception $e) {
            Log::error('Ã¢ÂÅ’ Error resetting FamilySearch filters', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            session()->flash('message', 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€ Ã˜Â´Ã˜Â§Ã™â€ Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§: ' . $e->getMessage());
            session()->flash('type', 'error');
        }
    }

    /**
     * Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™ÂÃ˜Â¹Ã˜Â§Ã™â€ž
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
     * Ã˜Â´Ã™â€¦Ã˜Â§Ã˜Â±Ã˜Â´ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™ÂÃ˜Â¹Ã˜Â§Ã™â€ž
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
            // Ã˜Â´Ã™â€¦Ã˜Â§Ã˜Â±Ã˜Â´ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™ÂÃ˜Â¹Ã˜Â§Ã™â€ž Ã˜Â¯Ã˜Â± tempFilters
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
     * Ã˜ÂªÃ™Ë†Ã™â€žÃ›Å’Ã˜Â¯ ÃšÂ©Ã™â€žÃ›Å’Ã˜Â¯ ÃšÂ©Ã˜Â´
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
     * Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  ÃšÂ©Ã˜Â´
     * @return void
     */
    protected function clearCache(): void
    {
        try {
            // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  ÃšÂ©Ã˜Â´Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã˜Â±Ã˜ÂªÃ˜Â¨Ã˜Â· Ã˜Â¨Ã˜Â§ Ã˜Â§Ã›Å’Ã™â€  ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â±
            $pattern = 'family_search_*_' . Auth::id();

            // Laravel Cache doesn't support pattern deletion directly,
            // so we'll just forget the current cache key
            $currentKey = $this->getCacheKey();
            Cache::forget($currentKey);

            Log::debug('Ã°Å¸Â§Â¹ FamilySearch cache cleared', ['cache_key' => $currentKey]);

        } catch (\Exception $e) {
            Log::warning('Ã¢Å¡Â Ã¯Â¸Â Error clearing FamilySearch cache', [
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

            // Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ ÃšÂ©Ã˜Â§Ã™â€¦Ã™â€ž Ã˜Â§Ã˜Â¹Ã˜Â¶Ã˜Â§Ã›Å’ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã˜Â§ Ã˜ÂªÃ™â€¦Ã˜Â§Ã™â€¦ Ã˜Â§Ã˜Â·Ã™â€žÃ˜Â§Ã˜Â¹Ã˜Â§Ã˜Âª Ã™Ë† Ã™â€¦Ã˜Â±Ã˜ÂªÃ˜Â¨Ã¢â‚¬Å’Ã˜Â³Ã˜Â§Ã˜Â²Ã›Å’ Ã™â€¦Ã™â€ Ã˜Â§Ã˜Â³Ã˜Â¨
            $family = Family::with(['members' => function($query) {
                // Ã™â€¦Ã˜Â±Ã˜ÂªÃ˜Â¨Ã¢â‚¬Å’Ã˜Â³Ã˜Â§Ã˜Â²Ã›Å’: Ã˜Â§Ã˜Â¨Ã˜ÂªÃ˜Â¯Ã˜Â§ Ã˜Â³Ã˜Â±Ã™Â¾Ã˜Â±Ã˜Â³Ã˜Âª Ã™Ë† Ã˜Â³Ã™Â¾Ã˜Â³ Ã˜Â¨Ã™â€¡ Ã˜ÂªÃ˜Â±Ã˜ÂªÃ›Å’Ã˜Â¨ ID
                $query->orderBy('is_head', 'desc')
                      ->orderBy('id', 'asc');
            }])->findOrFail($familyId);

            // Ã˜ÂªÃ™â€¡Ã›Å’Ã™â€¡ ÃšÂ©Ã˜Â§Ã™â€žÃšÂ©Ã˜Â´Ã™â€  ÃšÂ©Ã˜Â§Ã™â€¦Ã™â€ž Ã˜Â§Ã˜Â¹Ã˜Â¶Ã˜Â§Ã›Å’ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡
            $this->familyMembers = $family->members;

            // Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦ selectedHead Ã˜Â¨Ã™â€¡ ID Ã˜Â³Ã˜Â±Ã™Â¾Ã˜Â±Ã˜Â³Ã˜Âª Ã™ÂÃ˜Â¹Ã™â€žÃ›Å’
            foreach ($this->familyMembers as $member) {
                if ($member->is_head) {
                    $this->selectedHead = $member->id;
                    break;
                }
            }

            // Ã˜Â§Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ž Ã˜Â±Ã™Ë†Ã›Å’Ã˜Â¯Ã˜Â§Ã˜Â¯ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â§Ã˜Â³ÃšÂ©Ã˜Â±Ã™Ë†Ã™â€ž Ã˜Â¨Ã™â€¡ Ã™â€¦Ã™Ë†Ã™â€šÃ˜Â¹Ã›Å’Ã˜Âª Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã˜Â§Ã˜Â² Ã˜Â´Ã˜Â¯Ã™â€¡
            $this->dispatch('family-expanded', $familyId);
        }
    }

    /**
     * Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦ Ã˜Â³Ã˜Â±Ã™Â¾Ã˜Â±Ã˜Â³Ã˜Âª Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡
     *
     * @param int $familyId Ã˜Â´Ã™â€ Ã˜Â§Ã˜Â³Ã™â€¡ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡
     * @param int $memberId Ã˜Â´Ã™â€ Ã˜Â§Ã˜Â³Ã™â€¡ Ã˜Â¹Ã˜Â¶Ã™Ë†
     * @return void
     */
    public function setFamilyHead($familyId, $memberId)
    {
        try {
            $family = Family::findOrFail($familyId);

            // Ã™ÂÃ™â€šÃ˜Â· Ã˜Â§ÃšÂ¯Ã˜Â± Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜ÂªÃ˜Â§Ã›Å’Ã›Å’Ã˜Â¯ Ã™â€ Ã˜Â´Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯Ã˜Å’ Ã˜Â§Ã˜Â¬Ã˜Â§Ã˜Â²Ã™â€¡ Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â± Ã˜Â³Ã˜Â±Ã™Â¾Ã˜Â±Ã˜Â³Ã˜Âª Ã˜Â±Ã˜Â§ Ã˜Â¨Ã˜Â¯Ã™â€¡Ã›Å’Ã™â€¦
            if ($family->verified_at) {
                $this->dispatch('show-toast', [
                    'message' => 'Ã¢ÂÅ’ Ã˜Â§Ã™â€¦ÃšÂ©Ã˜Â§Ã™â€  Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â± Ã˜Â³Ã˜Â±Ã™Â¾Ã˜Â±Ã˜Â³Ã˜Âª Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã˜ÂªÃ˜Â§Ã›Å’Ã›Å’Ã˜Â¯ Ã˜Â´Ã˜Â¯Ã™â€¡ Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã™â€ Ã˜Â¯Ã˜Â§Ã˜Â±Ã˜Â¯',
                    'type' => 'error'
                ]);
                return;
            }

            // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã˜Â§Ã›Å’Ã™â€ ÃšÂ©Ã™â€¡ Ã˜Â¹Ã˜Â¶Ã™Ë† Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â´Ã˜Â¯Ã™â€¡ Ã™â€¦Ã˜ÂªÃ˜Â¹Ã™â€žÃ™â€š Ã˜Â¨Ã™â€¡ Ã™â€¡Ã™â€¦Ã›Å’Ã™â€  Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â§Ã˜Â³Ã˜Âª
            $member = Member::where('id', $memberId)->where('family_id', $familyId)->first();
            if (!$member) {
                $this->dispatch('show-toast', [
                    'message' => 'Ã¢ÂÅ’ Ã˜Â¹Ã˜Â¶Ã™Ë† Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â´Ã˜Â¯Ã™â€¡ Ã˜Â¯Ã˜Â± Ã˜Â§Ã›Å’Ã™â€  Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª Ã™â€ Ã˜Â´Ã˜Â¯',
                    'type' => 'error'
                ]);
                return;
            }

                // Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦ Ã™â€¦Ã˜ÂªÃ˜ÂºÃ›Å’Ã˜Â± Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â´Ã˜Â¯Ã™â€¡
                $this->selectedHead = $memberId;

                // Ã™â€¦Ã˜Â¯Ã›Å’Ã˜Â±Ã›Å’Ã˜Âª Ã˜ÂªÃ˜Â±Ã˜Â§ÃšÂ©Ã™â€ Ã˜Â´ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â§Ã˜Â·Ã™â€¦Ã›Å’Ã™â€ Ã˜Â§Ã™â€  Ã˜Â§Ã˜Â² Ã˜ÂµÃ˜Â­Ã˜Âª Ã˜Â¯Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§
                DB::beginTransaction();

            // Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã™Â¾Ã˜Â§Ã›Å’ÃšÂ¯Ã˜Â§Ã™â€¡ Ã˜Â¯Ã˜Â§Ã˜Â¯Ã™â€¡ - Ã™ÂÃ™â€šÃ˜Â· Ã›Å’ÃšÂ© Ã™â€ Ã™ÂÃ˜Â± Ã˜Â³Ã˜Â±Ã™Â¾Ã˜Â±Ã˜Â³Ã˜Âª
                Member::where('family_id', $familyId)->update(['is_head' => false]);
                Member::where('id', $memberId)->update(['is_head' => true]);

                DB::commit();

                // Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã˜Â¨Ã˜Â¯Ã™Ë†Ã™â€  Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã›Å’Ã˜Â±Ã›Å’ Ã™â€¦Ã˜Â¬Ã˜Â¯Ã˜Â¯ ÃšÂ©Ã˜Â§Ã™â€¦Ã™â€ž
                if ($this->expandedFamily === $familyId && !empty($this->familyMembers)) {
                    // Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ state Ã˜Â¯Ã˜Â§Ã˜Â®Ã™â€žÃ›Å’ Ã˜Â¨Ã˜Â¯Ã™Ë†Ã™â€  Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã›Å’Ã˜Â±Ã›Å’ Ã™â€¦Ã˜Â¬Ã˜Â¯Ã˜Â¯
                foreach ($this->familyMembers as $familyMember) {
                        // Ã™ÂÃ™â€šÃ˜Â· Ã™Ë†Ã˜Â¶Ã˜Â¹Ã›Å’Ã˜Âª is_head Ã˜Â±Ã˜Â§ Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â± Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜Â¯Ã™â€¡Ã›Å’Ã™â€¦
                    $familyMember->is_head = ($familyMember->id == $memberId);
                    }
                }

                // Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã™Â¾Ã›Å’Ã˜Â§Ã™â€¦ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª
                $this->dispatch('show-toast', [
                'message' => 'Ã¢Å“â€¦ Ã˜Â³Ã˜Â±Ã™Â¾Ã˜Â±Ã˜Â³Ã˜Âª Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â± Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª',
                    'type' => 'success'
                ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('show-toast', [
                'message' => 'Ã¢ÂÅ’ Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â§Ã˜Â·Ã™â€žÃ˜Â§Ã˜Â¹Ã˜Â§Ã˜Âª: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    public function verifyFamily($familyId)
    {
        // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã˜Â¯Ã˜Â³Ã˜ÂªÃ˜Â±Ã˜Â³Ã›Å’ ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â±
        if (!Auth::check() || !Gate::allows('verify-family')) {
            $this->dispatch('show-toast', [
                'message' => 'Ã°Å¸Å¡Â« Ã˜Â´Ã™â€¦Ã˜Â§ Ã˜Â§Ã˜Â¬Ã˜Â§Ã˜Â²Ã™â€¡ Ã˜ÂªÃ˜Â§Ã›Å’Ã›Å’Ã˜Â¯ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â±Ã˜Â§ Ã™â€ Ã˜Â¯Ã˜Â§Ã˜Â±Ã›Å’Ã˜Â¯',
                'type' => 'error'
            ]);
            return;
        }

        $family = Family::findOrFail($familyId);

        // Ã˜Â§ÃšÂ¯Ã˜Â± Ã™â€šÃ˜Â¨Ã™â€žÃ˜Â§Ã™â€¹ Ã˜ÂªÃ˜Â§Ã›Å’Ã›Å’Ã˜Â¯ Ã˜Â´Ã˜Â¯Ã™â€¡Ã˜Å’ Ã˜Â§Ã˜Â·Ã™â€žÃ˜Â§Ã˜Â¹ Ã˜Â¨Ã˜Â¯Ã™â€¡Ã›Å’Ã™â€¦
        if ($family->verified_at) {
            $this->dispatch('show-toast', [
                'message' => 'Ã¢Å¡Â Ã¯Â¸Â Ã˜Â§Ã›Å’Ã™â€  Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã™â€šÃ˜Â¨Ã™â€žÃ˜Â§Ã™â€¹ Ã˜ÂªÃ˜Â§Ã›Å’Ã›Å’Ã˜Â¯ Ã˜Â´Ã˜Â¯Ã™â€¡ Ã˜Â§Ã˜Â³Ã˜Âª',
                'type' => 'warning'
            ]);
            return;
        }

        // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã˜Â§Ã›Å’Ã™â€ ÃšÂ©Ã™â€¡ Ã›Å’ÃšÂ© Ã˜Â³Ã˜Â±Ã™Â¾Ã˜Â±Ã˜Â³Ã˜Âª Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â´Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯
        $headsCount = Member::where('family_id', $familyId)->where('is_head', true)->count();

        if ($headsCount === 0) {
            $this->dispatch('show-toast', [
                'message' => 'Ã¢ÂÅ’ Ã™â€žÃ˜Â·Ã™ÂÃ˜Â§Ã™â€¹ Ã™â€šÃ˜Â¨Ã™â€ž Ã˜Â§Ã˜Â² Ã˜ÂªÃ˜Â§Ã›Å’Ã›Å’Ã˜Â¯Ã˜Å’ Ã›Å’ÃšÂ© Ã˜Â³Ã˜Â±Ã™Â¾Ã˜Â±Ã˜Â³Ã˜Âª Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ ÃšÂ©Ã™â€ Ã›Å’Ã˜Â¯',
                'type' => 'error'
            ]);
            return;
        }

        if ($headsCount > 1) {
            $this->dispatch('show-toast', [
                'message' => 'Ã¢Å¡Â Ã¯Â¸Â Ã˜Â®Ã˜Â·Ã˜Â§: Ã˜Â¨Ã›Å’Ã˜Â´ Ã˜Â§Ã˜Â² Ã›Å’ÃšÂ© Ã˜Â³Ã˜Â±Ã™Â¾Ã˜Â±Ã˜Â³Ã˜Âª Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â´Ã˜Â¯Ã™â€¡ Ã˜Â§Ã˜Â³Ã˜Âª. Ã™â€žÃ˜Â·Ã™ÂÃ˜Â§Ã™â€¹ Ã™ÂÃ™â€šÃ˜Â· Ã›Å’ÃšÂ© Ã™â€ Ã™ÂÃ˜Â± Ã˜Â±Ã˜Â§ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ ÃšÂ©Ã™â€ Ã›Å’Ã˜Â¯',
                'type' => 'error'
            ]);
            // Ã˜Â§Ã˜ÂµÃ™â€žÃ˜Â§Ã˜Â­ Ã˜Â®Ã™Ë†Ã˜Â¯ÃšÂ©Ã˜Â§Ã˜Â± - Ã™ÂÃ™â€šÃ˜Â· Ã˜Â§Ã™Ë†Ã™â€žÃ›Å’Ã™â€  Ã˜Â³Ã˜Â±Ã™Â¾Ã˜Â±Ã˜Â³Ã˜Âª Ã˜Â±Ã˜Â§ Ã™â€ ÃšÂ¯Ã™â€¡ Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜Â¯Ã˜Â§Ã˜Â±Ã›Å’Ã™â€¦
            $firstHead = Member::where('family_id', $familyId)->where('is_head', true)->first();
            Member::where('family_id', $familyId)->update(['is_head' => false]);
            $firstHead->update(['is_head' => true]);
            return;
        }

        // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã˜Â­Ã˜Â¯Ã˜Â§Ã™â€šÃ™â€ž Ã›Å’ÃšÂ© Ã˜Â¹Ã˜Â¶Ã™Ë† Ã˜Â¯Ã˜Â± Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡
        $membersCount = Member::where('family_id', $familyId)->count();
        if ($membersCount === 0) {
            $this->dispatch('show-toast', [
                'message' => 'Ã¢ÂÅ’ Ã˜Â§Ã›Å’Ã™â€  Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã™â€¡Ã›Å’Ãšâ€  Ã˜Â¹Ã˜Â¶Ã™Ë†Ã›Å’ Ã™â€ Ã˜Â¯Ã˜Â§Ã˜Â±Ã˜Â¯ Ã™Ë† Ã™â€šÃ˜Â§Ã˜Â¨Ã™â€ž Ã˜ÂªÃ˜Â§Ã›Å’Ã›Å’Ã˜Â¯ Ã™â€ Ã›Å’Ã˜Â³Ã˜Âª',
                'type' => 'error'
            ]);
            return;
        }

        // Ã˜ÂªÃ˜Â§Ã›Å’Ã›Å’Ã˜Â¯ Ã™Ë† Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã˜ÂªÃ˜Â§Ã˜Â±Ã›Å’Ã˜Â® Ã˜ÂªÃ˜Â§Ã›Å’Ã›Å’Ã˜Â¯
        $family->verified_at = now();
        $family->verified_by = Auth::id();
        $family->save();

        // Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã™Â¾Ã›Å’Ã˜Â§Ã™â€¦ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª
        $this->dispatch('show-toast', [
            'message' => 'Ã¢Å“â€¦ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜ÂªÃ˜Â§Ã›Å’Ã›Å’Ã˜Â¯ Ã˜Â´Ã˜Â¯ Ã™Ë† Ã˜Â¢Ã™â€¦Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â§Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ž Ã˜Â¨Ã™â€¡ Ã˜Â¨Ã›Å’Ã™â€¦Ã™â€¡ Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯',
            'type' => 'success'
        ]);
    }

    public function copyText($text)
    {
        $this->dispatch('copy-text', $text);
        $this->dispatch('show-toast', [
            'message' => 'Ã°Å¸â€œâ€¹ Ã™â€¦Ã˜ÂªÃ™â€  Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª ÃšÂ©Ã™Â¾Ã›Å’ Ã˜Â´Ã˜Â¯: ' . $text,
            'type' => 'success'
        ]);
    }



    /**
     * Ã˜Â¨Ã˜Â§Ã˜Â²ÃšÂ¯Ã˜Â´Ã˜Âª Ã˜Â¨Ã™â€¡ Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª Ã™Â¾Ã›Å’Ã˜Â´Ã™ÂÃ˜Â±Ã˜Â¶
     */
    public function resetToDefaultSettings()
    {
        // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â´Ã˜Â¯Ã™â€¡
        $this->selectedCriteria = [];
        $this->criteriaRequireDocument = [];

        // Ã™â€¦Ã™â€šÃ˜Â¯Ã˜Â§Ã˜Â±Ã˜Â¯Ã™â€¡Ã›Å’ Ã™â€¦Ã˜Â¬Ã˜Â¯Ã˜Â¯ Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™â€šÃ˜Â§Ã˜Â¯Ã›Å’Ã˜Â± Ã™Â¾Ã›Å’Ã˜Â´Ã™ÂÃ˜Â±Ã˜Â¶
        foreach ($this->availableCriteria as $criterion) {
            $this->selectedCriteria[$criterion->id] = false;
            $this->criteriaRequireDocument[$criterion->id] = true;
        }

        $this->dispatch('notify', ['message' => 'Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª Ã˜Â¨Ã™â€¡ Ã˜Â­Ã˜Â§Ã™â€žÃ˜Âª Ã™Â¾Ã›Å’Ã˜Â´Ã™ÂÃ˜Â±Ã˜Â¶ Ã˜Â¨Ã˜Â§Ã˜Â²ÃšÂ¯Ã˜Â´Ã˜Âª.', 'type' => 'info']);
    }

    //======================================================================
    //== Ã™â€¦Ã˜ÂªÃ˜Â¯Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â³Ã›Å’Ã˜Â³Ã˜ÂªÃ™â€¦ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã™Â¾Ã™Ë†Ã›Å’Ã˜Â§
    //======================================================================

    /**
     * Ã™Ë†Ã˜Â²Ã™â€ Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã›Å’ÃšÂ© Ã˜Â§Ã™â€žÃšÂ¯Ã™Ë†Ã›Å’ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡Ã¢â‚¬Å’Ã˜Â´Ã˜Â¯Ã™â€¡ Ã˜Â±Ã˜Â§ Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã›Å’Ã˜Â±Ã›Å’ Ã™â€¦Ã›Å’Ã¢â‚¬Å’ÃšÂ©Ã™â€ Ã˜Â¯.
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
     * Ã›Å’ÃšÂ© Ã˜Â§Ã™â€žÃšÂ¯Ã™Ë†Ã›Å’ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯ Ã˜Â±Ã˜Â§ Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã›Å’Ã˜Â§ Ã›Å’ÃšÂ© Ã˜Â§Ã™â€žÃšÂ¯Ã™Ë†Ã›Å’ Ã™â€¦Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã˜Â±Ã˜Â§ Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã™â€¦Ã›Å’Ã¢â‚¬Å’ÃšÂ©Ã™â€ Ã˜Â¯.
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

        $this->dispatch('notify', ['message' => 'Ã˜Â§Ã™â€žÃšÂ¯Ã™Ë† Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã˜Â´Ã˜Â¯.', 'type' => 'success']);
    }

    /**
     * Ã˜Â§Ã™â€žÃšÂ¯Ã™Ë†Ã›Å’ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨Ã¢â‚¬Å’Ã˜Â´Ã˜Â¯Ã™â€¡ Ã˜Â±Ã˜Â§ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã™Ë† Ã™â€¦Ã˜Â±Ã˜ÂªÃ˜Â¨Ã¢â‚¬Å’Ã˜Â³Ã˜Â§Ã˜Â²Ã›Å’ Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™â€¦Ã›Å’Ã¢â‚¬Å’ÃšÂ©Ã™â€ Ã˜Â¯.
     */
    public function applyRankingScheme()
    {
        if (!$this->selectedSchemeId) {
             $this->dispatch('notify', ['message' => 'Ã™â€žÃ˜Â·Ã™ÂÃ˜Â§ Ã˜Â§Ã˜Â¨Ã˜ÂªÃ˜Â¯Ã˜Â§ Ã›Å’ÃšÂ© Ã˜Â§Ã™â€žÃšÂ¯Ã™Ë† Ã˜Â±Ã˜Â§ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã›Å’Ã˜Â§ Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ ÃšÂ©Ã™â€ Ã›Å’Ã˜Â¯.', 'type' => 'error']);
             return;
        }
        $this->appliedSchemeId = $this->selectedSchemeId;
        $this->sortBy('calculated_score');
        $this->resetPage();
        $this->showRankModal = false;

        // Ã˜Â¯Ã˜Â±Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª Ã™â€ Ã˜Â§Ã™â€¦ Ã˜Â§Ã™â€žÃšÂ¯Ã™Ë†Ã›Å’ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â´Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã˜Â¯Ã˜Â± Ã™Â¾Ã›Å’Ã˜Â§Ã™â€¦
        $schemeName = \App\Models\RankingScheme::find($this->selectedSchemeId)->name ?? '';
        $this->dispatch('notify', [
            'message' => "Ã˜Â§Ã™â€žÃšÂ¯Ã™Ë†Ã›Å’ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã‚Â«{$schemeName}Ã‚Â» Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜Â´Ã˜Â¯.",
            'type' => 'success'
        ]);
    }

    /**
     * Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€žÃ¢â‚¬Å’Ã˜Â´Ã˜Â¯Ã™â€¡ Ã˜Â±Ã˜Â§ Ã™Â¾Ã˜Â§ÃšÂ© Ã™â€¦Ã›Å’Ã¢â‚¬Å’ÃšÂ©Ã™â€ Ã˜Â¯.
     */
    public function clearRanking()
    {
        $this->appliedSchemeId = null;
        $this->sortBy('created_at');
        $this->resetPage();
        $this->showRankModal = false;
        $this->dispatch('notify', ['message' => 'Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã˜Â­Ã˜Â°Ã™Â Ã˜Â´Ã˜Â¯.', 'type' => 'info']);
    }
    public function applyAndClose()
    {
        try {
            // Ã˜Â§Ã˜Â·Ã™â€¦Ã›Å’Ã™â€ Ã˜Â§Ã™â€  Ã˜Â§Ã˜Â² Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã™â€¡Ã™â€¦Ã™â€¡ Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â±Ã˜Â§Ã˜Âª
            $this->loadRankSettings();

            // Ã˜Â¨Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã™â€žÃ›Å’Ã˜Â³Ã˜Âª Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â¯Ã˜Â± Ã˜Â¯Ã˜Â³Ã˜ÂªÃ˜Â±Ã˜Â³
            $this->availableRankSettings = \App\Models\RankSetting::active()->ordered()->get();

            // Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â±Ã˜Â§Ã˜Âª Ã˜Â¨Ã™â€¡ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§
            if ($this->appliedSchemeId) {
                // Ã˜Â§ÃšÂ¯Ã˜Â± Ã›Å’ÃšÂ© Ã˜Â·Ã˜Â±Ã˜Â­ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â´Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯Ã˜Å’ Ã˜Â¯Ã™Ë†Ã˜Â¨Ã˜Â§Ã˜Â±Ã™â€¡ Ã˜Â¢Ã™â€  Ã˜Â±Ã˜Â§ Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™â€¦Ã›Å’Ã¢â‚¬Å’ÃšÂ©Ã™â€ Ã›Å’Ã™â€¦
                $this->applyRankingScheme();

                $this->sortBy('calculated_score');
            }

            // Ã˜Â¨Ã˜Â³Ã˜ÂªÃ™â€  Ã™â€¦Ã™Ë†Ã˜Â¯Ã˜Â§Ã™â€ž Ã™Ë† Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã™Â¾Ã›Å’Ã˜Â§Ã™â€¦
            $this->showRankModal = false;
            $this->dispatch('notify', [
                'message' => 'Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â±Ã˜Â§Ã˜Âª Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜Â´Ã˜Â¯.',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            // Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â±Ã˜Â§Ã˜Âª
            $this->dispatch('notify', [
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â±Ã˜Â§Ã˜Âª: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    public function loadRankSettings()
    {
        Log::info('Ã°Å¸â€œâ€¹ STEP 2: Loading rank settings', [
            'user_id' => Auth::id(),
            'timestamp' => now()
        ]);
        $this->rankSettings = RankSetting::orderBy('sort_order')->get();
        $this->rankingSchemes = \App\Models\RankingScheme::orderBy('name')->get();
        $this->availableCriteria = RankSetting::where('is_active', true)->orderBy('sort_order')->get();
        // Update available rank settings for display
        $this->availableRankSettings = $this->rankSettings;
        // Ã˜Â§Ã˜ÂµÃ™â€žÃ˜Â§Ã˜Â­ count Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â¢Ã˜Â±Ã˜Â§Ã›Å’Ã™â€¡/ÃšÂ©Ã˜Â§Ã™â€žÃšÂ©Ã˜Â´Ã™â€ 
        $rankSettingsCount = is_array($this->rankSettings) ? count($this->rankSettings) : $this->rankSettings->count();
        $rankingSchemesCount = is_array($this->rankingSchemes) ? count($this->rankingSchemes) : $this->rankingSchemes->count();
        $availableCriteriaCount = is_array($this->availableCriteria) ? count($this->availableCriteria) : $this->availableCriteria->count();
        $activeCriteria = $this->availableCriteria instanceof \Illuminate\Support\Collection ? $this->availableCriteria->pluck('name', 'id')->toArray() : [];
        Log::info('Ã¢Å“â€¦ STEP 2 COMPLETED: Rank settings loaded', [
            'rankSettings_count' => $rankSettingsCount,
            'rankingSchemes_count' => $rankingSchemesCount,
            'availableCriteria_count' => $availableCriteriaCount,
            'active_criteria' => $activeCriteria,
            'user_id' => Auth::id()
        ]);
        // Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã™Â¾Ã›Å’Ã˜Â§Ã™â€¦ Ã™â€¦Ã™â€ Ã˜Â§Ã˜Â³Ã˜Â¨ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â¨Ã˜Â§Ã˜Â² Ã˜Â´Ã˜Â¯Ã™â€  Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª
        $this->dispatch('notify', [
            'message' => 'Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ Ã˜Â´Ã˜Â¯ - ' . $rankSettingsCount . ' Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±',
            'type' => 'info'
        ]);
    }

    /**
     * Ã™ÂÃ˜Â±Ã™â€¦ Ã˜Â§Ã™ÂÃ˜Â²Ã™Ë†Ã˜Â¯Ã™â€  Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯ Ã˜Â±Ã˜Â§ Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜Â¯Ã™â€¡Ã˜Â¯.
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
            'message' => 'Ã™ÂÃ˜Â±Ã™â€¦ Ã˜Â§Ã›Å’Ã˜Â¬Ã˜Â§Ã˜Â¯ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯ Ã˜Â¢Ã™â€¦Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â´Ã˜Â¯',
            'type' => 'info'
        ]);
    }

    /**
     * Ã›Å’ÃšÂ© Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â±Ã˜Â§ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã™â€¦Ã›Å’Ã¢â‚¬Å’ÃšÂ©Ã™â€ Ã˜Â¯.
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
     * Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â±Ã˜Â§Ã˜Âª Ã˜Â±Ã˜Â§ Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã™â€¦Ã›Å’Ã¢â‚¬Å’ÃšÂ©Ã™â€ Ã˜Â¯ (Ã™â€¡Ã™â€¦ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â§Ã™ÂÃ˜Â²Ã™Ë†Ã˜Â¯Ã™â€  Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯ Ã™Ë† Ã™â€¡Ã™â€¦ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´).
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
            // Ã™â€¦Ã˜Â­Ã˜Â§Ã˜Â³Ã˜Â¨Ã™â€¡ sort_order Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â±ÃšÂ©Ã™Ë†Ã˜Â±Ã˜Â¯ Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯
            if (!$this->editingRankSettingId) {
                $maxOrder = RankSetting::max('sort_order') ?? 0;
                $this->editingRankSetting['sort_order'] = $maxOrder + 10;
                $this->editingRankSetting['is_active'] = true;
                $this->editingRankSetting['slug'] = \Illuminate\Support\Str::slug($this->editingRankSetting['name']);
            }

            // Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡
            $setting = RankSetting::updateOrCreate(
                ['id' => $this->editingRankSettingId],
                $this->editingRankSetting
            );

            // Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€ Ã˜Â´Ã˜Â§Ã™â€ Ã›Å’ Ã™ÂÃ˜Â±Ã™â€¦
            $this->resetForm();

            // Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ Ã™â€¦Ã˜Â¬Ã˜Â¯Ã˜Â¯ Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª
            $this->loadRankSettings();

            // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  ÃšÂ©Ã˜Â´ Ã™â€žÃ›Å’Ã˜Â³Ã˜Âª Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§
            $this->clearFamiliesCache();

            $this->dispatch('notify', [
                'message' => 'Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã˜Â´Ã˜Â¯',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Ã˜Â­Ã˜Â°Ã™Â Ã›Å’ÃšÂ© Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’
     * @param int $id
     */
    public function delete($id)
    {
        try {
            $setting = RankSetting::find($id);
            if ($setting) {
                // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã˜Â§Ã˜Â³Ã˜ÂªÃ™ÂÃ˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â´Ã˜Â¯Ã™â€  Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±
                $usageCount = \App\Models\FamilyCriterion::where('rank_setting_id', $id)->count();
                if ($usageCount > 0) {
                    $this->dispatch('notify', [
                        'message' => "Ã˜Â§Ã›Å’Ã™â€  Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â¯Ã˜Â± {$usageCount} Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â§Ã˜Â³Ã˜ÂªÃ™ÂÃ˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â´Ã˜Â¯Ã™â€¡ Ã™Ë† Ã™â€šÃ˜Â§Ã˜Â¨Ã™â€ž Ã˜Â­Ã˜Â°Ã™Â Ã™â€ Ã›Å’Ã˜Â³Ã˜Âª. Ã˜Â¨Ã™â€¡ Ã˜Â¬Ã˜Â§Ã›Å’ Ã˜Â­Ã˜Â°Ã™Â Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã›Å’Ã˜Â¯ Ã˜Â¢Ã™â€  Ã˜Â±Ã˜Â§ Ã˜ÂºÃ›Å’Ã˜Â±Ã™ÂÃ˜Â¹Ã˜Â§Ã™â€ž ÃšÂ©Ã™â€ Ã›Å’Ã˜Â¯.",
                        'type' => 'error'
                    ]);
                    return;
                }

                $setting->delete();
                $this->loadRankSettings();

                // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  ÃšÂ©Ã˜Â´ Ã™â€žÃ›Å’Ã˜Â³Ã˜Âª Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§
                $this->clearFamiliesCache();

                $this->dispatch('notify', [
                    'message' => 'Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â­Ã˜Â°Ã™Â Ã˜Â´Ã˜Â¯',
                    'type' => 'success'
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â­Ã˜Â°Ã™Â Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Ã˜Â§Ã™â€ Ã˜ÂµÃ˜Â±Ã˜Â§Ã™Â Ã˜Â§Ã˜Â² Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´/Ã˜Â§Ã™ÂÃ˜Â²Ã™Ë†Ã˜Â¯Ã™â€  Ã™Ë† Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€ Ã˜Â´Ã˜Â§Ã™â€ Ã›Å’ Ã™ÂÃ˜Â±Ã™â€¦
     */
    public function cancel()
    {
        $this->resetForm();
        $this->dispatch('notify', [
            'message' => 'Ã˜Â¹Ã™â€¦Ã™â€žÃ›Å’Ã˜Â§Ã˜Âª Ã™â€žÃ˜ÂºÃ™Ë† Ã˜Â´Ã˜Â¯',
            'type' => 'info'
        ]);
    }

    /**
     * Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€ Ã˜Â´Ã˜Â§Ã™â€ Ã›Å’ Ã™ÂÃ˜Â±Ã™â€¦ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´/Ã˜Â§Ã™ÂÃ˜Â²Ã™Ë†Ã˜Â¯Ã™â€ 
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
     * Ã˜Â¨Ã˜Â§Ã˜Â² ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã™â€¦Ã™Ë†Ã˜Â¯Ã˜Â§Ã™â€ž Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡
     */
    public function openRankModal()
    {
        Log::info('Ã°Å¸Å½Â¯ STEP 1: Opening rank modal', [
            'user_id' => Auth::id(),
            'timestamp' => now()
        ]);
        $this->loadRankSettings();
        $this->showRankModal = true;
        $rankSettingsCount = is_array($this->rankSettings) ? count($this->rankSettings) : $this->rankSettings->count();
        Log::info('Ã¢Å“â€¦ STEP 1 COMPLETED: Rank modal opened', [
            'showRankModal' => $this->showRankModal,
            'rankSettings_count' => $rankSettingsCount,
            'user_id' => Auth::id()
        ]);
    }

    /**
     * Ã˜Â¨Ã˜Â³Ã˜ÂªÃ™â€  Ã™â€¦Ã™Ë†Ã˜Â¯Ã˜Â§Ã™â€ž Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡
     */
    public function closeRankModal()
    {
        $this->showRankModal = false;
    }

    /**
     * Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â´Ã˜Â¯Ã™â€¡
     */
    public function applyCriteria()
    {
        try {
            Log::info('Ã°Å¸Å½Â¯ STEP 3: Starting applyCriteria with ranking sort', [
                'selectedCriteria' => $this->selectedCriteria,
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            // Ã˜Â§Ã˜Â³Ã˜ÂªÃ˜Â®Ã˜Â±Ã˜Â§Ã˜Â¬ ID Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â´Ã˜Â¯Ã™â€¡
            $selectedRankSettingIds = array_keys(array_filter($this->selectedCriteria,
                fn($value) => $value === true
            ));

            Log::info('Ã°Å¸â€œÅ  STEP 3.1: Selected criteria analysis', [
                'selectedRankSettingIds' => $selectedRankSettingIds,
                'selectedRankSettingIds_count' => count($selectedRankSettingIds),
                'user_id' => Auth::id()
            ]);

            if (empty($selectedRankSettingIds)) {
                Log::warning('Ã¢ÂÅ’ STEP 3 FAILED: No criteria selected for ranking', [
                    'user_id' => Auth::id()
                ]);
                // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™Ë† Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª
                $this->specific_criteria = null;
                $this->sortField = 'created_at';
                $this->sortDirection = 'desc';
                $this->resetPage();
                $this->clearFamiliesCache();
                // Ã˜Â¨Ã˜Â³Ã˜ÂªÃ™â€  Ã™â€¦Ã™Ë†Ã˜Â¯Ã˜Â§Ã™â€ž
                $this->showRankModal = false;
                $this->dispatch('notify', [
                    'message' => 'Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™Ë† Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã™â€¡Ã˜Â§ Ã™Â¾Ã˜Â§ÃšÂ© Ã˜Â´Ã˜Â¯',
                    'type' => 'info'
                ]);
                return;
            }

            // Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ id Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã™â€¡Ã˜Â§ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± (Ã™â€¦Ã˜Â§Ã™â€ Ã™â€ Ã˜Â¯ FamiliesApproval)
            $this->specific_criteria = implode(',', $selectedRankSettingIds);

            // Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦ Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’
            $this->sortField = 'weighted_rank';
            $this->sortDirection = 'desc'; // Ã˜Â§Ã™â€¦Ã˜ÂªÃ›Å’Ã˜Â§Ã˜Â² Ã˜Â¨Ã˜Â§Ã™â€žÃ˜Â§Ã˜ÂªÃ˜Â± Ã˜Â§Ã™Ë†Ã™â€ž

            Log::info('Ã¢Å¡â„¢Ã¯Â¸Â STEP 3.3: Sort parameters set', [
                'sortField' => $this->sortField,
                'sortDirection' => $this->sortDirection,
                'specific_criteria' => $this->specific_criteria,
                'user_id' => Auth::id()
            ]);

            // Reset Ã˜ÂµÃ™ÂÃ˜Â­Ã™â€¡ Ã™Ë† cache
            $this->resetPage();
            $this->clearFamiliesCache();

            $criteriaList = implode('Ã˜Å’ ', $selectedRankSettingIds);

            $this->dispatch('notify', [
                'message' => "Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã™â€¡Ã˜Â§ Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜Â´Ã˜Â¯: {$criteriaList}",
                'type' => 'success'
            ]);

            // Ã˜Â¨Ã˜Â³Ã˜ÂªÃ™â€  Ã™â€¦Ã™Ë†Ã˜Â¯Ã˜Â§Ã™â€ž
            $this->showRankModal = false;

            Log::info('Ã¢Å“â€¦ STEP 3 COMPLETED: Ranking sort applied successfully', [
                'criteria_ids' => $selectedRankSettingIds,
                'sort_field' => $this->sortField,
                'sort_direction' => $this->sortDirection,
                'user_id' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error('Ã¢ÂÅ’ STEP 3 ERROR: Error in ranking sort: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡
     */
    public function editRankSetting($id)
    {
        try {
            $setting = RankSetting::find($id);
            if ($setting) {
                // Ã™Â¾Ã˜Â± ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã™ÂÃ˜Â±Ã™â€¦ Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™â€šÃ˜Â§Ã˜Â¯Ã›Å’Ã˜Â± Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã™â€¦Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ - Ã˜Â¨Ã˜Â§ Ã™Â¾Ã˜Â´Ã˜ÂªÃ›Å’Ã˜Â¨Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â§Ã˜Â² Ã™â€¡Ã˜Â± Ã˜Â¯Ã™Ë† Ã™â€ Ã˜Â§Ã™â€¦ Ã™ÂÃ›Å’Ã™â€žÃ˜Â¯
                $this->rankSettingName = $setting->name;
                $this->rankSettingDescription = $setting->description;
                $this->rankSettingWeight = $setting->weight;

                // Ã™Â¾Ã˜Â´Ã˜ÂªÃ›Å’Ã˜Â¨Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â§Ã˜Â² Ã™â€¡Ã˜Â± Ã˜Â¯Ã™Ë† Ã™â€ Ã˜Â§Ã™â€¦ Ã™ÂÃ›Å’Ã™â€žÃ˜Â¯ Ã˜Â±Ã™â€ ÃšÂ¯
                if (isset($setting->bg_color)) {
                    $this->rankSettingColor = $setting->bg_color;
                } elseif (isset($setting->color)) {
                    $this->rankSettingColor = $setting->color;
                } else {
                    $this->rankSettingColor = 'bg-green-100';
                }

                // Ã™Â¾Ã˜Â´Ã˜ÂªÃ›Å’Ã˜Â¨Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â§Ã˜Â² Ã™â€¡Ã˜Â± Ã˜Â¯Ã™Ë† Ã™â€ Ã˜Â§Ã™â€¦ Ã™ÂÃ›Å’Ã™â€žÃ˜Â¯ Ã™â€ Ã›Å’Ã˜Â§Ã˜Â² Ã˜Â¨Ã™â€¡ Ã™â€¦Ã˜Â¯Ã˜Â±ÃšÂ©
                if (isset($setting->requires_document)) {
                    $this->rankSettingNeedsDoc = $setting->requires_document ? 1 : 0;
                } elseif (isset($setting->needs_doc)) {
                    $this->rankSettingNeedsDoc = $setting->needs_doc ? 1 : 0;
                } else {
                    $this->rankSettingNeedsDoc = 1;
                }

                $this->editingRankSettingId = $id;
                $this->isEditingMode = true; // Ã™â€¦Ã˜Â´Ã˜Â®Ã˜Âµ Ã™â€¦Ã›Å’Ã¢â‚¬Å’ÃšÂ©Ã™â€ Ã˜Â¯ ÃšÂ©Ã™â€¡ Ã˜Â¯Ã˜Â± Ã˜Â­Ã˜Â§Ã™â€ž Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ Ã™â€¡Ã˜Â³Ã˜ÂªÃ›Å’Ã™â€¦ Ã™â€ Ã™â€¡ Ã˜Â§Ã™ÂÃ˜Â²Ã™Ë†Ã˜Â¯Ã™â€ 

                // Ã˜Â«Ã˜Â¨Ã˜Âª Ã˜Â¯Ã˜Â± Ã™â€žÃ˜Â§ÃšÂ¯
                Log::info('Editing rank setting:', [
                    'id' => $setting->id,
                    'name' => $setting->name
                ]);

                $this->dispatch('notify', [
                    'message' => 'Ã˜Â¯Ã˜Â± Ã˜Â­Ã˜Â§Ã™â€ž Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±: ' . $setting->name,
                    'type' => 'info'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error loading rank setting:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            $this->dispatch('notify', [
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ Ã˜Â§Ã˜Â·Ã™â€žÃ˜Â§Ã˜Â¹Ã˜Â§Ã˜Âª Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Ã˜Â±Ã›Å’Ã˜Â³Ã˜Âª ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã™ÂÃ˜Â±Ã™â€¦ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± - Ã™â€¦Ã˜ÂªÃ˜Â¯ Ã˜Â¹Ã™â€¦Ã™Ë†Ã™â€¦Ã›Å’
     */
    public function resetRankSettingForm()
    {
        $this->rankSettingName = '';
        $this->rankSettingDescription = '';
        $this->rankSettingWeight = 5;
        $this->rankSettingColor = '#60A5FA';
        $this->rankSettingNeedsDoc = true;
        $this->editingRankSettingId = null;
        $this->isEditingMode = false; // Ã™â€¦Ã˜Â´Ã˜Â®Ã˜Âµ Ã™â€¦Ã›Å’Ã¢â‚¬Å’ÃšÂ©Ã™â€ Ã˜Â¯ ÃšÂ©Ã™â€¡ Ã˜Â¯Ã˜Â± Ã˜Â­Ã˜Â§Ã™â€ž Ã˜Â§Ã™ÂÃ˜Â²Ã™Ë†Ã˜Â¯Ã™â€  Ã™â€¡Ã˜Â³Ã˜ÂªÃ›Å’Ã™â€¦ Ã™â€ Ã™â€¡ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´

        // Ã˜Â§Ã˜Â·Ã™â€žÃ˜Â§Ã˜Â¹Ã¢â‚¬Å’Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â¨Ã™â€¡ ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â± Ã˜Â¯Ã˜Â± Ã˜ÂµÃ™Ë†Ã˜Â±Ã˜ÂªÃ›Å’ ÃšÂ©Ã™â€¡ Ã˜Â§Ã›Å’Ã™â€  Ã™â€¦Ã˜ÂªÃ˜Â¯ Ã™â€¦Ã˜Â³Ã˜ÂªÃ™â€šÃ›Å’Ã™â€¦Ã˜Â§Ã™â€¹ Ã˜Â§Ã˜Â² UI Ã™ÂÃ˜Â±Ã˜Â§Ã˜Â®Ã™Ë†Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â´Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯
        if (request()->hasHeader('x-livewire')) {
            $this->dispatch('notify', [
                'message' => 'Ã™ÂÃ˜Â±Ã™â€¦ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€ Ã˜Â´Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â´Ã˜Â¯',
                'type' => 'info'
            ]);
        }
    }

    /**
     * Ã˜Â¨Ã˜Â§Ã˜Â²ÃšÂ¯Ã˜Â´Ã˜Âª Ã˜Â¨Ã™â€¡ Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª Ã™Â¾Ã›Å’Ã˜Â´Ã™ÂÃ˜Â±Ã˜Â¶
     */
    public function resetToDefaults()
    {
        // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡
        $this->family_rank_range = null;
        $this->specific_criteria = null;
        $this->selectedCriteria = [];

        // Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€ Ã˜Â´Ã˜Â§Ã™â€ Ã›Å’ Ã˜ÂµÃ™ÂÃ˜Â­Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã™Ë† Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã™â€žÃ›Å’Ã˜Â³Ã˜Âª
        $this->resetPage();
        $this->closeRankModal();

        // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  ÃšÂ©Ã˜Â´ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â§Ã˜Â·Ã™â€¦Ã›Å’Ã™â€ Ã˜Â§Ã™â€  Ã˜Â§Ã˜Â² Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â¯Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§
        if (Auth::check()) {
            cache()->forget('families_query_' . Auth::id());
        }

        $this->dispatch('notify', [
            'message' => 'Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡ Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â¨Ã™â€¡ Ã˜Â­Ã˜Â§Ã™â€žÃ˜Âª Ã™Â¾Ã›Å’Ã˜Â´Ã™ÂÃ˜Â±Ã˜Â¶ Ã˜Â¨Ã˜Â§Ã˜Â²ÃšÂ¯Ã˜Â±Ã˜Â¯Ã˜Â§Ã™â€ Ã˜Â¯Ã™â€¡ Ã˜Â´Ã˜Â¯',
            'type' => 'success'
        ]);
    }

    /**
     * Ã˜Â­Ã˜Â°Ã™Â Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±
     */
    public function deleteRankSetting($id)
    {
        try {
            $setting = RankSetting::find($id);
            if ($setting) {
                $name = $setting->name;
                $setting->delete();

                $this->dispatch('notify', [
                    'message' => "Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã‚Â«{$name}Ã‚Â» Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â­Ã˜Â°Ã™Â Ã˜Â´Ã˜Â¯",
                    'type' => 'warning'
                ]);

                // Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ Ã™â€¦Ã˜Â¬Ã˜Â¯Ã˜Â¯ Ã™â€žÃ›Å’Ã˜Â³Ã˜Âª
                $this->availableRankSettings = RankSetting::active()->ordered()->get();
            }
        } catch (\Exception $e) {
            Log::error('Error deleting rank setting:', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            $this->dispatch('notify', [
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â­Ã˜Â°Ã™Â Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’
     */
    public function saveRankSetting()
    {
        try {
            // Ã˜Â§Ã˜Â¹Ã˜ÂªÃ˜Â¨Ã˜Â§Ã˜Â±Ã˜Â³Ã™â€ Ã˜Â¬Ã›Å’
            if ($this->editingRankSettingId) {
                // Ã˜Â¯Ã˜Â± Ã˜Â­Ã˜Â§Ã™â€žÃ˜Âª Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ Ã™ÂÃ™â€šÃ˜Â· Ã™Ë†Ã˜Â²Ã™â€  Ã™â€šÃ˜Â§Ã˜Â¨Ã™â€ž Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Âª
                $this->validate([
                    'rankSettingWeight' => 'required|integer|min:0|max:10',
                ]);
            } else {
                // Ã˜Â¯Ã˜Â± Ã˜Â­Ã˜Â§Ã™â€žÃ˜Âª Ã˜Â§Ã™ÂÃ˜Â²Ã™Ë†Ã˜Â¯Ã™â€  Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯ Ã™â€¡Ã™â€¦Ã™â€¡ Ã™ÂÃ›Å’Ã™â€žÃ˜Â¯Ã™â€¡Ã˜Â§ Ã˜Â§Ã™â€žÃ˜Â²Ã˜Â§Ã™â€¦Ã›Å’ Ã™â€¡Ã˜Â³Ã˜ÂªÃ™â€ Ã˜Â¯
                $this->validate([
                    'rankSettingName' => 'required|string|max:255',
                    'rankSettingWeight' => 'required|integer|min:0|max:10',
                    'rankSettingDescription' => 'nullable|string',
                    'rankSettingNeedsDoc' => 'required|boolean',
                ]);
            }

            if ($this->editingRankSettingId) {
                // Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã™â€¦Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ - Ã™ÂÃ™â€šÃ˜Â· Ã™Ë†Ã˜Â²Ã™â€ 
                $setting = RankSetting::find($this->editingRankSettingId);
                if ($setting) {
                    $setting->weight = $this->rankSettingWeight;
                    $setting->save();

                    $this->dispatch('notify', [
                        'message' => 'Ã™Ë†Ã˜Â²Ã™â€  Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â´Ã˜Â¯: ' . $setting->name,
                        'type' => 'success'
                    ]);
                }
            } else {
                // Ã˜Â§Ã›Å’Ã˜Â¬Ã˜Â§Ã˜Â¯ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯
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
                    'message' => 'Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯ Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â§Ã›Å’Ã˜Â¬Ã˜Â§Ã˜Â¯ Ã˜Â´Ã˜Â¯: ' . $this->rankSettingName,
                    'type' => 'success'
                ]);
            }

            // Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ Ã™â€¦Ã˜Â¬Ã˜Â¯Ã˜Â¯ Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª
            $this->availableRankSettings = RankSetting::active()->ordered()->get();
            $this->clearFamiliesCache();
            $this->resetRankSettingForm();

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã™Ë† Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜Â¢Ã™â€ 
     *
     * @param int $filterId Ã˜Â´Ã™â€ Ã˜Â§Ã˜Â³Ã™â€¡ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±
     * @return bool
     */
    public function loadRankFilter($filterId)
    {
        try {
            $user = auth()->user();

            // Ã™ÂÃ™â€šÃ˜Â· Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã˜Â±Ã˜Â§ Ã˜Â¬Ã˜Â³Ã˜ÂªÃ˜Â¬Ã™Ë† ÃšÂ©Ã™â€ 
            $filter = SavedFilter::where('filter_type', 'rank_settings')
                ->where(function ($q) use ($user) {
                    // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â®Ã™Ë†Ã˜Â¯ ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â±
                    $q->where('user_id', $user->id)
                      // Ã›Å’Ã˜Â§ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â³Ã˜Â§Ã˜Â²Ã™â€¦Ã˜Â§Ã™â€ Ã›Å’ (Ã˜Â§ÃšÂ¯Ã˜Â± ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â± Ã˜Â¹Ã˜Â¶Ã™Ë† Ã˜Â³Ã˜Â§Ã˜Â²Ã™â€¦Ã˜Â§Ã™â€  Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯)
                      ->orWhere('organization_id', $user->organization_id);
                })
                ->find($filterId);

            if (!$filter) {
                $this->dispatch('notify', [
                    'message' => 'Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª Ã™â€ Ã˜Â´Ã˜Â¯ Ã›Å’Ã˜Â§ Ã™â€¦Ã˜Â®Ã˜ÂµÃ™Ë†Ã˜Âµ Ã˜Â§Ã›Å’Ã™â€  Ã˜Â¨Ã˜Â®Ã˜Â´ Ã™â€ Ã›Å’Ã˜Â³Ã˜Âª',
                    'type' => 'warning'
                ]);
                return false;
            }

            // Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±
            $config = $filter->filters_config;

            $this->selectedCriteria = $config['selectedCriteria'] ?? [];
            $this->family_rank_range = $config['family_rank_range'] ?? '';
            $this->specific_criteria = $config['specific_criteria'] ?? '';

            // Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€ Ã˜Â´Ã˜Â§Ã™â€ Ã›Å’ Ã˜ÂµÃ™ÂÃ˜Â­Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’
            $this->resetPage();

            // Ã˜Â§Ã™ÂÃ˜Â²Ã˜Â§Ã›Å’Ã˜Â´ Ã˜ÂªÃ˜Â¹Ã˜Â¯Ã˜Â§Ã˜Â¯ Ã˜Â§Ã˜Â³Ã˜ÂªÃ™ÂÃ˜Â§Ã˜Â¯Ã™â€¡ Ã™Ë† Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â¢Ã˜Â®Ã˜Â±Ã›Å’Ã™â€  Ã˜Â²Ã™â€¦Ã˜Â§Ã™â€  Ã˜Â§Ã˜Â³Ã˜ÂªÃ™ÂÃ˜Â§Ã˜Â¯Ã™â€¡
            $filter->increment('usage_count');
            $filter->update(['last_used_at' => now()]);

            // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  ÃšÂ©Ã˜Â´
            $this->clearFamiliesCache();

            $this->dispatch('notify', [
                'message' => 'Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡ "' . $filter->name . '" Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ Ã˜Â´Ã˜Â¯',
                'type' => 'success'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error loading rank filter: ' . $e->getMessage());
            $this->dispatch('notify', [
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’: ' . $e->getMessage(),
                'type' => 'error'
            ]);
            return false;
        }
    }

    /**
     * Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡
     *
     * @param string $name Ã™â€ Ã˜Â§Ã™â€¦ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±
     * @param string $description Ã˜ÂªÃ™Ë†Ã˜Â¶Ã›Å’Ã˜Â­Ã˜Â§Ã˜Âª Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±
     * @return bool
     */
    public function saveRankFilter($name, $description = '')
    {
        try {
            // Ã˜Â§Ã˜Â¹Ã˜ÂªÃ˜Â¨Ã˜Â§Ã˜Â±Ã˜Â³Ã™â€ Ã˜Â¬Ã›Å’ Ã™Ë†Ã˜Â±Ã™Ë†Ã˜Â¯Ã›Å’
            if (empty(trim($name))) {
                $this->dispatch('notify', [
                    'message' => 'Ã™â€ Ã˜Â§Ã™â€¦ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â§Ã™â€žÃ˜Â²Ã˜Â§Ã™â€¦Ã›Å’ Ã˜Â§Ã˜Â³Ã˜Âª',
                    'type' => 'error'
                ]);
                return false;
            }

            // Ã˜ÂªÃ™â€¡Ã›Å’Ã™â€¡ Ã™Â¾Ã›Å’ÃšÂ©Ã˜Â±Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™ÂÃ˜Â¹Ã™â€žÃ›Å’ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡
            $filtersConfig = [
                'selectedCriteria' => $this->selectedCriteria,
                'family_rank_range' => $this->family_rank_range,
                'specific_criteria' => $this->specific_criteria,
                // Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã›Å’Ã˜Â¯ Ã™ÂÃ›Å’Ã™â€žÃ˜Â¯Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â¯Ã›Å’ÃšÂ¯Ã˜Â± Ã™â€¦Ã˜Â±Ã˜Â¨Ã™Ë†Ã˜Â· Ã˜Â¨Ã™â€¡ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã˜Â±Ã˜Â§ Ã˜Â§Ã˜Â¶Ã˜Â§Ã™ÂÃ™â€¡ ÃšÂ©Ã™â€ Ã›Å’Ã˜Â¯
            ];

            // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã˜Â§Ã›Å’Ã™â€ ÃšÂ©Ã™â€¡ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã›Å’ Ã˜Â¨Ã˜Â§ Ã™â€¡Ã™â€¦Ã›Å’Ã™â€  Ã™â€ Ã˜Â§Ã™â€¦ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â§Ã›Å’Ã™â€  ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â± Ã™Ë† Ã™â€ Ã™Ë†Ã˜Â¹ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã™â€ Ã˜Â¯Ã˜Â§Ã˜Â±Ã˜Â¯
            $existingFilter = SavedFilter::where('user_id', auth()->id())
                                        ->where('name', trim($name))
                                        ->where('filter_type', 'rank_settings')
                                        ->first();

            if ($existingFilter) {
                $this->dispatch('notify', [
                    'message' => 'Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã›Å’ Ã˜Â¨Ã˜Â§ Ã˜Â§Ã›Å’Ã™â€  Ã™â€ Ã˜Â§Ã™â€¦ Ã™â€šÃ˜Â¨Ã™â€žÃ˜Â§Ã™â€¹ Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã˜Â´Ã˜Â¯Ã™â€¡ Ã˜Â§Ã˜Â³Ã˜Âª',
                    'type' => 'error'
                ]);
                return false;
            }

            // Ã˜Â§Ã›Å’Ã˜Â¬Ã˜Â§Ã˜Â¯ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯
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
                'message' => 'Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡ "' . $name . '" Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã˜Â´Ã˜Â¯',
                'type' => 'success'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error saving rank filter: ' . $e->getMessage());
            $this->dispatch('notify', [
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’: ' . $e->getMessage(),
                'type' => 'error'
            ]);
            return false;
        }
    }

    /**
     * Ã˜Â§Ã˜Â¶Ã˜Â§Ã™ÂÃ™â€¡ ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã›Å’Ã™â€¦Ã˜Â§Ã˜Â±Ã›Å’ Ã˜Â®Ã˜Â§Ã˜Âµ
     */
    public function filterBySpecialDisease()
    {
        $this->status = 'special_disease';
        $this->resetPage();
        $this->dispatch('notify', [
            'message' => 'Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã›Å’Ã™â€¦Ã˜Â§Ã˜Â±Ã›Å’ Ã˜Â®Ã˜Â§Ã˜Âµ Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜Â´Ã˜Â¯',
            'type' => 'success'
        ]);
    }

    /**
     * Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã˜Â¨Ã™â€¡ query builder
     */
    protected function applySortToQueryBuilder($queryBuilder)
    {
        try {
            Log::info('Ã°Å¸Å½Â¯ STEP 4: Starting applySortToQueryBuilder', [
                'sortField' => $this->sortField,
                'sortDirection' => $this->sortDirection,
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            if (empty($this->sortField)) {
                Log::info('Ã°Å¸â€â€ž STEP 4: No sort field specified, using default', [
                    'user_id' => Auth::id()
                ]);
                return $queryBuilder;
            }

            // Ã˜ÂªÃ˜Â¹Ã˜Â±Ã›Å’Ã™Â Ã™ÂÃ›Å’Ã™â€žÃ˜Â¯Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€šÃ˜Â§Ã˜Â¨Ã™â€ž Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã™Ë† Ã™â€ ÃšÂ¯Ã˜Â§Ã˜Â´Ã˜Âª Ã˜Â¢Ã™â€ Ã™â€¡Ã˜Â§
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

            Log::info('Ã¢Å¡â„¢Ã¯Â¸Â STEP 4.1: Sort parameters prepared', [
                'sortField' => $this->sortField,
                'sortDirection' => $sortDirection,
                'sortMappings' => array_keys($sortMappings),
                'user_id' => Auth::id()
            ]);

            // Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã™â€ Ã™Ë†Ã˜Â¹ Ã™ÂÃ›Å’Ã™â€žÃ˜Â¯
            switch ($this->sortField) {
                case 'head_name':
                    Log::info('Ã°Å¸â€œâ€¹ STEP 4.2: Applying head_name sort');
                    // Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã˜Â®Ã˜Â§Ã˜Âµ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™â€ Ã˜Â§Ã™â€¦ Ã˜Â³Ã˜Â±Ã™Â¾Ã˜Â±Ã˜Â³Ã˜Âª
                    $queryBuilder->getEloquentBuilder()
                        ->leftJoin('people as head_person', 'families.head_id', '=', 'head_person.id')
                        ->orderBy('head_person.first_name', $sortDirection)
                        ->orderBy('head_person.last_name', $sortDirection);
                    break;

                case 'final_insurances_count':
                    Log::info('Ã°Å¸â€œâ€¹ STEP 4.2: Applying final_insurances_count sort');
                    // Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã˜ÂªÃ˜Â¹Ã˜Â¯Ã˜Â§Ã˜Â¯ Ã˜Â¨Ã›Å’Ã™â€¦Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€ Ã™â€¡Ã˜Â§Ã›Å’Ã›Å’
                    $queryBuilder->getEloquentBuilder()
                        ->withCount('finalInsurances')
                        ->orderBy('final_insurances_count', $sortDirection);
                    break;

                case 'calculated_rank':
                    Log::info('Ã°Å¸â€œâ€¹ STEP 4.2: Applying calculated_rank sort');
                    // Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡ Ã™â€¦Ã˜Â­Ã˜Â§Ã˜Â³Ã˜Â¨Ã™â€¡ Ã˜Â´Ã˜Â¯Ã™â€¡
                    if ($sortDirection === 'desc') {
                        $queryBuilder->getEloquentBuilder()->orderByRaw('families.calculated_rank IS NULL, families.calculated_rank DESC');
                    } else {
                        $queryBuilder->getEloquentBuilder()->orderByRaw('families.calculated_rank IS NULL, families.calculated_rank ASC');
                    }
                    break;

                case 'weighted_rank':
                    Log::info('Ã°Å¸â€œâ€¹ STEP 4.2: Applying weighted_rank sort');
                    // Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã˜Â§Ã™â€¦Ã˜ÂªÃ›Å’Ã˜Â§Ã˜Â² Ã™Ë†Ã˜Â²Ã™â€ Ã›Å’ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â´Ã˜Â¯Ã™â€¡
                    $this->applyWeightedRankSort($queryBuilder, $sortDirection);
                    break;

                default:
                    Log::info('Ã°Å¸â€œâ€¹ STEP 4.2: Applying default sort');
                    // Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã™â€¦Ã˜Â¹Ã™â€¦Ã™Ë†Ã™â€žÃ›Å’ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â³Ã˜Â§Ã›Å’Ã˜Â± Ã™ÂÃ›Å’Ã™â€žÃ˜Â¯Ã™â€¡Ã˜Â§
                    if (isset($sortMappings[$this->sortField])) {
                        $fieldName = $sortMappings[$this->sortField];
                        $queryBuilder->getEloquentBuilder()->orderBy($fieldName, $sortDirection);
                    } else {
                        Log::warning('Ã¢Å¡Â Ã¯Â¸Â STEP 4 WARNING: Unknown sort field', [
                            'sort_field' => $this->sortField,
                            'user_id' => Auth::id()
                        ]);
                        // Ã˜Â¨Ã˜Â§Ã˜Â²ÃšÂ¯Ã˜Â´Ã˜Âª Ã˜Â¨Ã™â€¡ Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã™Â¾Ã›Å’Ã˜Â´Ã¢â‚¬Å’Ã™ÂÃ˜Â±Ã˜Â¶
                        $queryBuilder->getEloquentBuilder()->orderBy('families.created_at', 'desc');
                    }
                    break;
            }

            Log::info('Ã¢Å“â€¦ STEP 4 COMPLETED: Sort applied successfully', [
                'sort_field' => $this->sortField,
                'sort_direction' => $sortDirection,
                'user_id' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error('Ã¢ÂÅ’ STEP 4 ERROR: Error applying sort', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            // Ã˜Â¯Ã˜Â± Ã˜ÂµÃ™Ë†Ã˜Â±Ã˜Âª Ã˜Â®Ã˜Â·Ã˜Â§Ã˜Å’ Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã˜ÂªÃ˜Â§Ã˜Â±Ã›Å’Ã˜Â® Ã˜Â§Ã›Å’Ã˜Â¬Ã˜Â§Ã˜Â¯
            $queryBuilder->getEloquentBuilder()->orderBy('families.created_at', 'desc');
        }
    }

    /**
     * Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã™Ë†Ã˜Â²Ã™â€ Ã›Å’ Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â´Ã˜Â¯Ã™â€¡
     */
    protected function applyWeightedRankSort($queryBuilder, $sortDirection)
    {
        try {
            Log::info('Ã°Å¸Å½Â¯ STEP 5: Starting applyWeightedRankSort', [
                'sortDirection' => $sortDirection,
                'selectedCriteria' => $this->selectedCriteria ?? [],
                'user_id' => Auth::id(),
                'timestamp' => now()
            ]);

            // Ã˜Â¯Ã˜Â±Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â´Ã˜Â¯Ã™â€¡
            $selectedCriteriaIds = array_keys(array_filter($this->selectedCriteria ?? [], fn($value) => $value === true));

            Log::info('Ã°Å¸â€œÅ  STEP 5.1: Selected criteria analysis', [
                'selectedCriteriaIds' => $selectedCriteriaIds,
                'selectedCriteriaIds_count' => count($selectedCriteriaIds),
                'user_id' => Auth::id()
            ]);

            if (empty($selectedCriteriaIds)) {
                Log::warning('Ã¢ÂÅ’ STEP 5 FAILED: No criteria selected for weighted sort', [
                    'user_id' => Auth::id()
                ]);
                // Ã˜Â§ÃšÂ¯Ã˜Â± Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã›Å’ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã™â€ Ã˜Â´Ã˜Â¯Ã™â€¡Ã˜Å’ Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã˜ÂªÃ˜Â§Ã˜Â±Ã›Å’Ã˜Â® Ã˜Â§Ã›Å’Ã˜Â¬Ã˜Â§Ã˜Â¯
                $queryBuilder->getEloquentBuilder()->orderBy('families.created_at', 'desc');
                return;
            }

            // Ã˜Â§Ã›Å’Ã˜Â¬Ã˜Â§Ã˜Â¯ subquery Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™â€¦Ã˜Â­Ã˜Â§Ã˜Â³Ã˜Â¨Ã™â€¡ Ã˜Â§Ã™â€¦Ã˜ÂªÃ›Å’Ã˜Â§Ã˜Â² Ã™Ë†Ã˜Â²Ã™â€ Ã›Å’ Ã˜Â¨Ã˜Â§ Ã˜Â¶Ã˜Â±Ã˜Â¨ Ã™Ë†Ã˜Â²Ã™â€  Ã˜Â¯Ã˜Â± Ã˜ÂªÃ˜Â¹Ã˜Â¯Ã˜Â§Ã˜Â¯ Ã™â€¦Ã™Ë†Ã˜Â§Ã˜Â±Ã˜Â¯
            $criteriaIds = implode(',', $selectedCriteriaIds);
            $weightedScoreSubquery = "
                (
                    SELECT COALESCE(SUM(
                        rs.weight * (
                            -- Ã˜Â´Ã™â€¦Ã˜Â§Ã˜Â±Ã˜Â´ Ã™â€¦Ã™Ë†Ã˜Â§Ã˜Â±Ã˜Â¯ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â¯Ã˜Â± acceptance_criteria (0 Ã›Å’Ã˜Â§ 1)
                            CASE
                                WHEN JSON_CONTAINS(families.acceptance_criteria, CAST(rs.id AS JSON))
                                THEN 1
                                ELSE 0
                            END +
                            -- Ã˜Â´Ã™â€¦Ã˜Â§Ã˜Â±Ã˜Â´ Ã˜ÂªÃ˜Â¹Ã˜Â¯Ã˜Â§Ã˜Â¯ Ã˜Â§Ã˜Â¹Ã˜Â¶Ã˜Â§Ã›Å’ Ã˜Â¯Ã˜Â§Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â§Ã›Å’Ã™â€  Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã˜Â¯Ã˜Â± problem_type
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

            Log::info('Ã¢Å¡â„¢Ã¯Â¸Â STEP 5.2: Weighted score subquery created', [
                'criteriaIds' => $criteriaIds,
                'weightedScoreSubquery_length' => strlen($weightedScoreSubquery),
                'user_id' => Auth::id()
            ]);

            // Ã˜Â§Ã˜Â¶Ã˜Â§Ã™ÂÃ™â€¡ ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã˜Â§Ã™â€¦Ã˜ÂªÃ›Å’Ã˜Â§Ã˜Â² Ã™â€¦Ã˜Â­Ã˜Â§Ã˜Â³Ã˜Â¨Ã™â€¡ Ã˜Â´Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã™â€¡ select
            $queryBuilder->getEloquentBuilder()
                ->addSelect(DB::raw("({$weightedScoreSubquery}) as weighted_score"))
                ->orderBy('weighted_score', $sortDirection)
                ->orderBy('families.created_at', 'desc'); // Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã˜Â«Ã˜Â§Ã™â€ Ã™Ë†Ã›Å’Ã™â€¡

            Log::info('Ã¢Å“â€¦ STEP 5 COMPLETED: Weighted rank sort applied successfully', [
                'criteria_ids' => $selectedCriteriaIds,
                'sort_direction' => $sortDirection,
                'user_id' => Auth::id()
            ]);

        } catch (\Exception $e) {
            Log::error('Ã¢ÂÅ’ STEP 5 ERROR: Error applying weighted rank sort', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            // Ã˜Â¯Ã˜Â± Ã˜ÂµÃ™Ë†Ã˜Â±Ã˜Âª Ã˜Â®Ã˜Â·Ã˜Â§Ã˜Å’ Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã˜ÂªÃ˜Â§Ã˜Â±Ã›Å’Ã˜Â® Ã˜Â§Ã›Å’Ã˜Â¬Ã˜Â§Ã˜Â¯
            $queryBuilder->getEloquentBuilder()->orderBy('families.created_at', 'desc');
        }
    }

    /**
     * دانلود فایل اکسل برای خانواده‌های موجود در صفحه
     */
    public function downloadPageExcel()
    {
        try {
            // دریافت query با تمام فیلترها و eager loading
            $queryBuilder = $this->buildFamiliesQuery();
            $query = $queryBuilder->getEloquentBuilder();

            // محاسبه offset برای صفحه فعلی
            $offset = ($this->page - 1) * $this->perPage;

            // محدود کردن به خانواده‌های صفحه فعلی
            $families = $query->skip($offset)->take($this->perPage)->get();

            // بررسی خالی بودن نتایج
            if ($families->isEmpty()) {
                session()->flash('error', 'هیچ خانواده‌ای برای دانلود یافت نشد.');
                return;
            }

            // ساخت نام فایل
            $filename = 'families-page-' . $this->page . '-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

            // استفاده از کلاس جدید FamilySearchExport
            return Excel::download(
                new \App\Exports\FamilySearchExport($families, $this->status),
                $filename
            );
        } catch (\Exception $e) {
            Log::error('Error downloading page Excel', [
                'page' => $this->page,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            session()->flash('error', 'خطا در دانلود فایل اکسل. لطفاً دوباره تلاش کنید.');
            return;
        }
    }

    /**
     * دانلود فایل اکسل برای تمام خانواده‌های فیلتر شده
     */
    public function downloadAllExcel()
    {
        try {
            // دریافت query با تمام فیلترها و eager loading
            $queryBuilder = $this->buildFamiliesQuery();
            $query = $queryBuilder->getEloquentBuilder();

            // بررسی تعداد رکوردها برای جلوگیری از timeout
            $totalCount = $query->count();

            // محدودیت برای جلوگیری از مشکلات حافظه و timeout
            $maxRecords = 10000;
            if ($totalCount > $maxRecords) {
                session()->flash('error', "تعداد خانواده‌های انتخاب شده ({$totalCount}) از حد مجاز ({$maxRecords}) بیشتر است. لطفاً فیلترهای بیشتری اعمال کنید.");
                return;
            }

            // نمایش notification برای دانلودهای بزرگ
            if ($totalCount > 5000) {
                session()->flash('warning', 'تعداد رکوردها زیاد است. دانلود ممکن است چند لحظه طول بکشد.');
            }

            // دریافت تمام نتایج
            $families = $query->get();

            // بررسی خالی بودن نتایج
            if ($families->isEmpty()) {
                session()->flash('error', 'هیچ خانواده‌ای برای دانلود یافت نشد.');
                return;
            }

            // logging برای ردیابی دانلودهای بزرگ
            Log::info('Downloading all families', [
                'count' => $families->count(),
                'user_id' => Auth::id(),
                'status' => $this->status,
                'filters' => [
                    'search' => $this->search,
                    'province_id' => $this->province_id,
                    'city_id' => $this->city_id,
                    'charity_id' => $this->charity_id,
                    'status' => $this->status
                ]
            ]);

            // ساخت نام فایل
            $filename = 'families-all-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

            // استفاده از کلاس FamilySearchExport
            return Excel::download(
                new \App\Exports\FamilySearchExport($families, $this->status),
                $filename
            );
        } catch (\Exception $e) {
            Log::error('Error downloading all Excel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            session()->flash('error', 'خطا در دانلود فایل اکسل. لطفاً دوباره تلاش کنید.');
            return;
        }
    }

    /**
     * Ã˜Â´Ã˜Â±Ã™Ë†Ã˜Â¹ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ Ã˜Â¹Ã˜Â¶Ã™Ë† Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡
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
                    'message' => 'Ã˜Â¹Ã˜Â¶Ã™Ë† Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª Ã™â€ Ã˜Â´Ã˜Â¯',
                    'type' => 'error'
                ]);
                return;
            }

            // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã™â€¦Ã˜Â¬Ã™Ë†Ã˜Â² Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´
            $family = $member->family;
            try {
                Gate::authorize('updateMembers', $family);
            } catch (AuthorizationException $e) {
                // Ã˜Â³Ã˜Â§Ã˜Â®Ã˜Âª Ã™Â¾Ã›Å’Ã˜Â§Ã™â€¦ Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã™Ë†Ã˜Â¶Ã˜Â¹Ã›Å’Ã˜Âª wizard_status
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

            // Ã˜Â¯Ã˜Â±Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª Ã˜Â¢Ã˜Â±Ã˜Â§Ã›Å’Ã™â€¡ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™Â¾Ã˜Â°Ã›Å’Ã˜Â±Ã˜Â´ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ dropdown
            $problemTypesArray = $member->getProblemTypesArray(); // English keys for the dropdown

            // Ã˜Â­Ã˜Â°Ã™Â Ã˜ÂªÃšÂ©Ã˜Â±Ã˜Â§Ã˜Â±Ã›Å’Ã¢â‚¬Å’Ã™â€¡Ã˜Â§ (Ã˜Â¨Ã˜Â¯Ã™Ë†Ã™â€  sort Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â­Ã™ÂÃ˜Â¸ Ã˜ÂªÃ˜Â±Ã˜ÂªÃ›Å’Ã˜Â¨ insertion order)
            if (is_array($problemTypesArray)) {
                $problemTypesArray = array_unique($problemTypesArray);
                // sort() Ã˜Â­Ã˜Â°Ã™Â Ã˜Â´Ã˜Â¯: Ã˜ÂªÃ˜Â±Ã˜ÂªÃ›Å’Ã˜Â¨ insertion order Ã˜Â­Ã™ÂÃ˜Â¸ Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜Â´Ã™Ë†Ã˜Â¯
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
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â´Ã˜Â±Ã™Ë†Ã˜Â¹ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â±Ã˜Â§Ã˜Âª Ã˜Â¹Ã˜Â¶Ã™Ë† Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡
     * @return void
     */
    public function saveMember()
    {
        try {
            $member = Member::find($this->editingMemberId);
            if (!$member) {
                $this->dispatch('notify', [
                    'message' => 'Ã˜Â¹Ã˜Â¶Ã™Ë† Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª Ã™â€ Ã˜Â´Ã˜Â¯',
                    'type' => 'error'
                ]);
                return;
            }

            // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã™â€¦Ã˜Â¬Ã™Ë†Ã˜Â² Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ Ã™â€šÃ˜Â¨Ã™â€ž Ã˜Â§Ã˜Â² validation
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

                // Ã™â€žÃ˜ÂºÃ™Ë† Ã˜Â­Ã˜Â§Ã™â€žÃ˜Âª Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´
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
                'editingMemberData.relationship.required' => 'Ã™â€ Ã˜Â³Ã˜Â¨Ã˜Âª Ã˜Â§Ã™â€žÃ˜Â²Ã˜Â§Ã™â€¦Ã›Å’ Ã˜Â§Ã˜Â³Ã˜Âª',
                'editingMemberData.occupation.required' => 'Ã˜Â´Ã˜ÂºÃ™â€ž Ã˜Â§Ã™â€žÃ˜Â²Ã˜Â§Ã™â€¦Ã›Å’ Ã˜Â§Ã˜Â³Ã˜Âª',
                'editingMemberData.problem_type.max' => 'Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã™Â¾Ã˜Â°Ã›Å’Ã˜Â±Ã˜Â´ Ã™â€ Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã˜Â¯ Ã˜Â¨Ã›Å’Ã˜Â´ Ã˜Â§Ã˜Â² 1000 ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â§ÃšÂ©Ã˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯',
            ]);

            // Ã˜Â¢Ã™â€¦Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã˜Â³Ã˜Â§Ã˜Â²Ã›Å’ Ã˜Â¯Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡
            $updateData = [
                'relationship' => $this->editingMemberData['relationship'],
                'relationship_fa' => $this->editingMemberData['relationship'], // Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã™â€¦Ã™â€šÃ˜Â¯Ã˜Â§Ã˜Â± Ã™ÂÃ˜Â§Ã˜Â±Ã˜Â³Ã›Å’ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ relationship_fa
                'occupation' => $this->editingMemberData['occupation'],
            ];

            // Ã™â€¦Ã˜Â¯Ã›Å’Ã˜Â±Ã›Å’Ã˜Âª Ã™â€ Ã™Ë†Ã˜Â¹ Ã˜Â´Ã˜ÂºÃ™â€ž
            if ($this->editingMemberData['occupation'] === 'Ã˜Â´Ã˜Â§Ã˜ÂºÃ™â€ž') {
                $updateData['job_type'] = $this->editingMemberData['job_type'] ?? null;
            } else {
                $updateData['job_type'] = null;
            }

            // Ã™â€¦Ã˜Â¯Ã›Å’Ã˜Â±Ã›Å’Ã˜Âª Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã™Â¾Ã˜Â°Ã›Å’Ã˜Â±Ã˜Â´ (problem_type) - Ã™Â¾Ã›Å’Ã˜Â´Ã˜Â±Ã™ÂÃ˜ÂªÃ™â€¡ Ã™Ë† Ã˜Â¨Ã™â€¡Ã˜Â¨Ã™Ë†Ã˜Â¯ Ã›Å’Ã˜Â§Ã™ÂÃ˜ÂªÃ™â€¡
            // The problem_type array comes from the MultiSelect component via wire:model.live binding. It contains English keys.
            $problemTypeArray = null;
            $problemTypeInput = $this->editingMemberData['problem_type'] ?? '';

            Log::info('Processing problem_type input', [
                'member_id' => $this->editingMemberId,
                'input_type' => gettype($problemTypeInput),
                'input_value_persian' => $problemTypeInput
            ]);

            // Ã™Â¾Ã˜Â±Ã˜Â¯Ã˜Â§Ã˜Â²Ã˜Â´ Ã™â€¦Ã˜Â³Ã˜ÂªÃ™â€šÃ›Å’Ã™â€¦ Ã˜Â¢Ã˜Â±Ã˜Â§Ã›Å’Ã™â€¡ Ã˜Â§Ã˜Â² dropdown
            if (is_array($problemTypeInput)) {
                // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã™â€¦Ã™â€šÃ˜Â§Ã˜Â¯Ã›Å’Ã˜Â± Ã˜Â®Ã˜Â§Ã™â€žÃ›Å’ Ã™Ë† null Ã™Ë† Ã˜ÂªÃšÂ©Ã˜Â±Ã˜Â§Ã˜Â±Ã›Å’Ã¢â‚¬Å’Ã™â€¡Ã˜Â§
                $problemTypesForStorage = array_filter($problemTypeInput, function($item) {
                    return !is_null($item) && trim((string)$item) !== '';
                });

                // Ã˜Â­Ã˜Â°Ã™Â Ã™â€¦Ã™â€šÃ˜Â§Ã˜Â¯Ã›Å’Ã˜Â± Ã˜ÂªÃšÂ©Ã˜Â±Ã˜Â§Ã˜Â±Ã›Å’ (Ã˜Â¨Ã˜Â¯Ã™Ë†Ã™â€  sort Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â­Ã™ÂÃ˜Â¸ insertion order)
                $problemTypesForStorage = array_unique(array_values($problemTypesForStorage));
                // sort() Ã˜Â­Ã˜Â°Ã™Â Ã˜Â´Ã˜Â¯: chipÃ¢â‚¬Å’Ã™â€¡Ã˜Â§ Ã˜Â¨Ã™â€¡ Ã˜ÂªÃ˜Â±Ã˜ÂªÃ›Å’Ã˜Â¨ Ã˜Â§Ã˜Â¶Ã˜Â§Ã™ÂÃ™â€¡ Ã˜Â´Ã˜Â¯Ã™â€  Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã˜Â¯Ã˜Â§Ã˜Â¯Ã™â€¡ Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜Â´Ã™Ë†Ã™â€ Ã˜Â¯

                // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã˜Â§Ã˜Â¶Ã˜Â§Ã™ÂÃ›Å’ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â­Ã˜Â°Ã™Â Ã™â€¦Ã™â€šÃ˜Â§Ã˜Â¯Ã›Å’Ã˜Â± Ã™â€¦Ã˜Â´Ã˜Â§Ã˜Â¨Ã™â€¡
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
                    // Ã˜Â¢Ã˜Â±Ã˜Â§Ã›Å’Ã™â€¡ Ã˜Â®Ã˜Â§Ã™â€žÃ›Å’ Ã˜Â§Ã˜Â³Ã˜Âª Ã›Å’Ã˜Â§ Ã™â€¡Ã›Å’Ãšâ€  Ã™â€¦Ã™â€šÃ˜Â¯Ã˜Â§Ã˜Â± Ã™â€¦Ã˜Â¹Ã˜ÂªÃ˜Â¨Ã˜Â±Ã›Å’ Ã™â€ Ã˜Â¯Ã˜Â§Ã˜Â±Ã˜Â¯
                    $problemTypeArray = null;
                }
            } else if (!empty($problemTypeInput) && trim($problemTypeInput) !== '') {
                // Ã˜Â§ÃšÂ¯Ã˜Â± Ã˜Â±Ã˜Â´Ã˜ÂªÃ™â€¡ Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯ (Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â³Ã˜Â§Ã˜Â²ÃšÂ¯Ã˜Â§Ã˜Â±Ã›Å’ Ã˜Â¨Ã˜Â§ Ã˜Â±Ã™Ë†Ã˜Â´ Ã™â€šÃ˜Â¨Ã™â€žÃ›Å’)
                $problemTypeString = trim((string) $problemTypeInput);

                // Ã˜ÂªÃ™â€šÃ˜Â³Ã›Å’Ã™â€¦ Ã˜Â±Ã˜Â´Ã˜ÂªÃ™â€¡ Ã˜Â¨Ã˜Â§ ÃšÂ©Ã˜Â§Ã™â€¦Ã˜Â§
                $problemTypes = array_map('trim', explode(',', $problemTypeString));

                // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã™â€¦Ã™â€šÃ˜Â§Ã˜Â¯Ã›Å’Ã˜Â± Ã˜Â®Ã˜Â§Ã™â€žÃ›Å’
                $problemTypes = array_filter($problemTypes, function($item) {
                    return !empty(trim($item));
                });

                // Ã˜ÂªÃ˜Â¨Ã˜Â¯Ã›Å’Ã™â€ž Ã™ÂÃ˜Â§Ã˜Â±Ã˜Â³Ã›Å’ Ã˜Â¨Ã™â€¡ Ã˜Â§Ã™â€ ÃšÂ¯Ã™â€žÃ›Å’Ã˜Â³Ã›Å’
                $problemTypesForStorage = [];
                foreach ($problemTypes as $problemType) {
                    $englishValue = \App\Helpers\ProblemTypeHelper::persianToEnglish(trim($problemType));
                    if (!in_array($englishValue, $problemTypesForStorage)) {
                        $problemTypesForStorage[] = $englishValue;
                    }
                }

                // Ã˜Â­Ã˜Â°Ã™Â Ã™â€¦Ã™â€šÃ˜Â§Ã˜Â¯Ã›Å’Ã˜Â± Ã˜ÂªÃšÂ©Ã˜Â±Ã˜Â§Ã˜Â±Ã›Å’ Ã™Ë† Ã™â€¦Ã˜Â±Ã˜ÂªÃ˜Â¨Ã¢â‚¬Å’Ã˜Â³Ã˜Â§Ã˜Â²Ã›Å’
                $problemTypesForStorage = array_unique($problemTypesForStorage);
                sort($problemTypesForStorage);

                if (!empty($problemTypesForStorage)) {
                    $problemTypeArray = array_values($problemTypesForStorage);
                }
            }

            // Ã˜Â§ÃšÂ¯Ã˜Â± Ã˜Â¢Ã˜Â±Ã˜Â§Ã›Å’Ã™â€¡ Ã˜Â®Ã˜Â§Ã™â€žÃ›Å’ Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯Ã˜Å’ null Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ ÃšÂ©Ã™â€  (Ã™â€ Ã™â€¡ Ã˜Â¢Ã˜Â±Ã˜Â§Ã›Å’Ã™â€¡ Ã˜Â®Ã˜Â§Ã™â€žÃ›Å’)
            $updateData['problem_type'] = empty($problemTypeArray) ? null : $problemTypeArray;

            // Ã™â€žÃ˜Â§ÃšÂ¯ Ã™â€ Ã˜ÂªÃ›Å’Ã˜Â¬Ã™â€¡ Ã˜ÂªÃ˜Â¨Ã˜Â¯Ã›Å’Ã™â€ž (Ã™â€¡Ã™â€¦Ã›Å’Ã˜Â´Ã™â€¡ Ã™â€žÃ˜Â§ÃšÂ¯ ÃšÂ©Ã™â€ )
            Log::info('Problem_type conversion completed', [
                'member_id' => $this->editingMemberId,
                'input_raw' => $problemTypeInput,
                'input_type' => gettype($problemTypeInput),
                'input_is_empty' => empty($problemTypeInput),
                'input_is_empty_array' => is_array($problemTypeInput) && empty($problemTypeInput),
                'processed_array' => $problemTypeArray,
                'will_store_in_db' => $updateData['problem_type']
            ]);

            // Ã™â€žÃ˜Â§ÃšÂ¯ ÃšÂ©Ã˜Â§Ã™â€¦Ã™â€ž Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â¯Ã›Å’Ã˜Â¨Ã˜Â§ÃšÂ¯ Ã™Ë† Ã˜Â±Ã˜Â¯Ã›Å’Ã˜Â§Ã˜Â¨Ã›Å’ Ã™â€¦Ã˜Â´ÃšÂ©Ã™â€žÃ˜Â§Ã˜Âª
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

            // Ã™â€žÃ˜Â§ÃšÂ¯ Ã˜Â¯Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ relationship Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ debug
            Log::info('Relationship data being saved', [
                'member_id' => $this->editingMemberId,
                'relationship' => $updateData['relationship'],
                'relationship_fa' => $updateData['relationship_fa']
            ]);

            $member->update($updateData);

            // Ã™â€žÃ˜Â§ÃšÂ¯ Ã˜Â¨Ã˜Â¹Ã˜Â¯ Ã˜Â§Ã˜Â² Ã˜Â¢Ã™Â¾Ã˜Â¯Ã›Å’Ã˜Âª Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜ÂªÃ˜Â£Ã›Å’Ã›Å’Ã˜Â¯ Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â±Ã˜Â§Ã˜Âª
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

            // Ã™â€¡Ã™â€¦ÃšÂ¯Ã˜Â§Ã™â€¦Ã¢â‚¬Å’Ã˜Â³Ã˜Â§Ã˜Â²Ã›Å’ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™Â¾Ã˜Â°Ã›Å’Ã˜Â±Ã˜Â´ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â§Ã˜Â¹Ã˜Â¶Ã˜Â§
            $family = $member->family;
            $family->load('members'); // Ã˜Â§Ã˜Â·Ã™â€¦Ã›Å’Ã™â€ Ã˜Â§Ã™â€  Ã˜Â§Ã˜Â² Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã›Å’Ã˜Â±Ã›Å’ Ã˜Â§Ã˜Â¹Ã˜Â¶Ã˜Â§Ã›Å’ Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â´Ã˜Â¯Ã™â€¡
            $family->syncAcceptanceCriteriaFromMembers();

            // Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã™ÂÃ™Ë†Ã˜Â±Ã›Å’ Ã˜Â¯Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã˜Â­Ã™â€žÃ›Å’ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã˜Â¨Ã™â€žÃ˜Â§Ã™ÂÃ˜Â§Ã˜ÂµÃ™â€žÃ™â€¡
            if ($this->expandedFamily === $member->family_id && !empty($this->familyMembers)) {
                foreach ($this->familyMembers as $key => $familyMember) {
                    if ($familyMember->id == $member->id) {
                        // Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â¯Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â¹Ã˜Â¶Ã™Ë†
                        $this->familyMembers[$key]->relationship = $updateData['relationship'];
                        $this->familyMembers[$key]->occupation = $updateData['occupation'];
                        $this->familyMembers[$key]->job_type = $updateData['job_type'];
                        $this->familyMembers[$key]->problem_type = $updateData['problem_type'];
                        $this->familyMembers[$key]->relationship_fa = $updateData['relationship_fa']; // Ã˜Â§Ã˜ÂµÃ™â€žÃ˜Â§Ã˜Â­: Ã˜Â§Ã˜Â³Ã˜ÂªÃ™ÂÃ˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â§Ã˜Â² relationship_fa Ã™â€ Ã™â€¡ relationship

                        Log::info('Member data updated locally for immediate display', [
                            'member_id' => $member->id,
                            'updated_fields' => array_keys($updateData)
                        ]);
                        break;
                    }
                }

                // Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â§Ã˜Â·Ã™â€žÃ˜Â§Ã˜Â¹Ã˜Â§Ã˜Âª Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¯Ã˜Â± familyMembers Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã™ÂÃ™Ë†Ã˜Â±Ã›Å’
                // Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã™ÂÃ™Ë†Ã˜Â±Ã›Å’Ã˜Å’ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â±Ã˜Â§ Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã™â€¦Ã›Å’Ã¢â‚¬Å’ÃšÂ©Ã™â€ Ã›Å’Ã™â€¦
                $freshFamily = $family->fresh(['members']); // Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã›Å’Ã˜Â±Ã›Å’ Ã™â€¦Ã˜Â¬Ã˜Â¯Ã˜Â¯ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã˜Â¹ Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â´Ã˜Â¯Ã™â€¡
                $this->familyMembers = $this->familyMembers->map(function($familyMember) use ($freshFamily) {
                    if ($familyMember->family_id === $freshFamily->id) {
                        $familyMember->family = $freshFamily; // Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â§Ã˜Â·Ã™â€žÃ˜Â§Ã˜Â¹Ã˜Â§Ã˜Âª Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡
                    }
                    return $familyMember;
                });

                Log::info('Family acceptance_criteria updated locally for immediate display', [
                    'family_id' => $freshFamily->id,
                    'updated_acceptance_criteria' => $freshFamily->acceptance_criteria
                ]);
            }

            // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  ÃšÂ©Ã˜Â´Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã˜Â®Ã˜ÂªÃ™â€žÃ™Â Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â§Ã˜Â·Ã™â€¦Ã›Å’Ã™â€ Ã˜Â§Ã™â€  Ã˜Â§Ã˜Â² Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã˜Â¯Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯
            $this->clearFamiliesCache();

            // Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  ÃšÂ©Ã˜Â´ Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡
            \Cache::forget('family_rank_' . $family->id);

            // Ã˜Â§Ã˜Â¬Ã˜Â¨Ã˜Â§Ã˜Â± Ã˜Â¨Ã™â€¡ Ã˜Â±Ã›Å’Ã™ÂÃ˜Â±Ã˜Â´ ÃšÂ©Ã˜Â§Ã™â€¦Ã™Â¾Ã™Ë†Ã™â€ Ã™â€ Ã˜Âª Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â±Ã˜Â§Ã˜Âª
            $this->refreshFamilyInList($family->id);

            // Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã™â€žÃ›Å’Ã˜Â³Ã˜Âª Ã˜Â§Ã˜ÂµÃ™â€žÃ›Å’ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã™ÂÃ™Ë†Ã˜Â±Ã›Å’ Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â±Ã˜Â§Ã˜Âª
            $this->updateFamilyInMainList($family->id);

            $this->dispatch('family-data-updated', [
                'family_id' => $family->id,
                'acceptance_criteria' => $family->acceptance_criteria
            ]);

            // Ã˜Â¨Ã˜Â³Ã˜ÂªÃ™â€  Ã˜Â­Ã˜Â§Ã™â€žÃ˜Âª Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´
            $this->cancelMemberEdit();

            $this->dispatch('notify', [
                'message' => 'Ã˜Â§Ã˜Â·Ã™â€žÃ˜Â§Ã˜Â¹Ã˜Â§Ã˜Âª Ã˜Â¹Ã˜Â¶Ã™Ë† Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â´Ã˜Â¯',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving member:', [
                'member_id' => $this->editingMemberId,
                'data' => $this->editingMemberData,
                'error' => $e->getMessage()
            ]);

            $this->dispatch('notify', [
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã˜Â§Ã˜Â·Ã™â€žÃ˜Â§Ã˜Â¹Ã˜Â§Ã˜Âª: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Ã˜Â¨Ã™â€¡Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã™â€¦Ã˜Â´Ã˜Â®Ã˜Âµ Ã˜Â¯Ã˜Â± Ã™â€žÃ›Å’Ã˜Â³Ã˜Âª families Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã™ÂÃ™Ë†Ã˜Â±Ã›Å’ Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â±Ã˜Â§Ã˜Âª
     * @param int $familyId
     * @return void
     */
    protected function refreshFamilyInList($familyId)
    {
        // Ã˜Â§ÃšÂ¯Ã˜Â± Ã™â€žÃ›Å’Ã˜Â³Ã˜Âª families Ã˜Â¯Ã˜Â± ÃšÂ©Ã˜Â§Ã™â€¦Ã™Â¾Ã™Ë†Ã™â€ Ã™â€ Ã˜Âª Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã˜Â¯Ã˜Â§Ã˜Â±Ã˜Â¯Ã˜Å’ Ã˜Â¢Ã™â€  Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â±Ã˜Â§ Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ ÃšÂ©Ã™â€ 
        try {
            // Ã˜Â§Ã›Å’Ã™â€  method Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ refresh ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã˜Â¯Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ ÃšÂ©Ã˜Â´ Ã˜Â´Ã˜Â¯Ã™â€¡ ÃšÂ©Ã˜Â§Ã™â€¦Ã™Â¾Ã™Ë†Ã™â€ Ã™â€ Ã˜Âª Ã˜Â§Ã˜Â³Ã˜Âª
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
     * Ã˜Â¯Ã˜Â±Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª Ã™Â¾Ã›Å’Ã˜Â§Ã™â€¦ Ã˜Â®Ã˜Â·Ã˜Â§Ã›Å’ Authorization Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ wizard_status Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡
     * @param Family $family
     * @return string
     */
    protected function getAuthorizationErrorMessage($family)
    {
        $wizardStatus = $family->wizard_status;

        // Ã˜Â§Ã˜Â³Ã˜ÂªÃ™ÂÃ˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â§Ã˜Â² enum Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â¯Ã˜Â±Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª Ã˜Â¨Ã˜Â±Ãšâ€ Ã˜Â³Ã˜Â¨ Ã™ÂÃ˜Â§Ã˜Â±Ã˜Â³Ã›Å’
        try {
            if ($wizardStatus) {
                // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã˜Â§Ã›Å’Ã™â€ ÃšÂ©Ã™â€¡ Ã˜Â¢Ã›Å’Ã˜Â§ Ã™â€šÃ˜Â¨Ã™â€žÃ˜Â§Ã™â€¹ Ã›Å’ÃšÂ© enum instance Ã˜Â§Ã˜Â³Ã˜Âª Ã›Å’Ã˜Â§ Ã˜Â®Ã›Å’Ã˜Â±
                if ($wizardStatus instanceof \App\Enums\InsuranceWizardStep) {
                    $statusEnum = $wizardStatus;
                    $wizardStatusValue = $wizardStatus->value;
                } else {
                    $statusEnum = \App\Enums\InsuranceWizardStep::from($wizardStatus);
                    $wizardStatusValue = $wizardStatus;
                }
                $statusLabel = $statusEnum->label();

                // Ã™Â¾Ã›Å’Ã˜Â§Ã™â€¦Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã˜Â®Ã˜ÂªÃ™â€žÃ™Â Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã™Ë†Ã˜Â¶Ã˜Â¹Ã›Å’Ã˜Âª
                return match($wizardStatusValue) {
                    'pending' => 'Ã˜Â®Ã˜Â·Ã˜Â§Ã›Å’ Ã˜ÂºÃ›Å’Ã˜Â±Ã™â€¦Ã™â€ Ã˜ÂªÃ˜Â¸Ã˜Â±Ã™â€¡: Ã˜Â´Ã™â€¦Ã˜Â§ Ã˜Â¨Ã˜Â§Ã›Å’Ã˜Â¯ Ã˜Â¨Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã›Å’Ã˜Â¯ Ã˜Â§Ã›Å’Ã™â€  Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â±Ã˜Â§ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ ÃšÂ©Ã™â€ Ã›Å’Ã˜Â¯',
                    'reviewing' => "Ã˜Â§Ã›Å’Ã™â€  Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¯Ã˜Â± Ã™â€¦Ã˜Â±Ã˜Â­Ã™â€žÃ™â€¡ {$statusLabel} Ã˜Â§Ã˜Â³Ã˜Âª Ã™Ë† Ã™ÂÃ™â€šÃ˜Â· Ã˜Â§Ã˜Â¯Ã™â€¦Ã›Å’Ã™â€  Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã˜Â¯ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ ÃšÂ©Ã™â€ Ã˜Â¯",
                    'share_allocation' => "Ã˜Â§Ã›Å’Ã™â€  Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¯Ã˜Â± Ã™â€¦Ã˜Â±Ã˜Â­Ã™â€žÃ™â€¡ {$statusLabel} Ã˜Â§Ã˜Â³Ã˜Âª Ã™Ë† Ã™ÂÃ™â€šÃ˜Â· Ã˜Â§Ã˜Â¯Ã™â€¦Ã›Å’Ã™â€  Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã˜Â¯ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ ÃšÂ©Ã™â€ Ã˜Â¯",
                    'approved' => "Ã˜Â§Ã›Å’Ã™â€  Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜ÂªÃ˜Â§Ã›Å’Ã›Å’Ã˜Â¯ Ã˜Â´Ã˜Â¯Ã™â€¡ ({$statusLabel}) Ã™Ë† Ã™ÂÃ™â€šÃ˜Â· Ã˜Â§Ã˜Â¯Ã™â€¦Ã›Å’Ã™â€  Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã˜Â¯ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ ÃšÂ©Ã™â€ Ã˜Â¯",
                    'excel_upload' => "Ã˜Â§Ã›Å’Ã™â€  Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¯Ã˜Â± Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â¸Ã˜Â§Ã˜Â± Ã˜ÂµÃ˜Â¯Ã™Ë†Ã˜Â± Ã˜Â¨Ã›Å’Ã™â€¦Ã™â€¡ ({$statusLabel}) Ã™Ë† Ã™ÂÃ™â€šÃ˜Â· Ã˜Â§Ã˜Â¯Ã™â€¦Ã›Å’Ã™â€  Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã˜Â¯ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ ÃšÂ©Ã™â€ Ã˜Â¯",
                    'insured' => "Ã˜Â§Ã›Å’Ã™â€  Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã›Å’Ã™â€¦Ã™â€¡ Ã˜Â´Ã˜Â¯Ã™â€¡ ({$statusLabel}) Ã™Ë† Ã™ÂÃ™â€šÃ˜Â· Ã˜Â§Ã˜Â¯Ã™â€¦Ã›Å’Ã™â€  Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã˜Â¯ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ ÃšÂ©Ã™â€ Ã˜Â¯",
                    'renewal' => "Ã˜Â§Ã›Å’Ã™â€  Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¯Ã˜Â± Ã™â€¦Ã˜Â±Ã˜Â­Ã™â€žÃ™â€¡ Ã˜ÂªÃ™â€¦Ã˜Â¯Ã›Å’Ã˜Â¯ ({$statusLabel}) Ã™Ë† Ã™ÂÃ™â€šÃ˜Â· Ã˜Â§Ã˜Â¯Ã™â€¦Ã›Å’Ã™â€  Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã˜Â¯ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ ÃšÂ©Ã™â€ Ã˜Â¯",
                    'rejected' => "Ã˜Â§Ã›Å’Ã™â€  Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â±Ã˜Â¯ Ã˜Â´Ã˜Â¯Ã™â€¡ ({$statusLabel}) Ã™Ë† Ã™ÂÃ™â€šÃ˜Â· Ã˜Â§Ã˜Â¯Ã™â€¦Ã›Å’Ã™â€  Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã˜Â¯ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ ÃšÂ©Ã™â€ Ã˜Â¯",
                    default => "Ã˜Â§Ã›Å’Ã™â€  Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¯Ã˜Â± Ã™â€¦Ã˜Â±Ã˜Â­Ã™â€žÃ™â€¡ {$statusLabel} Ã˜Â§Ã˜Â³Ã˜Âª Ã™Ë† Ã™ÂÃ™â€šÃ˜Â· Ã˜Â§Ã˜Â¯Ã™â€¦Ã›Å’Ã™â€  Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã˜Â¯ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ ÃšÂ©Ã™â€ Ã˜Â¯"
                };
            }
        } catch (\Exception $e) {
            Log::error('Error getting wizard status label', [
                'wizard_status' => $wizardStatus,
                'error' => $e->getMessage()
            ]);
        }

        // Ã™Â¾Ã›Å’Ã˜Â§Ã™â€¦ Ã™Â¾Ã›Å’Ã˜Â´Ã¢â‚¬Å’Ã™ÂÃ˜Â±Ã˜Â¶ Ã˜Â§ÃšÂ¯Ã˜Â± wizard_status Ã˜Â®Ã˜Â§Ã™â€žÃ›Å’ Ã›Å’Ã˜Â§ Ã™â€ Ã˜Â§Ã™â€¦Ã˜Â¹Ã˜ÂªÃ˜Â¨Ã˜Â± Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯
        return 'Ã˜Â´Ã™â€¦Ã˜Â§ Ã™â€¦Ã˜Â¬Ã™Ë†Ã˜Â² Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ Ã˜Â§Ã›Å’Ã™â€  Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â±Ã˜Â§ Ã™â€ Ã˜Â¯Ã˜Â§Ã˜Â±Ã›Å’Ã˜Â¯. Ã™ÂÃ™â€šÃ˜Â· Ã˜Â§Ã˜Â¯Ã™â€¦Ã›Å’Ã™â€  Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã˜Â¯ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ ÃšÂ©Ã™â€ Ã˜Â¯';
    }

    /**
     * Ã˜Â¨Ã™â€¡Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â®Ã˜Â§Ã˜Âµ Ã˜Â¯Ã˜Â± Ã™â€žÃ›Å’Ã˜Â³Ã˜Âª Ã˜Â§Ã˜ÂµÃ™â€žÃ›Å’ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§
     * @param int $familyId
     * @return void
     */
    protected function updateFamilyInMainList($familyId)
    {
        try {
            // Ã˜Â¨Ã˜Â§Ã˜Â²Ã›Å’Ã˜Â§Ã˜Â¨Ã›Å’ Ã˜Â¯Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯ Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã˜Â§ Ã˜ÂªÃ™â€¦Ã˜Â§Ã™â€¦ Ã˜Â±Ã™Ë†Ã˜Â§Ã˜Â¨Ã˜Â·
            $updatedFamily = Family::with([
                'head', 'province', 'city', 'district', 'region', 'charity', 'organization', 'members'
            ])->find($familyId);

            if (!$updatedFamily) {
                Log::warning('Family not found for update', ['family_id' => $familyId]);
                return;
            }

            // Ã™Ë†Ã˜Â§Ã˜Â¯Ã˜Â§Ã˜Â± ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã™â€¡ refresh Ã˜Â§Ã˜Â² Ã˜Â¯Ã›Å’Ã˜ÂªÃ˜Â§Ã˜Â¨Ã›Å’Ã˜Â³ Ã˜ÂªÃ˜Â§ Ã˜Â¯Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯ Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã›Å’Ã˜Â±Ã›Å’ Ã˜Â´Ã™Ë†Ã™â€ Ã˜Â¯
            $updatedFamily->refresh();
            $updatedFamily->load(['members', 'head', 'province', 'city', 'district', 'region', 'charity', 'organization']);

            // Ã˜Â§ÃšÂ¯Ã˜Â± property families Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã˜Â¯Ã˜Â§Ã˜Â±Ã˜Â¯Ã˜Å’ Ã˜Â¢Ã™â€  Ã˜Â±Ã˜Â§ Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ ÃšÂ©Ã™â€ 
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

            // Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ familyMembers Ã˜Â§ÃšÂ¯Ã˜Â± Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¨Ã˜Â§Ã˜Â² Ã˜Â§Ã˜Â³Ã˜Âª
            if ($this->expandedFamily === $familyId && !empty($this->familyMembers)) {
                $this->familyMembers = $updatedFamily->members;
                Log::info('Family members updated in expanded view', [
                    'family_id' => $familyId,
                    'members_count' => $this->familyMembers->count()
                ]);
            }

            // Ã˜Â§Ã˜Â¬Ã˜Â¨Ã˜Â§Ã˜Â± Ã˜Â¨Ã™â€¡ Ã˜Â±Ã›Å’Ã˜Â±Ã™â€ Ã˜Â¯Ã˜Â± Ã™â€¦Ã˜Â¬Ã˜Â¯Ã˜Â¯ ÃšÂ©Ã˜Â§Ã™â€¦Ã™Â¾Ã™Ë†Ã™â€ Ã™â€ Ã˜Âª Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â±Ã˜Â§Ã˜Âª
            $this->dispatch('family-updated', [
                'familyId' => $familyId,
                'acceptanceCriteria' => $updatedFamily->acceptance_criteria
            ]);

            // Ã˜Â±Ã›Å’Ã™ÂÃ˜Â±Ã˜Â´ Ã™â€¦Ã˜Â¬Ã˜Â¯Ã˜Â¯ ÃšÂ©Ã˜Â§Ã™â€¦Ã™Â¾Ã™Ë†Ã™â€ Ã™â€ Ã˜Âª Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã™â€ Ã™â€¦Ã˜Â§Ã›Å’Ã˜Â´ Ã˜ÂªÃ˜ÂºÃ›Å’Ã›Å’Ã˜Â±Ã˜Â§Ã˜Âª
            $this->skipRender = false; // Ã˜Â§Ã˜Â·Ã™â€¦Ã›Å’Ã™â€ Ã˜Â§Ã™â€  Ã˜Â§Ã˜Â² Ã˜Â±Ã›Å’Ã˜Â±Ã™â€ Ã˜Â¯Ã˜Â± Ã™â€¦Ã˜Â¬Ã˜Â¯Ã˜Â¯

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
     * Ã™â€žÃ˜ÂºÃ™Ë† Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ Ã˜Â¹Ã˜Â¶Ã™Ë† Ã˜Â®Ã˜Â§Ã™â€ Ã™Ë†Ã˜Â§Ã˜Â¯Ã™â€¡
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
     * Ã˜Â¯Ã˜Â±Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª ÃšÂ¯Ã˜Â²Ã›Å’Ã™â€ Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€ Ã˜Â³Ã˜Â¨Ã˜Âª
     * @return array
     */
    public function getRelationshipOptions()
    {
        return [
            'Ã™â€¦Ã˜Â§Ã˜Â¯Ã˜Â±' => 'Ã™â€¦Ã˜Â§Ã˜Â¯Ã˜Â±',
            'Ã™Â¾Ã˜Â¯Ã˜Â±' => 'Ã™Â¾Ã˜Â¯Ã˜Â±',
            'Ã˜Â²Ã™â€ ' => 'Ã˜Â²Ã™â€ ',
            'Ã˜Â´Ã™Ë†Ã™â€¡Ã˜Â±' => 'Ã˜Â´Ã™Ë†Ã™â€¡Ã˜Â±',
            'Ã™â€¡Ã™â€¦Ã˜Â³Ã˜Â±' => 'Ã™â€¡Ã™â€¦Ã˜Â³Ã˜Â±',
            'Ã™Â¾Ã˜Â³Ã˜Â±' => 'Ã™Â¾Ã˜Â³Ã˜Â±',
            'Ã˜Â¯Ã˜Â®Ã˜ÂªÃ˜Â±' => 'Ã˜Â¯Ã˜Â®Ã˜ÂªÃ˜Â±',
            'Ã™â€¦Ã˜Â§Ã˜Â¯Ã˜Â±Ã˜Â¨Ã˜Â²Ã˜Â±ÃšÂ¯' => 'Ã™â€¦Ã˜Â§Ã˜Â¯Ã˜Â±Ã˜Â¨Ã˜Â²Ã˜Â±ÃšÂ¯',
            'Ã™Â¾Ã˜Â¯Ã˜Â±Ã˜Â¨Ã˜Â²Ã˜Â±ÃšÂ¯' => 'Ã™Â¾Ã˜Â¯Ã˜Â±Ã˜Â¨Ã˜Â²Ã˜Â±ÃšÂ¯',
            'Ã˜Â³Ã˜Â§Ã›Å’Ã˜Â±' => 'Ã˜Â³Ã˜Â§Ã›Å’Ã˜Â±'
        ];
    }

    /**
     * Ã˜Â¯Ã˜Â±Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª ÃšÂ¯Ã˜Â²Ã›Å’Ã™â€ Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â´Ã˜ÂºÃ™â€ž
     * @return array
     */
    public function getOccupationOptions()
    {
        return [
            'Ã˜Â´Ã˜Â§Ã˜ÂºÃ™â€ž' => 'Ã˜Â´Ã˜Â§Ã˜ÂºÃ™â€ž',
            'Ã˜Â¨Ã›Å’ÃšÂ©Ã˜Â§Ã˜Â±' => 'Ã˜Â¨Ã›Å’ÃšÂ©Ã˜Â§Ã˜Â±',
            'Ã™â€¦Ã˜Â­Ã˜ÂµÃ™â€ž' => 'Ã™â€¦Ã˜Â­Ã˜ÂµÃ™â€ž',
            'Ã˜Â¯Ã˜Â§Ã™â€ Ã˜Â´Ã˜Â¬Ã™Ë†' => 'Ã˜Â¯Ã˜Â§Ã™â€ Ã˜Â´Ã˜Â¬Ã™Ë†',
            'Ã˜Â§Ã˜Â² ÃšÂ©Ã˜Â§Ã˜Â± Ã˜Â§Ã™ÂÃ˜ÂªÃ˜Â§Ã˜Â¯Ã™â€¡' => 'Ã˜Â§Ã˜Â² ÃšÂ©Ã˜Â§Ã˜Â± Ã˜Â§Ã™ÂÃ˜ÂªÃ˜Â§Ã˜Â¯Ã™â€¡',
            'Ã˜ÂªÃ˜Â±ÃšÂ© Ã˜ÂªÃ˜Â­Ã˜ÂµÃ›Å’Ã™â€ž' => 'Ã˜ÂªÃ˜Â±ÃšÂ© Ã˜ÂªÃ˜Â­Ã˜ÂµÃ›Å’Ã™â€ž',
            'Ã˜Â®Ã˜Â§Ã™â€ Ã™â€¡Ã¢â‚¬Å’Ã˜Â¯Ã˜Â§Ã˜Â±' => 'Ã˜Â®Ã˜Â§Ã™â€ Ã™â€¡Ã¢â‚¬Å’Ã˜Â¯Ã˜Â§Ã˜Â±'
        ];
    }

    //======================================================================
    //== Ã™â€¦Ã˜ÂªÃ˜Â¯Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â³Ã›Å’Ã˜Â³Ã˜ÂªÃ™â€¦ Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã™Ë† Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§
    //======================================================================

    /**
     * Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™ÂÃ˜Â¹Ã™â€žÃ›Å’ Ã˜Â¨Ã˜Â§ Ã™â€ Ã˜Â§Ã™â€¦ Ã™Ë† Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª Ã™â€¦Ã˜Â´Ã˜Â®Ã˜Âµ
     * @param string $name
     * @param string|null $description
     * @return void
     */
    public function saveFilter($name, $description = null)
    {
        try {
            // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã™Ë†Ã˜Â¯Ã˜Â§Ã™â€ž Ã›Å’Ã˜Â§ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â§Ã™â€ Ã˜ÂªÃ˜Â®Ã˜Â§Ã˜Â¨ Ã˜Â´Ã˜Â¯Ã™â€¡
            $currentFilters = $this->tempFilters ?? $this->activeFilters ?? [];
            $hasModalFilters = !empty($currentFilters);
            $hasSelectedCriteria = !empty($this->selectedCriteria) && count(array_filter($this->selectedCriteria)) > 0;

            if (!$hasModalFilters && !$hasSelectedCriteria) {
                $this->dispatch('notify', [
                    'message' => 'Ã™â€¡Ã›Å’Ãšâ€  Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã›Å’Ã˜Â§ Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â±Ã›Å’ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã™Ë†Ã˜Â¬Ã™Ë†Ã˜Â¯ Ã™â€ Ã˜Â¯Ã˜Â§Ã˜Â±Ã˜Â¯',
                    'type' => 'warning'
                ]);
                return;
            }

            // Ã˜Â§Ã›Å’Ã˜Â¬Ã˜Â§Ã˜Â¯ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã˜Â´Ã˜Â¯Ã™â€¡
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
                'message' => "Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± '{$name}' Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã˜Â´Ã˜Â¯",
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving filter', [
                'name' => $name,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Ã˜Â­Ã˜Â°Ã™Â Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã˜Â´Ã˜Â¯Ã™â€¡
     * @param int $filterId
     * @return void
     */
    public function deleteSavedFilter($filterId)
    {
        try {
            $savedFilter = SavedFilter::find($filterId);
            if (!$savedFilter) {
                $this->dispatch('notify', [
                    'message' => 'Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™â€¦Ã™Ë†Ã˜Â±Ã˜Â¯ Ã™â€ Ã˜Â¸Ã˜Â± Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª Ã™â€ Ã˜Â´Ã˜Â¯',
                    'type' => 'error'
                ]);
                return;
            }

            // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã˜Â¯Ã˜Â³Ã˜ÂªÃ˜Â±Ã˜Â³Ã›Å’ - Ã™ÂÃ™â€šÃ˜Â· Ã˜ÂµÃ˜Â§Ã˜Â­Ã˜Â¨ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã˜Â¯ Ã˜Â¢Ã™â€  Ã˜Â±Ã˜Â§ Ã˜Â­Ã˜Â°Ã™Â ÃšÂ©Ã™â€ Ã˜Â¯
            if ($savedFilter->user_id !== Auth::id()) {
                $this->dispatch('notify', [
                    'message' => 'Ã˜Â´Ã™â€¦Ã˜Â§ Ã™â€¦Ã˜Â¬Ã˜Â§Ã˜Â² Ã˜Â¨Ã™â€¡ Ã˜Â­Ã˜Â°Ã™Â Ã˜Â§Ã›Å’Ã™â€  Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™â€ Ã›Å’Ã˜Â³Ã˜ÂªÃ›Å’Ã˜Â¯',
                    'type' => 'error'
                ]);
                return;
            }

            // Ã˜Â­Ã˜Â°Ã™Â Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±
            $filterName = $savedFilter->name;
            $savedFilter->delete();

            Log::info('Ã°Å¸â€”â€˜Ã¯Â¸Â Saved filter deleted successfully', [
                'filter_id' => $filterId,
                'filter_name' => $filterName,
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => 'Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â­Ã˜Â°Ã™Â Ã˜Â´Ã˜Â¯',
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('Ã¢ÂÅ’ Error deleting saved filter', [
                'filter_id' => $filterId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â­Ã˜Â°Ã™Â Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã˜Â´Ã˜Â¯Ã™â€¡ ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â±
     * @param string $filterType Ã™â€ Ã™Ë†Ã˜Â¹ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± - 'family_search' Ã›Å’Ã˜Â§ 'rank_settings'
     * @return array
     */
    public function loadSavedFilters($filterType = 'family_search')
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return [];
            }

            // Ã˜ÂªÃ˜Â¹Ã›Å’Ã›Å’Ã™â€  Ã™â€ Ã™Ë†Ã˜Â¹ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ Ã™Â¾Ã˜Â§Ã˜Â±Ã˜Â§Ã™â€¦Ã˜ÂªÃ˜Â± Ã™Ë†Ã˜Â±Ã™Ë†Ã˜Â¯Ã›Å’
            $actualFilterType = $filterType;

            // Ã˜ÂªÃ˜Â¨Ã˜Â¯Ã›Å’Ã™â€ž Ã™â€ Ã˜Â§Ã™â€¦Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã˜ÂªÃ˜Â¯Ã˜Â§Ã™Ë†Ã™â€ž Ã˜Â¨Ã™â€¡ Ã™â€ Ã™Ë†Ã˜Â¹ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™Ë†Ã˜Â§Ã™â€šÃ˜Â¹Ã›Å’
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

            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€šÃ˜Â§Ã˜Â¨Ã™â€ž Ã˜Â¯Ã˜Â³Ã˜ÂªÃ˜Â±Ã˜Â³ Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â±
            $query = SavedFilter::where('filter_type', $actualFilterType)
                ->where(function ($q) use ($user) {
                    // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â®Ã™Ë†Ã˜Â¯ ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â±
                    $q->where('user_id', $user->id);

                    // Ã˜Â§ÃšÂ¯Ã˜Â± ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â± Ã˜Â¨Ã›Å’Ã™â€¦Ã™â€¡ Ã˜Â§Ã˜Â³Ã˜ÂªÃ˜Å’ Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã˜Â¯ Ã™â€¡Ã™â€¦Ã™â€¡ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â±Ã˜Â§Ã™â€  Ã˜Â³Ã˜Â§Ã˜Â²Ã™â€¦Ã˜Â§Ã™â€ Ã˜Â´ Ã˜Â±Ã˜Â§ Ã˜Â¨Ã˜Â¨Ã›Å’Ã™â€ Ã˜Â¯
                    if ($user->isInsurance() && $user->organization_id) {
                        $q->orWhereHas('user', function($userQuery) use ($user) {
                            $userQuery->where('organization_id', $user->organization_id);
                        });
                    }
                    // Ã˜Â§ÃšÂ¯Ã˜Â± ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â± Ã˜Â®Ã›Å’Ã˜Â±Ã›Å’Ã™â€¡ Ã˜Â§Ã˜Â³Ã˜ÂªÃ˜Å’ Ã™ÂÃ™â€šÃ˜Â· Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â®Ã™Ë†Ã˜Â¯Ã˜Â´ Ã˜Â±Ã˜Â§ Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜Â¨Ã›Å’Ã™â€ Ã˜Â¯ (ÃšÂ©Ã™â€¡ Ã˜Â¯Ã˜Â± Ã˜Â¨Ã˜Â§Ã™â€žÃ˜Â§ Ã˜Â§Ã˜Â¶Ã˜Â§Ã™ÂÃ™â€¡ Ã˜Â´Ã˜Â¯Ã™â€¡)
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
     * Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ Ã™Ë† Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã˜Â´Ã˜Â¯Ã™â€¡
     * @param int $filterId
     * @return void
     */
    public function loadFilter($filterId)
    {
        try {
            $savedFilter = SavedFilter::find($filterId);
            if (!$savedFilter) {
                $this->dispatch('notify', [
                    'message' => 'Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™â€¦Ã™Ë†Ã˜Â±Ã˜Â¯ Ã™â€ Ã˜Â¸Ã˜Â± Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª Ã™â€ Ã˜Â´Ã˜Â¯',
                    'type' => 'error'
                ]);
                return;
            }

            // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã˜Â¯Ã˜Â³Ã˜ÂªÃ˜Â±Ã˜Â³Ã›Å’ Ã˜Â¨Ã˜Â± Ã˜Â§Ã˜Â³Ã˜Â§Ã˜Â³ user_id Ã™Ë† organization_id
            $user = Auth::user();
            $hasAccess = false;

            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â®Ã™Ë†Ã˜Â¯ ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â±
            if ($savedFilter->user_id === $user->id) {
                $hasAccess = true;
            }
            // Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â³Ã˜Â§Ã˜Â²Ã™â€¦Ã˜Â§Ã™â€ Ã›Å’ (Ã˜Â§ÃšÂ¯Ã˜Â± ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â± Ã˜Â¹Ã˜Â¶Ã™Ë† Ã™â€¡Ã™â€¦Ã˜Â§Ã™â€  Ã˜Â³Ã˜Â§Ã˜Â²Ã™â€¦Ã˜Â§Ã™â€  Ã˜Â¨Ã˜Â§Ã˜Â´Ã˜Â¯)
            elseif ($savedFilter->organization_id && $savedFilter->organization_id === $user->organization_id) {
                $hasAccess = true;
            }

            if (!$hasAccess) {
                $this->dispatch('notify', [
                    'message' => 'Ã˜Â´Ã™â€¦Ã˜Â§ Ã˜Â¨Ã™â€¡ Ã˜Â§Ã›Å’Ã™â€  Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¯Ã˜Â³Ã˜ÂªÃ˜Â±Ã˜Â³Ã›Å’ Ã™â€ Ã˜Â¯Ã˜Â§Ã˜Â±Ã›Å’Ã˜Â¯',
                    'type' => 'error'
                ]);
                return;
            }

            // Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ Ã˜Â¯Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±
            $filterData = $savedFilter->filters_config;

            // Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™â€¦Ã™Ë†Ã˜Â¯Ã˜Â§Ã™â€ž
            if (isset($filterData['filters']) && is_array($filterData['filters'])) {
                $this->tempFilters = $filterData['filters'];
                $this->activeFilters = $filterData['filters'];
                $this->filters = $filterData['filters'];
            }

            // Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ ÃšÂ©Ã˜Â§Ã™â€¦Ã™Â¾Ã™Ë†Ã™â€ Ã™â€ Ã˜Âª
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

            // Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª Ã˜Â±Ã˜ÂªÃ˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â¨Ã™â€ Ã˜Â¯Ã›Å’
            if (isset($filterData['rank_settings'])) {
                $rankSettings = $filterData['rank_settings'];
                $this->selectedCriteria = $rankSettings['selected_criteria'] ?? [];
                $this->appliedSchemeId = $rankSettings['applied_scheme_id'] ?? null;
            }

            // Ã˜Â§Ã˜Â¹Ã™â€¦Ã˜Â§Ã™â€ž Ã˜ÂªÃ™â€ Ã˜Â¸Ã›Å’Ã™â€¦Ã˜Â§Ã˜Âª Ã˜Â³Ã™Ë†Ã˜Â±Ã˜Âª
            if (isset($filterData['sort'])) {
                $this->sortField = $filterData['sort']['field'] ?? 'created_at';
                $this->sortDirection = $filterData['sort']['direction'] ?? 'desc';
            }

            // Ã˜Â§Ã™ÂÃ˜Â²Ã˜Â§Ã›Å’Ã˜Â´ Ã˜Â´Ã™â€¦Ã˜Â§Ã˜Â±Ã™â€ Ã˜Â¯Ã™â€¡ Ã˜Â§Ã˜Â³Ã˜ÂªÃ™ÂÃ˜Â§Ã˜Â¯Ã™â€¡
            $savedFilter->increment('usage_count');
            $savedFilter->update(['last_used_at' => now()]);

            // Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€ Ã˜Â´Ã˜Â§Ã™â€ Ã›Å’ Ã˜ÂµÃ™ÂÃ˜Â­Ã™â€¡ Ã™Ë† Ã™Â¾Ã˜Â§ÃšÂ© ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  ÃšÂ©Ã˜Â´
            $this->resetPage();
            $this->clearCache();

            Log::info('Filter loaded successfully', [
                'filter_id' => $filterId,
                'filter_name' => $savedFilter->name,
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => "Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± '{$savedFilter->name}' Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ Ã˜Â´Ã˜Â¯",
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading filter', [
                'filter_id' => $filterId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â¨Ã˜Â§Ã˜Â±ÃšÂ¯Ã˜Â°Ã˜Â§Ã˜Â±Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Ã˜Â­Ã˜Â°Ã™Â Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã˜Â´Ã˜Â¯Ã™â€¡
     * @param int $filterId
     * @return void
     */
    public function deleteFilter($filterId)
    {
        try {
            $savedFilter = SavedFilter::find($filterId);
            if (!$savedFilter) {
                $this->dispatch('notify', [
                    'message' => 'Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™â€¦Ã™Ë†Ã˜Â±Ã˜Â¯ Ã™â€ Ã˜Â¸Ã˜Â± Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª Ã™â€ Ã˜Â´Ã˜Â¯',
                    'type' => 'error'
                ]);
                return;
            }

            // Ã™ÂÃ™â€šÃ˜Â· Ã˜ÂµÃ˜Â§Ã˜Â­Ã˜Â¨ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã˜Â¯ Ã˜Â¢Ã™â€  Ã˜Â±Ã˜Â§ Ã˜Â­Ã˜Â°Ã™Â ÃšÂ©Ã™â€ Ã˜Â¯
            if ($savedFilter->user_id !== Auth::id()) {
                $this->dispatch('notify', [
                    'message' => 'Ã˜Â´Ã™â€¦Ã˜Â§ Ã™ÂÃ™â€šÃ˜Â· Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã›Å’Ã˜Â¯ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â®Ã™Ë†Ã˜Â¯ Ã˜Â±Ã˜Â§ Ã˜Â­Ã˜Â°Ã™Â ÃšÂ©Ã™â€ Ã›Å’Ã˜Â¯',
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
                'message' => "Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± '{$filterName}' Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â­Ã˜Â°Ã™Â Ã˜Â´Ã˜Â¯",
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting filter', [
                'filter_id' => $filterId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â­Ã˜Â°Ã™Â Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â°Ã˜Â®Ã›Å’Ã˜Â±Ã™â€¡ Ã˜Â´Ã˜Â¯Ã™â€¡
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
                    'message' => 'Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™â€¦Ã™Ë†Ã˜Â±Ã˜Â¯ Ã™â€ Ã˜Â¸Ã˜Â± Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª Ã™â€ Ã˜Â´Ã˜Â¯',
                    'type' => 'error'
                ]);
                return;
            }

            // Ã™ÂÃ™â€šÃ˜Â· Ã˜ÂµÃ˜Â§Ã˜Â­Ã˜Â¨ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã˜Â¯ Ã˜Â¢Ã™â€  Ã˜Â±Ã˜Â§ Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ ÃšÂ©Ã™â€ Ã˜Â¯
            if ($savedFilter->user_id !== Auth::id()) {
                $this->dispatch('notify', [
                    'message' => 'Ã˜Â´Ã™â€¦Ã˜Â§ Ã™ÂÃ™â€šÃ˜Â· Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜ÂªÃ™Ë†Ã˜Â§Ã™â€ Ã›Å’Ã˜Â¯ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â®Ã™Ë†Ã˜Â¯ Ã˜Â±Ã˜Â§ Ã™Ë†Ã›Å’Ã˜Â±Ã˜Â§Ã›Å’Ã˜Â´ ÃšÂ©Ã™â€ Ã›Å’Ã˜Â¯',
                    'type' => 'error'
                ]);
                return;
            }

            // Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â¯Ã˜Â§Ã˜Â¯Ã™â€¡Ã¢â‚¬Å’Ã™â€¡Ã˜Â§Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â§ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±Ã™â€¡Ã˜Â§Ã›Å’ Ã™ÂÃ˜Â¹Ã™â€žÃ›Å’
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
                'message' => "Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± '{$name}' Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â´Ã˜Â¯",
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
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * ÃšÂ©Ã™Â¾Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â± Ã˜Â¬Ã˜Â§Ã˜Â±Ã›Å’
     * @param int $filterId
     * @return void
     */
    public function duplicateFilter($filterId)
    {
        try {
            $originalFilter = SavedFilter::find($filterId);
            if (!$originalFilter) {
                $this->dispatch('notify', [
                    'message' => 'Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã™â€¦Ã™Ë†Ã˜Â±Ã˜Â¯ Ã™â€ Ã˜Â¸Ã˜Â± Ã›Å’Ã˜Â§Ã™ÂÃ˜Âª Ã™â€ Ã˜Â´Ã˜Â¯',
                    'type' => 'error'
                ]);
                return;
            }

            // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã˜Â¯Ã˜Â³Ã˜ÂªÃ˜Â±Ã˜Â³Ã›Å’
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
                    'message' => 'Ã˜Â´Ã™â€¦Ã˜Â§ Ã˜Â¨Ã™â€¡ Ã˜Â§Ã›Å’Ã™â€  Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± Ã˜Â¯Ã˜Â³Ã˜ÂªÃ˜Â±Ã˜Â³Ã›Å’ Ã™â€ Ã˜Â¯Ã˜Â§Ã˜Â±Ã›Å’Ã˜Â¯',
                    'type' => 'error'
                ]);
                return;
            }

            // Ã˜Â§Ã›Å’Ã˜Â¬Ã˜Â§Ã˜Â¯ ÃšÂ©Ã™Â¾Ã›Å’ Ã˜Â§Ã˜Â² Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±
            $newFilterName = $originalFilter->name . ' (ÃšÂ©Ã™Â¾Ã›Å’)';
            $duplicatedFilter = SavedFilter::create([
                'name' => $newFilterName,
                'description' => $originalFilter->description,
                'filters_config' => $originalFilter->filters_config,
                'filter_type' => $originalFilter->filter_type,
                'visibility' => 'private', // ÃšÂ©Ã™Â¾Ã›Å’Ã¢â‚¬Å’Ã™â€¡Ã˜Â§ Ã™â€¡Ã™â€¦Ã›Å’Ã˜Â´Ã™â€¡ Ã˜Â®Ã˜ÂµÃ™Ë†Ã˜ÂµÃ›Å’ Ã™â€¡Ã˜Â³Ã˜ÂªÃ™â€ Ã˜Â¯
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
                'message' => "ÃšÂ©Ã™Â¾Ã›Å’ Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â± '{$newFilterName}' Ã˜Â¨Ã˜Â§ Ã™â€¦Ã™Ë†Ã™ÂÃ™â€šÃ›Å’Ã˜Âª Ã˜Â§Ã›Å’Ã˜Â¬Ã˜Â§Ã˜Â¯ Ã˜Â´Ã˜Â¯",
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('Error duplicating filter', [
                'filter_id' => $filterId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->dispatch('notify', [
                'message' => 'Ã˜Â®Ã˜Â·Ã˜Â§ Ã˜Â¯Ã˜Â± ÃšÂ©Ã™Â¾Ã›Å’ ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã™ÂÃ›Å’Ã™â€žÃ˜ÂªÃ˜Â±: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Ã˜Â­Ã˜Â°Ã™Â Ã›Å’ÃšÂ© Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã™Â¾Ã˜Â°Ã›Å’Ã˜Â±Ã˜Â´ Ã˜Â§Ã˜Â² Ã™â€žÃ›Å’Ã˜Â³Ã˜Âª Ã˜Â¢Ã˜Â±Ã˜Â§Ã›Å’Ã™â€¡
     * Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â§Ã˜Â³Ã˜ÂªÃ™ÂÃ˜Â§Ã˜Â¯Ã™â€¡ Ã˜Â¯Ã˜Â± multi-select dropdown
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
            // Ã˜Â­Ã˜Â°Ã™Â ÃšÂ©Ã™â€žÃ›Å’Ã˜Â¯ Ã™â€¦Ã˜Â´Ã˜Â®Ã˜Âµ
            $this->editingMemberData['problem_type'] = array_filter(
                $this->editingMemberData['problem_type'],
                function($item) use ($key) {
                    return (string)$item !== (string)$key; // Ã˜Â§Ã˜Â·Ã™â€¦Ã›Å’Ã™â€ Ã˜Â§Ã™â€  Ã˜Â§Ã˜Â² Ã™â€¦Ã™â€šÃ˜Â§Ã›Å’Ã˜Â³Ã™â€¡ Ã˜Â±Ã˜Â´Ã˜ÂªÃ™â€¡Ã¢â‚¬Å’Ã˜Â§Ã›Å’
                }
            );

            // Ã˜Â¨Ã˜Â§Ã˜Â²Ã™â€ Ã˜Â´Ã˜Â§Ã™â€ Ã›Å’ ÃšÂ©Ã™â€žÃ›Å’Ã˜Â¯Ã™â€¡Ã˜Â§Ã›Å’ Ã˜Â¢Ã˜Â±Ã˜Â§Ã›Å’Ã™â€¡ Ã™Ë† Ã˜Â­Ã˜Â°Ã™Â Ã˜ÂªÃšÂ©Ã˜Â±Ã˜Â§Ã˜Â±Ã›Å’Ã¢â‚¬Å’Ã™â€¡Ã˜Â§
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
     * Ã˜Â§Ã˜Â¶Ã˜Â§Ã™ÂÃ™â€¡ ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€  Ã™â€¦Ã˜Â¹Ã›Å’Ã˜Â§Ã˜Â± Ã™Â¾Ã˜Â°Ã›Å’Ã˜Â±Ã˜Â´ Ã˜Â¬Ã˜Â¯Ã›Å’Ã˜Â¯ Ã˜Â¨Ã˜Â§ Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã˜ÂªÃšÂ©Ã˜Â±Ã˜Â§Ã˜Â±
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

        // Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ Ã˜ÂªÃšÂ©Ã˜Â±Ã˜Â§Ã˜Â± Ã™â€šÃ˜Â¨Ã™â€ž Ã˜Â§Ã˜Â² Ã˜Â§Ã˜Â¶Ã˜Â§Ã™ÂÃ™â€¡ ÃšÂ©Ã˜Â±Ã˜Â¯Ã™â€ 
        if (!in_array($key, $this->editingMemberData['problem_type'])) {
            $this->editingMemberData['problem_type'][] = $key;

            // Ã˜Â­Ã˜Â°Ã™Â Ã˜Â§Ã˜Â­Ã˜ÂªÃ™â€¦Ã˜Â§Ã™â€žÃ›Å’ Ã˜ÂªÃšÂ©Ã˜Â±Ã˜Â§Ã˜Â±Ã›Å’Ã¢â‚¬Å’Ã™â€¡Ã˜Â§ (Ã˜Â¨Ã˜Â¯Ã™Ë†Ã™â€  sort Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â­Ã™ÂÃ˜Â¸ insertion order)
            $this->editingMemberData['problem_type'] = array_unique($this->editingMemberData['problem_type']);
            // sort() Ã˜Â­Ã˜Â°Ã™Â Ã˜Â´Ã˜Â¯: Ã˜ÂªÃ˜Â±Ã˜ÂªÃ›Å’Ã˜Â¨ Ã˜Â§Ã˜Â¶Ã˜Â§Ã™ÂÃ™â€¡ Ã˜Â´Ã˜Â¯Ã™â€  Ã˜Â­Ã™ÂÃ˜Â¸ Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜Â´Ã™Ë†Ã˜Â¯

            Log::info('Problem type added successfully', [
                'added_key' => $key,
                'current_array' => $this->editingMemberData['problem_type'],
                'member_id' => $this->editingMemberId
            ]);
        }
    }

    /**
     * Ã˜Â¨Ã™â€¡Ã¢â‚¬Å’Ã˜Â±Ã™Ë†Ã˜Â²Ã˜Â±Ã˜Â³Ã˜Â§Ã™â€ Ã›Å’ Ã˜Â®Ã™Ë†Ã˜Â¯ÃšÂ©Ã˜Â§Ã˜Â± problem_type Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Ã˜Â­Ã˜Â°Ã™Â Ã˜ÂªÃšÂ©Ã˜Â±Ã˜Â§Ã˜Â±Ã›Å’Ã¢â‚¬Å’Ã™â€¡Ã˜Â§ Ã˜Â¯Ã˜Â± Ã˜Â²Ã™â€¦Ã˜Â§Ã™â€  Ã™Ë†Ã˜Â§Ã™â€šÃ˜Â¹Ã›Å’
     * This hook fires when the MultiSelect component updates the parent's editingMemberData.problem_type via wire:model.
     * Provides automatic deduplication.
     * @param mixed $value
     * @return void
     */
    public function updatedEditingMemberDataProblemType($value)
    {
        if (is_array($value)) {
            // Ã˜Â­Ã˜Â°Ã™Â Ã™â€¦Ã™â€šÃ˜Â§Ã˜Â¯Ã›Å’Ã˜Â± Ã˜Â®Ã˜Â§Ã™â€žÃ›Å’ Ã™Ë† Ã˜ÂªÃšÂ©Ã˜Â±Ã˜Â§Ã˜Â±Ã›Å’
            $cleanedArray = array_filter($value, function($item) {
                return !is_null($item) && trim((string)$item) !== '';
            });

            $cleanedArray = array_unique($cleanedArray);
            $cleanedArray = array_values($cleanedArray);
            // sort() Ã˜Â­Ã˜Â°Ã™Â Ã˜Â´Ã˜Â¯: Ã˜ÂªÃ˜Â±Ã˜ÂªÃ›Å’Ã˜Â¨ insertion order Ã˜Â­Ã™ÂÃ˜Â¸ Ã™â€¦Ã›Å’Ã¢â‚¬Å’Ã˜Â´Ã™Ë†Ã˜Â¯ (Ã˜Â¨Ã™â€¡ Ã˜Â¬Ã˜Â§Ã›Å’ comparison sorted)

            // Comparison Ã˜Â¨Ã˜Â¯Ã™Ë†Ã™â€  sort - Ã˜Â¨Ã˜Â±Ã˜Â±Ã˜Â³Ã›Å’ count Ã™Ë† Ã™â€¦Ã™â€šÃ˜Â§Ã˜Â¯Ã›Å’Ã˜Â±
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

            // Dispatch event Ã˜Â¨Ã˜Â±Ã˜Â§Ã›Å’ Alpine.js
            $this->dispatch('problem-types-updated', [
                'count' => count($this->editingMemberData['problem_type'])
            ]);
        }
    }

}
