<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClaimsSummary extends Model
{
    // این یک مدل virtual هست که از query builder استفاده می‌کنه
    
    /**
     * دریافت خلاصه خسارات به تفکیک تاریخ و نوع بیمه
     */
    public static function getSummaryByDateAndType($startDate = null, $endDate = null, $insuranceType = null, $filters = [])
    {
        $query = DB::table('insurance_allocations as ia')
            ->leftJoin('families as f', 'ia.family_id', '=', 'f.id')
            ->leftJoin('funding_transactions as ft', 'ia.funding_transaction_id', '=', 'ft.id')
            ->select([
                DB::raw('DATE(ia.created_at) as allocation_date'),
                DB::raw('DATE(ia.issue_date) as issue_date'),
                DB::raw('DATE(ia.paid_at) as paid_date'),
                'ft.description as insurance_type',
                DB::raw('COUNT(DISTINCT ia.family_id) as family_count'),
                DB::raw('COUNT(ia.id) as total_claims'),
                DB::raw('SUM(ia.amount) as total_amount'),
                DB::raw('AVG(ia.amount) as average_amount'),
                DB::raw('MIN(ia.amount) as min_amount'),
                DB::raw('MAX(ia.amount) as max_amount')
            ])
            ->whereNotNull('ia.amount')
            ->where('ia.amount', '>', 0);

        // فیلتر تاریخ شروع (تبدیل از جلالی به میلادی)
        if ($startDate) {
            $gregorianStartDate = \Morilog\Jalali\CalendarUtils::createDatetimeFromFormat('Y/m/d', $startDate)->format('Y-m-d');
            $query->whereDate('ia.created_at', '>=', $gregorianStartDate);
        }
        
        // فیلتر تاریخ پایان (تبدیل از جلالی به میلادی)
        if ($endDate) {
            $gregorianEndDate = \Morilog\Jalali\CalendarUtils::createDatetimeFromFormat('Y/m/d', $endDate)->format('Y-m-d');
            $query->whereDate('ia.created_at', '<=', $gregorianEndDate);
        }
        
        // فیلتر نوع بیمه
        if ($insuranceType) {
            $query->where('ft.description', $insuranceType);
        }
        
        // فیلتر کد خانواده
        if (!empty($filters['familyCode'])) {
            $query->where('f.family_code', 'LIKE', '%' . $filters['familyCode'] . '%');
        }
        
        // فیلتر وضعیت پرداخت
        if (!empty($filters['paymentStatus'])) {
            switch($filters['paymentStatus']) {
                case 'paid':
                    $query->whereNotNull('ia.paid_at')->where('ia.paid_at', '!=', '');
                    break;
                case 'pending':
                    $query->where(function($q) {
                        $q->whereNull('ia.paid_at')->orWhere('ia.paid_at', '=', '');
                    });
                    break;
                // حذف حالت rejected چون ستون rejected_at وجود ندارد
            }
        }
        
        // فیلتر حداقل مبلغ
        if (!empty($filters['minAmount'])) {
            $query->where('ia.amount', '>=', $filters['minAmount']);
        }
        
        // فیلتر حداکثر مبلغ
        if (!empty($filters['maxAmount'])) {
            $query->where('ia.amount', '<=', $filters['maxAmount']);
        }

        return $query->groupBy([
                'allocation_date',
                'issue_date', 
                'paid_date',
                'insurance_type'
            ])
            ->orderBy('allocation_date', 'desc')
            ->orderBy('insurance_type')
            ->get();
    }

    /**
     * دریافت خلاصه خسارات به تفکیک ماه
     */
    public static function getMonthlySummary($year = null, $insuranceType = null)
    {
        // تبدیل سال جلالی به میلادی
        $gregorianYear = $year ? $year - 621 : date('Y');
        
        $query = DB::table('insurance_allocations as ia')
            ->leftJoin('funding_transactions as ft', 'ia.funding_transaction_id', '=', 'ft.id')
            ->select([
                DB::raw('YEAR(ia.created_at) as year'),
                DB::raw('MONTH(ia.created_at) as month'),
                DB::raw('MONTHNAME(ia.created_at) as month_name'),
                'ft.description as insurance_type',
                DB::raw('COUNT(DISTINCT ia.family_id) as family_count'),
                DB::raw('COUNT(ia.id) as total_claims'),
                DB::raw('SUM(ia.amount) as total_amount')
            ])
            ->whereYear('ia.created_at', $gregorianYear)
            ->whereNotNull('ia.amount')
            ->where('ia.amount', '>', 0);
        
        // فیلتر نوع بیمه
        if ($insuranceType) {
            $query->where('ft.description', $insuranceType);
        }
        
        return $query->groupBy([
                'year',
                'month', 
                'month_name',
                'insurance_type'
            ])
            ->orderBy('month')
            ->orderBy('insurance_type')
            ->get();
    }

    /**
     * دریافت آمار کلی خسارات
     */
    public static function getOverallStats($startDate = null, $endDate = null, $insuranceType = null)
    {
        $query = DB::table('insurance_allocations as ia')
            ->leftJoin('funding_transactions as ft', 'ia.funding_transaction_id', '=', 'ft.id')
            ->select([
                DB::raw('COUNT(DISTINCT ia.family_id) as total_families'),
                DB::raw('COUNT(ia.id) as total_claims'),
                DB::raw('SUM(ia.amount) as total_amount'),
                DB::raw('AVG(ia.amount) as average_claim_amount'),
                DB::raw('MAX(ia.created_at) as last_claim_date'),
                DB::raw('MIN(ia.created_at) as first_claim_date'),
                // آمار به تفکیک نوع بیمه
                DB::raw('COUNT(DISTINCT CASE WHEN ft.description IS NOT NULL THEN ft.description END) as insurance_types_count')
            ])
            ->whereNotNull('ia.amount')
            ->where('ia.amount', '>', 0);
        
        // فیلتر تاریخ شروع (تبدیل از جلالی به میلادی)
        if ($startDate) {
            $gregorianStartDate = \Morilog\Jalali\CalendarUtils::createDatetimeFromFormat('Y/m/d', $startDate)->format('Y-m-d');
            $query->whereDate('ia.created_at', '>=', $gregorianStartDate);
        }
        
        // فیلتر تاریخ پایان (تبدیل از جلالی به میلادی)
        if ($endDate) {
            $gregorianEndDate = \Morilog\Jalali\CalendarUtils::createDatetimeFromFormat('Y/m/d', $endDate)->format('Y-m-d');
            $query->whereDate('ia.created_at', '<=', $gregorianEndDate);
        }
        
        // فیلتر نوع بیمه
        if ($insuranceType) {
            $query->where('ft.description', $insuranceType);
        }
        
        return $query->first();
    }

    /**
     * دریافت Top خانواده‌ها از نظر مبلغ خسارت
     */
    public static function getTopFamiliesByClaims($limit = 10, $startDate = null, $endDate = null, $insuranceType = null)
    {
        $query = DB::table('insurance_allocations as ia')
            ->join('families as f', 'ia.family_id', '=', 'f.id')
            ->leftJoin('funding_transactions as ft', 'ia.funding_transaction_id', '=', 'ft.id')
            ->leftJoin('members as m', function($join) {
                $join->on('f.id', '=', 'm.family_id')
                     ->where('m.is_head', '=', 1);
            })
            ->select([
                'f.family_code',
                DB::raw('CONCAT(m.first_name, " ", m.last_name) as head_name'),
                'm.mobile',
                DB::raw('COUNT(ia.id) as claims_count'),
                DB::raw('SUM(ia.amount) as total_claims_amount'),
                DB::raw('AVG(ia.amount) as average_claim_amount'),
                DB::raw('MAX(ia.created_at) as last_claim_date')
            ])
            ->whereNotNull('ia.amount')
            ->where('ia.amount', '>', 0);
        
        // فیلتر تاریخ شروع (تبدیل از جلالی به میلادی)
        if ($startDate) {
            $gregorianStartDate = \Morilog\Jalali\CalendarUtils::createDatetimeFromFormat('Y/m/d', $startDate)->format('Y-m-d');
            $query->whereDate('ia.created_at', '>=', $gregorianStartDate);
        }
        
        // فیلتر تاریخ پایان (تبدیل از جلالی به میلادی)
        if ($endDate) {
            $gregorianEndDate = \Morilog\Jalali\CalendarUtils::createDatetimeFromFormat('Y/m/d', $endDate)->format('Y-m-d');
            $query->whereDate('ia.created_at', '<=', $gregorianEndDate);
        }
        
        // فیلتر نوع بیمه
        if ($insuranceType) {
            $query->where('ft.description', $insuranceType);
        }
        
        return $query->groupBy([
                'f.id',
                'f.family_code',
                'm.first_name',
                'm.last_name',
                'm.mobile'
            ])
            ->orderBy('total_claims_amount', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * دریافت خلاصه خسارات به تفکیک نوع بیمه
     */
    public static function getSummaryByInsuranceType($startDate = null, $endDate = null, $filters = [])
    {
        $query = DB::table('insurance_allocations as ia')
            ->leftJoin('funding_transactions as ft', 'ia.funding_transaction_id', '=', 'ft.id')
            ->select([
                'ft.description as insurance_type',
                DB::raw('COUNT(DISTINCT ia.family_id) as family_count'),
                DB::raw('COUNT(ia.id) as total_claims'),
                DB::raw('SUM(ia.amount) as total_amount'),
                DB::raw('AVG(ia.amount) as average_amount'),
                DB::raw('MIN(ia.amount) as min_amount'),
                DB::raw('MAX(ia.amount) as max_amount')
            ])
            ->whereNotNull('ia.amount')
            ->where('ia.amount', '>', 0);

        // فیلتر تاریخ شروع
        if ($startDate) {
            $gregorianStartDate = \Morilog\Jalali\CalendarUtils::createDatetimeFromFormat('Y/m/d', $startDate)->format('Y-m-d');
            $query->whereDate('ia.created_at', '>=', $gregorianStartDate);
        }
        
        // فیلتر تاریخ پایان
        if ($endDate) {
            $gregorianEndDate = \Morilog\Jalali\CalendarUtils::createDatetimeFromFormat('Y/m/d', $endDate)->format('Y-m-d');
            $query->whereDate('ia.created_at', '<=', $gregorianEndDate);
        }
        
        // اعمال فیلترهای اضافی
        self::applyAdditionalFilters($query, $filters);

        return $query->groupBy('insurance_type')
            ->orderBy('total_amount', 'desc')
            ->get();
    }
    
    /**
     * دریافت خلاصه خسارات به تفکیک وضعیت
     */
    public static function getSummaryByStatus($startDate = null, $endDate = null, $insuranceType = null, $filters = [])
    {
        $query = DB::table('insurance_allocations as ia')
            ->leftJoin('funding_transactions as ft', 'ia.funding_transaction_id', '=', 'ft.id')
            ->select([
                DB::raw("CASE 
                    WHEN ia.paid_at IS NOT NULL AND ia.paid_at != '' THEN 'پرداخت شده'
                    ELSE 'در انتظار پرداخت'
                END as payment_status"),
                DB::raw('COUNT(DISTINCT ia.family_id) as family_count'),
                DB::raw('COUNT(ia.id) as total_claims'),
                DB::raw('SUM(ia.amount) as total_amount'),
                DB::raw('AVG(ia.amount) as average_amount')
            ])
            ->whereNotNull('ia.amount')
            ->where('ia.amount', '>', 0);

        // فیلترهای تاریخ و نوع بیمه
        if ($startDate) {
            $gregorianStartDate = \Morilog\Jalali\CalendarUtils::createDatetimeFromFormat('Y/m/d', $startDate)->format('Y-m-d');
            $query->whereDate('ia.created_at', '>=', $gregorianStartDate);
        }
        
        if ($endDate) {
            $gregorianEndDate = \Morilog\Jalali\CalendarUtils::createDatetimeFromFormat('Y/m/d', $endDate)->format('Y-m-d');
            $query->whereDate('ia.created_at', '<=', $gregorianEndDate);
        }
        
        if ($insuranceType) {
            $query->where('ft.description', $insuranceType);
        }
        
        // اعمال فیلترهای اضافی
        self::applyAdditionalFilters($query, $filters);

        return $query->groupBy('payment_status')
            ->orderBy('total_claims', 'desc')
            ->get();
    }
    
    /**
     * اعمال فیلترهای اضافی به کوئری
     */
    private static function applyAdditionalFilters($query, $filters)
    {
        // فیلتر کد خانواده
        if (!empty($filters['familyCode'])) {
            $query->join('families as f2', 'ia.family_id', '=', 'f2.id')
                  ->where('f2.family_code', 'LIKE', '%' . $filters['familyCode'] . '%');
        }
        
        // فیلتر وضعیت پرداخت
        if (!empty($filters['paymentStatus'])) {
            switch($filters['paymentStatus']) {
                case 'paid':
                    $query->whereNotNull('ia.paid_at')->where('ia.paid_at', '!=', '');
                    break;
                case 'pending':
                    $query->where(function($q) {
                        $q->whereNull('ia.paid_at')->orWhere('ia.paid_at', '=', '');
                    });
                    break;
                // حذف حالت rejected چون ستون rejected_at وجود ندارد
            }
        }
        
        // فیلتر حداقل مبلغ
        if (!empty($filters['minAmount'])) {
            $query->where('ia.amount', '>=', $filters['minAmount']);
        }
        
        // فیلتر حداکثر مبلغ
        if (!empty($filters['maxAmount'])) {
            $query->where('ia.amount', '<=', $filters['maxAmount']);
        }
    }
}
