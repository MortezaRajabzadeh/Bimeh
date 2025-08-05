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
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class FamiliesImport implements ToCollection
{
    protected User $user;
    protected int $districtId;
    protected array $results = [
        'success' => 0,
        'failed' => 0,
        'families_created' => 0,
        'families_updated' => 0,
        'members_added' => 0,
        'members_updated' => 0,
        'errors' => [],
        'error_summary' => [],
        'sample_errors' => [],
        'validation_errors' => [],
        'database_errors' => [],
        'total_errors' => 0,
        'error_types' => [],
        'max_display_errors' => 20,
        'showing_count' => 0,
    ];

    /**
     * نقشه مقادیر فارسی به انگلیسی
     */
    protected array $valueMapping = [
        'gender' => [
            'مرد' => 'male',
            'زن' => 'female',
            'male' => 'male',
            'female' => 'female',
            'مذکر' => 'male',
            'مونث' => 'female',
        ],
        'marital_status' => [
            'مجرد' => 'single',
            'متاهل' => 'married',
            'single' => 'single',
            'married' => 'married',
        ],
        'boolean' => [
            'بلی' => true,
            'خیر' => false,
            'بله' => true,
            'نه' => false,
            'Yes' => true,
            'No' => false,
            'yes' => true,
            'no' => false,
            'دارد' => true,
            'ندارد' => false,
            '1' => true,
            '0' => false,
            1 => true,
            0 => false,
            true => true,
            false => false,
            'TRUE' => true,
            'FALSE' => false,
        ],
        'relationship' => [
            'مادر' => 'mother',
            'پدر' => 'father',
            'پسر' => 'son',
            'دختر' => 'daughter',
            'مادربزرگ' => 'grandmother',
            'پدربزرگ' => 'grandfather',
            'سایر' => 'other',
            'mother' => 'mother',
            'father' => 'father',
            'son' => 'son',
            'daughter' => 'daughter',
            'grandmother' => 'grandmother',
            'grandfather' => 'grandfather',
            'other' => 'other',
        ],
    ];

    /**
     * نقشه کلیدهای ستون‌های برای تطابق
     */
    protected array $columnMapping = [
        'شناسه_خانواده' => 'شناسه خانواده',
        'نام_روستا' => 'نام روستا',
        'سرپرست' => 'سرپرست؟',
        'نوع_عضو_خانواده' => 'نوع عضو خانواده',
        'نام' => 'نام',
        'نام_خانوادگی' => 'نام خانوادگی',
        'شغل' => 'شغل',
        'کد_ملی' => 'کد ملی',
        'تاریخ_تولد' => 'تاریخ تولد',
        'اعتیاد' => 'اعتیاد',
        'بیکار' => 'بیکار',
        'بیماری_خاص' => 'بیماری خاص',
        'ازکارافتادگی' => 'ازکارافتادگی',
        'توضیحات_بیشتر_کمک_کننده' => 'توضیحات بیشتر کمک‌کننده',
    ];

    /**
     * کلیدهای ستون‌های فارسی مطابق FamiliesTemplateExport
     */
    protected array $expectedHeaders = [
        'شناسه_خانواده' => 'شناسه خانواده',
        'استان' => 'استان',
        'شهر' => 'شهر',
        'سرپرست' => 'سرپرست؟',
        'نوع_عضو_خانواده' => 'نوع عضو خانواده',
        'نام' => 'نام',
        'نام_خانوادگی' => 'نام خانوادگی',
        'شغل' => 'شغل',
        'کد_ملی' => 'کد ملی',
        'تاریخ_تولد' => 'تاریخ تولد',
        'اعتیاد' => 'اعتیاد',
        'بیکار' => 'بیکار',
        'بیماری_خاص' => 'بیماری خاص',
        'ازکارافتادگی' => 'ازکارافتادگی',
        'توضیحات_بیشتر_کمک_کننده' => 'توضیحات بیشتر کمک‌کننده',
    ];

    public function __construct(User $user, int $districtId)
    {
        // تنظیم formatter سرتیتر به حالت none تا فرمت دقیق حفظ شود
        HeadingRowFormatter::default('none');

        $this->user = $user;
        $this->districtId = $districtId;
        $this->results = [
            'success' => 0,
            'failed' => 0,
            'families_created' => 0,
            'families_updated' => 0,
            'members_added' => 0,
            'members_updated' => 0,
            'errors' => [],
            'error_summary' => [],
            'sample_errors' => [],
            'validation_errors' => [],
            'database_errors' => [],
            'total_errors' => 0,
            'error_types' => [],
            'max_display_errors' => 20,
            'showing_count' => 0,
        ];
    }

    /**
     * تعیین ردیف سرتیتر - ردیف 3 (بعد از عنوان و راهنما)
     */
    public function headingRow(): int
    {
        return 2; // ردیف دوم به عنوان سرتیتر
    }

    /**
     * پردازش فایل اکسل با مدیریت header های ترکیبی
     */
    public function collection(Collection $rows)
    {

        if ($rows->isEmpty()) {
            return;
        }

        // تعیین ردیف‌های header
        $headingRowIndex = 1; // ردیف دوم (index 1) - header اصلی
        $subHeadingRowIndex = 2; // ردیف سوم (index 2) - sub header

        $headers = [];
        $subHeaders = [];
        $finalHeaders = [];

        // گروه‌بندی اعضا بر اساس شناسه خانواده
        $groupedFamilies = [];
        $familyIdMapping = [];
        $lastFamilyId = null;

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;

            try {
                // خواندن header اصلی
                if ($index === $headingRowIndex) {
                    $headers = $row->toArray();
                    continue;
                }

                // خواندن sub header
                if ($index === $subHeadingRowIndex) {
                    $subHeaders = $row->toArray();

                    // ساخت header های نهایی
                    foreach ($headers as $col => $mainTitle) {
                        $mainTitle = trim($mainTitle ?? '');
                        $subTitle = trim($subHeaders[$col] ?? '');

                        if ($mainTitle === 'نوع مشکل' && !empty($subTitle)) {
                            // برای ستون‌های زیر "نوع مشکل"، از sub header استفاده کن
                            $finalHeaders[$col] = $subTitle;
                        } elseif (!empty($mainTitle)) {
                            // برای سایر ستون‌ها از header اصلی استفاده کن
                            $finalHeaders[$col] = $mainTitle;
                        } elseif (!empty($subTitle)) {
                            // اگر header اصلی خالی بود از sub header استفاده کن
                            $finalHeaders[$col] = $subTitle;
                        } else {
                            // اگر هر دو خالی بودند، نام کلی بده
                            $finalHeaders[$col] = "ستون_" . ($col + 1);
                        }
                    }

                    continue;
                }

                // رد کردن ردیف‌های قبل از شروع داده‌ها
                if ($index < 3) { // ردیف‌های 0، 1، 2 header هستند
                    continue;
                }

                // اگر header ها هنوز تشکیل نشده‌اند، skip کن
                if (empty($finalHeaders)) {
                    continue;
                }

                // ترکیب داده‌ها با header های نهایی
                $rowArray = $row->toArray();
                $data = [];

                foreach ($finalHeaders as $col => $headerName) {
                    $data[$headerName] = $rowArray[$col] ?? null;
                }

                Log::info('Processing row data', [
                    'row_number' => $rowNumber,
                    'data_keys' => array_keys($data),
                    'sample_data' => array_slice($data, 0, 5, true)
                ]);

                // تطبیق کلیدها
                $rowData = $this->normalizeRowKeys($data, $rowNumber);

                // تشخیص خودکار ردیف‌های خالی
                if ($this->isRowEmpty($rowData, $rowNumber)) {
                    continue;
                }

                if ($this->shouldSkipRow($rowData, $rowNumber)) {
                    continue;
                }

                // اعتبارسنجی داده‌های ردیف (فقط فیلدهای ضروری)
                $validation = $this->validateRowData($rowData, $rowNumber);

                // اگر خطای اجباری داشت، این ردیف رو skip کن
                if (!$validation['valid']) {
                    foreach ($validation['errors'] as $error) {
                        $this->addError($error);
                    }
                    $this->results['failed']++;
                    continue;
                }

                // مدیریت شناسه خانواده (برای گروه‌بندی اعضا)
                $familyId = trim($rowData['family_id'] ?? '');

                if (!empty($familyId)) {
                    $lastFamilyId = $familyId;
                    $familyIdMapping[$rowNumber] = $familyId;
                } elseif ($lastFamilyId) {
                    $familyId = $lastFamilyId;
                    $familyIdMapping[$rowNumber] = "استفاده از آخرین ID: {$familyId}";
                } else {
                    $this->addError("ردیف {$rowNumber}: شناسه خانواده مشخص نیست");
                    $this->results['failed']++;
                    continue;
                }

                // اضافه کردن عضو به خانواده
                if (!isset($groupedFamilies[$familyId])) {
                    $groupedFamilies[$familyId] = [];
                }

                $groupedFamilies[$familyId][] = [
                    'data' => $rowData,
                    'row_number' => $rowNumber
                ];

            } catch (\Exception $e) {
                Log::error('Error processing row', [
                    'row_number' => $rowNumber,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $this->addError("ردیف {$rowNumber}: خطای غیرمنتظره - {$e->getMessage()}");
                $this->results['failed']++;
                continue;
            }
        }

        // Fix: Add the missing Log::info() call
        Log::info('Family grouping completed', [
            'total_families' => count($groupedFamilies),
            'families_overview' => array_map(fn($members) => count($members), $groupedFamilies)
        ]);

        // پردازش خانواده‌های گروه‌بندی شده
        $this->processFamilies($groupedFamilies);
    }

    /**
     * تبدیل کلیدهای ردیف به فرمت استاندارد
     */
    protected function normalizeRowKeys(array $row, int $rowNumber): array
    {
        $normalized = [];

        // نقشه تطبیق کلیدها (فارسی به انگلیسی)
        $keyMapping = [
            'شناسه خانواده' => 'family_id',
            'نام روستا' => 'village_name',
            'استان' => 'province_name',
            'شهر' => 'city_name',
            'شهرستان' => 'county_name',
            'سرپرست؟' => 'is_head',
            'نوع عضو خانواده' => 'relationship_fa',
            'نام' => 'first_name',
            'نام خانوادگی' => 'last_name',
            'شغل' => 'occupation',
            'کد ملی' => 'national_code',
            'تاریخ تولد' => 'birth_date',
            'جنسیت' => 'gender',
            'وضعیت تاهل' => 'marital_status',
            'موبایل' => 'mobile',
            'تلفن' => 'phone',
            'شماره شبا' => 'sheba',
            'اعتیاد' => 'addiction',
            'بیکار' => 'unemployed',
            'بیماری خاص' => 'special_disease',
            'ازکارافتادگی' => 'disability',
            'توضیحات بیشتر کمک‌کننده' => 'additional_details',
        ];

        // تطبیق کلیدها
        foreach ($row as $key => $value) {
            $key = trim($key);

            // جستجوی تطبیق دقیق
            if (isset($keyMapping[$key])) {
                $normalized[$keyMapping[$key]] = trim(strval($value ?? ''));
                continue;
            }

            // جستجوی تطبیق فازی
            foreach ($keyMapping as $persianKey => $englishKey) {
                if (str_contains($key, $persianKey) || str_contains($persianKey, $key)) {
                    $normalized[$englishKey] = trim(strval($value ?? ''));
                    break;
                }
            }
        }

        // Debug برای بررسی تطبیق کلیدها
        if ($rowNumber <= 5) {
            Log::info('Key mapping debug', [
                'original_keys' => array_keys($row),
                'mapped_keys' => array_keys($normalized),
                'family_id' => $normalized['family_id'] ?? 'NOT_FOUND',
                'first_name' => $normalized['first_name'] ?? 'NOT_FOUND'
            ]);
        }

        return $normalized;
    }

    /**
     * بررسی اینکه آیا ردیف باید نادیده گرفته شود (فقط ردیف‌های راهنما)
     */
    protected function shouldSkipRow(array $rowData, int $rowNumber): bool
    {
        $familyId = $rowData['family_id'] ?? '';
        $firstName = $rowData['first_name'] ?? '';

        // فقط ردیف‌های راهنما یا مثال را skip کن
        if ($familyId === 'راهنما' ||
            str_contains($familyId, 'راهنما') ||
            str_contains($familyId, 'مثال') ||
            $firstName === 'راهنما' ||
            str_contains($firstName, 'راهنما') ||
            str_contains($firstName, 'مثال')) {

            // Fix: Add the missing Log::info() call
            Log::info('Skipping guide/example row', [
                'reason' => 'ردیف راهنما یا مثال',
                'family_id' => $familyId,
                'first_name' => $firstName
            ]);
            return true;
        }

        return false;
    }

    /**
     * اعتبارسنجی داده‌های ردیف (فقط فیلدهای ضروری)
     */
    protected function validateRowData(array $rowData, int $rowNumber): array
    {
        $errors = [];

        // فقط فیلدهای اجباری - بقیه هیچ پیامی نمی‌دهند
        if (empty($rowData['first_name'])) {
            $errors[] = "❌ ردیف {$rowNumber}: نام الزامی است";
        }

        if (empty($rowData['last_name'])) {
            $errors[] = "❌ ردیف {$rowNumber}: نام خانوادگی الزامی است";
        }

        if (empty($rowData['national_code'])) {
            $errors[] = "❌ ردیف {$rowNumber}: کد ملی الزامی است";
        }

        // تمام فیلدهای دیگر (استان، شهر، تاریخ تولد، نوع عضو، کد ملی تکراری، وغیره)
        // بدون هیچ پیام warning یا error ای پذیرفته می‌شوند
        // کد ملی تکراری مشکل نیست چون از updateOrCreate استفاده می‌کنیم

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => [] // هیچ warning ای نمایش داده نمی‌شود
        ];
    }

    /**
     * پردازش یک گروه خانواده با اولویت updateOrCreate
     */
    protected function processFamilyGroup(array $familyData): void
    {
        $members = $familyData['members'];
        $firstMember = $members[0];
        $provinceName = trim($firstMember['province_name'] ?? '');
        $cityName = trim($firstMember['city_name'] ?? '');
        $familyTempId = $familyData['temp_id'];

        // بررسی وجود سرپرست در خانواده
        $hasHead = false;
        foreach ($members as $memberData) {
            $isHead = $this->mapBooleanValue($memberData['is_head'] ?? 'خیر');
            if ($isHead) {
                $hasHead = true;
                break;
            }
        }

        if (!$hasHead) {
            throw new \Exception("❌ خانواده شناسه {$familyTempId}: هیچ سرپرستی مشخص نشده است. هر خانواده باید حداقل یک سرپرست داشته باشد");
        }

        // ابتدا چک می‌کنیم آیا اعضای این خانواده از قبل وجود دارند
        // اگر بیش از نیمی از اعضا وجود داشته باشند، خانواده موجودشان را استفاده می‌کنیم
        $existingFamily = $this->findExistingFamily($members);

        if ($existingFamily) {
            Log::info('Using existing family', [
                'family_id' => $existingFamily->id,
                'family_code' => $existingFamily->family_code,
                'temp_id' => $familyTempId
            ]);
            $family = $existingFamily;
        } else {
            // تنظیم پیش‌فرض در صورت خالی بودن استان/شهر
            $province = null;
            $city = null;
            $address = "نامشخص";

            if (!empty($provinceName)) {
                $province = Province::where('name', 'LIKE', "%{$provinceName}%")->first();
                if ($province && !empty($cityName)) {
                    $city = City::where('province_id', $province->id)
                               ->where('name', 'LIKE', "%{$cityName}%")
                               ->first();

                    if ($city) {
                        $address = "شهر {$cityName}، استان {$provinceName}";
                    } else {
                        $address = "استان {$provinceName}";
                    }
                } elseif ($province) {
                    $address = "استان {$provinceName}";
                }
            }

            // ایجاد خانواده جدید
            $familyService = app(FamilyService::class);
            $family = $familyService->registerFamily([
                'family_code' => $this->generateUniqueFamilyCode(),
                'province_id' => $province?->id,
                'city_id' => $city?->id,
                'district_id' => $this->districtId,
                'address' => $address,
            ], $this->user);
            Log::info('Using existing family', [
                'family_id' => $family->id,
                'family_code' => $family->family_code,
                'temp_id' => $familyTempId
            ]);
        }

        // اضافه کردن اعضا
        foreach ($members as $memberData) {
            $this->addMemberToFamily($family, $memberData);
        }

        // بروزرسانی معیارهای پذیرش و محاسبه رتبه خانواده
        $this->updateAcceptanceCriteriaAndRank($family);
        
        // بررسی و اعمال معیار سرپرست مجرد
        $family->checkAndApplySingleParentCriteria();
    }

    /**
     * بروزرسانی معیارهای پذیرش و محاسبه رتبه خانواده
     */
    protected function updateAcceptanceCriteriaAndRank(Family $family): void
    {
        // دریافت همه اعضای خانواده با problem_type آن‌ها
        $members = $family->members()->get();

        // جمع‌آوری معیارهای پذیرش از مشکلات اعضا
        $acceptanceCriteria = [];

        foreach ($members as $member) {
            if (is_array($member->problem_type) && !empty($member->problem_type)) {
                foreach ($member->problem_type as $problem) {
                    // تبدیل نوع مشکل به معیار پذیرش متناظر
                    $criteria = $this->mapProblemToCriteria($problem);
                    if (!empty($criteria) && !in_array($criteria, $acceptanceCriteria)) {
                        $acceptanceCriteria[] = $criteria;
                    }
                }
            }
        }

        // بروزرسانی فیلد acceptance_criteria خانواده
        if (!empty($acceptanceCriteria)) {
            $family->acceptance_criteria = $acceptanceCriteria;
            $family->save();

            // محاسبه رتبه خانواده
            $family->calculateRank();
            Log::info('Using existing family', [
                'family_id' => $family->id,
                'criteria' => $acceptanceCriteria,
                'rank' => $family->calculated_rank
            ]);
        }
    }

    /**
     * تبدیل نوع مشکل به معیار پذیرش متناظر
     */
    protected function mapProblemToCriteria(string $problem): string
    {
        $mapping = [
            'اعتیاد' => 'اعتیاد',
            'بیماری های خاص' => 'بیماری های خاص',
            'از کار افتادگی' => 'از کار افتادگی',
            'بیکاری' => 'بیکاری',
            // برای سازگاری با مقادیر قدیمی
            'addiction' => 'اعتیاد',
            'special_disease' => 'بیماری های خاص',
            'work_disability' => 'از کار افتادگی',
            'unemployment' => 'بیکاری',
        ];

        return $mapping[$problem] ?? $problem;
    }

    /**
     * یافتن خانواده موجود بر اساس اعضای موجود
     */
    protected function findExistingFamily(array $members): ?Family
    {
        // کدهای ملی اعضا
        $nationalCodes = [];
        foreach ($members as $memberData) {
            if (!empty($memberData['national_code'])) {
                $nationalCodes[] = $memberData['national_code'];
            }
        }

        if (empty($nationalCodes)) {
            return null;
        }

        // جستجو برای اعضای موجود
        $existingMembers = Member::whereIn('national_code', $nationalCodes)->get();

        if ($existingMembers->isEmpty()) {
            return null;
        }

        // اگر بیش از نیمی از اعضا در یک خانواده هستند، آن خانواده را برمی‌گردانیم
        $familyCounts = $existingMembers->groupBy('family_id');
        $totalMembers = count($members);

        foreach ($familyCounts as $familyId => $familyMembers) {
            $existingCount = $familyMembers->count();
            if ($existingCount >= ceil($totalMembers / 2)) {
                return Family::find($familyId);
            }
        }

        return null;
    }

    /**
     * اضافه کردن عضو به خانواده با updateOrCreate
     */
    protected function addMemberToFamily(Family $family, array $memberData): void
    {
        // تبدیل مقادیر
        $isHead = $this->mapBooleanValue($memberData['is_head'] ?? 'خیر');
        $relationship = $this->mapRelationshipValue($memberData['relationship_fa']);

        // تشخیص جنسیت
        $gender = 'male'; // پیش‌فرض مرد
        if (in_array($relationship, ['mother', 'daughter', 'grandmother']) || 
            in_array($memberData['relationship_fa'], ['مادر', 'دختر', 'مادربزرگ'])) {
            $gender = 'female';
        }

        // تبدیل مقادیر مشکلات
        $problemTypes = [];
        if ($this->mapBooleanValue($memberData['addiction'] ?? 'خیر')) {
            $problemTypes[] = 'اعتیاد';
        }
        if ($this->mapBooleanValue($memberData['unemployed'] ?? 'خیر')) {
            $problemTypes[] = 'بیکاری';
        }
        if ($this->mapBooleanValue($memberData['special_disease'] ?? 'خیر')) {
            $problemTypes[] = 'بیماری های خاص';
        }
        if ($this->mapBooleanValue($memberData['disability'] ?? 'خیر')) {
            $problemTypes[] = 'از کار افتادگی';
        }

        $memberUpdateData = [
            'family_id' => $family->id,
            'charity_id' => $family->charity_id,
            'first_name' => $memberData['first_name'],
            'last_name' => $memberData['last_name'] ?? '',
            'birth_date' => $this->parseDate($memberData['birth_date'] ?? ''),
            'gender' => $gender,
            'relationship' => $relationship,
            'relationship_fa' => $memberData['relationship_fa'],
            'is_head' => $isHead,
            'occupation' => $memberData['occupation'] ?? '',
            'mobile' => $memberData['mobile'] ?? null,
            'phone' => $memberData['phone'] ?? null,
            'sheba' => $memberData['sheba'] ?? null,
            'problem_type' => $problemTypes,
            'special_conditions' => $memberData['additional_details'] ?? '',
        ];

        // استفاده از updateOrCreate برای جلوگیری از تکراری یا آپدیت کردن
        $member = Member::updateOrCreate(
            [
                'national_code' => $memberData['national_code'], // کلید یکتا برای تشخیص
            ],
            $memberUpdateData
        );

        // چک کردن ایا عضو جدید ایجاد شده یا آپدیت شده
        if ($member->wasRecentlyCreated) {
            $this->results['members_added']++;
        } else {
            $this->results['members_updated']++;
        }
    }

    /**
     * تبدیل مقدار boolean
     */
    protected function mapBooleanValue(string $value): bool
    {
        $value = trim($value);
        return $this->valueMapping['boolean'][$value] ?? false;
    }

    /**
     * تبدیل مقدار رابطه خانوادگی
     */
    protected function mapRelationshipValue(string $value): string
    {
        $value = trim($value);
        return $this->valueMapping['relationship'][$value] ?? 'other';
    }

    /**
     * پارس کردن تاریخ شمسی
     */
    protected function parseDate(string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            // حذف space اضافی
            $date = trim($date);

            // فرمت‌های مختلف تاریخ
            // 1. فرمت استاندارد: 1370/1/1
            if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $date, $matches)) {
                $year = intval($matches[1]);
                $month = intval($matches[2]);
                $day = intval($matches[3]);

                // اعتبارسنجی محدوده
                if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
                    return null;
                }

                $jalalian = new \Morilog\Jalali\Jalalian($year, $month, $day);
                return $jalalian->toCarbon()->format('Y-m-d');
            }

            // 2. فرمت با slash اضافی: 1356//04/21
            if (preg_match('/^(\d{4})\/+(\d{1,2})\/(\d{1,2})$/', $date, $matches)) {
                $year = intval($matches[1]);
                $month = intval($matches[2]);
                $day = intval($matches[3]);

                if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
                    return null;
                }

                $jalalian = new \Morilog\Jalali\Jalalian($year, $month, $day);
                return $jalalian->toCarbon()->format('Y-m-d');
            }

            // 3. فقط سال: 1360
            if (preg_match('/^(\d{4})$/', $date, $matches)) {
                $year = intval($matches[1]);
                // فرض می‌کنیم اول فروردین
                $jalalian = new \Morilog\Jalali\Jalalian($year, 1, 1);
                return $jalalian->toCarbon()->format('Y-m-d');
            }

            // 4. فرمت با صفر اضافی در ماه: 1314/080/1
            if (preg_match('/^(\d{4})\/0?(\d{1,2})\/(\d{1,2})$/', $date, $matches)) {
                $year = intval($matches[1]);
                $month = intval($matches[2]);
                $day = intval($matches[3]);

                if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
                    return null;
                }

                $jalalian = new \Morilog\Jalali\Jalalian($year, $month, $day);
                return $jalalian->toCarbon()->format('Y-m-d');
            }

        } catch (\Exception $e) {
        }

        return null;
    }

    /**
     * تولید کد یکتای خانواده
     */
    protected function generateUniqueFamilyCode(): string
    {
        do {
            $code = mt_rand(100000000, 999999999);
        } while (Family::where('family_code', $code)->exists());

        return (string) $code;
    }

    /**
     * بررسی معتبر بودن charity_id
     */
    protected function getValidCharityId(): ?int
    {
        if ($this->user->organization_id) {
            $orgExists = \App\Models\Organization::where('id', $this->user->organization_id)->exists();
            if ($orgExists) {
                return $this->user->organization_id;
            }
        }

        $firstCharity = \App\Models\Organization::where('type', 'charity')
                                                ->where('is_active', true)
                                                ->first();

        return $firstCharity?->id;
    }

    /**
     * اضافه کردن خطا
     */
    protected function addError(string $error): void
    {
        $this->results['errors'][] = $error;
        $this->results['total_errors']++;

        // محدود کردن نمایش خطاها
        if (count($this->results['errors']) > 20) {
            $this->results['errors'] = array_slice($this->results['errors'], 0, 20);
            $this->results['showing_count'] = 20;
        } else {
            $this->results['showing_count'] = count($this->results['errors']);
        }
    }

    /**
     * دریافت نتایج
     */
    public function getResults(): array
    {
        // تولید خلاصه خطاها قبل از بازگشت نتایج
        $this->generateErrorSummary();
        return $this->results;
    }

    /**
     * تولید خلاصه خطاها برای نمایش بهتر
     */
    protected function generateErrorSummary(): void
    {
        $summary = [];

        foreach ($this->results['error_types'] as $type => $count) {
            $typeLabel = $this->getErrorTypeLabel($type);
            $summary[] = "{$typeLabel}: {$count} مورد";
        }

        $this->results['error_summary'] = $summary;

        // اگر خطاهای زیادی هست، پیام اضافی اضافه کن
        if ($this->results['total_errors'] > $this->results['max_display_errors']) {
            $hiddenCount = $this->results['total_errors'] - $this->results['max_display_errors'];
            $this->results['sample_errors'][] = [
                'message' => "💡 {$hiddenCount} خطای اضافی مخفی شده‌اند. برای مشاهده کامل، لاگ سیستم را بررسی کنید.",
                'type' => 'summary',
                'context' => []
            ];
        }
    }

    /**
     * برچسب انواع خطاها
     */
    protected function getErrorTypeLabel(string $type): string
    {
        $labels = [
            'validation' => '❌ خطاهای اعتبارسنجی',
            'database' => '🔧 خطاهای پایگاه داده',
            'foreign_key' => '🔗 خطاهای ارتباط جداول',
            'duplicate' => '⚠️ اطلاعات تکراری',
            'data_format' => '📝 خطاهای فرمت داده',
            'province_city' => '📍 خطاهای استان/شهر',
            'general' => '🚫 خطاهای عمومی'
        ];

        return $labels[$type] ?? "🔍 {$type}";
    }

    /**
     * تشخیص خودکار ردیف‌های خالی
     */
    protected function isRowEmpty(array $rowData, int $rowNumber): bool
    {
        // فیلدهای اصلی که باید چک شوند
        $mainFields = ['first_name', 'last_name', 'national_code'];

        $hasData = false;
        foreach ($mainFields as $field) {
            if (!empty($rowData[$field]) && trim($rowData[$field]) !== '') {
                $hasData = true;
                break;
            }
        }

        // اگر فیلدهای اصلی خالی بودند، بررسی کنیم که آیا سایر فیلدها هم خالی هستند
        if (!$hasData) {
            // بررسی اضافی - اگر شناسه خانواده یا شغل هم داشته باشد، ردیف خالی نیست
            $extraFields = ['family_id', 'occupation', 'province_name', 'city_name'];
            foreach ($extraFields as $field) {
                if (!empty($rowData[$field]) && trim($rowData[$field]) !== '') {
                    Log::info('Using existing family', [
                    // اگر شناسه خانواده دارد ولی نام ندارد، احتمالاً ردیف خراب است
                        'has_family_id' => !empty($rowData['family_id']),
                        'has_name' => !empty($rowData['first_name']),
                        'has_last_name' => !empty($rowData['last_name']),
                        'has_national_code' => !empty($rowData['national_code'])
                    ]);
                    return false; // ردیف خراب ولی خالی نیست
                }
            }
            Log::info('Using existing family', [
                'reason' => 'ردیف خالی - تمام فیلدهای اصلی خالی'
            ]);
            return true;
        }

        return false;
    }

    /**
     * پردازش خانواده‌های گروه‌بندی شده
     */
    protected function processFamilies(array $groupedFamilies): void
    {
        // پردازش هر خانواده
        foreach ($groupedFamilies as $familyId => $familyMembers) {
            try {
                DB::beginTransaction();

                // استخراج داده‌های اعضا
                $membersData = array_map(fn($member) => $member['data'], $familyMembers);

                // چک کردن آیا خانواده جدید است یا آپدیت می‌شود
                $existingFamily = $this->findExistingFamily($membersData);
                $isNewFamily = !$existingFamily;

                // ایجاد ساختار سازگار با متد قدیمی
                $familyData = [
                    'members' => $membersData,
                    'temp_id' => $familyId
                ];

                $this->processFamilyGroup($familyData);

                DB::commit();

                if ($isNewFamily) {
                    $this->results['families_created']++;
                } else {
                    $this->results['families_updated']++;
                }

                $this->results['success']++;
                Log::info('Using existing family', [
                    'members_count' => count($membersData),
                    'is_new' => $isNewFamily
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::info('Using existing family', [
                    'family_id' => $familyId,
                    'error' => $e->getMessage()
                ]);

                $this->addError("❌ خانواده شناسه {$familyId}: " . $e->getMessage());
                $this->results['failed']++;
            }
        }

    }
}
