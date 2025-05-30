<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RankSetting;
use Illuminate\Validation\Rule;

class RankSettings extends Component
{
    use WithPagination;

    // خصوصیات کامپوننت
    public $search = '';
    public $filterCategory = '';
    public $showModal = false;
    public $editingId = null;

    // فیلدهای فرم
    public $name = '';
    public $key = '';
    public $description = '';
    public $weight = 1;
    public $category = 'other';
    public $is_active = true;
    public $sort_order = 0;

    // دسته‌بندی‌ها
    public $categories = [];

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        $this->categories = RankSetting::getCategories();
    }

    public function rules()
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
            'description' => 'nullable|string|max:1000',
            'weight' => 'required|integer|min:1|max:100',
            'category' => 'required|in:' . implode(',', array_keys(RankSetting::getCategories())),
            'is_active' => 'boolean',
            'sort_order' => 'required|integer|min:0',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'نام معیار الزامی است.',
            'name.max' => 'نام معیار نباید بیش از 255 کاراکتر باشد.',
            'key.required' => 'کلید معیار الزامی است.',
            'key.unique' => 'این کلید قبلاً استفاده شده است.',
            'key.regex' => 'کلید معیار باید فقط شامل حروف انگلیسی کوچک و خط تیره باشد.',
            'weight.required' => 'وزن معیار الزامی است.',
            'weight.min' => 'وزن معیار باید حداقل 1 باشد.',
            'weight.max' => 'وزن معیار باید حداکثر 100 باشد.',
            'category.required' => 'دسته‌بندی الزامی است.',
            'category.in' => 'دسته‌بندی انتخاب شده معتبر نیست.',
            'sort_order.required' => 'ترتیب نمایش الزامی است.',
            'sort_order.min' => 'ترتیب نمایش باید حداقل 0 باشد.',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterCategory()
    {
        $this->resetPage();
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
        $this->resetValidation();
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
        $this->sort_order = 0;
    }

    public function edit($id)
    {
        $rankSetting = RankSetting::findOrFail($id);
        
        $this->editingId = $id;
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
                // ویرایش
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
                
                session()->flash('success', 'معیار با موفقیت به‌روزرسانی شد.');
            } else {
                // ایجاد جدید
                RankSetting::create([
                    'name' => $this->name,
                    'key' => $this->key,
                    'description' => $this->description,
                    'weight' => $this->weight,
                    'category' => $this->category,
                    'is_active' => $this->is_active,
                    'sort_order' => $this->sort_order,
                ]);
                
                session()->flash('success', 'معیار جدید با موفقیت ایجاد شد.');
            }

            $this->closeModal();
        } catch (\Exception $e) {
            session()->flash('error', 'خطا در ذخیره اطلاعات: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        try {
            $rankSetting = RankSetting::findOrFail($id);
            $rankSetting->update(['is_active' => !$rankSetting->is_active]);
            
            $status = $rankSetting->is_active ? 'فعال' : 'غیرفعال';
            session()->flash('success', "وضعیت معیار به {$status} تغییر یافت.");
        } catch (\Exception $e) {
            session()->flash('error', 'خطا در تغییر وضعیت: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $rankSetting = RankSetting::findOrFail($id);
            
            // بررسی استفاده در خانواده‌ها
            if ($rankSetting->familyCriteria()->exists()) {
                session()->flash('error', 'این معیار در خانواده‌ها استفاده شده و قابل حذف نیست.');
                return;
            }
            
            $rankSetting->delete();
            session()->flash('success', 'معیار با موفقیت حذف شد.');
        } catch (\Exception $e) {
            session()->flash('error', 'خطا در حذف معیار: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = RankSetting::query();

        // جستجو
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('key', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // فیلتر دسته‌بندی
        if ($this->filterCategory) {
            $query->where('category', $this->filterCategory);
        }

        $rankSettings = $query->ordered()->paginate(10);

        return view('livewire.admin.rank-settings', [
            'rankSettings' => $rankSettings,
            'categories' => $this->categories,
        ]);
    }
}
