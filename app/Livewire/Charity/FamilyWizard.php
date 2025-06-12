<?php

namespace App\Livewire\Charity;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use App\Services\ProvinceCityService;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

class FamilyWizard extends Component
{
    use WithFileUploads;

    public $currentStep = 1;
    public $totalSteps = 3;
    public $family_code;
    public $province_id;
    public $city_id;
    public $district_id;
    public $provinces = [];
    public $cities;
    public $districts;
    public $selectedCity = null;
    public $selectedDistrict = null;
    public $postal_code;
    public $address;
    public $housing_status;
    public $housing_description;
    public $members = [];
    public $head_member_index;
    public $family_photo;
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
    public $additional_info = '';
    public $confirmSubmission = false;
    public $acceptance_criteria = [];

    protected $listeners = [
        'mapLocationSelected' => 'handleMapLocation'
    ];

    public function mount(): void
    {
        $this->provinces = Province::select('id', 'name')->cursor()->collect();
        if (empty($this->family_code)) {
            $this->family_code = $this->generateUniqueFamilyCode();
        }
    }
    
    public function updatedProvinceId($value): void
    {
        if ($this->city_id || $this->district_id) {
            $this->dispatch('show-message', 'info', 'با تغییر استان، شهر و دهستان انتخاب‌شده حذف شد.');
        }
        $this->reset(['city_id', 'district_id', 'cities', 'districts']);
        $this->cities = City::where('province_id', $value)
            ->select('id', 'name')
            ->cursor()
            ->collect();
    }
    
    public function updatedCityId($value): void
    {
        $this->reset(['district_id', 'districts']);
        $this->districts = District::where('city_id', $value)
            ->select('id', 'name')
            ->cursor()
            ->collect();
    }

    public function updatedDistrictId($value)
    {
        $this->selectedDistrict = District::find($value);
    }

