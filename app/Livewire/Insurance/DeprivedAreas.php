<?php

namespace App\Livewire\Insurance;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Province;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class DeprivedAreas extends Component
{
    use WithPagination;

    public $search = '';
    public $showOnlyDeprived = false;
    public $perPage = 20;
    public $expandedProvinces = []; // آرایه برای نگهداری استان‌های باز شده
    
    protected $paginationTheme = 'tailwind';
    
    protected $queryString = [
        'search' => ['except' => ''],
        'showOnlyDeprived' => ['except' => false],
        'perPage' => ['except' => 20]
    ];

    public function render()
    {
        // استفاده از کش برای کاهش بارگذاری داده از دیتابیس
        $cacheKey = "deprived_areas_" . md5($this->search . '_' . $this->showOnlyDeprived . '_' . $this->perPage . '_' . $this->getPage());
        $cacheTtl = now()->addHours(6); // کش برای 6 ساعت
        
        $provinces = Cache::remember($cacheKey, $cacheTtl, function () {
            // استفاده از select برای انتخاب فیلدهای مورد نیاز به جای همه فیلدها
            $query = Province::query()
                ->select(['id', 'name'])
                ->with([
                    'cities' => function($q) {
                        $q->select(['id', 'name', 'province_id'])
                            ->with(['districts' => function($q) {
                                $q->select(['id', 'name', 'city_id', 'is_deprived']);
                                
                                if ($this->showOnlyDeprived) {
                                    $q->where('is_deprived', true);
                                }
                                $q->orderBy('name');
                            }])
                            ->orderBy('name');
                    }
                ]);
            
            // بهینه‌سازی کوئری جستجو
            if (!empty($this->search)) {
                $searchTerm = trim($this->search);
                $cleanSearchTerm = $this->cleanSearchTerm($searchTerm);
                
                // استفاده از یک کوئری ساده‌تر و بهینه‌تر
                $query->where(function($q) use ($searchTerm, $cleanSearchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('name', 'like', "%{$cleanSearchTerm}%");
                    
                    // جستجوی شهرستان‌ها و دهستان‌ها فقط اگر عبارت جستجو بیشتر از 2 کاراکتر باشد
                    if (mb_strlen($searchTerm) > 2) {
                        $q->orWhereHas('cities', function($q) use ($searchTerm, $cleanSearchTerm) {
                            $q->where('name', 'like', "%{$searchTerm}%")
                              ->orWhere('name', 'like', "%{$cleanSearchTerm}%");
                        });
                        
                        $q->orWhereHas('cities.districts', function($q) use ($searchTerm, $cleanSearchTerm) {
                            $q->where('name', 'like', "%{$searchTerm}%")
                              ->orWhere('name', 'like', "%{$cleanSearchTerm}%");
                        });
                    }
                });
                
                // جستجوی جزئی کلمات (بهینه شده)
                if (mb_strlen($searchTerm) > 2) {
                    $words = explode(' ', $searchTerm);
                    if (count($words) > 1) {
                        $query->orWhere(function($q) use ($words) {
                            foreach ($words as $word) {
                                if (mb_strlen(trim($word)) > 2) {
                                    $word = trim($word);
                                    $q->orWhere('name', 'like', "%{$word}%");
                                }
                            }
                        });
                    }
                }
            }
            
            // فیلتر کردن در سطح SQL به جای PHP
            $query->whereHas('cities', function($q) {
                $q->whereHas('districts', function($q) {
                    if ($this->showOnlyDeprived) {
                        $q->where('is_deprived', true);
                    }
                });
            });
            
            if (!empty($this->search)) {
                // اگر جستجو داریم، همه نتایج را برگردان و در PHP مرتب کن
                $results = $query->orderBy('name')->get();
                
                // مرتب‌سازی ساده‌تر در PHP
                if (mb_strlen($this->search) > 2) {
                    $searchTerm = mb_strtolower($this->cleanSearchTerm($this->search));
                    
                    $results = $results->map(function($province) use ($searchTerm) {
                        // یک امتیاز ساده برای مرتب‌سازی
                        $provinceName = mb_strtolower($province->name);
                        $score = 0;
                        
                        if (strpos($provinceName, $searchTerm) !== false) {
                            $score += 3;
                        }
                        
                        $province->search_score = $score;
                        return $province;
                    })->sortByDesc('search_score');
                }
                
                // دستی صفحه‌بندی کن (بهینه‌سازی شده)
                $page = $this->getPage();
                $perPage = $this->perPage;
                $offset = ($page - 1) * $perPage;
                
                $items = $results->slice($offset, $perPage);
                $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                    $items,
                    $results->count(),
                    $perPage,
                    $page,
                    ['path' => request()->url(), 'pageName' => 'page']
                );
                
                return $paginator;
            }
            
            return $query->orderBy('name')->paginate($this->perPage);
        });
        
