<?php

namespace App\Livewire\Insurance;

use Livewire\Component;
use App\Models\Family;
use App\Exports\FamilyInsuranceExport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\WithFileUploads;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FamiliesApproval extends Component
{
    use WithFileUploads;

    public $families;
    public $selected = [];
    public $selectAll = false;
    public $tab = 'pending';
    public $expandedFamily = null;
    public $insuranceExcelFile;

    public function mount()
    {
        $this->refreshFamilies();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->families->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function updatedSelected()
    {
        $this->selectAll = count($this->selected) && count($this->selected) === $this->families->count();
    }
    public function approveSelected()
    {
        Family::whereIn('id', $this->selected)->update(['status' => 'reviewing']);
        $this->selected = [];
        $this->selectAll = false;
        $this->refreshFamilies();
        $this->dispatch('reset-checkboxes');
    }

    public function deleteSelected()
    {
        Family::whereIn('id', $this->selected)->delete();
        $this->selected = [];
        $this->selectAll = false;
        $this->refreshFamilies();
        $this->dispatch('reset-checkboxes');
    }

    public function returnToPendingSelected()
    {
        Family::whereIn('id', $this->selected)->update(['status' => 'pending']);
        $this->selected = [];
        $this->selectAll = false;
        $this->refreshFamilies();
        $this->dispatch('reset-checkboxes');
    }

    public function approveAndContinueSelected()
    {
        Family::whereIn('id', $this->selected)->update(['status' => 'approved']);
        $this->selected = [];
        $this->selectAll = false;
        $this->refreshFamilies();
        $this->dispatch('reset-checkboxes');
    }

    public function refreshFamilies()
    {
        $status = $this->tab;
        $query = Family::with(['province', 'city', 'members', 'head', 'insurances'])
            ->withCount('insurances');
        if (in_array($status, ['pending', 'reviewing', 'approved', 'renewal', 'deleted'])) {
            if ($status === 'deleted') {
                $query = $query->onlyTrashed();
            } else {
                $query = $query->where('status', $status);
            }
        }
        $this->families = $query->get();
    }

    public function setTab($tab)
    {
        $this->tab = $tab;
        $this->refreshFamilies();
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
            'insuranceExcelFile' => 'required|file|mimes:xlsx,xls',
        ], [
            'insuranceExcelFile.required' => 'لطفاً فایل اکسل را انتخاب کنید.',
            'insuranceExcelFile.mimes' => 'فرمت فایل باید اکسل باشد.',
        ]);

        $fullPath = $this->insuranceExcelFile->getRealPath();
        if (!$fullPath || !file_exists($fullPath)) {
            session()->flash('error', 'فایل اکسل یافت نشد یا قبلاً حذف شده است. لطفاً مجدداً بارگذاری کنید.');
            return;
        }
        $imported = \Maatwebsite\Excel\Facades\Excel::toCollection(null, $fullPath);
        $rows = $imported[0];
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $validInsuranceTypes = ['تکمیلی', 'درمانی', 'عمر', 'حوادث', 'سایر','تامین اجتماعی'];
        $total = count($rows);
        $totalInsuranceAmount = 0;
        $importedFamilyCodes = [];
        $updatedFamilyCodes = [];
        $createdFamilyCodes = [];
        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $familyCode = trim($row[0]);
                if (empty($familyCode) || stripos($familyCode, 'مثال') !== false || !is_numeric($familyCode)) {
                    continue;
                }
                $family = \App\Models\Family::where('family_code', $familyCode)->first();
                if (!$family) {
                    $errors[] = "ردیف " . ($i+1) . ": شناسه خانواده یافت نشد: $familyCode";
                    continue;
                }
                $importedFamilyCodes[] = $familyCode;
                // Validate and parse row data
                try {
                    $insuranceType = $this->validateInsuranceType(trim($row[1]), $familyCode, $i);
                    $insuranceAmount = $this->validateInsuranceAmount($row[2], $familyCode, $i);
                    $issueDate = $this->parseJalaliDate(trim($row[3]), 'تاریخ صدور', $familyCode, $i);
                    $endDate = $this->parseJalaliDate(trim($row[4]), 'تاریخ پایان', $familyCode, $i);
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                    continue;
                }
                $insurance = $family->insurances()->where('insurance_type', $insuranceType)->first();
                $data = [
                    'insurance_amount' => $insuranceAmount,
                    'insurance_issue_date' => $issueDate,
                    'insurance_end_date' => $endDate,
                    'insurance_payer' => Auth::user()->name,
                ];
                if ($insurance) {
                    // Check if data has actually changed
                    $isChanged = false;
                    foreach ($data as $key => $val) {
                        $currentValue = $insurance->$key;
                        // Handle date comparison properly
                        if ($key === 'insurance_issue_date' || $key === 'insurance_end_date') {
                            $currentValue = $this->safeFormatDate($currentValue);
                            $val = $this->safeFormatDate($val);
                        }
                        
                        if ($currentValue != $val) {
                            $isChanged = true;
                            break;
                        }
                    }
                    
                    if ($isChanged) {
                        $insurance->update($data);
                        $updated++;
                        if (!in_array($familyCode, $updatedFamilyCodes)) {
                            $updatedFamilyCodes[] = $familyCode;
                        }
                    } else {
                        $skipped++;
                    }
                } else {
                    $family->insurances()->create(array_merge($data, [
                        'insurance_type' => $insuranceType,
                    ]));
                    $created++;
                    if (!in_array($familyCode, $createdFamilyCodes)) {
                        $createdFamilyCodes[] = $familyCode;
                    }
                }
                $family->status = 'insured';
                $family->save();
                if (is_numeric($insuranceAmount) && $insuranceAmount > 0) {
                    $totalInsuranceAmount += $insuranceAmount;
                }
            }
            $fileNameToLog = $this->insuranceExcelFile ? $this->insuranceExcelFile->getClientOriginalName() : 'نامعلوم'; // نام فایل را اینجا بگیرید
            DB::commit();
            $this->insuranceExcelFile = null; 
            $maxErrorsToShow = 5;
            $errorCount = count($errors);
            $msg = "از {$total} ردیف، {$created} جدید، {$updated} بروزرسانی، {$skipped} بدون تغییر و {$errorCount} خطا.";
            if ($errorCount) {
                $errorText = '';
                foreach (array_slice($errors, 0, $maxErrorsToShow) as $err) {
                    $errorText .= $err . "\n";
                }
                if ($errorCount > $maxErrorsToShow) {
                    $errorText .= "\nتعداد کل خطاها: {$errorCount} - فقط ۵ مورد اول نمایش داده می‌شود.";
                }
                session()->flash('error', $msg . "\n" . $errorText);
            } else {
                session()->flash('success', $msg);
            }
            \App\Models\InsuranceImportLog::create([
                'file_name' => $fileNameToLog,
                'user_id' => Auth::id(),
                'total_rows' => $total,
                'created_count' => $created,
                'updated_count' => $updated,
                'skipped_count' => $skipped,
                'error_count' => $errorCount,
                'total_insurance_amount' => $totalInsuranceAmount,
                'family_codes' => $importedFamilyCodes,
                'updated_family_codes' => $updatedFamilyCodes,
                'created_family_codes' => $createdFamilyCodes,
                'errors' => $errorCount ? implode("\n", $errors) : null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Excel import error: ' . $e->getMessage());
            session()->flash('error', 'خطا در پردازش فایل اکسل.');
        }
    }

    /**
     * Validate and parse Jalali date
     */
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

    /**
     * Validate insurance amount
     */
    private function validateInsuranceAmount($amount, $familyCode, $rowIndex)
    {
        if (!is_numeric($amount) || $amount <= 0) {
            throw new \Exception("ردیف " . ($rowIndex + 1) . ": مبلغ بیمه نامعتبر برای خانواده {$familyCode}: {$amount}");
        }
        return (float) $amount;
    }

    /**
     * Validate insurance type
     */
    private function validateInsuranceType($type, $familyCode, $rowIndex)
    {
        $validTypes = ['تکمیلی', 'درمانی', 'عمر', 'حوادث', 'سایر', 'تامین اجتماعی'];
        
        if (!in_array($type, $validTypes)) {
            throw new \Exception("ردیف " . ($rowIndex + 1) . ": نوع بیمه نامعتبر برای خانواده {$familyCode}: {$type}");
        }
        return $type;
    }

    /**
     * Safely format date for comparison (handle both Carbon and string dates)
     */
    private function safeFormatDate($date)
    {
        if (empty($date)) {
            return null;
        }

        // If it's a Carbon instance, format it
        if ($date instanceof \Carbon\Carbon) {
            return $date->format('Y-m-d');
        }

        // If it's a string, try to parse and format it
        if (is_string($date)) {
            try {
                $carbonDate = \Carbon\Carbon::parse($date);
                return $carbonDate->format('Y-m-d');
            } catch (\Exception $e) {
                return $date; // Return as-is if can't parse
            }
        }

        return null;
    }

    public function render()
    {
        return view('livewire.insurance.families-approval', [
            'families' => $this->families,
            'totalSelectedMembers' => $this->totalSelectedMembers,
        ]);
    }
}
