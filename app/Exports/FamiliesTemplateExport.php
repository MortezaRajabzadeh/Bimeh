<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class FamiliesTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    /**
     * سرتیترهای فایل نمونه مطابق تصویر ارسالی
     */
    public function headings(): array
    {
        return [
            'شناسه خانواده',              // 0
            'استان',                     // 1
            'شهر',                       // 2
            'سرپرست؟',                  // 3
            'نوع عضو خانواده',            // 4
            'نام',                       // 5
            'نام خانوادگی',              // 6
            'شغل',                       // 7
            'کد ملی',                    // 8
            'تاریخ تولد',                // 9
            'اعتیاد',                    // 10
            'بیکار',                     // 11
            'بیماری خاص',                // 12
            'ازکارافتادگی',              // 13
            'توضیحات بیشتر کمک‌کننده'    // 14
        ];
    }

    /**
     * داده‌های نمونه
     */
    public function array(): array
    {
        return [
            // راهنمای استفاده
            [
                'راهنما',
                'نام استان',
                'نام شهر',
                'بلی یا خیر',
                'پدر/مادر/فرزند/همسر/نوه/غیره',
                'نام کامل',
                'نام خانوادگی',
                'نوع شغل',
                'کد ملی 10 رقمی',
                'مثال: 1380/01/01',
                'بلی/خیر',
                'بلی/خیر',
                'بلی/خیر',
                'بلی/خیر',
                'توضیحات اضافی در صورت نیاز'
            ],
            // خانواده اول - سرپرست (پدر)
            [
                '1',
                'تهران',
                'تهران',
                'بلی',
                'پدر',
                'احمد',
                'محمدی',
                'کارگر',
                '1234567890',
                '1360/05/15',
                'خیر',
                'بله',
                'خیر',
                'خیر',
                'نیازمند کمک برای یافتن شغل'
            ],
            // خانواده اول - مادر
            [
                '1',
                'تهران',
                'تهران',
                'خیر',
                'مادر',
                'فاطمه',
                'محمدی',
                'خانه‌دار',
                '9876543210',
                '1365/03/10',
                'خیر',
                'خیر',
                'بله',
                'خیر',
                'مبتلا به دیابت'
            ],
            // خانواده اول - فرزند
            [
                '1',
                'تهران',
                'تهران',
                'خیر',
                'فرزند',
                'علی',
                'محمدی',
                'دانش‌آموز',
                '1122334455',
                '1390/08/20',
                'خیر',
                'خیر',
                'خیر',
                'خیر',
                'دانش‌آموز کلاس نهم'
            ],
            // خانواده دوم - سرپرست (مادر)
            [
                '2',
                'خراسان رضوی',
                'مشهد',
                'بلی',
                'مادر',
                'زهرا',
                'کریمی',
                'خانه‌دار',
                '5566778899',
                '1358/12/10',
                'خیر',
                'خیر',
                'خیر',
                'بله',
                'از کار افتاده به دلیل حادثه'
            ],
            // خانواده دوم - فرزند
            [
                '2',
                'خراسان رضوی',
                'مشهد',
                'خیر',
                'فرزند',
                'مهدی',
                'کریمی',
                'دانش‌آموز',
                '6677889900',
                '1395/07/25',
                'خیر',
                'خیر',
                'خیر',
                'خیر',
                'دانش‌آموز ابتدایی'
            ]
        ];
    }

    /**
     * استایل‌های اکسل
     */
    public function styles(Worksheet $sheet)
    {
        // راست‌چین کردن تمام سلول‌ها
        $sheet->getStyle('A:O')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // سرتیترها bold و رنگ‌دار
        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F4FD']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ]
            ]
        ]);

        // راهنما با رنگ متفاوت
        $sheet->getStyle('A2:O2')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF2CC']
            ]
        ]);

        // رنگ‌بندی ستون‌های نوع مشکل
        $sheet->getStyle('K:N')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFEBEE']
            ]
        ]);

        return [];
    }
} 
