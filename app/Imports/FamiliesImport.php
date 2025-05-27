<?php

namespace App\Imports;

use App\Models\Family;
use App\Models\Member;
use App\Models\User;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Services\FamilyService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FamiliesImport implements ToCollection, WithHeadingRow
{
    protected User $user;
    protected int $districtId;
    protected array $results = [
        'success' => 0,
        'failed' => 0,
        'families_created' => 0,
        'members_added' => 0,
        'errors' => [],
    ];

    /**
     * نقشه فیلدهای فارسی و انگلیسی به انگلیسی
     * تفکیک خانواده‌ها بر اساس: استان + شهر + دهستان + آدرس
     */
    protected array $fieldMapping = [
        // اطلاعات آدرس - فارسی
        'استان' => 'province_name',
        'شهر' => 'city_name',
        'دهستان' => 'district_name', 
        'آدرس' => 'address',
        
        // اطلاعات آدرس - انگلیسی (از فایل کاربر)
        'astan' => 'province_name',
        'shhr' => 'city_name',
        'dhstan' => 'district_name',
        'adrs' => 'address',
        
        // اطلاعات عضو - فارسی  
        'نام' => 'first_name',
        'نام خانوادگی' => 'last_name', 
        'کد ملی' => 'national_code',
        'تاریخ تولد' => 'birth_date',
        'جنسیت' => 'gender',
        'نسبت خانوادگی' => 'relationship',
        'وضعیت تأهل' => 'marital_status',
        'شغل' => 'occupation',
        'موبایل' => 'phone',
        
        // اطلاعات عضو - انگلیسی (از فایل کاربر)
        'nam' => 'first_name',
        'nam_khanoadgy' => 'last_name',
        'kd_mly' => 'national_code', 
        'tarykh_told' => 'birth_date',
        'gnsyt' => 'gender',
        'nsbt_khanoadgy' => 'relationship',
        'odaayt_tahl' => 'marital_status',
        'shghl' => 'occupation',
        'mobayl' => 'phone',
    ];

    /**
     * نقشه مقادیر فارسی به انگلیسی
     * فقط فیلدهایی که در FamilyWizard استفاده میشن
     */
    protected array $valueMapping = [
        'gender' => [
            'مرد' => 'male',
            'زن' => 'female',
        ],
        'marital_status' => [
            'مجرد' => 'single',
            'متأهل' => 'married',
            'مطلقه' => 'divorced',
            'بیوه' => 'widowed',
        ],
        'relationship' => [
            'سرپرست' => 'head',
            'همسر' => 'spouse',
            'فرزند' => 'child',
            'والدین' => 'parent',
            'برادر' => 'brother',
            'خواهر' => 'sister',
            'سایر' => 'other',
        ],
    ];

    public function __construct(User $user, int $districtId)
    {
        $this->user = $user;
        $this->districtId = $districtId;
    }

    /**
     * پردازش فایل اکسل
     */
    public function collection(Collection $rows)
    {
        // حذف transaction اصلی چون برای هر خانواده transaction جداگانه داریم
        try {
            $familyService = app(FamilyService::class);
            $groupedMembers = [];
            
            // مرحله 1: پردازش و گروه‌بندی اعضا بر اساس آدرس
            foreach ($rows as $index => $row) {
                // رد کردن ردیف‌های راهنمایی
                if ($this->isGuideRow($row->toArray())) {
                    continue;
                }
                
                // Debug: نمایش raw data قبل از mapping
                Log::debug("Row " . ($index + 2) . " raw data:", $row->toArray());
                
                $rowData = $this->mapRow($row->toArray());
                
                // Debug: نمایش تمام فیلدهای mapped شده
                Log::debug("Row " . ($index + 2) . " mapped data:", $rowData);
                
                // بررسی وجود اطلاعات ضروری
                $firstName = trim($rowData['first_name'] ?? '');
                $nationalCode = trim($rowData['national_code'] ?? '');
                
                if (empty($firstName) || empty($nationalCode)) {
                    $this->results['failed']++;
                    $this->results['errors'][] = "ردیف " . ($index + 2) . ": نام یا کد ملی خالی است - نام: '{$firstName}' (طول: " . strlen($firstName) . "), کد ملی: '{$nationalCode}' (طول: " . strlen($nationalCode) . ")";
                    continue;
                }
                
                // ایجاد کلید منحصر به فرد برای آدرس
                $addressKey = $this->generateAddressKey($rowData);
                
                if (!isset($groupedMembers[$addressKey])) {
                    // پاک‌سازی و validation داده‌های خانواده
                    $address = $this->sanitizeAddress($rowData['address'] ?? '');
                    
                    $groupedMembers[$addressKey] = [
                        'family_data' => [
                            'province_name' => $rowData['province_name'] ?? '',
                            'city_name' => $rowData['city_name'] ?? '',
                            'district_name' => $rowData['district_name'] ?? '',
                            'address' => $address,
                        ],
                        'members' => []
                    ];
                }
                
                // آماده‌سازی داده عضو
                $relationship = $this->mapValue($rowData['relationship'] ?? 'other', 'relationship');
                
                $memberData = [
                    'first_name' => $rowData['first_name'],
                    'last_name' => $rowData['last_name'] ?? '',
                    'national_code' => $rowData['national_code'],
                    'birth_date' => $this->parseDate($rowData['birth_date'] ?? ''),
                    'gender' => $this->mapValue($rowData['gender'] ?? '', 'gender'),
                    'relationship' => $relationship,
                    'marital_status' => $this->mapValue($rowData['marital_status'] ?? '', 'marital_status'),
                    'occupation' => $rowData['occupation'] ?? '',
                    'phone' => $rowData['phone'] ?? '',
                    'is_head' => ($relationship === 'head'),
                ];
                
                $groupedMembers[$addressKey]['members'][] = $memberData;
            }
            
            // مرحله 2: ایجاد خانواده‌ها و اعضا
            foreach ($groupedMembers as $addressKey => $familyGroup) {
                // شروع transaction جداگانه برای هر خانواده
                DB::beginTransaction();
                
                try {
                    $familyData = $familyGroup['family_data'];
                    $members = $familyGroup['members'];
                    
                    if (empty($members)) {
                        DB::rollback();
                        continue;
                    }
                    
                    // پیش‌بررسی: چک کردن اعضای تکراری و validation کلی
                    $preValidation = $this->preValidateFamily($members);
                    if (!empty($preValidation['errors'])) {
                        DB::rollback();
                        $this->results['failed']++;
                        // اضافه کردن همه خطاهای این خانواده
                        foreach ($preValidation['errors'] as $error) {
                            $this->results['errors'][] = $error;
                        }
                        continue;
                    }
                    
                    // یافتن یا ایجاد استان، شهر و دهستان
                    $provinceId = $this->findOrCreateProvince($familyData['province_name']);
                    $cityId = $this->findOrCreateCity($familyData['city_name'], $provinceId);
                    $districtId = $this->findOrCreateDistrict($familyData['district_name'], $cityId);
                    
                    // ایجاد خانواده
                    $family = $familyService->registerFamily([
                        'family_code' => $this->generateUniqueFamilyCode(),
                        'province_id' => $provinceId,
                        'city_id' => $cityId,
                        'district_id' => $districtId,
                        'address' => $familyData['address'],
                    ], $this->user);
                    
                    // شمارش موقت اعضای موفق
                    $tempMembersAdded = 0;
                    $tempSuccess = 0;
                    
                    // اضافه کردن فقط اعضای معتبر
                    foreach ($members as $memberData) {
                        $firstName = trim($memberData['first_name'] ?? '');
                        $nationalCode = trim($memberData['national_code'] ?? '');
                        
                        // فقط اعضای معتبر را اضافه کن
                        if (!empty($firstName) && !empty($nationalCode) && strlen($nationalCode) <= 10) {
                            try {
                                $familyService->addMember($family, $memberData);
                                $tempMembersAdded++;
                                $tempSuccess++;
                            } catch (\Exception $memberException) {
                                // اگر اضافه کردن این عضو خاص مشکل داشت، آن را نادیده بگیر و ادامه بده
                                Log::warning('Error adding individual member', [
                                    'member_data' => $memberData,
                                    'error' => $memberException->getMessage()
                                ]);
                                
                                // اگر مشکل duplicate بود، خطا را به لیست اضافه کن
                                if (str_contains($memberException->getMessage(), 'Duplicate entry')) {
                                    $memberName = trim($firstName . ' ' . ($memberData['last_name'] ?? ''));
                                    $this->results['errors'][] = "⚠️ {$memberName} (کد ملی: {$nationalCode}) رد شد: قبلاً ثبت شده";
                                }
                            }
                        }
                    }
                    
                    // بررسی اینکه آیا حداقل یک عضو اضافه شده یا خیر
                    if ($tempMembersAdded === 0) {
                        // اگر هیچ عضوی اضافه نشد، خانواده را حذف کن
                        throw new \Exception("هیچ عضو معتبری برای این خانواده یافت نشد");
                    }
                    
                    // اگر همه چیز موفق بود، commit کن و شمارش را به‌روزرسانی کن
                    DB::commit();
                    
                    $this->results['families_created']++;
                    $this->results['members_added'] += $tempMembersAdded;
                    $this->results['success'] += $tempSuccess;
                    
                } catch (\Exception $e) {
                    // Rollback در صورت هر نوع خطا
                    DB::rollback();
                    
                    $this->results['failed']++;
                    $errorMessage = $this->translateDatabaseError($e->getMessage(), $familyGroup['members']);
                    $this->results['errors'][] = $errorMessage;
                    Log::error('Family creation error', [
                        'address_key' => $addressKey,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'members_count' => count($familyGroup['members']),
                        'family_data' => $familyData
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            // خطا در سطح کلی فایل
            $this->results['failed']++;
            $this->results['errors'][] = "خطا کلی در پردازش فایل: " . $e->getMessage();
            Log::error('Families Import Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * ایجاد کلید منحصر به فرد برای آدرس
     * بر اساس ترکیب: استان + شهر + دهستان + آدرس
     */
    protected function generateAddressKey(array $data): string
    {
        $province = trim($data['province_name'] ?? '');
        $city = trim($data['city_name'] ?? '');
        $district = trim($data['district_name'] ?? '');
        $address = trim($data['address'] ?? '');
        
        return md5(strtolower("$province|$city|$district|$address"));
    }

    /**
     * یافتن یا ایجاد استان
     */
    protected function findOrCreateProvince(string $name): int
    {
        if (empty($name)) {
            return 1; // استان پیشفرض
        }
        
        $province = Province::where('name', 'LIKE', "%{$name}%")->first();
        
        if (!$province) {
            $province = Province::create([
                'name' => $name,
            ]);
        }
        
        return $province->id;
    }

    /**
     * یافتن یا ایجاد شهر
     */
    protected function findOrCreateCity(string $name, int $provinceId): int
    {
        if (empty($name)) {
            return 1; // شهر پیشفرض
        }
        
        $city = City::where('name', 'LIKE', "%{$name}%")
                   ->where('province_id', $provinceId)
                   ->first();
        
        if (!$city) {
            $city = City::create([
                'name' => $name,
                'province_id' => $provinceId
            ]);
        }
        
        return $city->id;
    }

    /**
     * یافتن یا ایجاد دهستان
     */
    protected function findOrCreateDistrict(string $name, int $cityId): int
    {
        if (empty($name)) {
            return $this->districtId; // دهستان انتخاب شده توسط کاربر
        }
        
        $district = District::where('name', 'LIKE', "%{$name}%")
                           ->where('city_id', $cityId)
                           ->first();
        
        if (!$district) {
            $district = District::create([
                'name' => $name,
                'city_id' => $cityId
            ]);
        }
        
        return $district->id;
    }

    /**
     * تبدیل ردیف اکسل به فیلدهای قابل استفاده
     */
    protected function mapRow(array $row): array
    {
        $mapped = [];
        
        foreach ($row as $key => $value) {
            // حذف فضاهای اضافی و کاراکترهای غیرقابل رؤیت
            $normalizedKey = trim(preg_replace('/\s+/', ' ', $key));
            $normalizedValue = trim($value ?? '');
            
            if (isset($this->fieldMapping[$normalizedKey])) {
                $mapped[$this->fieldMapping[$normalizedKey]] = $normalizedValue;
            } else {
                // Log unmapped fields for debugging
                Log::debug("Unmapped field found: '{$normalizedKey}' (original: '{$key}')");
            }
        }
        
        return $mapped;
    }

    /**
     * تبدیل مقادیر فارسی به انگلیسی
     */
    protected function mapValue(string $value, string $type)
    {
        $normalizedValue = trim($value);
        
        if (isset($this->valueMapping[$type][$normalizedValue])) {
            return $this->valueMapping[$type][$normalizedValue];
        }
        
        return $normalizedValue;
    }

    /**
     * تبدیل تاریخ فارسی به فرمت مناسب
     */
    protected function parseDate(string $date): ?string
    {
        if (empty($date)) {
            return null;
        }
        
        try {
            $parts = explode('/', $date);
            if (count($parts) === 3) {
                return sprintf('%04d-%02d-%02d', $parts[0], $parts[1], $parts[2]);
            }
        } catch (\Exception $e) {
            // در صورت خطا null برگردان
        }
        
        return null;
    }

    /**
     * بررسی اینکه آیا ردیف راهنما است یا خیر
     */
    protected function isGuideRow(array $row): bool
    {
        $firstCell = reset($row);
        $firstCellStr = (string) $firstCell;
        
        // بررسی patterns مختلف برای ردیف‌های راهنما
        $guidePatterns = [
            '---',
            'راهنما',
            'مثال:',
            'کد پستی',
            'آدرس کامل',
            'نام و نام خانوادگی',
            'وارد کنید',
            '10 رقمی',
            'مالک یا مستاجر',
            'توضیحات اضافی'
        ];
        
        foreach ($guidePatterns as $pattern) {
            if (strpos($firstCellStr, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * پاک‌سازی آدرس
     */
    protected function sanitizeAddress(string $address): string
    {
        $cleaned = trim($address);
        
        // حذف راهنماها
        if (strpos($cleaned, 'آدرس کامل') !== false ||
            strpos($cleaned, 'وارد کنید') !== false) {
            return '';
        }
        
        return $cleaned;
    }

    /**
     * تولید کد منحصر به فرد خانواده
     */
    protected function generateUniqueFamilyCode(): string
    {
        $maxAttempts = 100;
        $attempt = 0;
        
        do {
            $attempt++;
            
            // تولید کد بر اساس تاریخ جاری + ID سازمان + شماره تصادفی
            $year = now()->format('Y');
            $month = str_pad(now()->format('m'), 2, '0', STR_PAD_LEFT);
            $day = str_pad(now()->format('d'), 2, '0', STR_PAD_LEFT);
            $charityId = str_pad($this->user->organization_id ?? 1, 3, '0', STR_PAD_LEFT);
            $randomSuffix = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            $code = $year . $month . $day . $charityId . $randomSuffix;
            
            // اگر بیش از حد تلاش کردیم، یک کد کاملاً تصادفی 15 رقمی تولید کنیم
            if ($attempt > $maxAttempts) {
                $code = str_pad(strval(random_int(100000000000000, 999999999999999)), 15, '0', STR_PAD_LEFT);
            }
            
        } while (Family::where('family_code', $code)->exists() && $attempt <= $maxAttempts + 10);
        
        return $code;
    }

    /**
     * پیش‌بررسی کامل خانواده قبل از ایجاد
     */
    protected function preValidateFamily(array $members): array
    {
        $errors = [];
        $validMembers = 0;
        
        foreach ($members as $index => $member) {
            $memberNumber = $index + 1;
            $firstName = trim($member['first_name'] ?? '');
            $nationalCode = trim($member['national_code'] ?? '');
            $memberName = trim($firstName . ' ' . ($member['last_name'] ?? ''));
            
            // بررسی فیلدهای ضروری
            if (empty($firstName)) {
                $errors[] = "❌ عضو {$memberNumber}: نام ضروری است";
                continue;
            }
            
            if (empty($nationalCode)) {
                $errors[] = "❌ {$memberName}: کد ملی ضروری است";
                continue;
            }
            
            // بررسی طول کد ملی
            if (strlen($nationalCode) > 10) {
                $errors[] = "❌ {$memberName}: کد ملی نباید بیشتر از 10 رقم باشد (فعلی: " . strlen($nationalCode) . " رقم)";
                continue;
            }
            
            // بررسی تکراری بودن در پایگاه داده
            $existingMember = Member::where('national_code', $nationalCode)->first();
            if ($existingMember) {
                $errors[] = "⚠️ {$memberName} (کد ملی: {$nationalCode}) قبلاً در سیستم ثبت شده است";
                continue;
            }
            
            // بررسی تکراری بودن در همین فایل (در همین خانواده)
            $duplicatesInFamily = array_filter($members, function($m) use ($nationalCode) {
                return trim($m['national_code'] ?? '') === $nationalCode;
            });
            
            if (count($duplicatesInFamily) > 1) {
                $errors[] = "⚠️ {$memberName} (کد ملی: {$nationalCode}) در همین خانواده تکراری است";
                continue;
            }
            
            $validMembers++;
        }
        
        // بررسی اینکه خانواده حداقل یک عضو معتبر دارد
        if ($validMembers === 0) {
            $errors[] = "❌ خانواده نامعتبر: هیچ عضو معتبری برای ایجاد خانواده وجود ندارد";
        }
        
        return [
            'valid_members' => $validMembers,
            'total_members' => count($members),
            'errors' => $errors
        ];
    }

    /**
     * بررسی وجود اعضای تکراری قبل از ایجاد خانواده (deprecated - استفاده از preValidateFamily)
     */
    protected function checkForDuplicateMembers(array $members): ?string
    {
        foreach ($members as $member) {
            $nationalCode = trim($member['national_code'] ?? '');
            
            if (empty($nationalCode)) {
                continue; // کدهای ملی خالی در validation اصلی چک می‌شوند
            }
            
            // بررسی وجود کد ملی در پایگاه داده
            $existingMember = Member::where('national_code', $nationalCode)->first();
            
            if ($existingMember) {
                $memberName = trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''));
                return "⚠️ کد ملی تکراری: {$memberName} (کد ملی: {$nationalCode}) قبلاً در سیستم ثبت شده است";
            }
        }
        
        return null; // هیچ تکراری پیدا نشد
    }

    /**
     * ترجمه خطاهای پایگاه داده به زبان قابل فهم
     */
    protected function translateDatabaseError(string $errorMessage, array $members = []): string
    {
        // خطای کد ملی تکراری
        if (str_contains($errorMessage, 'Duplicate entry') && str_contains($errorMessage, 'members_national_code_unique')) {
            preg_match('/Duplicate entry \'([^\']+)\'/', $errorMessage, $matches);
            $duplicateNationalCode = $matches[1] ?? 'نامشخص';
            
            // پیدا کردن نام صاحب کد ملی تکراری
            $memberName = 'نامشخص';
            foreach ($members as $member) {
                if ($member['national_code'] === $duplicateNationalCode) {
                    $memberName = trim($member['first_name'] . ' ' . $member['last_name']);
                    break;
                }
            }
            
            return "⚠️ کد ملی تکراری: {$memberName} (کد ملی: {$duplicateNationalCode}) قبلاً در سیستم ثبت شده است";
        }
        
        // خطای کد خانواده تکراری
        if (str_contains($errorMessage, 'Duplicate entry') && str_contains($errorMessage, 'families_family_code_unique')) {
            return "⚠️ کد خانواده تکراری: این خانواده قبلاً در سیستم ثبت شده است";
        }
        
        // خطای محدودیت کلید خارجی
        if (str_contains($errorMessage, 'foreign key constraint')) {
            if (str_contains($errorMessage, 'province_id')) {
                return "❌ خطا در اطلاعات استان: استان وارد شده معتبر نیست";
            }
            if (str_contains($errorMessage, 'city_id')) {
                return "❌ خطا در اطلاعات شهر: شهر وارد شده معتبر نیست";
            }
            if (str_contains($errorMessage, 'district_id')) {
                return "❌ خطا در اطلاعات منطقه: منطقه وارد شده معتبر نیست";
            }
            return "❌ خطا در ارتباط اطلاعات: یکی از فیلدهای وارد شده معتبر نیست";
        }
        
        // خطای فیلد خالی اجباری
        if (str_contains($errorMessage, 'cannot be null') || str_contains($errorMessage, 'not null')) {
            if (str_contains($errorMessage, 'first_name')) {
                return "❌ نام ضروری است: نام اعضای خانواده نباید خالی باشد";
            }
            if (str_contains($errorMessage, 'national_code')) {
                return "❌ کد ملی ضروری است: کد ملی اعضای خانواده نباید خالی باشد";
            }
            if (str_contains($errorMessage, 'family_code')) {
                return "❌ کد خانواده ضروری است: خطای داخلی در تولید کد خانواده";
            }
            return "❌ فیلد اجباری خالی است: یکی از فیلدهای ضروری خالی باقی مانده";
        }
        
        // خطای طول زیاد فیلد
        if (str_contains($errorMessage, 'Data too long for column')) {
            if (str_contains($errorMessage, 'national_code')) {
                return "❌ کد ملی طولانی: کد ملی نباید بیشتر از 10 رقم باشد";
            }
            if (str_contains($errorMessage, 'phone')) {
                return "❌ شماره تلفن طولانی: شماره تلفن نباید بیشتر از 15 رقم باشد";
            }
            if (str_contains($errorMessage, 'address')) {
                return "❌ آدرس طولانی: آدرس نباید بیشتر از 500 کاراکتر باشد";
            }
            return "❌ داده طولانی: یکی از فیلدها بیش از حد مجاز طولانی است";
        }
        
        // خطای مقدار غیرمعتبر enum
        if (str_contains($errorMessage, 'incorrect enum value') || str_contains($errorMessage, 'invalid enum')) {
            if (str_contains($errorMessage, 'gender')) {
                return "❌ جنسیت نامعتبر: مقادیر معتبر عبارتند از: مرد، زن";
            }
            if (str_contains($errorMessage, 'marital_status')) {
                return "❌ وضعیت تأهل نامعتبر: مقادیر معتبر عبارتند از: مجرد، متأهل، مطلقه، بیوه";
            }
            if (str_contains($errorMessage, 'relationship')) {
                return "❌ نسبت خانوادگی نامعتبر: مقادیر معتبر عبارتند از: سرپرست، همسر، فرزند، والدین، برادر، خواهر، سایر";
            }
            return "❌ مقدار نامعتبر: یکی از مقادیر وارد شده معتبر نیست";
        }
        
        // خطای عمومی connection
        if (str_contains($errorMessage, 'connection') || str_contains($errorMessage, 'timeout')) {
            return "🔌 مشکل ارتباط با پایگاه داده: لطفاً مجدداً تلاش کنید";
        }
        
        // خطای table موجود نبودن
        if (str_contains($errorMessage, 'Base table or view not found') || str_contains($errorMessage, "doesn't exist")) {
            if (str_contains($errorMessage, 'family_members')) {
                return "❌ خطای پیکربندی: Table اعضای خانواده یافت نشد. لطفاً با پشتیبانی تماس بگیرید.";
            }
            if (str_contains($errorMessage, 'families')) {
                return "❌ خطای پیکربندی: Table خانواده‌ها یافت نشد. لطفاً با پشتیبانی تماس بگیرید.";
            }
            return "❌ خطای پیکربندی پایگاه داده: یکی از table های ضروری یافت نشد. لطفاً با پشتیبانی تماس بگیرید.";
        }
        
        // خطاهای عمومی دیگر - خلاصه شده
        return "❌ خطا در ثبت اطلاعات: " . (strlen($errorMessage) > 100 ? 
            substr($errorMessage, 0, 100) . '...' : 
            $errorMessage);
    }

    /**
     * دریافت نتایج پردازش
     */
    public function getResults(): array
    {
        return $this->results;
    }
} 