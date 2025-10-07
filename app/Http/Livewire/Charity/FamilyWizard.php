<?php

namespace App\Livewire\Charity;

use App\Models\City;
use App\Models\District;
use App\Models\Province;
use Livewire\Component;
use Livewire\WithFileUploads;

class FamilyWizard extends Component
{
    use WithFileUploads;

    public $currentStep = 1;
    public $totalSteps = 3;
    
    // فیلدهای مربوط به موقعیت جغرافیایی
    public $province_id;
    public $city_id;
    public $district_id;
    
    public $postal_code;
    public $address;
    public $housing_status;
    public $housing_description;
    public $members = [];
    public $head_member_index;
    public $family_photo;
    public $family_code;
    public $provinces;
    public $cities;
    public $districts;
    public $head = [
        'first_name' => '',
        'last_name' => '',
        'national_code' => '',
        'father_name' => '',
        'birth_date' => '',
        'gender' => '',
        'marital_status' => '',
        'occupation' => '',
        'mobile' => '',
        'has_disability' => false,
        'has_chronic_disease' => false,
        'has_insurance' => false,
        'insurance_type' => ''
    ];

    protected $listeners = [
        'mapLocationSelected' => 'handleMapLocation'
    ];

    protected function rules()
    {
        return [
            'province_id' => 'required',
            'city_id' => 'required',
            'district_id' => 'required',
            'postal_code' => 'required|string|size:10',
            'address' => 'required|string|min:10',
            'housing_status' => 'required|in:owned,rented,relative,organizational,owner,tenant,relative,other',
            'housing_description' => 'nullable|string',
            'family_photo' => 'nullable|image|max:2048',
        ];
    }

    protected function messages()
    {
        return [
            'province_id.required' => 'انتخاب استان الزامی است',
            'city_id.required' => 'انتخاب شهرستان الزامی است',
            'district_id.required' => 'انتخاب دهستان الزامی است', 
            'postal_code.required' => 'کد پستی را وارد کنید',
            'postal_code.size' => 'کد پستی باید ۱۰ رقم باشد',
            'address.required' => 'آدرس را وارد کنید',
            'address.min' => 'آدرس باید حداقل ۱۰ حرف باشد',
            'housing_status.required' => 'وضعیت مسکن را انتخاب کنید',
            'housing_status.in' => 'وضعیت مسکن نامعتبر است',
            'family_photo.image' => 'فایل انتخاب شده باید تصویر باشد',
            'family_photo.max' => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد',
        ];
    }

    public function mount()
    {
        $this->currentStep = 1;
        $this->family_code = 'F-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $this->provinces = Province::orderBy('name')->get();
        $this->cities = collect();
        $this->districts = collect();
    }

    /**
     * هنگام تغییر استان، شهرستان‌های مربوطه را بارگذاری می‌کند
     */
    public function updatedProvinceId($value)
    {
        $this->cities = collect();
        $this->districts = collect();
        $this->city_id = null;
        $this->district_id = null;
        
        if ($value) {
            $this->cities = City::where('province_id', $value)->orderBy('name')->get();
        }
    }

    /**
     * هنگام تغییر شهرستان، دهستان‌های مربوطه را بارگذاری می‌کند
     */
    public function updatedCityId($value)
    {
        $this->districts = collect();
        $this->district_id = null;
        
        if ($value) {
            $this->districts = District::where('city_id', $value)->orderBy('name')->get();
        }
    }

