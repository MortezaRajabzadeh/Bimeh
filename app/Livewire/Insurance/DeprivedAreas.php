<?php

namespace App\Livewire\Insurance;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Province;

class DeprivedAreas extends Component
{
    use WithPagination;

    public $search = '';
    public $showOnlyDeprived = false;
    public $perPage = 20;
    public $expandedProvinces = []; // آرایه برای نگهداری استان‌های باز شده
    
    protected $paginationTheme = 'tailwind';

    public function render()
    {
        // شروع query با تمام استان‌ها
        $query = Province::query()->with([
            'cities' => function($cityQuery) {
                $cityQuery->with(['districts' => function($districtQuery) {
                    if ($this->showOnlyDeprived) {
                        $districtQuery->where('is_deprived', true);
                    }
                    $districtQuery->orderBy('name');
                }])->orderBy('name');
            }
        ]);

        // اعمال جستجوی پیشرفته
        if (!empty($this->search)) {
            $searchTerm = trim($this->search);
            $cleanSearchTerm = $this->cleanSearchTerm($searchTerm);
            
            $query->where(function($mainQuery) use ($searchTerm, $cleanSearchTerm) {
                // جستجوی دقیق در نام استان
                $mainQuery->where('name', 'like', "%{$searchTerm}%")
                         ->orWhere('name', 'like', "%{$cleanSearchTerm}%");
                
                // جستجو در شهرستان‌ها
                $mainQuery->orWhereHas('cities', function($cityQuery) use ($searchTerm, $cleanSearchTerm) {
                    $cityQuery->where('name', 'like', "%{$searchTerm}%")
                             ->orWhere('name', 'like', "%{$cleanSearchTerm}%");
                });
                
                // جستجو در دهستان‌ها
                $mainQuery->orWhereHas('cities.districts', function($districtQuery) use ($searchTerm, $cleanSearchTerm) {
                    $districtQuery->where('name', 'like', "%{$searchTerm}%")
                                 ->orWhere('name', 'like', "%{$cleanSearchTerm}%");
                });
                
                // جستجوی جزئی کلمات
                if (strlen($searchTerm) > 2) {
                    $words = explode(' ', $searchTerm);
                    if (count($words) > 1) {
                        foreach ($words as $word) {
                            if (strlen(trim($word)) > 1) {
                                $word = trim($word);
                                $mainQuery->orWhere('name', 'like', "%{$word}%")
                                         ->orWhereHas('cities', function($cityQuery) use ($word) {
                                             $cityQuery->where('name', 'like', "%{$word}%");
                                         })
                                         ->orWhereHas('cities.districts', function($districtQuery) use ($word) {
                                             $districtQuery->where('name', 'like', "%{$word}%");
                                         });
                            }
                        }
                    }
                }
            });
        }

        // فیلتر کردن استان‌هایی که دهستان دارند
        $query->whereHas('cities.districts');
        
        // اگر جستجو داریم، بر اساس مطابقت مرتب کن
        if (!empty($this->search)) {
            $provinces = $this->getSortedResultsByRelevance($query, $this->search);
            // اگر جستجو داریم، تمام استان‌ها رو باز کن
            foreach ($provinces as $province) {
                $this->expandedProvinces[$province->id] = true;
            }
        } else {
            $provinces = $query->orderBy('name')->paginate($this->perPage);
        }

        // فیلتر کردن شهرستان‌هایی که دهستان ندارند (بعد از بارگذاری)
        foreach ($provinces as $province) {
            $province->cities = $province->cities->filter(function($city) {
                return $city->districts->count() > 0;
            });
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
     * مرتب‌سازی نتایج بر اساس میزان مطابقت
     */
    private function getSortedResultsByRelevance($query, $searchTerm)
    {
        $allResults = $query->get();
        $searchTerm = strtolower($this->cleanSearchTerm($searchTerm));
        
        // محاسبه امتیاز مطابقت برای هر استان
        $scoredResults = $allResults->map(function($province) use ($searchTerm) {
            $score = 0;
            
            // امتیاز مطابقت نام استان (بالاترین اولویت)
            $provinceName = strtolower($province->name);
            if ($provinceName === $searchTerm) {
                $score += 1000; // مطابقت کامل
            } elseif (strpos($provinceName, $searchTerm) === 0) {
                $score += 800; // شروع با کلیدواژه
            } elseif (strpos($provinceName, $searchTerm) !== false) {
                $score += 600; // شامل کلیدواژه
            }
            
            // امتیاز مطابقت شهرستان‌ها
            foreach ($province->cities as $city) {
                $cityName = strtolower($city->name);
                if ($cityName === $searchTerm) {
                    $score += 500;
                } elseif (strpos($cityName, $searchTerm) === 0) {
                    $score += 400;
                } elseif (strpos($cityName, $searchTerm) !== false) {
                    $score += 300;
                }
                
                // امتیاز مطابقت دهستان‌ها
                foreach ($city->districts as $district) {
                    $districtName = strtolower($district->name);
                    if ($districtName === $searchTerm) {
                        $score += 200;
                    } elseif (strpos($districtName, $searchTerm) === 0) {
                        $score += 150;
                    } elseif (strpos($districtName, $searchTerm) !== false) {
                        $score += 100;
                    }
                }
            }
            
            $province->search_score = $score;
            return $province;
        });
        
        // مرتب‌سازی بر اساس امتیاز (از بالا به پایین)
        $sortedResults = $scoredResults->sortByDesc('search_score')->values();
        
        // تبدیل به paginated result
        $page = request()->get('page', 1);
        $perPage = $this->perPage;
        $offset = ($page - 1) * $perPage;
        
        $paginatedItems = $sortedResults->slice($offset, $perPage);
        
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $sortedResults->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
        
        return $paginated;
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
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function toggleFilter()
    {
        $this->showOnlyDeprived = !$this->showOnlyDeprived;
        $this->resetPage();
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