        if (!empty($this->search)) {
            // فقط در صورت جستجو همه استان‌ها باز شوند
            foreach ($provinces as $province) {
                $this->expandedProvinces[$province->id] = true;
            }
        }
        
        return view('livewire.insurance.deprived-areas', [
            'provinces' => $provinces,
        ]);
    }

    /**
     * Toggle کردن وضعیت باز/بسته استان
     */
    public function toggleProvince($provinceId)
    {
        if (isset($this->expandedProvinces[$provinceId])) {
            unset($this->expandedProvinces[$provinceId]);
        } else {
            $this->expandedProvinces[$provinceId] = true;
        }
    }

    /**
     * باز کردن تمام استان‌ها
     */
    public function expandAll()
    {
        $provinces = Province::whereHas('cities.districts')->get();
        foreach ($provinces as $province) {
            $this->expandedProvinces[$province->id] = true;
        }
    }

    /**
     * بستن تمام استان‌ها
     */
    public function collapseAll()
    {
        $this->expandedProvinces = [];
    }

    /**
     * پاک‌سازی کش در صورت تغییر فیلترها
     */
    public function updatedShowOnlyDeprived()
    {
        $this->resetPage();
        $this->clearDeprivedAreasCache();
    }
    
    /**
     * پاک‌سازی کش صفحه
     */
    private function clearDeprivedAreasCache()
    {
        try {
            // یافتن و حذف کلیدهای مربوط به کش این صفحه
            $cachePrefix = 'deprived_areas_';
            
            if (config('cache.default') === 'redis') {
                try {
                    $redis = Redis::connection();
                    $keys = $redis->keys(config('cache.prefix') . ':' . $cachePrefix . '*');
                    
                    if (!empty($keys)) {
                        foreach ($keys as $key) {
                            $redis->del($key);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('خطا در پاکسازی کش مناطق محروم (Redis): ' . $e->getMessage());
                }
            } else {
                Cache::flush(); // برای درایورهای دیگر، فقط کل کش را پاک می‌کنیم
            }
        } catch (\Exception $e) {
            Log::error('خطا در پاکسازی کش مناطق محروم: ' . $e->getMessage());
        }
    }

    /**
     * تمیز کردن کلیدواژه جستجو برای بهبود دقت
     */
    private function cleanSearchTerm($search)
    {
        // حذف کاراکترهای اضافی
        $cleaned = preg_replace('/[^\p{L}\p{N}\s]/u', '', $search);
        
        // حذف فاصله‌های اضافی
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        
        // تبدیل ی و ک عربی به فارسی
        $cleaned = str_replace(['ي', 'ك'], ['ی', 'ک'], $cleaned);
        
        return trim($cleaned);
    }

    public function updatingSearch()
    {
        $this->resetPage();
        $this->clearDeprivedAreasCache();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
        $this->clearDeprivedAreasCache();
    }

    public function toggleFilter()
    {
        $this->showOnlyDeprived = !$this->showOnlyDeprived;
        $this->resetPage();
        $this->clearDeprivedAreasCache();
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();
    }

    public function gotoPage($page)
    {
        $this->setPage($page);
    }
}
