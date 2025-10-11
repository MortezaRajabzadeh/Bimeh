<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * کلاس صدور گزارش مالی اکسل
 *
 * استفاده از FinancialReportService برای دریافت داده‌ها
 */
class FinancialReportExport implements
    FromArray,
    WithHeadings,
    WithStyles,
    WithCustomStartCell,
    WithTitle,
    WithColumnWidths
{
    private array $filters;
    private Collection $transactions;
    private array $summary;
    private FinancialReportService $financialReportService;

    /**
     * سازنده کلاس با Dependency Injection
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->financialReportService = app(FinancialReportService::class);
        $this->prepareData();
    }

    /**
     * تهیه داده‌ها با استفاده از Service
     */
    private function prepareData(): void
    {
        $this->transactions = $this->financialReportService->getAllTransactions($this->filters);
        $this->summary = $this->financialReportService->calculateSummary($this->transactions);
    }


    /**
     * ارائه داده‌ها به عنوان آرایه
     */
    public function array(): array
    {
        $data = [];

        // بخش خلاصه گزارش
        $data[] = ['خلاصه گزارش مالی بیمه'];
        $data[] = [];
        $data[] = ['کل واریزی:', number_format($this->summary['total_credit']) . ' ریال'];
        $data[] = ['کل پرداختی:', number_format($this->summary['total_debit']) . ' ریال'];
        $data[] = ['موجودی فعلی:', number_format($this->summary['balance']) . ' ریال'];
        $data[] = ['وضعیت مالی:', $this->summary['balance'] > 0 ? 'مثبت' : 'منفی'];
        $data[] = ['تعداد کل تراکنش‌ها:', $this->summary['transactions_count']];
        $data[] = ['تاریخ تولید گزارش:', jdate(now())->format('Y/m/d H:i')];
        $data[] = [];
        $data[] = [];

        // بخش جزئیات تراکنش‌ها
        foreach ($this->transactions as $transaction) {
            $data[] = [
                $transaction['id'],
                $transaction['title'],
                $transaction['description'],
                jdate($transaction['date'])->format('Y/m/d H:i'),
                $transaction['type'] === 'credit' ? 'واریز' : 'پرداخت',
                number_format($transaction['amount']),
                $transaction['source'] ?? '',
                $transaction['reference_no'] ?? '',
                $transaction['family_count'] ?? 0,
                $transaction['members_count'] ?? 0,
            ];
        }

        return $data;
    }

    /**
     * تعریف سرتیترهای جدول
     */
    public function headings(): array
    {
        return [
            'شناسه',
            'عنوان تراکنش',
            'توضیحات',
            'تاریخ و زمان',
            'نوع تراکنش',
            'مبلغ (ریال)',
            'منبع/مقصد',
            'شماره پیگیری',
            'تعداد خانواده',
            'تعداد اعضا',
        ];
    }

    /**
     * تنظیمات استایل صفحه اکسل
     */
    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        return [
            // استایل عنوان اصلی
            'A1' => [
                'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => '1565C0']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],

            // استایل بخش خلاصه
            'A3:B9' => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '2196F3'],
                    ],
                ],
            ],

            // استایل هدر جدول
            'A11:J11' => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1976D2']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],

            // استایل داده‌های جدول
            "A12:J{$highestRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],

            // استایل ستون مبلغ (F)
            "F12:F{$highestRow}" => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ],
        ];
    }

    /**
     * سلول شروع برای هدرها
     */
    public function startCell(): string
    {
        return 'A11';
    }

    /**
     * عنوان برگه اکسل
     */
    public function title(): string
    {
        return 'گزارش مالی بیمه';
    }

    /**
     * عرض ستون‌ها
     */
    public function columnWidths(): array
    {
        return [
            'A' => 12,  // شناسه
            'B' => 30,  // عنوان تراکنش
            'C' => 35,  // توضیحات
            'D' => 20,  // تاریخ و زمان
            'E' => 15,  // نوع تراکنش
            'F' => 20,  // مبلغ
            'G' => 25,  // منبع/مقصد
            'H' => 20,  // شماره پیگیری
            'I' => 15,  // تعداد خانواده
            'J' => 15,  // تعداد اعضا
        ];
    }
}
