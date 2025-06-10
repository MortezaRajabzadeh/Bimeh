<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Family;
use App\Exports\DynamicDataExport;
use App\Exports\FamilyInsuranceExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Enums\InsuranceWizardStep;
use Illuminate\Support\Facades\Log;

class FamilyDownloadController extends Controller
{
    /**
     * دانلود فایل اکسل بر اساس پارامترهای ارسالی
     */
    public function download(Request $request)
    {
        // بررسی اعتبار امضای URL
        if (!$request->hasValidSignature()) {
            abort(401, 'لینک دانلود نامعتبر است یا منقضی شده است.');
        }
        
        $type = $request->query('type', 'page');
        $filename = $request->query('filename', 'families-export.xlsx');
        
        if ($type === 'page') {
            // دانلود اکسل صفحه
            $activeTab = $request->query('tab', 'pending');
            $filters = json_decode($request->query('filters', '{}'), true);
            
            $query = Family::query()->with([
                'province', 'city', 'district', 'region', 'members', 'head', 'charity', 'organization',
                'insurances' => fn($q) => $q->orderBy('created_at', 'desc'),
                'finalInsurances'
            ]);

            // اعمال فیلتر بر اساس تب فعال
            switch ($activeTab) {
                case 'pending':
                    $query->where('wizard_status', InsuranceWizardStep::PENDING->value)
                        ->where('status', '!=', 'deleted');
                    break;
                case 'reviewing':
                    $query->where('wizard_status', InsuranceWizardStep::REVIEWING->value)
                        ->where('status', '!=', 'deleted');
                    break;
                case 'approved':
                    $query->where('wizard_status', InsuranceWizardStep::APPROVED->value)
                        ->where('status', '!=', 'deleted');
                    break;
                case 'excel':
                    $query->where('wizard_status', InsuranceWizardStep::EXCEL_UPLOAD->value)
                        ->where('status', '!=', 'deleted');
                    break;
                case 'insured':
                    $query->where('wizard_status', InsuranceWizardStep::INSURED->value)
                        ->where('status', '!=', 'deleted');
                    break;
                case 'renewal':
                    $query->where('wizard_status', InsuranceWizardStep::RENEWAL->value)
                        ->where('status', '!=', 'deleted');
                    break;
                case 'deleted':
                    $query->where('status', 'deleted');
                    break;
                default:
                    $query->where('wizard_status', InsuranceWizardStep::PENDING->value)
                        ->where('status', '!=', 'deleted');
                    break;
            }

            // اعمال فیلترهای جستجو
            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->whereHas('head', fn($sq) => $sq->where('full_name', 'like', '%' . $filters['search'] . '%'))
                      ->orWhere('family_code', 'like', '%' . $filters['search'] . '%');
                });
            }

            // اعمال فیلتر استان
            if (!empty($filters['province_id'])) {
                $query->where('province_id', $filters['province_id']);
            }

            // اعمال فیلتر شهر
            if (!empty($filters['city_id'])) {
                $query->where('city_id', $filters['city_id']);
            }

            // اعمال فیلتر ناحیه
            if (!empty($filters['district_id'])) {
                $query->where('district_id', $filters['district_id']);
            }

            // اعمال فیلتر منطقه
            if (!empty($filters['region_id'])) {
                $query->where('region_id', $filters['region_id']);
            }

            // اعمال فیلتر سازمان
            if (!empty($filters['organization_id'])) {
                $query->where('organization_id', $filters['organization_id']);
            }

            // اعمال فیلتر خیریه
            if (!empty($filters['charity_id'])) {
                $query->where('charity_id', $filters['charity_id']);
            }

            // اعمال مرتب‌سازی
            $sortField = $filters['sortField'] ?? 'created_at';
            $sortDirection = $filters['sortDirection'] ?? 'desc';
            $families = $query->orderBy($sortField, $sortDirection)->get();

            if ($families->isEmpty()) {
                abort(404, 'هیچ داده‌ای برای دانلود با فیلترهای فعلی وجود ندارد.');
            }

            $headings = [
                'کد خانوار',
                'نام سرپرست',
                'کد ملی سرپرست',
                'استان',
                'شهرستان',
                'منطقه',
                'موسسه خیریه',
                'وضعیت بیمه',
                'تاریخ آخرین وضعیت بیمه',
                'نوع بیمه گر',
                'مبلغ کل بیمه (ریال)',
                'سهم بیمه شونده (ریال)',
                'سهم سایر پرداخت کنندگان (ریال)',
                'تعداد اعضا',
            ];

            $dataKeys = [
                'family_code',
                'head.full_name',
                'head.national_code',
                'province.name',
                'city.name',
                'region.name',
                'charity.name',
                'finalInsurances.0.status',
                'finalInsurances.0.updated_at',
                'finalInsurances.0.insurance_payer',
                'finalInsurances.0.premium_amount',
                'finalInsurances.0.premium_amount',
                'finalInsurances.0.premium_amount',
                'members_count',
            ];

            return Excel::download(new DynamicDataExport($families, $headings, $dataKeys), $filename);
        } elseif ($type === 'insurance') {
            // دانلود اکسل بیمه
            $selectedIds = explode(',', $request->query('ids', ''));
            
            if (empty($selectedIds)) {
                abort(404, 'هیچ خانواده‌ای برای دانلود انتخاب نشده است.');
            }
            
            return Excel::download(new FamilyInsuranceExport($selectedIds), $filename);
        }
        
        abort(400, 'نوع دانلود نامعتبر است.');
    }
}
