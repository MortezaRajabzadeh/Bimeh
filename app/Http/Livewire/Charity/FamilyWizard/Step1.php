<?php

namespace App\Http\Livewire\Charity\FamilyWizard;

use App\Models\City;
use App\Models\District;
use App\Models\Province;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Collection;

class Step1 extends Component
{
    use WithFileUploads;

    public $province_id;
    public $city_id;
    public $district_id;
    public $housing_status;
    public $housing_description;
    public $address;
    public $family_photo;
    
    public $provinces;
    public $cities;
    public $districts;
    
    protected $rules = [
        'province_id' => 'required',
        'city_id' => 'required',
        'district_id' => 'required',
        'housing_status' => 'required',
        'address' => 'required|string|min:10|max:500',
        'family_photo' => 'nullable|image|max:2048',
    ];
    
    protected $messages = [
        'province_id.required' => 'انتخاب استان الزامی است',
        'city_id.required' => 'انتخاب شهرستان الزامی است',
        'district_id.required' => 'انتخاب دهستان الزامی است',
        'housing_status.required' => 'انتخاب وضعیت مسکن الزامی است',
        'address.required' => 'وارد کردن آدرس الزامی است',
        'address.min' => 'آدرس باید حداقل ۱۰ کاراکتر باشد',
        'family_photo.image' => 'فایل انتخاب شده باید تصویر باشد',
        'family_photo.max' => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد',
    ];
    
    public function mount()
    {
        // بارگذاری استان‌ها با استفاده از cursor() برای بهینه‌سازی مصرف حافظه
        // فقط فیلدهای id و name انتخاب می‌شوند
        $this->loadProvinces();
        $this->cities = new Collection();
        $this->districts = new Collection();
    }

    /**
     * بارگذاری لیست استان‌ها
     * استفاده از cursor برای بهینه‌سازی مصرف حافظه
     */
    private function loadProvinces()
    {
        $provinces = Province::select('id', 'name')
            ->orderBy('name')
            ->cursor();
        
        $this->provinces = collect();
        foreach ($provinces as $province) {
            $this->provinces->push($province);
        }
    }
    
    /**
     * بروزرسانی شهرستان‌ها بر اساس استان انتخاب شده
     */
    public function updatedProvinceId($value)
    {
        $this->city_id = null;
        $this->district_id = null;
        $this->cities = new Collection();
        $this->districts = new Collection();

        if ($value) {
            $cities = City::select('id', 'name')
                ->where('province_id', $value)
                ->orderBy('name')
                ->cursor();
            
            foreach ($cities as $city) {
                $this->cities->push($city);
            }
        }
    }
    
    /**
     * بروزرسانی دهستان‌ها بر اساس شهرستان انتخاب شده
     */
    public function updatedCityId($value)
    {
        $this->district_id = null;
        $this->districts = new Collection();
        
        if ($value) {
            $districts = District::select('id', 'name')
                ->where('city_id', $value)
                ->orderBy('name')
                ->cursor();
            
            foreach ($districts as $district) {
                $this->districts->push($district);
            }
        }
    }
    
    /**
     * ارسال و ذخیره اطلاعات فرم
     */
    public function submit()
    {
        $validatedData = $this->validate();
        
        try {
            // اینجا کد ذخیره اطلاعات در دیتابیس رو بنویسید
            
            $this->emit('show-message', 'success', 'اطلاعات پایه خانواده با موفقیت ذخیره شد.');
            
            // انتقال به مرحله بعدی
            $this->emit('step-completed', 1);
            
        } catch (\Exception $e) {
            $this->emit('show-message', 'error', 'خطا در ذخیره اطلاعات: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        return view('livewire.charity.family-wizard.step1');
    }
} 
