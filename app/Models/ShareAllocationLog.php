<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class ShareAllocationLog extends Model
{
    use HasFactory;

    /**
     * نام جدول در دیتابیس.
     *
     * @var string
     */
    protected $table = 'share_allocation_logs';

    /**
     * فیلدهایی که به صورت گروهی قابل تخصیص هستند (Mass Assignable).
     *
     * @var array<int, string>
     */


     protected $fillable = [
        'user_id',
        'batch_id',
        'description',
        'families_count',
        'family_ids',
        'shares_data',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'family_ids' => 'array',
        'shares_data' => 'array',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * مقادیر پیش‌فرض برای فیلدها
     */
    protected $attributes = [
        'shares_data' => '[]',
        'family_ids' => '[]',
        'total_amount' => 0,
        'status' => 'pending',
    ];



    /**
     * ✅ اسکوپ برای فیلتر کردن لاگ‌ها بر اساس شناسه خانواده
     */
    public function scopeForFamily($query, $familyId)
    {
        try {
            return $query->whereRaw('JSON_CONTAINS(family_ids, CAST(? AS JSON))', [json_encode($familyId)]);
        } catch (\Exception $e) {
            Log::error('خطا در جستجوی خانواده در لاگ‌های تخصیص سهم: ' . $e->getMessage(), [
                'family_id' => $familyId,
                'trace' => $e->getTraceAsString()
            ]);

            return $query->whereRaw('1 = 0');
        }
    }


    /**
     * رابطه با کاربری که این عملیات را ثبت کرده است.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // در مدل ShareAllocationLog
    /**
     * اسکوپ برای فیلتر کردن لاگ‌ها بر اساس شناسه خانواده
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $familyId شناسه خانواده
     * @return \Illuminate\Database\Eloquent\Builder
     */


    /**
     * ✅ اسکوپ برای فیلتر بر اساس وضعیت
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * ✅ اسکوپ برای عملیات‌های موفق
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * ✅ اسکوپ برای عملیات‌های ناموفق
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }


    /**
     * ✅ رابطه با سهم‌های ایجاد شده
     */
    public function insuranceShares()
    {
        return InsuranceShare::where('import_log_id', $this->id)->get();
    }


    /**
     * یک متد کمکی برای دریافت خانواده‌های مرتبط با این لاگ.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function families()
    {
        // از آنجایی که family_ids به صورت آرایه ذخیره شده، می‌توانیم از whereIn استفاده کنیم.
        return Family::whereIn('id', $this->family_ids ?? [])->get();
    }
}
