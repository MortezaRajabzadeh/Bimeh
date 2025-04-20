<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Region;

class DashboardController extends Controller
{
    /**
     * نمایش داشبورد خیریه
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $charity_id = Auth::user()->organization_id;
        
        // بررسی مقدار charity_id
        Log::info('DashboardController - Charity ID', ['charity_id' => $charity_id]);
        
        $query = Family::where('charity_id', $charity_id);
        
        // اعمال فیلترها
        if ($request->filled('q')) {
            // جستجو در کد خانواده و نام سرپرست خانوار
            $searchQuery = $request->input('q');
            $query->where(function ($q) use ($searchQuery) {
                $q->where('family_code', 'LIKE', '%' . $searchQuery . '%')
                  ->orWhereHas('members', function ($mq) use ($searchQuery) {
                      $mq->where('is_head', true)
                         ->where(function ($sq) use ($searchQuery) {
                             $sq->where('first_name', 'LIKE', '%' . $searchQuery . '%')
                                ->orWhere('last_name', 'LIKE', '%' . $searchQuery . '%');
                         });
                  });
            });
        }
        
        // فیلتر وضعیت بیمه
        if ($request->filled('status')) {
            if ($request->input('status') === 'insured') {
                $query->where('is_insured', true);
            } elseif ($request->input('status') === 'uninsured') {
                $query->where('is_insured', false);
            }
        }
        
        // فیلتر منطقه
        if ($request->filled('region')) {
            $query->where('region_id', $request->input('region'));
        }
        
        // دریافت تعداد کل رکوردها قبل از صفحه‌بندی
        $totalFamilies = $query->count();
        Log::info('DashboardController - Total families count before pagination', ['count' => $totalFamilies]);
        
        // دریافت خانواده‌ها با صفحه‌بندی
        $families = $query->with(['region', 'members' => function ($q) {
                $q->where('is_head', true);
            }])
            ->latest()
            ->paginate(10)
            ->withQueryString();
            
        // بررسی تعداد خانواده‌های دریافت شده
        Log::info('DashboardController - Families count after pagination', [
            'count' => $families->count(),
            'total' => $families->total(),
            'has_items' => $families->isNotEmpty()
        ]);
        
        // برای تست: اگر داده‌ای وجود ندارد، داده تستی اضافه کنیم
        if ($families->isEmpty()) {
            Log::warning('DashboardController - No families found, using test data');
            
            // بررسی کل جدول خانواده‌ها (بدون فیلتر)
            $allFamiliesCount = Family::count();
            Log::info('DashboardController - Total families in database', ['count' => $allFamiliesCount]);
            
            // چندتا داده تستی برای نمایش
            $testFamilies = [];
            for ($i = 1; $i <= 5; $i++) {
                $testFamily = new Family();
                $testFamily->id = $i;
                $testFamily->family_code = 'TEST' . $i;
                $testFamily->is_insured = ($i % 2 == 0);
                $testFamily->created_at = now();
                
                // اضافه کردن region
                $testRegion = new Region();
                $testRegion->name = 'منطقه تست ' . $i;
                $testRegion->province = 'استان تست';
                $testFamily->setRelation('region', $testRegion);
                
                // ساخت کالکشن اعضا
                $members = new \Illuminate\Database\Eloquent\Collection();
                $headMember = new Member();
                $headMember->first_name = 'نام';
                $headMember->last_name = 'خانوادگی ' . $i;
                $headMember->is_head = true;
                $headMember->is_insured = ($i % 2 == 0);
                $members->push($headMember);
                
                // اضافه کردن ۲ عضو دیگر
                for ($j = 1; $j <= 2; $j++) {
                    $member = new Member();
                    $member->first_name = 'عضو ' . $j;
                    $member->last_name = 'خانوادگی ' . $i;
                    $member->is_head = false;
                    $member->is_insured = ($i % 2 == 0);
                    $members->push($member);
                }
                
                $testFamily->setRelation('members', $members);
                $testFamilies[] = $testFamily;
            }
            
            // تبدیل به کالکشن
            $families = new \Illuminate\Pagination\LengthAwarePaginator(
                collect($testFamilies),
                count($testFamilies),
                10,
                1
            );
        }
        
        // آمار کلی
        $insuredFamilies = Family::where('charity_id', $charity_id)
            ->where('is_insured', true)
            ->count();
            
        $insuredMembers = Member::whereHas('family', function($query) use ($charity_id) {
            $query->where('charity_id', $charity_id)
                ->where('is_insured', true);
        })->count();
        
        $uninsuredFamilies = Family::where('charity_id', $charity_id)
            ->where('is_insured', false)
            ->count();
            
        $uninsuredMembers = Member::whereHas('family', function($query) use ($charity_id) {
            $query->where('charity_id', $charity_id)
                ->where('is_insured', false);
        })->count();
        
        // لیست مناطق برای فیلتر
        $regions = Region::active()->get();
        
        // بررسی مقادیر ارسالی به ویو
        Log::info('DashboardController - Data sent to view', [
            'families_count' => $families->count(),
            'regions_count' => $regions->count(),
            'insuredFamilies' => $insuredFamilies,
            'insuredMembers' => $insuredMembers,
            'uninsuredFamilies' => $uninsuredFamilies,
            'uninsuredMembers' => $uninsuredMembers
        ]);
        
        return view('charity.dashboard', compact(
            'families',
            'regions',
            'insuredFamilies', 
            'insuredMembers', 
            'uninsuredFamilies', 
            'uninsuredMembers'
        ));
    }
} 