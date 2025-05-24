<?php

namespace App\Exports;

use App\Models\Family;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Morilog\Jalali\Jalalian;

class FamilyInsuranceExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithColumnFormatting
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
        $families = Family::whereIn('id', $this->familyIds)->get();
        if ($this->addSampleRow) {
            // Add a sample row as the first row for user guidance
            $sample = [
                'family_code' => 'مثال: 123456 (فقط خواندنی - تغییر ندهید)',
                'insurance_type' => 'مثال: تامین اجتماعی',
                'insurance_amount' => 'مثال: 5000000 (ریال)',
                'insurance_issue_date' => 'مثال: 1403/03/01',
                'insurance_end_date' => 'مثال: 1404/03/01',
            ];
            $sampleCollection = collect([(object)$sample]);
            return $sampleCollection->concat($families);
        }
        return $families;
    }
    public function headings(): array
    {
        return [
            'شناسه خانواده (فقط عدد، تغییر ندهید)',
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
                $family['family_code'] ?? $family->family_code,
                $family['insurance_type'] ?? $family->insurance_type,
                $family['insurance_amount'] ?? $family->insurance_amount,
                $family['insurance_issue_date'] ?? $family->insurance_issue_date,
                $family['insurance_end_date'] ?? $family->insurance_end_date,
            ];
        }

        // Get first insurance or empty values
        $firstInsurance = $family->insurances()->first();
        
        return [
            (string) $family->family_code,
            $firstInsurance->insurance_type ?? '',
            $firstInsurance->insurance_amount ?? '',
            $this->formatDate($firstInsurance->insurance_issue_date ?? null),
            $this->formatDate($firstInsurance->insurance_end_date ?? null),
        ];
    }
    
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_NUMBER, // شناسه خانواده
            // فرمت سفارشی: عدد با کاما و کلمه ریال
            'C' => '#,##0 "ریال"', // مبلغ بیمه
            // تاریخ‌ها به صورت متن جلالی خروجی می‌شوند و نیازی به فرمت اکسل ندارند
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