    public function nextStep()
    {
        // $this->validate(); // اعتبارسنجی قبل از رفتن به مرحله بعد
            'currentStep' => $this->currentStep,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'address' => $this->address,
            'members' => $this->members,
            'head_member_index' => $this->head_member_index,
        ]);
        try {
            $result = $this->validateCurrentStep();
            if ($result === true && $this->currentStep < $this->totalSteps) {
                $this->currentStep++;
                $this->dispatch('show-message', 'success', 'مرحله با موفقیت تکمیل شد');
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage() ?: 'خطای ناشناخته‌ای رخ داده است. لطفاً مجدداً تلاش کنید.';
            session()->flash('error', $msg);
            $this->dispatch('show-message', 'error', $msg);
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
                    throw new \Exception('استان را انتخاب کنید');
                }
                if (empty($this->city_id)) {
                    throw new \Exception('شهرستان را انتخاب کنید');
                }
                if (empty($this->district_id)) {
                    throw new \Exception('دهستان را انتخاب کنید');
                }
                if (empty($this->address)) {
                    throw new \Exception('آدرس را وارد کنید');
                }
                return true;

            case 2:
                if (empty($this->members) || count($this->members) == 0) {
                    throw new \Exception('حداقل یک عضو خانواده باید ثبت شود');
                }
                if (!isset($this->head_member_index)) {
                    throw new \Exception('سرپرست خانواده را مشخص کنید');
                }
                foreach ($this->members as $index => $member) {
                    if (empty($member['relationship'])) {
                        throw new \Exception("نسبت عضو خانواده شماره " . ($index + 1) . " را مشخص کنید");
                    }
                    if (empty($member['first_name'])) {
                        throw new \Exception("نام عضو خانواده شماره " . ($index + 1) . " را وارد کنید");
                    }
                    if (empty($member['last_name'])) {
                        throw new \Exception("نام خانوادگی عضو خانواده شماره " . ($index + 1) . " را وارد کنید");
                    }
                    if (empty($member['national_code'])) {
                        throw new \Exception("کد ملی عضو خانواده شماره " . ($index + 1) . " را وارد کنید");
                    }
                    if (!preg_match('/^[0-9]{10}$/', $member['national_code'])) {
                        throw new \Exception("کد ملی عضو خانواده شماره " . ($index + 1) . " باید ۱۰ رقم باشد");
                    }
                    
                    // اعتبارسنجی اطلاعات سرپرست
                    if ((int)$index === (int)$this->head_member_index) {
                        if (empty($member['phone'])) {
                            throw new \Exception("شماره تماس سرپرست خانوار را وارد کنید");
                        }
                        if (!preg_match('/^09[0-9]{9}$/', $member['phone'])) {
                            throw new \Exception("شماره تماس سرپرست خانوار باید با ۰۹ شروع شود و ۱۱ رقم باشد");
                        }
                        if (empty($member['sheba'])) {
                            throw new \Exception("شماره شبا سرپرست خانوار را وارد کنید");
                        }
                        if (!preg_match('/^IR[0-9]{24}$/', $member['sheba'])) {
                            throw new \Exception("شماره شبا باید با IR شروع شود و ۲۶ کاراکتر باشد");
                        }
                    }
                }
                return true;

            case 3:
                if (!$this->confirmSubmission) {
                    throw new \Exception('لطفاً صحت اطلاعات را تأیید کنید');
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

        if ($step == 2) {
            return !empty($this->family_code) &&
                   !empty($this->province_id) &&
                   !empty($this->city_id) &&
                   !empty($this->address);
        }

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
            $this->dispatch('show-message', 'error', 'لطفاً ابتدا مرحله قبل را تکمیل کنید');
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

        $this->dispatch('show-message', 'success', 'عضو جدید اضافه شد');
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

            $this->dispatch('show-message', 'success', 'عضو خانواده حذف شد');
        }
    }

    protected function validateMember($member)
    {
        if (empty($member['first_name'])) {
            $this->dispatch('show-message', 'error', 'نام عضو خانواده را وارد کنید');
            return false;
        }

        if (empty($member['last_name'])) {
            $this->dispatch('show-message', 'error', 'نام خانوادگی عضو خانواده را وارد کنید');
            return false;
        }

        if (empty($member['national_code'])) {
            $this->dispatch('show-message', 'error', 'کد ملی عضو خانواده را وارد کنید');
            return false;
        }

        if (empty($member['relationship'])) {
            $this->dispatch('show-message', 'error', 'نسبت عضو خانواده را مشخص کنید');
            return false;
        }

        return true;
    }

    public function render()
    {
        // فقط پاس دادن مقادیر، بدون مقداردهی مجدد
        if (isset($this->head_member_index) && isset($this->members[$this->head_member_index])) {
            $this->head = $this->members[$this->head_member_index];
        }
        return view('livewire.charity.family-wizard', [
            'cities' => $this->cities,
            'districts' => $this->districts,
        ]);
    }

    protected function rules()
    {
        if ($this->currentStep == 1) {
            return [
                'province_id' => 'required',
                'city_id' => 'required',
                'address' => 'required',
            ];
        }
        if ($this->currentStep == 2) {
            return [
                'members' => 'required|array|min:1',
                'head_member_index' => 'required',
            ];
        }
        if ($this->currentStep == 3) {
            return [
                'confirmSubmission' => 'accepted',
            ];
        }
        return [
            'province_id' => 'nullable',
        ];
    }

    public function submit()
    {
            'step' => $this->currentStep,
            'confirm' => $this->confirmSubmission,
            'members_count' => count($this->members),
        ]);
        if ($this->currentStep == 3) {
            $this->validate();
        }
        if (!$this->validateCurrentStep()) {
                'step' => $this->currentStep,
                'confirm' => $this->confirmSubmission,
                'members_count' => count($this->members),
            ]);
            return;
        }
        try {
            // ایجاد خانواده جدید
            $family = \App\Models\Family::create([
                'family_code' => $this->family_code,
                'province_id' => $this->province_id,
                'city_id' => $this->city_id,
                'address' => $this->address,
                'acceptance_criteria' => $this->acceptance_criteria,
                'charity_id' => Auth::user()->organization_id,
                'registered_by' => Auth::id(),
                'status' => 'pending', // در انتظار تایید بیمه
                'wizard_status' => 'pending'
                
            ]);

            // ذخیره تصویر خانواده (انتقال از tmp به media)
            if ($this->family_photo) {
                $tmpPath = storage_path('app/public/tmp/' . $this->family_photo);
                if (file_exists($tmpPath)) {
                    $family->addMedia($tmpPath)->toMediaCollection('family_photos');
                    @unlink($tmpPath);
                }
            }

            // ذخیره اعضای خانواده
            foreach ($this->members as $index => $member) {
                // Ensure unique national_code
                $nationalCode = $member['national_code'];
                if (\App\Models\Member::where('national_code', $nationalCode)->exists()) {
                    // Generate a random unique 10-digit code
                    do {
                        $nationalCode = str_pad(strval(rand(0, 9999999999)), 10, '0', STR_PAD_LEFT);
                    } while (\App\Models\Member::where('national_code', $nationalCode)->exists());
                }
                $birthDate = null;
                if (!empty($member['birth_date'])) {
                    $birthDate = $member['birth_date'];
                }
                $family->members()->create([
                    'first_name' => $member['first_name'],
                    'last_name' => $member['last_name'],
                    'national_code' => $nationalCode,
                    'birth_date' => $birthDate,
                    'relationship' => $member['relationship'],
                    'occupation' => $member['occupation'] ?? null,
                    'problem_type' => $member['problem_type'] ?? null,
                    'is_head' => ((int)$index === (int)$this->head_member_index),
                    'gender' => $member['gender'] ?? null,
                    'marital_status' => $member['marital_status'] ?? null,
                    'education' => $member['education'] ?? null,
                    'phone' => $member['phone'] ?? null,
                    'sheba' => $member['sheba'] ?? null,
                ]);
            }

                'family_id' => $family->id,
            ]);

            $this->dispatch('show-message', 'success', 'خانواده با موفقیت ثبت شد');

            return $this->redirectRoute('charity.dashboard', ['highlight' => $family->id]);
        } catch (\Exception $e) {
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $msg = $e->getMessage() ?: 'خطای ناشناخته‌ای رخ داده است. لطفاً مجدداً تلاش کنید.';
            $this->dispatch('show-message', 'error', $msg);
        }
    }

    public function updatedMembers()
    {
        $allProblems = [];
        foreach ($this->members as $member) {
            if (!empty($member['problem_type'])) {
                if (is_array($member['problem_type'])) {
                    $allProblems = array_merge($allProblems, $member['problem_type']);
                } else {
                    $allProblems[] = $member['problem_type'];
                }
            }
        }
        $this->acceptance_criteria = array_values(array_unique($allProblems));
    }

    /**
     * Generate a unique family code
     * Format: [year][month][day][charity_id][random_6_digits]
     * @return string
     */
    private function generateUniqueFamilyCode(): string
    {
        $maxAttempts = 100;
        $attempt = 0;
        
        do {
            $attempt++;
            
            // تولید کد بر اساس تاریخ جاری + ID سازمان + شماره تصادفی
            $year = now()->format('Y');
            $month = str_pad(now()->format('m'), 2, '0', STR_PAD_LEFT);
            $day = str_pad(now()->format('d'), 2, '0', STR_PAD_LEFT);
            $charityId = str_pad(Auth::user()->organization_id ?? 1, 3, '0', STR_PAD_LEFT);
            $randomSuffix = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            $code = $year . $month . $day . $charityId . $randomSuffix;
            
            // اگر بیش از حد تلاش کردیم، یک کد کاملاً تصادفی 15 رقمی تولید کنیم
            if ($attempt > $maxAttempts) {
                $code = str_pad(strval(random_int(100000000000000, 999999999999999)), 15, '0', STR_PAD_LEFT);
            }
            
        } while (\App\Models\Family::where('family_code', $code)->exists() && $attempt <= $maxAttempts + 10);
        
        return $code;
    }

    protected function messages()
    {
        return [
            'province_id.required' => 'استان را انتخاب کنید.',
            'city_id.required' => 'شهرستان را انتخاب کنید.',
            'district_id.required' => 'دهستان را انتخاب کنید.',
            'address.required' => 'آدرس را وارد کنید.',
            'members.required' => 'حداقل یک عضو خانواده باید ثبت شود.',
            'members.array' => 'فرمت اعضای خانواده صحیح نیست.',
            'members.min' => 'حداقل یک عضو خانواده باید ثبت شود.',
            'head_member_index.required' => 'سرپرست خانواده را مشخص کنید.',
            'confirmSubmission.accepted' => 'لطفاً صحت اطلاعات را تأیید کنید.',
        ];
    }

    public function uploadFamilyPhoto()
    {
        if (request()->hasFile('family_photo')) {
            $file = request()->file('family_photo');
            $filename = uniqid('family_', true) . '.' . $file->getClientOriginalExtension();
            $file->storeAs('tmp', $filename, 'public');
            $this->family_photo = $filename;
        }
    }

    public function removeFamilyPhoto()
    {
        if ($this->family_photo) {
            $tmpPath = storage_path('app/public/tmp/' . $this->family_photo);
            if (file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
            $this->family_photo = null;
        }
    }

    public function updatedHeadMemberIndex($value)
    {
        // ارسال رویداد به جاوا اسکریپت برای اعمال تغییرات
        $this->dispatch('headMemberChanged', $value);
    }

    public function updateHeadMemberIndex($value)
    {
        $this->head_member_index = $value;
    }
} 
