<?php

namespace App\Http\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RankSetting;
use Illuminate\Validation\Rule;

class RankSettings extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $showModal = false;
    public $editingId = null;
    public $name = '';
    public $key = '';
    public $description = '';
    public $weight = 1;
    public $category = 'other';
    public $is_active = true;
    public $sort_order = 0;

    public $search = '';
    public $filterCategory = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterCategory' => ['except' => ''],
    ];

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'key' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z_]+$/',
                Rule::unique('rank_settings', 'key')->ignore($this->editingId),
            ],
            'description' => 'nullable|string',
            'weight' => 'required|integer|min:1|max:100',
            'category' => 'required|in:disability,disease,addiction,economic,social,other',
            'is_active' => 'boolean',
            'sort_order' => 'required|integer|min:0',
        ];
    }

    protected $validationAttributes = [
        'name' => 'نام معیار',
        'key' => 'کلید معیار',
        'description' => 'توضیحات',
        'weight' => 'وزن',
        'category' => 'دسته‌بندی',
        'is_active' => 'وضعیت فعال',
        'sort_order' => 'ترتیب نمایش',
    ];

    public function mount()
    {
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editingId = null;
        $this->name = '';
        $this->key = '';
        $this->description = '';
        $this->weight = 1;
        $this->category = 'other';
        $this->is_active = true;
        $this->sort_order = RankSetting::max('sort_order') + 1;
        $this->showModal = false;
        $this->resetValidation();
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function edit($id)
    {
        $rankSetting = RankSetting::findOrFail($id);
        
        $this->editingId = $rankSetting->id;
        $this->name = $rankSetting->name;
        $this->key = $rankSetting->key;
        $this->description = $rankSetting->description;
        $this->weight = $rankSetting->weight;
        $this->category = $rankSetting->category;
        $this->is_active = $rankSetting->is_active;
        $this->sort_order = $rankSetting->sort_order;
        
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        try {
            if ($this->editingId) {
                $rankSetting = RankSetting::findOrFail($this->editingId);
                $rankSetting->update([
                    'name' => $this->name,
                    'key' => $this->key,
                    'description' => $this->description,
                    'weight' => $this->weight,
                    'category' => $this->category,
                    'is_active' => $this->is_active,
                    'sort_order' => $this->sort_order,
                ]);
                
                session()->flash('success', 'معیار رتبه‌بندی با موفقیت به‌روزرسانی شد.');
            } else {
                RankSetting::create([
                    'name' => $this->name,
                    'key' => $this->key,
                    'description' => $this->description,
                    'weight' => $this->weight,
                    'category' => $this->category,
                    'is_active' => $this->is_active,
                    'sort_order' => $this->sort_order,
                ]);
                
                session()->flash('success', 'معیار رتبه‌بندی جدید با موفقیت ایجاد شد.');
            }

            $this->closeModal();
        } catch (\Exception $e) {
            session()->flash('error', 'خطا در ذخیره معیار: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $rankSetting = RankSetting::findOrFail($id);
            
            // بررسی اینکه آیا این معیار در خانواده‌ای استفاده شده یا نه
            if ($rankSetting->familyCriteria()->exists()) {
                session()->flash('error', 'این معیار در خانواده‌هایی استفاده شده و قابل حذف نیست.');
                return;
            }
            
            $rankSetting->delete();
            session()->flash('success', 'معیار رتبه‌بندی با موفقیت حذف شد.');
        } catch (\Exception $e) {
            session()->flash('error', 'خطا در حذف معیار: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        try {
            $rankSetting = RankSetting::findOrFail($id);
            $rankSetting->update(['is_active' => !$rankSetting->is_active]);
            
            $status = $rankSetting->is_active ? 'فعال' : 'غیرفعال';
            session()->flash('success', "معیار با موفقیت {$status} شد.");
        } catch (\Exception $e) {
            session()->flash('error', 'خطا در تغییر وضعیت: ' . $e->getMessage());
        }
    }

    public function getCategories()
    {
        return RankSetting::getCategories();
    }

    public function render()
    {
        $query = RankSetting::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('key', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterCategory) {
            $query->where('category', $this->filterCategory);
        }

        $rankSettings = $query->ordered()->paginate(10);

        return view('livewire.admin.rank-settings', [
            'rankSettings' => $rankSettings,
            'categories' => $this->getCategories(),
        ]);
    }
} 
