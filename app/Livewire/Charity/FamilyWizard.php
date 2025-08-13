<?php

namespace App\Livewire\Charity;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\ProvinceCityService;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;
use App\Helpers\ProblemTypeHelper;

class FamilyWizard extends Component
{
    use WithFileUploads;

    public $currentStep = 1;
    public $totalSteps = 3;
    public $family_id; // For storing the created family ID
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
    public $specialDiseaseDocuments = [];
    public $uploadedDocuments = [];

    protected $listeners = [
        'mapLocationSelected' => 'handleMapLocation'
    ];

    protected $rules = [
        'specialDiseaseDocuments.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
    ];

    protected $messages = [
        'specialDiseaseDocuments.*.mimes' => 'فرمت فایل باید از نوع: pdf, jpg, jpeg یا png باشد.',
        'specialDiseaseDocuments.*.max' => 'حداکثر حجم فایل 5 مگابایت می‌باشد.',
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
        try {
            // ذخیره فایل‌های موقت قبل از رفتن به مرحله بعد
            $this->saveTemporaryDocuments();
            
            $this->validateCurrentStep();
            
            if ($this->currentStep < $this->totalSteps) {
                $this->currentStep++;
            }
        } catch (\Exception $e) {
            // Use session flash message for error display
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * ذخیره فایل‌های موقت قبل از رفتن به مرحله بعد
     */
    private function saveTemporaryDocuments()
    {
        foreach ($this->specialDiseaseDocuments as $index => $file) {
            if ($file && !isset($this->uploadedDocuments[$index])) {
                try {
                    // Check if file is valid
                    if (!$file->isValid()) {
                        Log::warning('Invalid file uploaded', [
                            'member_index' => $index,
                            'original_name' => $file->getClientOriginalName(),
                        ]);
                        continue;
                    }

                    // Store the file temporarily and keep track of it
                    $path = $file->store('temp/special-disease-docs');
                    
                    // Verify the file was stored successfully
                    if (!Storage::exists($path)) {
                        Log::error('File was not stored successfully', [
                            'member_index' => $index,
                            'path' => $path,
                        ]);
                        continue;
                    }

                    $this->uploadedDocuments[$index] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ];
                    
                    // Log for debugging
                    Log::info('Temporary document saved', [
                        'member_index' => $index,
                        'original_name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Error saving temporary document', [
                        'error' => $e->getMessage(),
                        'member_index' => $index,
                        'file_info' => $file ? [
                            'original_name' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'mime_type' => $file->getMimeType(),
                        ] : 'null',
                    ]);
                }
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
                        // بررسی شماره موبایل (اختیاری - اگر خالی باشد مقدار پیش‌فرض ثبت می‌شود)
                        if (!empty($member['mobile']) && !preg_match('/^09[0-9]{9}$/', $member['mobile'])) {
                            throw new \Exception("شماره موبایل سرپرست خانوار باید با ۰۹ شروع شود و ۱۱ رقم باشد");
                        }

                        // بررسی شماره تماس ثابت (اختیاری)
                        if (!empty($member['phone']) && !preg_match('/^0[0-9]{10}$/', $member['phone'])) {
                            throw new \Exception("شماره تماس ثابت باید با ۰ شروع شود و ۱۱ رقم باشد");
                        }

                                            // بررسی شماره شبا (اختیاری - اگر خالی باشد مقدار پیش‌فرض ثبت می‌شود)
                    if (!empty($member['sheba']) && !preg_match('/^IR[0-9]{24}$/', $member['sheba'])) {
                        throw new \Exception("شماره شبا باید با IR شروع شود و ۲۶ کاراکتر باشد");
                    }

                    // بررسی مدرک بیماری خاص
                    if (isset($member['problem_type']) && is_array($member['problem_type']) && in_array('بیماری خاص', $member['problem_type'])) {
                        // بررسی اینکه آیا فایل آپلود شده است یا نه
                        $hasUploadedDocument = isset($this->uploadedDocuments[$index]) && !empty($this->uploadedDocuments[$index]);
                        $hasNewDocument = isset($this->specialDiseaseDocuments[$index]) && $this->specialDiseaseDocuments[$index];
                        
                        if (!$hasUploadedDocument && !$hasNewDocument) {
                            throw new \Exception("برای عضو خانواده شماره " . ($index + 1) . " که بیماری خاص دارد، مدرک پزشکی الزامی است");
                        }
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
            // Use session flash message for error display
            session()->flash('error', 'لطفاً ابتدا مرحله قبل را تکمیل کنید');
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

        session()->flash('success', 'عضو جدید اضافه شد');
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

        // اعتبارسنجی مقادیر مجاز برای نسبت
        $allowedRelationships = ['mother', 'father', 'son', 'daughter', 'grandmother', 'grandfather', 'other'];
        if (!in_array($member['relationship'], $allowedRelationships)) {
            $this->dispatch('show-message', 'error', 'نسبت انتخاب شده معتبر نیست');
            return false;
        }

        return true;
    }

    public function render()
    {
        // آپلود فایل‌های انتخاب شده در مرحله 3
        if ($this->currentStep == 3) {
            $this->saveTemporaryDocuments();
        }
        
        // فقط پاس دادن مقادیر، بدون مقداردهی مجدد
        if (isset($this->head_member_index) && isset($this->members[$this->head_member_index])) {
            $this->head = $this->members[$this->head_member_index];
        }
        
        return view('livewire.charity.family-wizard', [
            'cities' => $this->cities,
            'districts' => $this->districts,
            'uploadedDocuments' => $this->uploadedDocuments,
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

    /**
     * Save uploaded special disease documents to permanent storage
     */
    private function saveUploadedDocuments()
    {
        foreach ($this->uploadedDocuments as $memberIndex => $document) {
            try {
                // Get the member data
                $member = $this->members[$memberIndex] ?? null;
                if (!$member) continue;

                // Check if the file exists and has content
                if (!Storage::exists($document['path'])) {
                    Log::warning('Temporary file not found', [
                        'path' => $document['path'],
                        'member_index' => $memberIndex,
                    ]);
                    continue;
                }

                $fileContent = Storage::get($document['path']);
                if ($fileContent === null || empty($fileContent)) {
                    Log::warning('Temporary file is empty or null', [
                        'path' => $document['path'],
                        'member_index' => $memberIndex,
                    ]);
                    continue;
                }

                // Create a temporary file for MediaLibrary
                $tempPath = storage_path('app/temp/' . uniqid() . '_' . $document['original_name']);
                file_put_contents($tempPath, $fileContent);

                // Store file path for later use in member creation
                $this->members[$memberIndex]['special_disease_document_path'] = $tempPath;
                $this->members[$memberIndex]['special_disease_document_name'] = $document['original_name'];

                // Delete the temp file from Livewire storage
                Storage::delete($document['path']);

                Log::info('Special disease document prepared for MediaLibrary', [
                    'member_index' => $memberIndex,
                    'original_path' => $document['path'],
                    'temp_path' => $tempPath,
                    'original_name' => $document['original_name'],
                ]);

            } catch (\Exception $e) {
                Log::error('Error preparing special disease document for MediaLibrary', [
                    'error' => $e->getMessage(),
                    'member_index' => $memberIndex,
                    'document' => $document,
                ]);

                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'خطا در آماده‌سازی مدرک بیماری خاص. لطفاً دوباره تلاش کنید.'
                ]);
            }
        }
    }

    /**
     * Update member's incomplete data status after document upload
     */
    private function updateMemberIncompleteStatus($memberIndex)
    {
        if (!isset($this->members[$memberIndex])) return;

        // Check if member has special disease in their problems
        $hasSpecialDisease = in_array('بیماری خاص', $this->members[$memberIndex]['problem_type'] ?? []);

        if ($hasSpecialDisease && !empty($this->members[$memberIndex]['special_disease_document'])) {
            // Remove 'special_disease_document' from incomplete data if it exists
            $this->members[$memberIndex]['incomplete_data'] = array_filter(
                $this->members[$memberIndex]['incomplete_data'] ?? [],
                fn($item) => $item !== 'special_disease_document'
            );
        }
    }

    public function submit()
    {
        // Add JavaScript console log for debugging
        $this->dispatch('console-log', 'Submit method called from PHP');
        
        Log::debug('Submit method started', ['step' => 'start']);

        // Validate all steps before submission
        try {
            $this->validateCurrentStep();
            Log::debug('Validation passed', ['step' => 'validation']);
        } catch (\Exception $e) {
            Log::error('Validation failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        if (!$this->validateNationalCodes()) {
            Log::error('National code validation failed');
            return;
        }
        Log::debug('National code validation passed');

        try {
            // بررسی یکتایی کد ملی قبل از ایجاد خانواده
            Log::debug('Validating national codes uniqueness');
            $this->validateNationalCodes();
            Log::debug('National codes are unique');

            // ایجاد خانواده جدید
            Log::debug('Creating family record', [
                'family_code' => $this->family_code,
                'province_id' => $this->province_id,
                'city_id' => $this->city_id
            ]);
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

            Log::debug('Family created successfully', ['family_id' => $family->id]);

            // ذخیره مدارک آپلود شده
            Log::debug('Starting to save uploaded documents', [
                'uploaded_documents_count' => count($this->uploadedDocuments),
                'special_disease_documents_count' => count(array_filter($this->specialDiseaseDocuments)),
            ]);
            
            $this->saveUploadedDocuments();

            // ذخیره اعضای خانواده
            foreach ($this->members as $index => $member) {
                $nationalCode = $member['national_code'];
                $birthDate = null;
                if (!empty($member['birth_date'])) {
                    $birthDate = $member['birth_date'];
                }

                // تنظیم مقادیر پیش‌فرض برای mobile و sheba
                $mobile = $this->setDefaultMobile($member['mobile'] ?? null);
                $sheba = $this->setDefaultSheba($member['sheba'] ?? null);

                $createdMember = $family->members()->create([
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
                    'mobile' => $mobile,
                    'phone' => $member['phone'] ?? null,
                    'sheba' => $sheba,
                ]);

                // آپلود مدرک بیماری خاص اگر وجود دارد
                if (isset($member['special_disease_document_path']) && !empty($member['special_disease_document_path'])) {
                    try {
                        $filePath = $member['special_disease_document_path'];
                        if (file_exists($filePath)) {
                            $fileName = $member['special_disease_document_name'];
                            
                            $createdMember->addMedia($filePath)
                                ->usingName($createdMember->full_name . ' - مدرک بیماری خاص')
                                ->usingFileName($fileName)
                                ->toMediaCollection('special_disease_documents');

                            // حذف فایل موقت
                            unlink($filePath);

                            Log::info('Special disease document uploaded to MediaLibrary', [
                                'member_id' => $createdMember->id,
                                'file_path' => $filePath,
                                'file_name' => $fileName,
                            ]);
                        } else {
                            Log::warning('Special disease document file not found', [
                                'member_id' => $createdMember->id,
                                'file_path' => $filePath,
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Error uploading special disease document to media library', [
                            'error' => $e->getMessage(),
                            'member_id' => $createdMember->id,
                            'file_path' => $member['special_disease_document_path'] ?? null
                        ]);
                    }
                }
            }

            // Store family ID for use in the view
            $this->family_id = $family->id;

            // Dispatch success notification
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'خانواده با موفقیت ثبت شد.'
            ]);
            
            // Redirect to families page using Livewire redirect
            $this->redirect(route('charity.uninsured-families', ['highlight' => $family->id]));
        } catch (\Illuminate\Validation\ValidationException $e) {
            // نمایش خطاهای validation بدون پاک کردن داده‌ها
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'لطفاً خطاهای موجود را برطرف کنید.'
            ]);

            // بازگشت به مرحله 2 اگر خطای کد ملی وجود دارد
            $errors = $e->errors();
            foreach ($errors as $field => $messages) {
                if (str_contains($field, 'national_code')) {
                    $this->currentStep = 2;
                    break;
                }
            }

            // پرتاب مجدد exception برای نمایش خطاها
            throw $e;
        } catch (\Exception $e) {
            $msg = $e->getMessage() ?: 'خطای ناشناخته‌ای رخ داده است. لطفاً مجدداً تلاش کنید.';
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $msg
            ]);
        }
    }




    /**
     * بررسی یکتایی کدهای ملی
     */
    private function validateNationalCodes()
    {
        $errors = [];
        $nationalCodes = [];

        Log::debug('Starting national code validation', [
            'members_count' => count($this->members),
            'member_national_codes' => array_map(fn($m) => $m['national_code'] ?? null, $this->members)
        ]);

        // جمع‌آوری کدهای ملی و بررسی تکراری در لیست
        foreach ($this->members as $index => $member) {
            $nationalCode = $member['national_code'] ?? null;
            Log::debug('Validating member national code', [
                'member_index' => $index,
                'national_code' => $nationalCode,
                'member_name' => ($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')
            ]);

            if (empty($nationalCode)) {
                $errors["members.{$index}.national_code"] = "کد ملی الزامی است.";
                Log::error('Empty national code found', ['member_index' => $index]);
                continue;
            }

            // بررسی تکراری در لیست فعلی
            if (in_array($nationalCode, $nationalCodes)) {
                $errorMsg = "کد ملی {$nationalCode} در لیست اعضا تکراری است.";
                $errors["members.{$index}.national_code"] = $errorMsg;
                Log::error('Duplicate national code in current submission', [
                    'national_code' => $nationalCode,
                    'member_index' => $index
                ]);
            } else {
                $nationalCodes[] = $nationalCode;
            }

            // بررسی وجود در دیتابیس
            $existingMember = \App\Models\Member::where('national_code', $nationalCode)->first();
            if ($existingMember) {
                $errorMsg = "کد ملی {$nationalCode} قبلاً در سیستم ثبت شده است.";
                $errors["members.{$index}.national_code"] = $errorMsg;

                Log::warning('Attempt to register duplicate national code', [
                    'national_code' => $nationalCode,
                    'existing_member_id' => $existingMember->id,
                    'member_name' => $existingMember->full_name,
                    'existing_family_id' => $existingMember->family_id,
                    'user_id' => Auth::id(),
                    'timestamp' => now()->toDateTimeString()
                ]);
            }
        }

        if (!empty($errors)) {
            Log::error('National code validation failed', [
                'errors' => $errors,
                'members_count' => count($this->members),
                'unique_national_codes' => $nationalCodes
            ]);
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }

        Log::debug('National code validation passed successfully', [
            'unique_national_codes' => $nationalCodes
        ]);
        return true;
    }

    /**
     * بررسی کد ملی در زمان واقعی
     */
    public function updatedMembers($value, $key)
    {
        // اگر کد ملی تغییر کرده باشد
        if (str_contains($key, '.national_code')) {
            $parts = explode('.', $key);
            $memberIndex = $parts[0];
            $nationalCode = $value;

            if ($nationalCode) {
                // پاک کردن خطای قبلی
                $this->resetErrorBag("members.{$memberIndex}.national_code");

                // بررسی وجود در دیتابیس
                $existingMember = \App\Models\Member::where('national_code', $nationalCode)->first();
                if ($existingMember) {
                    $this->addError("members.{$memberIndex}.national_code", "این کد ملی قبلاً در سیستم ثبت شده است.");
                }

                // بررسی تکراری در لیست فعلی
                $duplicateCount = 0;
                foreach ($this->members as $index => $member) {
                    if (($member['national_code'] ?? null) === $nationalCode) {
                        $duplicateCount++;
                    }
                }

                if ($duplicateCount > 1) {
                    $this->addError("members.{$memberIndex}.national_code", "این کد ملی در لیست اعضا تکراری است.");
                }
            } else {
                // اگر کد ملی خالی شد، خطا را پاک کن
                $this->resetErrorBag("members.{$memberIndex}.national_code");
            }
        }

        // منطق قبلی برای acceptance_criteria
        $allProblems = [];
        foreach ($this->members as $member) {
            if (!empty($member['problem_type'])) {
                if (is_array($member['problem_type'])) {
                    // تبدیل مقادیر انگلیسی به فارسی
                    $persianProblems = array_map(function($problem) {
                        return ProblemTypeHelper::englishToPersian($problem);
                    }, $member['problem_type']);
                    $allProblems = array_merge($allProblems, $persianProblems);
                } else {
                    // تبدیل مقدار انگلیسی به فارسی
                    $allProblems[] = ProblemTypeHelper::englishToPersian($member['problem_type']);
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
            'members.*.national_code.unique' => 'این کد ملی قبلاً در سیستم ثبت شده است.',
            'members.*.national_code.distinct' => 'کد ملی نمی‌تواند تکراری باشد.',
        ];
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


    /**
     * پاک کردن خطای کد ملی خاص
     */
    public function clearNationalCodeError($memberIndex)
    {
        $this->resetErrorBag("members.{$memberIndex}.national_code");
    }

    /**
     * بررسی و پاک کردن خطاهای کد ملی هنگام تغییر
     */
    public function checkNationalCodeOnChange($memberIndex, $nationalCode)
    {
        if (empty($nationalCode)) {
            $this->resetErrorBag("members.{$memberIndex}.national_code");
            return;
        }

        // بررسی وجود در دیتابیس
        $existingMember = \App\Models\Member::where('national_code', $nationalCode)->first();
        if ($existingMember) {
            $this->addError("members.{$memberIndex}.national_code", "این کد ملی قبلاً در سیستم ثبت شده است.");
            return;
        }

        // بررسی تکراری در لیست فعلی
        $duplicateCount = 0;
        foreach ($this->members as $index => $member) {
            if (($member['national_code'] ?? null) === $nationalCode) {
                $duplicateCount++;
            }
        }

        if ($duplicateCount > 1) {
            $this->addError("members.{$memberIndex}.national_code", "این کد ملی در لیست اعضا تکراری است.");
        } else {
            $this->resetErrorBag("members.{$memberIndex}.national_code");
        }
    }

    /**
     * Handle file upload for special disease documents
     */
    public function updatedSpecialDiseaseDocuments($value, $key)
    {
        // Extract member index from the key (e.g., 'specialDiseaseDocuments.1' => 1)
        $memberIndex = explode('.', $key)[1] ?? null;
        if ($memberIndex === null) return;

        // Validate the uploaded file
        $this->validateOnly("specialDiseaseDocuments.{$memberIndex}");

        // Get the file and upload it
        $file = $this->specialDiseaseDocuments[$memberIndex] ?? null;
        if ($file) {
            try {
                // Store the file temporarily and keep track of it
                $path = $file->store('temp/special-disease-docs');
                $this->uploadedDocuments[$memberIndex] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];

                // Show success message
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'مدرک بیماری خاص با موفقیت آپلود شد.'
                ]);

                // Clear any validation errors for this field
                $this->resetErrorBag("specialDiseaseDocuments.{$memberIndex}");

            } catch (\Exception $e) {
                Log::error('Error uploading special disease document', [
                    'error' => $e->getMessage(),
                    'member_index' => $memberIndex,
                ]);

                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'خطا در آپلود مدرک. لطفاً دوباره تلاش کنید.'
                ]);
            }
        } else {
            // اگر فایل حذف شده، از uploadedDocuments هم حذف کنیم
            if (isset($this->uploadedDocuments[$memberIndex])) {
                try {
                    Storage::delete($this->uploadedDocuments[$memberIndex]['path']);
                    unset($this->uploadedDocuments[$memberIndex]);
                } catch (\Exception $e) {
                    Log::error('Error removing uploaded document', [
                        'error' => $e->getMessage(),
                        'member_index' => $memberIndex,
                    ]);
                }
            }
        }
    }

    /**
     * Remove an uploaded document
     */
    public function removeDocument($memberIndex)
    {
        try {
            // Remove the file from storage if it exists
            if (isset($this->uploadedDocuments[$memberIndex])) {
                Storage::delete($this->uploadedDocuments[$memberIndex]['path']);
                unset($this->uploadedDocuments[$memberIndex]);
                $this->specialDiseaseDocuments[$memberIndex] = null;

                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => 'مدرک با موفقیت حذف شد.'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error removing document', [
                'error' => $e->getMessage(),
                'member_index' => $memberIndex,
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'خطا در حذف مدرک. لطفاً دوباره تلاش کنید.'
            ]);
        }
    }

    /**
     * Set default value for mobile number if empty
     *
     * @param string|null $mobile
     * @return string
     */
    protected function setDefaultMobile($mobile)
    {
        return !empty($mobile) ? $mobile : 'بدون شماره';
    }

    /**
     * Set default value for Sheba number if empty
     *
     * @param string|null $sheba
     * @return string
     */
    protected function setDefaultSheba($sheba)
    {
        return !empty($sheba) ? $sheba : 'بدون شماره شبا';
    }
}
