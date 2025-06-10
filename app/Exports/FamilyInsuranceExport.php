<?php

namespace App\Exports;

use App\Models\Family;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Morilog\Jalali\Jalalian;

class FamilyInsuranceExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithColumnFormatting, WithStyles
{
    protected $familyIds;
    protected $addSampleRow = true;
    
    public function __construct(array $familyIds, $addSampleRow = true)
    {
        $this->familyIds = $familyIds;
        $this->addSampleRow = $addSampleRow;
    }
    
    public function collection()
    {
        if (empty($this->familyIds)) {
            return collect([]);
        }
        
        $families = Family::whereIn('id', $this->familyIds)->get();
        
        if ($families->isEmpty()) {
            if ($this->addSampleRow) {
                // ایجاد یک ردیف نمونه برای راهنمایی کاربر حتی اگر هیچ خانواده‌ای یافت نشد
                $sample = [
                    'family_code' => 'نمونه (مثال - تغییر ندهید)',
                    'insurance_type' => 'مثال: تامین اجتماعی',
                    'premium_amount' => 'مثال: 5000000 (ریال)',
                    'start_date' => 'مثال: 1403/03/01',
                    'end_date' => 'مثال: 1404/03/01',
                ];
                return collect([(object)$sample]);
            }
            return collect([]);
        }
        
        if ($this->addSampleRow && $families->count() > 0) {
            // Use the first selected family's code for consistency
            $firstFamily = $families->first();
            $realFamilyCode = $firstFamily->family_code;
            
            // Add a sample row as the first row for user guidance
            $sample = [
                'family_code' => $realFamilyCode . ' (مثال - تغییر ندهید)',
                'insurance_type' => 'مثال: تامین اجتماعی',
                'premium_amount' => 'مثال: 5000000 (ریال)',
                'start_date' => 'مثال: 1403/03/01',
                'end_date' => 'مثال: 1404/03/01',
            ];
            $sampleCollection = collect([(object)$sample]);
            return $sampleCollection->concat($families);
        }
        
        return $families;
    }
    
    public function headings(): array
    {
        return [
            'شناسه خانواده (فقط عدد بدون تغییر)',
            'نوع بیمه (تکمیلی/درمانی/عمر/حوادث/سایر/تامین اجتماعی)',
            'مبلغ بیمه (ریال، فقط عدد)',
            'تاریخ صدور (جلالی، مثال: 1403/03/01)',
            'تاریخ پایان (جلالی، مثال: 1404/03/01)',
        ];
    }
    
    public function map($family): array
    {
        // Sample row: pass as-is  
        if (is_array($family) || (is_object($family) && isset($family->insurance_type) && str_contains($family->insurance_type, 'مثال'))) {
            return [
                is_array($family) ? $family['family_code'] : $family->family_code,
                is_array($family) ? $family['insurance_type'] : $family->insurance_type,
                is_array($family) ? $family['premium_amount'] : $family->premium_amount,
                is_array($family) ? $family['start_date'] : $family->start_date,
                is_array($family) ? $family['end_date'] : $family->end_date,
            ];
        }
        
        // بررسی خالی بودن کد خانواده و استفاده از آیدی به عنوان کد خانواده در صورت خالی بودن
        $familyCode = $family->family_code;
        if (empty($familyCode)) {
            $familyCode = (string)$family->id;
            
            // به‌روزرسانی کد خانواده در دیتابیس
            try {
                $family->family_code = $familyCode;
                $family->save();
            } catch (\Exception $e) {
                // خطا را نادیده می‌گیریم
            }
        }
        
        // فقط کد خانواده را پر می‌کنیم و بقیه فیلدها را خالی می‌گذاریم تا ادمین پر کند
        return [
            $familyCode, // شناسه خانواده - فقط این مقدار پر می‌شود
            '',  // نوع بیمه - خالی می‌گذاریم تا ادمین پر کند
            '',  // مبلغ بیمه - خالی می‌گذاریم تا ادمین پر کند
            '',  // تاریخ صدور - خالی می‌گذاریم تا ادمین پر کند
            '',  // تاریخ پایان - خالی می‌گذاریم تا ادمین پر کند
        ];
    }
    
    public function columnFormats(): array
    {
        return [
            'A' => '@', // شناسه خانواده - فرمت متنی Excel بدون کاراکتر اضافی
            // فرمت سفارشی: عدد با کاما و کلمه ریال
            'C' => '#,##0 "ریال"', // مبلغ بیمه
            // تاریخ‌ها به صورت متن جلالی خروجی می‌شوند و نیازی به فرمت اکسل ندارند
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Force column A (family_code) to be text format
        $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode('@');
        
        return [
            // Style the header row
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Format date safely (handle both Carbon and string dates)
     */
    private function formatDate($date)
    {
        if (empty($date)) {
            return '';
        }

        // If it's already a string in the correct format, return as-is
        if (is_string($date) && preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $date)) {
            return $date;
        }

        // If it's a Carbon instance, format it
        if ($date instanceof \Carbon\Carbon) {
            return $date->format('Y/m/d');
        }

        // If it's a string that looks like a date, try to parse it
        if (is_string($date)) {
            try {
                $carbonDate = \Carbon\Carbon::parse($date);
                return $carbonDate->format('Y/m/d');
            } catch (\Exception $e) {
                return $date; // Return as-is if can't parse
            }
        }

        return '';
    }
} 