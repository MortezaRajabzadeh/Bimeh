<?php

namespace App\Livewire\Charity;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Region;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class FamilyWizard extends Component
{
    use WithFileUploads;

    // ویزارد - مراحل فرم
    public $currentStep = 1;
    public $totalSteps = 4;
    
    // اطلاعات خانواده
    public $family_code;
    public $region_id;
    public $address;
    public $postal_code;
    public $housing_status;
    public $housing_description;
    public $additional_info;
    
    // مدیریت عکس خانواده
    public $family_photo;
    public $family_photo_preview;
    
    // سرپرست خانوار
    public $head = [
        'first_name' => '',
        'last_name' => '',
        'national_code' => '',
        'father_name' => '',
        'birth_date' => '',
        'gender' => '',
        'marital_status' => '',
        'education' => '',
        'has_disability' => false,
        'has_chronic_disease' => false,
        'has_insurance' => false,
        'occupation' => '',
        'mobile' => '',
        'insurance_type' => '',
        'is_head' => true,
    ];
    
    // اعضای خانواده
    public $members = [];
    
    // تأیید نهایی
    public $confirmSubmission = false;
    
    // برای نمایش خطاها و هشدارها
    public $nationalCodeExists = false;
    public $mobileExists = false;
    public $suggestedRegion = null;
    public $suggestedInsurance = null;
    public $ageCalculated = null;
    
    // لیست‌های انتخاب
    public $regions = [];
    public $insuranceTypes = [
        'social_security' => 'تأمین اجتماعی',
        'health_services' => 'خدمات درمانی',
        'armed_forces' => 'نیروهای مسلح',
        'salamat' => 'بیمه سلامت',
        'none' => 'ندارد'
    ];

    protected $listeners = ['mapLocationSelected'];

    public function mount()
    {
        // تولید خودکار کد خانواده
        $this->family_code = 'F' . date('Ymd') . rand(1000, 9999);
        
        // دریافت لیست مناطق
        $this->regions = Region::pluck('name', 'id')->toArray();
        
        // یک عضو خالی به عنوان شروع اضافه می‌کنیم
        $this->addMember();
        
        // اطمینان از مقداردهی اولیه آرایه تگ‌ها برای هر عضو
        foreach ($this->members as &$member) {
            if (!isset($member['special_conditions_tags']) || !is_array($member['special_conditions_tags'])) {
                $member['special_conditions_tags'] = [];
            }
        }
    }

    public function render()
    {
        return view('livewire.charity.family-wizard');
    }

