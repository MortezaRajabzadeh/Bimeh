<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FundingTransaction;
use App\Models\InsuranceAllocation;
use App\Models\Family;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use App\Models\InsuranceImportLog;
use App\Models\InsuranceShare;
use App\Models\InsurancePayment;
use App\Models\ShareAllocationLog; // مدل جدید اضافه شده
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FinancialReportExport;
use Illuminate\Support\Facades\Cache;

class FinancialReportController extends Controller
{
    /**
     * نمایش صفحه گزارش مالی
     */
    public function index(Request )
    {
         = ->get('per_page', 15);

        // محاسبه موجودی کل با کش (مدت زمان کش: 10 دقیقه)
         = Cache::remember('financial_report_total_credit', 600, function () {
            return FundingTransaction::sum('amount');
        });

         = Cache::remember('financial_report_total_debit', 600, function () {
            return InsuranceAllocation::sum('amount') +
                   InsuranceImportLog::sum('total_insurance_amount') +
                   InsurancePayment::sum('total_amount') +
                   ShareAllocationLog::where('status', 'completed')->sum('total_amount');
        });

         =  - ;

        // گرفتن همه تراکنش‌ها با جزئیات بهتر
         = collect();

        // 1. تراکنش‌های بودجه - بهینه شده با chunk و select
        FundingTransaction::select('id', 'funding_source_id', 'amount', 'description', 'allocated', 'created_at')
            ->with(['source' => function() {
                ->select('id', 'name');
            }])
            ->chunk(500, function() use (&) {
                foreach ( as ) {
                     = ->allocated ?? false;
                     =  ? 'تخصیص بودجه' : __('financial.transaction_types.budget_allocation');
                     =  ? 'debit' : 'credit';

                    ->push([
                        'id' => ->id,
                        'title' => ,
                        'amount' => ->amount,
                        'type' => ,
                        'date' => ->created_at,
                        'date_formatted' => jdate(->created_at)->format('Y/m/d'),
                        'sort_timestamp' => ->created_at->timestamp,
                        'description' => ->description ?? 'تراکنش مالی',
                        'source' => ->source->name ?? 'نامشخص',
                    ]);
                }
            });

        // 1.5. تخصیص‌های بودجه خانواده‌ها - بهینه شده
        \App\Models\FamilyFundingAllocation::select('id', 'family_id', 'funding_source_id', 'transaction_id', 'amount', 'percentage', 'description', 'status', 'approved_at', 'created_at')
            ->with([
                'family' => function() {
                    ->select('id', 'name', 'family_code');
                },
                'fundingSource' => function() {
                    ->select('id', 'name');
                }
            ])
            ->withCount('family.members as members_count')
            ->where('status', '!=', \App\Models\FamilyFundingAllocation::STATUS_PENDING)
            ->chunk(500, function() use (&) {
                foreach ( as ) {
                    // فقط تخصیص‌هایی که به تراکنش مالی متصل نیستند را اضافه می‌کنیم
                    // تا از دوبار شمارش جلوگیری شود
                    if (->transaction_id === null) {
                         = ->members_count ?? 0;

                        ->push([
                            'id' => ->id,
                            'title' => 'تخصیص بودجه خانواده',
                            'amount' => ->amount,
                            'type' => 'debit',
                            'date' => ->approved_at ?? ->created_at,
                            'date_formatted' => jdate(->approved_at ?? ->created_at)->format('Y/m/d'),
                            'sort_timestamp' => (->approved_at ?? ->created_at)->timestamp,
                            'description' => ->description ?: 'تخصیص ' . ->percentage . '% از حق بیمه',
                            'reference_no' => 'ALLOC-' . ->id,
                            'details' => ->fundingSource ? ->fundingSource->name : 'منبع مالی نامشخص',
                            'payment_id' => ->id,
                            'family_count' => 1,
                            'members_count' => ,
                            'family' => ->family,
                            'members' => collect(),
                            'created_family_codes' => [],
                            'updated_family_codes' => [],
                            'allocation_type' => 'family_funding'
                        ]);
                    }
                }
            });

        // 2. پرداخت‌های بیمه منفرد (InsuranceAllocation) - بهینه شده
        InsuranceAllocation::select('id', 'family_id', 'amount', 'description', 'created_at')
            ->with([
                'family' => function() {
                    ->select('id', 'name', 'family_code');
                }
            ])
            ->withCount('family.members as members_count')
            ->chunk(500, function() use (&) {
                foreach ( as ) {
                     = ->members_count ?? 0;

                    ->push([
                        'id' => ->id,
                        'title' => __('financial.transaction_types.premium_payment'),
                        'amount' => ->amount,
                        'type' => 'debit',
                        'date' => ->created_at,
                        'date_formatted' => jdate(->created_at)->format('Y/m/d'),
                        'sort_timestamp' => ->created_at->timestamp,
                        'description' => ->description,
                        'reference_no' => null,
                        'details' => null,
                        'payment_id' => ->id,
                        'family_count' => 1,
                        'members_count' => ,
                        'family' => ->family,
                        'members' => collect(),
                        'created_family_codes' => [],
                        'updated_family_codes' => [],
                    ]);
                }
            });

        // 3. پرداخت‌های اکسل ایمپورت شده - بهینه شده با رفع N+1 query
         = collect();
         = collect();
        
        InsuranceImportLog::select('id', 'total_insurance_amount', 'created_at', 'file_name', 'created_count', 'updated_count', 'created_family_codes', 'updated_family_codes')
            ->chunk(500, function() use (&, &) {
                foreach ( as ) {
                    ->push();
                    
                     = array_merge(
                        is_array(->created_family_codes) ? ->created_family_codes : [],
                        is_array(->updated_family_codes) ? ->updated_family_codes : []
                    );
                    
                     = ->merge();
                }
            });

        // یک کوئری واحد برای دریافت تعداد اعضای تمام خانواده‌ها
         = [];
        if (->isNotEmpty()) {
             = Family::whereIn('family_code', ->unique()->values()->toArray())
                ->withCount('members')
                ->get()
                ->pluck('members_count', 'family_code')
                ->toArray();
        }

        // حالا تراکنش‌ها را اضافه می‌کنیم
        foreach ( as ) {
             = array_merge(
                is_array(->created_family_codes) ? ->created_family_codes : [],
                is_array(->updated_family_codes) ? ->updated_family_codes : []
            );
             = count();

            // محاسبه تعداد اعضا از آرایه از پیش آماده شده
             = 0;
            foreach ( as ) {
                 += [] ?? 0;
            }

            ->push([
                'id' => ->id,
                'title' => __('financial.transaction_types.premium_import'),
                'amount' => ->total_insurance_amount,
                'type' => 'debit',
                'date' => ->created_at,
                'date_formatted' => jdate(->created_at)->format('Y/m/d'),
                'sort_timestamp' => ->created_at->timestamp,
                'description' => 'ایمپورت اکسل: ' . (->file_name ?? ''),
                'reference_no' => null,
                'details' => null,
                'payment_id' => null,
                'family_count' => ,
                'members_count' => ,
                'count_success' => ->created_count + ->updated_count,
                'members' => collect(),
                'family' => null,
                'updated_family_codes' => is_array(->updated_family_codes) ? ->updated_family_codes : [],
                'created_family_codes' => is_array(->created_family_codes) ? ->created_family_codes : [],
            ]);
        }

        // 4. پرداخت‌های سیستماتیک (InsurancePayment) - بهینه شده
        InsurancePayment::select('id', 'family_insurance_id', 'total_amount', 'payment_date', 'created_at', 'description', 'transaction_reference', 'insured_persons_count')
            ->with([
                'familyInsurance' => function() {
                    ->select('id', 'family_id');
                },
                'familyInsurance.family' => function() {
                    ->select('id', 'name', 'family_code');
                },
                'details' => function() {
                    ->select('id', 'insurance_payment_id', 'member_id');
                },
                'details.member' => function() {
                    ->select('id', 'first_name', 'last_name');
                }
            ])
            ->withCount('familyInsurance.family.members as family_members_count')
            ->chunk(500, function() use (&) {
                foreach ( as ) {
                     = ->familyInsurance ? ->familyInsurance->family : null;
                     = ->insured_persons_count ?? (->family_members_count ?? 0);

                    ->push([
                        'id' => ->id,
                        'title' => __('financial.transaction_types.premium_payment'),
                        'amount' => ->total_amount,
                        'type' => 'debit',
                        'date' => ->payment_date ?? ->created_at,
                        'date_formatted' => jdate(->payment_date ?? ->created_at)->format('Y/m/d'),
                        'sort_timestamp' => (->payment_date ?? ->created_at)->timestamp,
                        'description' => ->description,
                        'reference_no' => ->transaction_reference,
                        'details' => null,
                        'payment_id' => ->id,
                        'family_count' => 1,
                        'members_count' => ,
                        'family' => ,
                        'members' => ->details ? ->details->map->member : collect(),
                        'created_family_codes' => [],
                        'updated_family_codes' => [],
                    ]);
                }
            });

        // 5. سهم‌های بیمه (InsuranceShare) - بهینه شده
        InsuranceShare::select('id', 'family_insurance_id', 'funding_source_id', 'amount', 'percentage', 'updated_at')
            ->with([
                'familyInsurance' => function() {
                    ->select('id', 'family_id');
                },
                'familyInsurance.family' => function() {
                    ->select('id', 'name', 'family_code');
                },
                'fundingSource' => function() {
                    ->select('id', 'name');
                }
            ])
            ->withCount('familyInsurance.family.members as family_members_count')
            ->whereHas('familyInsurance', function() {
                ->where('status', 'insured');
            })
            ->where('amount', '>', 0)
            ->chunk(500, function() use (&) {
                foreach ( as ) {
                     = ->familyInsurance->family;
                     = ->family_members_count ?? 0;

                    // هر سهم را به عنوان یک تراکنش بدهی جداگانه در نظر می‌گیریم
                    ->push([
                        'id' => 'share-' . ->id, // یک شناسه منحصر به فرد
                        'title' => 'پرداخت سهم بیمه',
                        'amount' => ->amount, // مبلغ نهایی سهم
                        'type' => 'debit',
                        'date' => ->updated_at, // تاریخ نهایی شدن مبلغ
                        'date_formatted' => jdate(->updated_at)->format('Y/m/d'),
                        'sort_timestamp' => ->updated_at->timestamp,
                        'description' => "پرداخت سهم {->percentage}% برای خانواده " . (->name ?? ->family_code),
                        'reference_no' => 'SHARE-' . ->id,
                        'details' => ->fundingSource ? ->fundingSource->name : 'منبع مالی نامشخص',
                        'payment_id' => ->id,
                        'family_count' => 1,
                        'members_count' => ,
                        'family' => ,
                        'members' => collect(),
                        'created_family_codes' => [],
                        'updated_family_codes' => [],
                        'is_share' => true // برای تشخیص در view
                    ]);
                }
            });

        // 6. خواندن لاگ‌های تخصیص سهم گروهی - بهینه شده
        ShareAllocationLog::select('id', 'total_amount', 'updated_at', 'description', 'families_count', 'batch_id')
            ->where('status', 'completed')
            ->where('total_amount', '>', 0)
            ->chunk(500, function() use (&) {
                foreach ( as ) {
                    ->push([
                        'id' => 'alloc-' . ->id,
                        'title' => 'تخصیص سهم گروهی',
                        'amount' => ->total_amount,
                        'type' => 'debit',
                        'date' => ->updated_at,
                        'date_formatted' => jdate(->updated_at)->format('Y/m/d'),
                        'sort_timestamp' => ->updated_at->timestamp,
                        'description' => ->description,
                        'details' => ->families_count . ' خانواده',
                        'payment_id' => ->id,
                        'family_count' => ->families_count,
                        'batch_id' => ->batch_id,
                    ]);
                }
            });

        // ساده‌ترین sorting - فقط بر اساس timestamp
         = ->sortByDesc('sort_timestamp')->values();

        // Manual pagination
         = ->get('page', 1);
         = ( - 1) * ;
         = ->slice(, );

         = new \Illuminate\Pagination\LengthAwarePaginator(
            ,
            ->count(),
            ,
            ,
            [
                'path' => ->url(),
                'pageName' => 'page',
            ]
        );

        // گزارش ایمپورت‌های اکسل
         = InsuranceImportLog::with('user')->orderByDesc('created_at')->paginate(20, ['*'], 'logs_page');
         = InsuranceImportLog::sum('total_insurance_amount');

        // اطلاعات خانواده‌های بیمه شده برای نمایش در گزارش
         = \App\Models\Family::whereHas('insurances', function() {
            ->where('status', 'active');
        })->with(['insurances' => function() {
            ->where('status', 'active')->with('shares');
        }, 'members'])
        ->paginate(10, ['*'], 'families_page');

        return view('insurance.financial-report', compact('transactionsPaginated', 'balance', 'logs', 'totalAmount', 'insuredFamilies'));
    }

