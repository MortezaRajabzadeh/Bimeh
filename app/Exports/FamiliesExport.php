<?php

namespace App\Exports;

use App\Models\Family;
use App\Helpers\DateHelper;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FamiliesExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    ShouldAutoSize,
    WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Family::with(['charity', 'insurance', 'region']);
        
        if (isset($this->filters['charity_id'])) {
            $query->where('charity_id', $this->filters['charity_id']);
        }
        
        if (isset($this->filters['insurance_id'])) {
            $query->where('insurance_id', $this->filters['insurance_id']);
        }
        
        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        
        if (isset($this->filters['region_id'])) {
            $query->where('region_id', $this->filters['region_id']);
        }
        
        if (isset($this->filters['date_from']) && isset($this->filters['date_to'])) {
            $query->whereBetween('created_at', [$this->filters['date_from'], $this->filters['date_to']]);
        }
        
        return $query->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'کد خانوار',
            'خیریه',
            'بیمه',
            'منطقه',
            'وضعیت',
            'تعداد اعضا',
            'تاریخ ثبت',
        ];
    }

    /**
     * @param Family $family
     * @return array
     */
    public function map($family): array
    {
        return [
            $family->family_code,
            $family->charity ? $family->charity->name : 'نامشخص',
            $family->insurance ? $family->insurance->name : 'نامشخص',
            $family->region ? $family->region->name : 'نامشخص',
            $this->getStatusText($family->status),
            $family->members()->count(),
            DateHelper::toJalali($family->created_at, 'Y/m/d'),
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Convert status code to readable text
     * 
     * @param string $status
     * @return string
     */
    private function getStatusText($status)
    {
        $statuses = [
            'active' => 'فعال',
            'inactive' => 'غیرفعال',
            'pending' => 'در انتظار تایید',
            'rejected' => 'رد شده',
        ];

        return $statuses[$status] ?? $status;
    }
} 