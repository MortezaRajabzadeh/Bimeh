<?php

namespace App\Exports;

use App\Helpers\ProblemTypeHelper;
use App\Helpers\DateHelper;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FamilySearchExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    ShouldAutoSize,
    WithStyles
{
    protected $families;
    protected $status;

    /**
     * Constructor
     *
     * @param Collection|array $families
     * @param string|null $status ('insured', 'uninsured', null)
     */
    public function __construct($families, $status = null)
    {
        $this->families = $families;
        $this->status = $status;
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        if ($this->families instanceof Collection) {
            return $this->families;
        }
        
        if (is_array($this->families)) {
            return collect($this->families);
        }
        
        return collect([]);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $baseHeadings = [
            'شناسه خانواده',
            'استان',
            'شهر',
            'منطقه',
            'خیریه',
            'نام سرپرست',
            'کد ملی سرپرست',
            'موبایل سرپرست',
            'تعداد اعضا',
            'معیارهای پذیرش',
            'تاریخ عضویت',
        ];

        // اضافه کردن ستون‌های بیمه برای بیمه شده‌ها
        if ($this->status === 'insured') {
            $baseHeadings = array_merge($baseHeadings, [
                'تعداد بیمه‌ها',
                'نوع بیمه',
                'تاریخ شروع بیمه',
                'تاریخ پایان بیمه',
                'پرداخت کننده حق بیمه',
            ]);
        }

        return $baseHeadings;
    }

    /**
     * @param mixed $family
     * @return array
     */
    public function map($family): array
    {
        $baseData = [
            $family->family_code ?: $family->id,
            $family->province->name ?? 'نامشخص',
            $family->city->name ?? 'نامشخص',
            $family->region->name ?? 'نامشخص',
            $family->charity->name ?? 'نامشخص',
            $this->getHeadName($family),
            $this->getHeadNationalCode($family),
            $this->getHeadMobile($family),
            $this->getMembersCount($family),
            $this->getCriteriaText($family),
            DateHelper::toJalali($family->created_at, 'Y/m/d'),
        ];

        // اضافه کردن اطلاعات بیمه برای بیمه شده‌ها
        if ($this->status === 'insured') {
            $baseData = array_merge($baseData, [
                $this->getInsuranceCount($family),
                $this->getInsuranceTypes($family),
                $this->getInsuranceStartDate($family),
                $this->getInsuranceEndDate($family),
                $this->getInsurancePayers($family),
            ]);
        }

        return $baseData;
    }

    /**
     * دریافت نام سرپرست
     */
    private function getHeadName($family)
    {
        if ($family->head) {
            return trim($family->head->first_name . ' ' . $family->head->last_name);
        }
        return 'نامشخص';
    }

    /**
     * دریافت کد ملی سرپرست
     */
    private function getHeadNationalCode($family)
    {
        return $family->head->national_code ?? 'نامشخص';
    }

    /**
     * دریافت موبایل سرپرست
     */
    private function getHeadMobile($family)
    {
        return $family->head->mobile ?? 'نامشخص';
    }

    /**
     * دریافت تعداد اعضا
     */
    private function getMembersCount($family)
    {
        if (isset($family->members_count)) {
            return $family->members_count;
        }
        
        if ($family->members) {
            return $family->members->count();
        }
        
        return 0;
    }

    /**
     * دریافت متن معیارهای پذیرش
     */
    private function getCriteriaText($family)
    {
        $allCriteria = [];

        // 1. معیارهای از acceptance_criteria خانواده
        if (!empty($family->acceptance_criteria)) {
            $acceptanceCriteria = is_array($family->acceptance_criteria) 
                ? $family->acceptance_criteria 
                : json_decode($family->acceptance_criteria, true);
                
            if (is_array($acceptanceCriteria)) {
                $allCriteria = array_merge($allCriteria, $acceptanceCriteria);
            }
        }

        // 2. معیارهای از problem_type اعضا
        if ($family->members) {
            foreach ($family->members as $member) {
                if (!empty($member->problem_type)) {
                    $problemTypes = is_array($member->problem_type) 
                        ? $member->problem_type 
                        : json_decode($member->problem_type, true);
                        
                    if (is_array($problemTypes)) {
                        $allCriteria = array_merge($allCriteria, $problemTypes);
                    }
                }
            }
        }

        // 3. معیارهای از familyCriteria relation
        if ($family->familyCriteria) {
            foreach ($family->familyCriteria as $criterion) {
                if ($criterion->has_criteria && $criterion->rankSetting) {
                    $allCriteria[] = $criterion->rankSetting->name;
                }
            }
        }

        // حذف تکراری‌ها
        $allCriteria = array_unique($allCriteria);

        // تبدیل به فارسی
        $persianCriteria = ProblemTypeHelper::convertArrayToPersian($allCriteria);

        return !empty($persianCriteria) ? implode(', ', $persianCriteria) : 'ندارد';
    }

    /**
     * دریافت تعداد بیمه‌ها
     */
    private function getInsuranceCount($family)
    {
        if ($family->finalInsurances) {
            return $family->finalInsurances->count();
        }
        return 0;
    }

    /**
     * دریافت انواع بیمه
     */
    private function getInsuranceTypes($family)
    {
        if (!$family->finalInsurances || $family->finalInsurances->isEmpty()) {
            return 'ندارد';
        }

        $types = $family->finalInsurances
            ->pluck('insurance_type')
            ->filter()
            ->unique()
            ->values();

        return $types->isNotEmpty() ? $types->implode(', ') : 'ندارد';
    }

    /**
     * دریافت تاریخ شروع بیمه
     */
    private function getInsuranceStartDate($family)
    {
        $latestInsurance = $family->finalInsurances ? $family->finalInsurances->first() : null;
        
        if ($latestInsurance && $latestInsurance->start_date) {
            return DateHelper::toJalali($latestInsurance->start_date, 'Y/m/d');
        }
        
        return 'نامشخص';
    }

    /**
     * دریافت تاریخ پایان بیمه
     */
    private function getInsuranceEndDate($family)
    {
        $latestInsurance = $family->finalInsurances ? $family->finalInsurances->first() : null;
        
        if ($latestInsurance) {
            if ($latestInsurance->end_date) {
                return DateHelper::toJalali($latestInsurance->end_date, 'Y/m/d');
            }
            return 'نامحدود';
        }
        
        return 'نامشخص';
    }

    /**
     * دریافت پرداخت‌کنندگان حق بیمه
     */
    private function getInsurancePayers($family)
    {
        $latestInsurance = $family->finalInsurances ? $family->finalInsurances->first() : null;
        
        if (!$latestInsurance) {
            return 'نامشخص';
        }

        // بررسی fundingSource
        if ($latestInsurance->fundingSource) {
            return $latestInsurance->fundingSource->name;
        }

        // بررسی shares
        if ($latestInsurance->shares && $latestInsurance->shares->isNotEmpty()) {
            $payerNames = $latestInsurance->shares
                ->map(function ($share) {
                    return $share->payer_name ?? 'نامشخص';
                })
                ->filter()
                ->unique()
                ->values();

            return $payerNames->isNotEmpty() ? $payerNames->implode(', ') : 'نامشخص';
        }

        // اگر هیچکدام موجود نبود، از فیلد insurance_payer استفاده کن
        return $latestInsurance->insurance_payer ?? 'نامشخص';
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
}