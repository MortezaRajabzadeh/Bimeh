<?php

namespace App\Livewire\Insurance;

use Livewire\Component;
use App\Models\Family;
use App\Exports\FamilyInsuranceExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\FamilyInsurance;
use App\Services\InsuranceShareService;
use Carbon\Carbon;

class FamiliesApproval extends Component
{
    use WithFileUploads, WithPagination;

    public $selected = [];
    public $selectAll = false;
    public $tab = 'pending';
    public $expandedFamily = null;
    public $insuranceExcelFile;
    public $perPage = 15;

    protected $paginationTheme = 'bootstrap';

    // ایجاد لیستنر برای ذخیره سهم‌بندی
    protected $listeners = [
        'sharesAllocated' => 'onSharesAllocated',
        'reset-checkboxes' => 'onResetCheckboxes',
        'switchToReviewingTab' => 'switchToReviewingTab'
    ];

    public function mount()
    {
        // پیش‌فرض pagination
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $families = $this->getFamiliesProperty();
            $this->selected = $families->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function updatedSelected()
    {
        $families = $this->getFamiliesProperty();
        $this->selectAll = count($this->selected) && count($this->selected) === $families->count();
    }

    public function approveSelected()
    {
        Family::whereIn('id', $this->selected)->update(['status' => 'reviewing']);
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
        $this->dispatch('reset-checkboxes');
    }

    public function deleteSelected()
    {
        Family::whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
        $this->dispatch('reset-checkboxes');
    }

    public function returnToPendingSelected()
    {
        Family::whereIn('id', $this->selected)->update(['status' => 'pending']);
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
        $this->dispatch('reset-checkboxes');
    }

    public function approveAndContinueSelected()
    {
        if (empty($this->selected)) {
            return;
        }

        // به جای آپدیت مستقیم وضعیت، ابتدا مودال سهم‌بندی را نمایش می‌دهیم
        $this->dispatch('openShareAllocationModal', $this->selected);
    }

    public function setTab($tab)
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function getFamiliesProperty()
    {
        $status = $this->tab;
        $query = Family::with(['province', 'city', 'members', 'head', 'insurances'])
            ->withCount('insurances');
        
        if (in_array($status, ['pending', 'reviewing', 'approved', 'insured', 'renewal', 'deleted'])) {
            if ($status === 'deleted') {
                $query = $query->onlyTrashed();
            } else {
                // هر تب فقط خانواده‌های همان وضعیت را نمایش دهد
                $query = $query->where('status', $status);
            }
        }

        return $query->paginate($this->perPage);
    }

    public function toggleFamily($familyId)
    {
        $this->expandedFamily = $this->expandedFamily === $familyId ? null : $familyId;
    }

    public function getTotalSelectedMembersProperty()
    {
        if (empty($this->selected)) {
            return 0;
        }
        return Family::withCount('members')->whereIn('id', $this->selected)->get()->sum('members_count');
    }

    public function downloadInsuranceExcel()
    {
        if (empty($this->selected)) {
            return null;
        }
        return Excel::download(new FamilyInsuranceExport($this->selected), 'insurance-families.xlsx');
    }

    public function uploadInsuranceExcel()
    {
        $this->validate([
            'insuranceExcelFile' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ]);

        try {
            // استفاده از دیسک public برای اطمینان از دسترسی
            $filename = time() . '_' . $this->insuranceExcelFile->getClientOriginalName();
            $path = $this->insuranceExcelFile->storeAs('excel_imports', $filename, 'public');
            $fullPath = storage_path('app/public/' . $path);

            // بررسی وجود فایل
            if (!file_exists($fullPath)) {
                throw new \Exception('فایل آپلود شده قابل دسترسی نیست. لطفاً دوباره تلاش کنید.');
            }

            // لاگ برای بررسی وضعیت
            Log::info('شروع پردازش فایل اکسل: ' . $fullPath . ' (وجود فایل: ' . (file_exists($fullPath) ? 'بله' : 'خیر') . ')');
            
            try {
                $imported = \Maatwebsite\Excel\Facades\Excel::toCollection(null, $fullPath);
                $rows = $imported[0];
                Log::info('اکسل با روش اول با موفقیت خوانده شد. تعداد کل ردیف‌ها: ' . count($rows));
            } catch (\Exception $e) {
                Log::warning('خطا در خواندن اکسل با روش اول: ' . $e->getMessage());
                
                // تلاش مجدد با تنظیمات دیگر
                try {
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                    $reader->setReadDataOnly(true);
                    $spreadsheet = $reader->load($fullPath);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = collect($worksheet->toArray());
                    Log::info('اکسل با روش دوم با موفقیت خوانده شد. تعداد کل ردیف‌ها: ' . count($rows));
                } catch (\Exception $e2) {
                    Log::error('خطا در خواندن اکسل با روش دوم: ' . $e2->getMessage());
                    throw new \Exception('خطا در خواندن فایل اکسل. لطفاً از فرمت صحیح استفاده کنید: ' . $e2->getMessage());
                }
            }
            
            Log::info('تعداد کل ردیف‌های فایل اکسل: ' . count($rows));
            
            // بررسی ساختار فایل اکسل
            if (count($rows) > 0) {
                Log::info('نمونه ردیف اول: ' . json_encode($rows[0]->toArray()));
                
                // لاگ جزئیات بیشتر از ساختار فایل
                if (count($rows) > 2) {
                    Log::info('نمونه ردیف دوم: ' . json_encode($rows[1]->toArray()));
                    Log::info('نمونه ردیف سوم: ' . json_encode($rows[2]->toArray()));
                    
                    // لاگ تعداد ستون‌ها
                    $columnCount = count($rows[0]);
                    Log::info('تعداد ستون‌های فایل اکسل: ' . $columnCount);
                    
                    // لاگ ساختار ستون‌ها
                    for ($c = 0; $c < $columnCount; $c++) {
                        Log::info("ستون {$c}: " . (isset($rows[0][$c]) ? $rows[0][$c] : 'خالی'));
                    }
                }
            }
            
            $errors = [];
            $successCount = 0;
            $totalRecords = 0;
            
            // آماده‌سازی دیتاهای لاگ ایمپورت
            $createdFamilyCodes = [];
            $updatedFamilyCodes = [];
            $totalAmount = 0;

            // لیست کدهای خانواده‌های اکسل برای بررسی
            $excelFamilyCodes = [];
            foreach ($rows as $i => $row) {
                if ($i === 0 && stripos($row[0], 'کد') !== false) {
                    Log::info('ردیف اول به عنوان هدر شناسایی شد و رد می‌شود');
                    continue; // ردیف اول هدر است
                }
                
                $familyCode = trim($row[0]);
                // حذف کاراکترهای اضافه که ممکن است توسط اکسل اضافه شوند
                $familyCode = ltrim($familyCode, "'");
                $familyCode = ltrim($familyCode, "=");
                $familyCode = ltrim($familyCode, "\t");
                
                if (!empty($familyCode) && stripos($familyCode, 'مثال') === false && is_numeric($familyCode)) {
                    Log::info("کد خانواده شناسایی شد: {$familyCode}");
                    $excelFamilyCodes[] = $familyCode;
                } else {
                    Log::warning("کد خانواده نامعتبر در ردیف " . ($i+1) . ": {$familyCode}");
                }
            }
            
            Log::info("تعداد کدهای خانواده شناسایی شده: " . count($excelFamilyCodes));
            
            // بررسی وجود کدهای خانواده در دیتابیس
            $existingFamilies = \App\Models\Family::whereIn('family_code', $excelFamilyCodes)->get();
            Log::info("تعداد خانواده‌های موجود در دیتابیس: " . $existingFamilies->count());
            
            if ($existingFamilies->count() === 0) {
                Log::error("هیچ خانواده‌ای با کدهای داده شده یافت نشد! کدها: " . implode(', ', array_slice($excelFamilyCodes, 0, 5)) . "...");
                throw new \Exception("هیچ کد خانواده‌ای در دیتابیس یافت نشد! لطفاً فایل خود را بررسی کنید.");
            }
            
            // ایجاد نقشه از کد خانواده به آبجکت خانواده برای دسترسی سریع‌تر
            $familyMap = $existingFamilies->keyBy('family_code');
            
            // شروع پردازش ردیف‌های اکسل
            foreach ($rows as $i => $row) {
                if ($i === 0 && stripos($row[0], 'کد') !== false) {
                    continue; // ردیف اول هدر است
                }
                
                $totalRecords++;
                
                $familyCode = trim($row[0]);
                // حذف کاراکترهای اضافه که ممکن است توسط اکسل اضافه شوند
                $familyCode = ltrim($familyCode, "'");
                $familyCode = ltrim($familyCode, "=");
                $familyCode = ltrim($familyCode, "\t");
                
                if (empty($familyCode) || stripos($familyCode, 'مثال') !== false || !is_numeric($familyCode)) {
                    Log::warning("رد کردن ردیف " . ($i+1) . " به دلیل کد خانواده نامعتبر: {$familyCode}");
                    continue;
                }
                
                $family = $familyMap[$familyCode] ?? null;
                if (!$family) {
                    $errors[] = "ردیف " . ($i+1) . ": شناسه خانواده یافت نشد: {$familyCode}";
                    Log::warning("خانواده با کد {$familyCode} در نقشه خانواده‌ها یافت نشد");
                    continue;
                }
                
                Log::info("پردازش خانواده با کد {$familyCode} - ردیف " . ($i+1));
                
                try {
                    // بررسی دقیق ستون‌های فایل اکسل
                    Log::info("بررسی دقیق ستون‌های ردیف " . ($i+1) . ": " . json_encode($row->toArray()));
                    
                    // نوع بیمه در ستون دوم (ایندکس 1)
                    $insuranceType = isset($row[1]) && !empty($row[1]) ? $row[1] : 'تکمیلی';
                    
                    // مبلغ بیمه در ستون سوم (ایندکس 2)
                    $premiumAmount = 0;
                    if (isset($row[2]) && !empty($row[2])) {
                        // حذف "ریال" و کاما از مبلغ
                        $premiumAmount = str_replace(['ریال', ',', ' '], '', $row[2]);
                        $premiumAmount = intval($premiumAmount);
                        Log::info("مقدار خام ستون مبلغ برای {$familyCode}: " . var_export($row[2], true) . " -> تبدیل شده به: {$premiumAmount}");
                    }
                    
                    // اگر مبلغ حق بیمه در فایل اکسل صفر باشد، از مقدار پیش‌فرض استفاده کنیم
                    if ($premiumAmount <= 0) {
                        // مقدار پیش‌فرض 1000000 ریال
                        $premiumAmount = 1000000;
                        Log::info("مبلغ حق بیمه برای خانواده {$familyCode} صفر یا خالی است. از مقدار پیش‌فرض استفاده می‌شود: {$premiumAmount} ریال");
                    }
                    
                    // تاریخ شروع در ستون چهارم (ایندکس 3)
                    $startDate = null;
                    if (isset($row[3]) && !empty($row[3])) {
                        try {
                            if (is_numeric($row[3])) {
                                // تبدیل تاریخ اکسل به تاریخ میلادی
                                $startDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[3]);
                            } else {
                                // فرض می‌کنیم تاریخ به فرمت شمسی است
                                $jalaliDate = $row[3];
                                $startDate = \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $jalaliDate)->toCarbon();
                            }
                            Log::info("تاریخ شروع برای {$familyCode}: " . $startDate->format('Y-m-d'));
                        } catch (\Exception $e) {
                            Log::warning("خطا در تبدیل تاریخ شروع: {$row[3]} - " . $e->getMessage());
                            // در صورت خطا در تاریخ، از امروز استفاده می‌کنیم
                            $startDate = now();
                            Log::info("استفاده از تاریخ امروز به عنوان تاریخ شروع: " . $startDate->format('Y-m-d'));
                        }
                    } else {
                        $startDate = now();
                        Log::info("تاریخ شروع تعیین نشده، استفاده از تاریخ امروز: " . $startDate->format('Y-m-d'));
                    }
                    
                    // تاریخ پایان در ستون پنجم (ایندکس 4) یا یکسال بعد از تاریخ شروع
                    $endDate = null;
                    if (isset($row[4]) && !empty($row[4])) {
                        try {
                            if (is_numeric($row[4])) {
                                $endDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[4]);
                            } else {
                                $jalaliDate = $row[4];
                                $endDate = \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $jalaliDate)->toCarbon();
                            }
                            Log::info("تاریخ پایان برای {$familyCode}: " . $endDate->format('Y-m-d'));
                        } catch (\Exception $e) {
                            Log::warning("خطا در تبدیل تاریخ پایان: {$row[4]} - " . $e->getMessage());
                            // در صورت خطا، یکسال بعد از تاریخ شروع
                            $endDate = \Carbon\Carbon::parse(date('Y-m-d', strtotime('+1 year', strtotime($startDate->format('Y-m-d')))));
                            Log::info("استفاده از یکسال بعد از تاریخ شروع به عنوان تاریخ پایان: " . $endDate->format('Y-m-d'));
                        }
                    } else {
                        // یکسال بعد از تاریخ شروع
                        $endDate = \Carbon\Carbon::parse(date('Y-m-d', strtotime('+1 year', strtotime($startDate->format('Y-m-d')))));
                        Log::info("تاریخ پایان تعیین نشده، استفاده از یکسال بعد از تاریخ شروع: " . $endDate->format('Y-m-d'));
                    }
                    
                    // ایجاد یا به‌روزرسانی رکورد بیمه خانواده
                    $existingInsurance = \App\Models\FamilyInsurance::where('family_id', $family->id)
                        ->latest('start_date')
                        ->first();
                        
                    if ($existingInsurance) {
                        // به‌روزرسانی رکورد موجود
                        $existingInsurance->update([
                            'premium_amount' => $premiumAmount,
                            'insurance_type' => $insuranceType,
                            'insurance_payer' => Auth::user()->name ?? 'سیستم',
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                        ]);
                        $updatedFamilyCodes[] = $familyCode;
                        Log::info("رکورد بیمه برای خانواده {$familyCode} به‌روزرسانی شد");
                    } else {
                        // ایجاد رکورد جدید
                        \App\Models\FamilyInsurance::create([
                            'family_id' => $family->id,
                            'premium_amount' => $premiumAmount,
                            'insurance_type' => $insuranceType,
                            'insurance_payer' => Auth::user()->name ?? 'سیستم',
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'status' => 'active',
                        ]);
                        $createdFamilyCodes[] = $familyCode;
                        Log::info("رکورد بیمه جدید برای خانواده {$familyCode} ایجاد شد");
                    }
                    
                    // بروزرسانی وضعیت خانواده
                    $family->update([
                        'insurance_status' => 'active',
                        'status' => 'insured' // تغییر وضعیت به بیمه شده
                    ]);
                    
                    $totalAmount += $premiumAmount;
                    $successCount++;
                    
                } catch (\Exception $e) {
                    Log::error("خطا در ردیف " . ($i+1) . ": " . $e->getMessage());
                    $errors[] = "ردیف " . ($i+1) . ": " . $e->getMessage();
                }
            }
            
            // ثبت لاگ ایمپورت
            try {
                // تبدیل تمام آرایه‌های مورد نیاز به JSON
                $formattedErrors = json_encode($errors, JSON_UNESCAPED_UNICODE);
                
                // لاگ مقادیر برای عیب‌یابی
                Log::info("نوع داده errors: " . gettype($formattedErrors));
                Log::info("مقدار errors: " . substr($formattedErrors, 0, 100) . "...");
                
                \App\Models\InsuranceImportLog::create([
                    'user_id' => Auth::id(),
                    'file_name' => $this->insuranceExcelFile->getClientOriginalName(),
                    'total_rows' => $totalRecords,
                    'created_count' => count($createdFamilyCodes),
                    'updated_count' => count($updatedFamilyCodes),
                    'error_count' => count($errors),
                    'errors' => $formattedErrors,
                    'created_family_codes' => $createdFamilyCodes,
                    'updated_family_codes' => $updatedFamilyCodes,
                    'total_insurance_amount' => $totalAmount,
                    'status' => 'completed',
                ]);
                
                Log::info("لاگ ایمپورت با موفقیت ذخیره شد.");
            } catch (\Exception $e) {
                Log::error("خطا در ذخیره لاگ ایمپورت: " . $e->getMessage());
                // ادامه اجرای کد بدون توقف در صورت خطا در ذخیره لاگ
            }
            
            // حذف فایل موقت
            try {
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                    Log::info("فایل موقت با موفقیت حذف شد: " . $fullPath);
                }
            } catch (\Exception $e) {
                Log::warning("خطا در حذف فایل موقت: " . $e->getMessage());
            }
            
            Log::info("پایان پردازش اکسل: {$successCount} مورد موفق از {$totalRecords} رکورد");
            
            session()->flash('success', "{$successCount} خانواده با موفقیت بیمه شدند. " . 
                count($createdFamilyCodes) . " خانواده جدید و " . 
                count($updatedFamilyCodes) . " خانواده به‌روزرسانی شدند.");
                
            // بازنشانی فرم
            $this->reset(['insuranceExcelFile']);
            $this->resetErrorBag();
            
            $this->dispatch('refreshFamiliesList');
            
        } catch (\Exception $e) {
            Log::error('خطا در آپلود اکسل بیمه: ' . $e->getMessage());
            session()->flash('error', 'خطا در پردازش فایل: ' . $e->getMessage());
        }
    }

    private function parseJalaliDate($dateString, $fieldName, $familyCode, $rowIndex)
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            if (preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $dateString)) {
                return \Morilog\Jalali\Jalalian::fromFormat('Y/m/d', $dateString)->toCarbon();
            } else {
                throw new \Exception('Invalid format');
            }
        } catch (\Exception $e) {
            throw new \Exception("ردیف " . ($rowIndex + 1) . ": {$fieldName} نامعتبر برای خانواده {$familyCode}: {$dateString} (فرمت صحیح: 1403/03/01)");
        }
    }

    private function validateInsuranceAmount($amount, $familyCode, $rowIndex)
    {
        // اضافه کردن لاگ برای بررسی مقدار ورودی
        Log::info("مقدار حق بیمه دریافتی برای خانواده {$familyCode}: " . var_export($amount, true) . " - نوع داده: " . gettype($amount));
        
        // اگر مقدار آرایه باشد (احتمالاً خروجی اکسل)
        if (is_array($amount)) {
            Log::info("مقدار آرایه‌ای است: " . json_encode($amount));
            if (isset($amount[0])) {
                $amount = $amount[0];
            }
        }
        
        // تبدیل هر چیزی به رشته برای پردازش
        $amount = (string) $amount;
        
        // حذف کاما از اعداد
        $amount = str_replace(',', '', $amount);
        
        // بررسی اگر مقدار رشته است و شامل ریال یا تومان است
        if (strpos($amount, 'ریال') !== false || strpos($amount, 'تومان') !== false) {
            // حذف کلمات "ریال" و "تومان"
            $amount = str_replace(['ریال', 'تومان'], '', $amount);
            // حذف فاصله‌ها
            $amount = trim($amount);
            Log::info("مقدار پس از حذف واحد پول: {$amount}");
        }
        
        // حذف همه کاراکترهای غیر عددی
        $cleanAmount = preg_replace('/[^0-9]/', '', $amount);
        Log::info("مقدار پس از پاکسازی: {$cleanAmount}");
        
        if (empty($cleanAmount) || !is_numeric($cleanAmount) || (int)$cleanAmount <= 0) {
            throw new \Exception("ردیف " . ($rowIndex + 1) . ": مبلغ بیمه نامعتبر برای خانواده {$familyCode}: {$amount}");
        }
        
        $amount = (float) $cleanAmount;
        Log::info("مقدار نهایی حق بیمه برای خانواده {$familyCode}: {$amount}");
        
        return $amount;
    }

    private function validateInsuranceType($type, $familyCode, $rowIndex)
    {
        $validTypes = ['تکمیلی', 'درمانی', 'عمر', 'حوادث', 'سایر', 'تامین اجتماعی'];
        
        if (!in_array($type, $validTypes)) {
            throw new \Exception("ردیف " . ($rowIndex + 1) . ": نوع بیمه نامعتبر برای خانواده {$familyCode}: {$type}");
        }
        return $type;
    }

    private function safeFormatDate($date)
    {
        if (empty($date)) {
            return null;
        }

        if ($date instanceof \Carbon\Carbon) {
            return $date->format('Y-m-d');
        }

        if (is_string($date)) {
            try {
                $carbonDate = \Carbon\Carbon::parse($date);
                return $carbonDate->format('Y-m-d');
            } catch (\Exception $e) {
                return $date;
            }
        }

        return null;
    }

    /**
     * ذخیره مستقیم اطلاعات بیمه در دیتابیس
     * 
     * @param integer $familyId
     * @param string $insuranceType
     * @param float $premium
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @return boolean
     */
    private function saveInsuranceDirectly($familyId, $insuranceType, $premium, $startDate = null, $endDate = null)
    {
        try {
            // حذف رکوردهای قبلی با همین نوع بیمه
            DB::table('family_insurances')
                ->where('family_id', $familyId)
                ->where('insurance_type', $insuranceType)
                ->delete();
            
            // ایجاد رکورد جدید
            $startDate = $startDate ?: now();
            $endDate = $endDate ?: now()->addYear();
            
            $insertData = [
                'family_id' => $familyId,
                'insurance_type' => $insuranceType,
                'premium_amount' => $premium,
                'start_date' => $startDate instanceof \DateTime ? $startDate->format('Y-m-d') : $startDate,
                'end_date' => $endDate instanceof \DateTime ? $endDate->format('Y-m-d') : $endDate,
                'insurance_payer' => Auth::user()->name,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $id = DB::table('family_insurances')->insertGetId($insertData);
            
            Log::info("ذخیره مستقیم رکورد بیمه (ID: {$id}) برای خانواده {$familyId} با مبلغ {$premium}");
            
            // ذخیره در کش
            Cache::forget('family_premium_' . $familyId);
            
            return true;
        } catch (\Exception $e) {
            Log::error("خطا در ذخیره مستقیم رکورد بیمه: " . $e->getMessage());
            return false;
        }
    }

    /**
     * تغییر وضعیت خانواده به بیمه شده و ثبت در دیتابیس
     * 
     * @param \App\Models\Family $family
     * @param string $familyCode
     * @return bool
     */
    private function updateFamilyStatus($family, $familyCode)
    {
        try {
            // فقط اگر وضعیت approved باشد، آن را تغییر دهیم
            if ($family->status === 'approved') {
                $oldStatus = $family->status;
                $family->status = 'insured';
                $result = $family->save();
                
                if ($result) {
                    Log::info("✅ تغییر وضعیت خانواده {$familyCode} از {$oldStatus} به insured با موفقیت انجام شد");
                    return true;
                } else {
                    Log::error("❌ خطا در تغییر وضعیت خانواده {$familyCode} از {$oldStatus} به insured");
                    return false;
                }
            } elseif ($family->status === 'insured') {
                // در صورتی که قبلاً بیمه شده باشد، نیازی به تغییر نیست
                Log::info("ℹ️ خانواده {$familyCode} قبلاً بیمه شده است");
                return true;
            } else {
                Log::warning("⚠️ خانواده {$familyCode} در وضعیت {$family->status} است و نمی‌تواند به بیمه شده تغییر کند");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("❌ خطای استثنا در تغییر وضعیت خانواده {$familyCode}: " . $e->getMessage());
            return false;
        }
    }

    // متد فراخوانی شده بعد از ذخیره سهم‌بندی
    public function onSharesAllocated()
    {
        $this->selected = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    // متد فراخوانی شده برای ریست کردن چک‌باکس‌ها
    public function onResetCheckboxes()
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    // متد جدید برای تغییر تب به "در انتظار صدور"
    public function switchToReviewingTab()
    {
        $this->tab = 'approved';
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function render()
    {
        return view('livewire.insurance.families-approval', [
            'families' => $this->families,
            'totalSelectedMembers' => $this->totalSelectedMembers,
        ]);
    }
}