    public function nextStep()
    {
        if ($this->currentStep === 1) {
            if (empty($this->province_id) || empty($this->city_id) || empty($this->district_id) || 
                empty($this->address) || empty($this->housing_status)) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'لطفاً همه فیلدهای ستاره‌دار را پر کنید'
                ]);
                return;
            }
        }

        if ($this->validateCurrentStep()) {
            if ($this->currentStep < $this->totalSteps) {
                $this->currentStep++;
                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => 'مرحله با موفقیت تکمیل شد'
                ]);
            }
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    protected function validateCurrentStep()
    {
        switch ($this->currentStep) {
            case 1:
                if (empty($this->province_id)) {
                    $this->addError('province_id', 'استان را انتخاب کنید');
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => 'لطفاً استان را انتخاب کنید'
                    ]);
                    return false;
                }
                if (empty($this->city_id)) {
                    $this->addError('city_id', 'شهرستان را انتخاب کنید');
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => 'لطفاً شهرستان را انتخاب کنید'
                    ]);
                    return false;
                }
                if (empty($this->district_id)) {
                    $this->addError('district_id', 'دهستان را انتخاب کنید');
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => 'لطفاً دهستان را انتخاب کنید'
                    ]);
                    return false;
                }
                if (empty($this->address)) {
                    $this->addError('address', 'آدرس را وارد کنید');
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => 'لطفاً آدرس را وارد کنید'
                    ]);
                    return false;
                }
                if (empty($this->housing_status)) {
                    $this->addError('housing_status', 'وضعیت مسکن را انتخاب کنید');
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => 'لطفاً وضعیت مسکن را انتخاب کنید'
                    ]);
                    return false;
                }
                return true;

            case 2:
                if (empty($this->members) || count($this->members) == 0) {
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => 'حداقل یک عضو خانواده باید ثبت شود'
                    ]);
                    return false;
                }
                if (!isset($this->head_member_index)) {
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => 'سرپرست خانواده را مشخص کنید'
                    ]);
                    return false;
                }
                return true;

            default:
                return true;
        }
    }

    public function canProceedToStep($step)
    {
        if ($step <= 1) {
            return true;
        }

        // برای رفتن به مرحله 2، مرحله 1 باید تکمیل شده باشد
        if ($step == 2) {
            return !empty($this->province_id) && 
                   !empty($this->city_id) && 
                   !empty($this->district_id) && 
                   !empty($this->address) && 
                   !empty($this->housing_status);
        }

        // برای رفتن به مرحله 3، مرحله 2 باید تکمیل شده باشد
        if ($step == 3) {
            return $this->canProceedToStep(2) && 
                   !empty($this->members) && 
                   count($this->members) > 0 && 
                   isset($this->head_member_index);
        }

        return false;
    }

    public function goToStep($step)
    {
        if ($this->canProceedToStep($step)) {
            $this->currentStep = $step;
        } else {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'لطفاً ابتدا مرحله قبل را تکمیل کنید'
            ]);
        }
    }

    public function handleMapLocation($data)
    {
        $this->address = $data['address'];
        $this->dispatch('addressUpdated', $this->address);
    }

    public function getFamilyDataProperty()
    {
        return [
            'photo' => $this->family_photo ? $this->family_photo->temporaryUrl() : null,
            'head_name' => $this->head['first_name'] . ' ' . $this->head['last_name'],
            'code' => $this->family_code,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'members_count' => count($this->members) + 1
        ];
    }

    public function addMember()
    {
        $this->members[] = [
            'relationship' => '',  // نسبت
            'first_name' => '',    // نام
            'last_name' => '',     // نام خانوادگی
            'birth_date' => '',    // تاریخ تولد
            'national_code' => '', // کد ملی
            'occupation' => '',    // شغل
            'problem_type' => '',  // نوع مشکل
            'is_head' => false     // سرپرست
        ];

        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => 'عضو جدید اضافه شد'
        ]);
    }

    public function removeMember($index)
    {
        if (isset($this->members[$index])) {
            // اگر عضو حذف شده سرپرست خانوار بود
            if ($this->head_member_index == $index) {
                $this->head_member_index = null;
            }
            // اگر عضو حذف شده قبل از سرپرست خانوار بود، ایندکس سرپرست رو آپدیت کن
            elseif ($this->head_member_index > $index) {
                $this->head_member_index--;
            }

            unset($this->members[$index]);
            $this->members = array_values($this->members); // بازسازی ایندکس‌ها

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'عضو خانواده حذف شد'
            ]);
        }
    }

    protected function validateMember($member)
    {
        if (empty($member['first_name'])) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'نام عضو خانواده را وارد کنید'
            ]);
            return false;
        }

        if (empty($member['last_name'])) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'نام خانوادگی عضو خانواده را وارد کنید'
            ]);
            return false;
        }

        if (empty($member['national_code'])) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'کد ملی عضو خانواده را وارد کنید'
            ]);
            return false;
        }

        if (empty($member['relationship'])) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'نسبت عضو خانواده را مشخص کنید'
            ]);
            return false;
        }

        return true;
    }

    public function render()
    {
        return view('livewire.charity.family-wizard');
    }

    
    public function submit()
    {
        if (!$this->validateCurrentStep()) {
            return;
        }

        try {
            // ایجاد خانواده جدید
            $family = \App\Models\Family::create([
                'code' => $this->family_code,
                'province_id' => $this->province_id,
                'city_id' => $this->city_id, 
                'district_id' => $this->district_id,
                'postal_code' => $this->postal_code,
                'address' => $this->address,
                'housing_status' => $this->housing_status,
                'housing_description' => $this->housing_description,
            ]);

            // ذخیره تصویر خانواده
            if ($this->family_photo) {
                $family->addMedia($this->family_photo)->toMediaCollection('family_photos');
            }

            // ذخیره اعضای خانواده
            foreach ($this->members as $index => $member) {
                $family->members()->create([
                    'first_name' => $member['first_name'],
                    'last_name' => $member['last_name'],
                    'national_code' => $member['national_code'],
                    'birth_date' => $member['birth_date'] ?? null,
                    'relationship' => $member['relationship'],
                    'occupation' => $member['occupation'] ?? null,
                    'problem_type' => $member['problem_type'] ?? null,
                    'is_head' => ($index === $this->head_member_index),
                    'gender' => $member['gender'] ?? null,
                    'marital_status' => $member['marital_status'] ?? null,
                    'education_level' => $member['education_level'] ?? null,
                    'phone' => $member['phone'] ?? null,
                    'address' => $member['address'] ?? null,
                    'description' => $member['description'] ?? null
                ]);
            }

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'خانواده با موفقیت ثبت شد'
            ]);

            return redirect()->route('charity.families.index');

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'خطا در ثبت خانواده: ' . $e->getMessage()
            ]);
        }
    }
} 