    /**
     * صدور گزارش اکسل
     */
    public function exportExcel(Request )
    {
        try {
            // اعتبارسنجی ورودی‌ها
             = ->validate([
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'type' => 'nullable|in:credit,debit,all',
                'format' => 'nullable|in:xlsx,csv'
            ]);

            // بررسی دسترسی کاربر
            if (!auth()->user()->can('view advanced reports')) {
                return back()->with('error', 'شما دسترسی لازم برای دانلود گزارش را ندارید.');
            }

            // بررسی وجود کلاس Export
            if (!class_exists(\App\Exports\FinancialReportExport::class)) {
                return back()->with('error', 'کلاس صدور گزارش یافت نشد. لطفاً با مدیر سیستم تماس بگیرید.');
            }

            // تولید نام فایل با تاریخ شمسی
             = jdate(now())->format('Y-m-d_H-i-s');
             = ['format'] ?? 'xlsx';
             = "financial_report_{}.{}";

            // ثبت لاگ شروع عملیات


            // تولید گزارش
             = new \App\Exports\FinancialReportExport();

            // انتخاب نوع فایل
             =  === 'csv' ?
                \Maatwebsite\Excel\Excel::CSV :
                \Maatwebsite\Excel\Excel::XLSX;

            // دانلود فایل
            return Excel::download(, , , [
                'Content-Type' =>  === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

        } catch (\Illuminate\Validation\ValidationException ) {
            return back()
                ->withErrors(->validator)
                ->with('error', 'داده‌های ورودی نامعتبر است.');

        } catch (\Illuminate\Auth\Access\AuthorizationException ) {
            return back()->with('error', 'شما دسترسی لازم برای این عملیات را ندارید.');

        } catch (\Maatwebsite\Excel\Exceptions\LaravelExcelException ) {


            return back()->with('error', 'خطا در تولید فایل اکسل: ' . ->getMessage());

        } catch (\Exception ) {
            // ثبت خطای عمومی در لاگ


            // نمایش پیام خطا به کاربر
            return back()->with('error', 'خطا در تولید گزارش: ' . ->getMessage());
        }
    }
    /**
     * نمایش جزئیات پرداخت
     */
    public function paymentDetails(Request , )
    {
         = ->get('type', 'allocation'); // allocation, payment, import, family_funding

        switch () {
            case 'allocation':
                 = InsuranceAllocation::with(['family.members'])->findOrFail();
                 = collect([->family]);
                break;

            case 'payment':
                 = InsurancePayment::with(['familyInsurance.family', 'details.member'])->findOrFail();
                 = collect([->familyInsurance->family]);
                break;

            case 'import':
                 = InsuranceImportLog::findOrFail();
                 = array_merge(
                    is_array(->created_family_codes) ? ->created_family_codes : [],
                    is_array(->updated_family_codes) ? ->updated_family_codes : []
                );
                 = Family::whereIn('family_code', )->with('members')->get();
                break;

            case 'family_funding':
                 = \App\Models\FamilyFundingAllocation::with(['family.members', 'fundingSource'])->findOrFail();
                 = collect([->family]);
                 = ;
                break;

            default:
                abort(404);
        }

        return view('insurance.payment-details', compact('families', 'type', 'paymentId', 'payment'));
    }

    public function importLogs()
    {
         = InsuranceImportLog::with('user')->orderByDesc('created_at')->paginate(20);
         = InsuranceImportLog::sum('total_insurance_amount');
        return view('insurance.financial-report', compact('logs', 'totalAmount'));
    }

    /**
     * نمایش جزئیات تخصیص بیمه
     */
    public function shareDetails()
    {
         = \App\Models\InsuranceShare::with(['family.members', 'fundingSource', 'creator'])
            ->findOrFail();

         = ->family;
         = new \App\Services\InsuranceShareService();
         = ->getSummary(->id);

        return view('insurance.share-details', [
            'share' => ,
            'family' => ,
            'shareSummary' => 
        ]);
    }

    /**
     * پاک کردن کش گزارش مالی
     */
    public function clearCache()
    {
        Cache::forget('financial_report_total_credit');
        Cache::forget('financial_report_total_debit');
        Cache::forget('funding_transactions_with_source');
        Cache::forget('family_allocations_with_relations');
        Cache::forget('insurance_allocations_with_family');

        return back()->with('success', 'کش گزارش مالی پاک شد.');
    }
}