    // مدیریت مراحل ویزارد
    public function nextStep()
    {
        // اعتبارسنجی هر مرحله
        $this->validateStep($this->currentStep);
        
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep($step)
    {
        if ($step >= 1 && $step <= $this->totalSteps) {
            $this->currentStep = $step;
        }
    }

    // اعتبارسنجی مراحل
    protected function validateStep($step)
    {
        switch ($step) {
            case 1: // اطلاعات خانواده
                $this->validate([
                    'region_id' => 'required',
                    'address' => 'required|string|min:10|max:500',
                    'postal_code' => 'required|string|size:10|regex:/^[0-9]+$/',
                    'housing_status' => 'required|string',
                ], [
                    'region_id.required' => 'انتخاب منطقه الزامی است.',
                    'address.required' => 'آدرس الزامی است.',
                    'address.min' => 'آدرس باید حداقل ۱۰ کاراکتر باشد.',
                    'postal_code.required' => 'کد پستی الزامی است.',
                    'postal_code.size' => 'کد پستی باید ۱۰ رقم باشد.',
                    'postal_code.regex' => 'کد پستی فقط باید شامل ارقام باشد.',
                    'housing_status.required' => 'انتخاب وضعیت مسکن الزامی است.',
                ]);
                break;
                
            case 2: // سرپرست خانوار
                $this->validate([
                    'head.first_name' => 'required|string|min:2|max:100',
                    'head.last_name' => 'required|string|min:2|max:100',
                    'head.national_code' => 'required|string|size:10|regex:/^[0-9]+$/|unique:family_members,national_code',
                    'head.father_name' => 'required|string|min:2|max:100',
                    'head.birth_date' => 'required|string|regex:/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/',
                    'head.gender' => 'required|string|in:male,female',
                    'head.mobile' => 'required|string|regex:/^09[0-9]{9}$/|unique:family_members,mobile',
                ]);
                break;
                
            case 3: // اعضای خانواده
                foreach ($this->members as $index => $member) {
                    if (!empty($member['first_name']) || !empty($member['last_name']) || !empty($member['national_code'])) {
                        $this->validate([
                            "members.{$index}.first_name" => 'required|string|min:2|max:100',
                            "members.{$index}.last_name" => 'required|string|min:2|max:100',
                            "members.{$index}.national_code" => 'required|string|size:10|regex:/^[0-9]+$/|unique:family_members,national_code',
                            "members.{$index}.relationship" => 'required|string',
                        ]);
                    }
                }
                break;
        }
    }

    // اضافه کردن عضو جدید
    public function addMember()
    {
        $this->members[] = [
            'first_name' => '',
            'last_name' => '',
            'national_code' => '',
            'father_name' => '',
            'birth_date' => '',
            'gender' => '',
            'relationship' => '',
            'has_disability' => false,
            'has_chronic_disease' => false,
            'has_insurance' => false,
            'is_head' => false,
            'insurance_type' => '',
            'special_conditions_tags' => [],
        ];
    }

    // حذف عضو
    public function removeMember($index)
    {
        if (isset($this->members[$index])) {
            unset($this->members[$index]);
            $this->members = array_values($this->members);
        }
    }

    // آپدیت پیشنهادات و بررسی فیلدها
    public function updatedPostalCode()
    {
        // تشخیص منطقه بر اساس کد پستی
        if (strlen($this->postal_code) === 10) {
            // الگوریتم ساده تشخیص منطقه بر اساس کد پستی
            $regionCode = intval(substr($this->postal_code, 0, 2));
            
            if ($regionCode >= 10 && $regionCode <= 19) {
                $this->suggestedRegion = $regionCode - 9; // مثلاً کد پستی 11 برای منطقه 2 تهران
                $this->region_id = $this->suggestedRegion;
            }
        }
    }

    // بررسی کد ملی
    public function updatedHeadNationalCode()
    {
        $this->checkNationalCode($this->head['national_code']);
    }

    public function updatedMembersNationalCode($value, $index)
    {
        $index = explode('.', $index)[0]; // برای دریافت ایندکس عضو از نام فیلد
        $this->checkNationalCode($this->members[$index]['national_code']);
    }

    // بررسی تاریخ تولد
    public function updatedHeadBirthDate()
    {
        $this->calculateAge($this->head['birth_date']);
    }

    public function updatedMembersBirthDate($value, $index)
    {
        $index = explode('.', $index)[0];
        $this->calculateAge($this->members[$index]['birth_date']);
    }

    // پیشنهاد نوع بیمه
    public function updatedHeadHasInsurance()
    {
        if ($this->head['has_insurance']) {
            $this->suggestInsurance();
        }
    }

    // دریافت موقعیت از نقشه
    public function mapLocationSelected($location)
    {
        $this->address = $location['address'] ?? $this->address;
        $this->region_id = $location['region_id'] ?? $this->region_id;
    }

    // بررسی وجود کد ملی
    protected function checkNationalCode($code)
    {
        if (strlen($code) === 10) {
            // در اینجا می‌توانیم از دیتابیس بررسی کنیم
            $exists = FamilyMember::where('national_code', $code)->exists();
            
            if ($exists) {
                $this->nationalCodeExists = true;
                $this->dispatchBrowserEvent('show-toast', [
                    'type' => 'warning',
                    'message' => 'این کد ملی قبلاً در سیستم ثبت شده است!'
                ]);
            } else {
                $this->nationalCodeExists = false;
            }

            // اعتبارسنجی الگوریتم کد ملی ایران
            $this->validateIranianNationalCode($code);
        }
    }

    // محاسبه سن
    protected function calculateAge($birthDate)
    {
        if (preg_match('/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/', $birthDate)) {
            list($year, $month, $day) = explode('/', $birthDate);
            
            $jDateNow = now()->format('Y/m/d');
            list($jNowYear, $jNowMonth, $jNowDay) = explode('/', $jDateNow);
            
            $age = $jNowYear - $year;
            
            if ($jNowMonth < $month || ($jNowMonth == $month && $jNowDay < $day)) {
                $age--;
            }
            
            $this->ageCalculated = $age;
            $this->dispatchBrowserEvent('show-age', [
                'age' => $age
            ]);
        }
    }

    // پیشنهاد نوع بیمه
    protected function suggestInsurance()
    {
        // روش ساده: اگر فرد سن بالا یا بیماری خاص دارد، بیمه سلامت پیشنهاد می‌شود
        if (isset($this->ageCalculated) && $this->ageCalculated > 60 || $this->head['has_chronic_disease']) {
            $this->suggestedInsurance = 'salamat';
            $this->head['insurance_type'] = 'salamat';
            
            $this->dispatchBrowserEvent('show-toast', [
                'type' => 'info',
                'message' => 'بیمه سلامت برای این فرد پیشنهاد می‌شود'
            ]);
        }
    }

    // اعتبارسنجی کد ملی ایران
    protected function validateIranianNationalCode($nationalCode)
    {
        if (!preg_match('/^[0-9]{10}$/', $nationalCode)) {
            return false;
        }
        
        $check = (int)$nationalCode[9];
        $sum = 0;
        
        for ($i = 0; $i < 9; $i++) {
            $sum += ((int)$nationalCode[$i]) * (10 - $i);
        }
        
        $remainder = $sum % 11;
        
        $result = ($remainder < 2 && $check == $remainder) || ($remainder >= 2 && $check == (11 - $remainder));
        
        if (!$result) {
            $this->dispatchBrowserEvent('show-toast', [
                'type' => 'error',
                'message' => 'کد ملی وارد شده صحیح نیست!'
            ]);
        }
        
        return $result;
    }

    // ثبت نهایی خانواده
    public function submit()
    {
        // اعتبارسنجی نهایی تمام مراحل
        $this->validateStep(1);
        $this->validateStep(2);
        $this->validateStep(3);
        
        // بررسی تأیید نهایی
        $this->validate([
            'confirmSubmission' => 'accepted'
        ], [
            'confirmSubmission.accepted' => 'لطفاً صحت اطلاعات را تأیید کنید.'
        ]);
        
        try {
            DB::beginTransaction();
            
            // ایجاد خانواده
            $family = Family::create([
                'family_code' => $this->family_code,
                'region_id' => $this->region_id,
                'address' => $this->address,
                'postal_code' => $this->postal_code,
                'housing_status' => $this->housing_status,
                'housing_description' => $this->housing_description,
                'additional_info' => $this->additional_info,
            ]);
            
            // آپلود عکس خانواده (اگر وجود داشت)
            if ($this->family_photo) {
                $photoPath = $this->family_photo->store('family-photos', 'public');
                $family->update(['photo_path' => $photoPath]);
            }
            
            // ایجاد سرپرست خانوار
            $family->members()->create(array_merge($this->head, [
                'family_id' => $family->id,
                'is_head' => true
            ]));
            
            // ایجاد اعضای خانواده
            foreach ($this->members as $member) {
                if (!empty($member['first_name']) && !empty($member['last_name']) && !empty($member['national_code'])) {
                    $member['special_conditions'] = is_array($member['special_conditions_tags'])
                        ? implode(',', array_filter($member['special_conditions_tags']))
                        : '';
                    unset($member['special_conditions_tags']);
                    $family->members()->create(array_merge($member, [
                        'family_id' => $family->id,
                        'is_head' => false
                    ]));
                }
            }
            
            DB::commit();
            
            // نمایش پیام موفقیت
            $this->dispatchBrowserEvent('show-toast', [
                'type' => 'success',
                'message' => 'اطلاعات خانواده با موفقیت ثبت شد'
            ]);
            
            // ریدایرکت به صفحه جزئیات خانواده
            return redirect()->route('charity.families.show', $family->id);
            
        } catch (Exception $e) {
            DB::rollBack();
            
            $this->dispatchBrowserEvent('show-toast', [
                'type' => 'error',
                'message' => 'خطا در ثبت اطلاعات: ' . $e->getMessage()
            ]);
        }
    }
} 