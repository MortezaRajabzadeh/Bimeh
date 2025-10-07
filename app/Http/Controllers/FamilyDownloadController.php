<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Family;
use App\Exports\DynamicDataExport;
use App\Exports\FamilyInsuranceExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Enums\InsuranceWizardStep;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FamilyDownloadController extends Controller
{
    /**
     * دانلود فایل اکسل بر اساس پارامترهای ارسالی
     */
    public function download(Request $request)
    {
        try {
            // بررسی اعتبار امضای URL
            if (!$request->hasValidSignature()) {
                abort(401, 'لینک دانلود نامعتبر است یا منقضی شده است.');
            }

            $type = $request->query('type', 'page');
            $filename = $request->query('filename', 'families-export.xlsx');

            // لاگ برای دیباگ
            Log::info('Starting download process', [
                'type' => $type,
                'filename' => $filename,
                'user_id' => Auth::check() ? Auth::id() : 'unauthenticated',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

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
                        $query->where(function($q) {
                            $q->where('wizard_status', InsuranceWizardStep::PENDING->value)
                              ->orWhere('status', 'pending');
                        })->where('status', '!=', 'deleted');
                        break;
                    case 'reviewing':
                        $query->where(function($q) {
                            $q->where('wizard_status', InsuranceWizardStep::REVIEWING->value)
                              ->orWhere('status', 'reviewing');
                        })->where('status', '!=', 'deleted');
                        break;
                    case 'approved':
                        $query->where(function($q) {
                            $q->whereIn('wizard_status', [
                                InsuranceWizardStep::SHARE_ALLOCATION->value,
                                InsuranceWizardStep::APPROVED->value
                            ])->orWhere('status', 'approved');
                        })->where('status', '!=', 'deleted');
                        break;
                    case 'excel':
                        $query->where('wizard_status', InsuranceWizardStep::EXCEL_UPLOAD->value)
                            ->where('status', '!=', 'deleted');
                        break;
                    case 'insured':
                        $query->where(function($q) {
                            $q->where('wizard_status', InsuranceWizardStep::INSURED->value)
                              ->orWhere('is_insured', true);
                        })->where('status', '!=', 'deleted');
                        break;
                    case 'renewal':
                        $query->where('wizard_status', InsuranceWizardStep::RENEWAL->value)
                            ->where('status', '!=', 'deleted');
                        break;
                    case 'deleted':
                        $query->where('status', 'deleted');
                        break;
                    default:
                        $query->where(function($q) {
                            $q->where('wizard_status', InsuranceWizardStep::PENDING->value)
                              ->orWhere('status', 'pending');
                        })->where('status', '!=', 'deleted');
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
                    Log::warning('No families found for download', [
                        'filters' => $filters,
                        'activeTab' => $activeTab
                    ]);
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

                Log::info('Generating page excel file', [
                    'families_count' => $families->count(),
                    'filename' => $filename
                ]);

                // تنظیم هدرها برای دانلود
                $response = Excel::download(new DynamicDataExport($families, $headings, $dataKeys), $filename);

                if ($response instanceof BinaryFileResponse) {
                    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
                    $response->headers->set('Pragma', 'public');
                    $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
                    $response->headers->set('Expires', '0');
                }

                return $response;

            } elseif ($type === 'insurance') {
                // دانلود اکسل بیمه
                $selectedIds = explode(',', $request->query('ids', ''));

                if (empty($selectedIds)) {
                    Log::warning('No IDs provided for insurance excel download');
                    abort(404, 'هیچ خانواده‌ای برای دانلود انتخاب نشده است.');
                }

                // لاگ برای دیباگ
                Log::info('Generating insurance excel file', [
                    'ids_count' => count($selectedIds),
                    'filename' => $filename
                ]);

                // تنظیم هدرها برای دانلود
                $response = Excel::download(new FamilyInsuranceExport($selectedIds, true), $filename);

                if ($response instanceof BinaryFileResponse) {
                    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
                    $response->headers->set('Pragma', 'public');
                    $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
                    $response->headers->set('Expires', '0');
                }

                return $response;
            }

            Log::warning('Invalid download type', ['type' => $type]);
            abort(400, 'نوع دانلود نامعتبر است.');

        } catch (\Exception $e) {
            Log::error('Error in download process', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            abort(500, 'خطا در دانلود فایل: ' . $e->getMessage());
        }
    }
}
