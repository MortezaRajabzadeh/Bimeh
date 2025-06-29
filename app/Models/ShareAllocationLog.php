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
        'file_hash', // ✅ اضافه کردن برای جلوگیری از تکرار
        'created_count', // ✅ تعداد رکوردهای ایجاد شده
        'updated_count', // ✅ تعداد رکوردهای به‌روزرسانی شده
        'skipped_count', // ✅ تعداد رکوردهای رد شده
        'error_count', // ✅ تعداد خطاها
        'errors', // ✅ لیست خطاها
    ];

    protected $casts = [
        'family_ids' => 'array',
        'shares_data' => 'array',
        'errors' => 'array', // ✅ اضافه کردن
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
     * ✅ محاسبه درصد موفقیت
     */
    public function getSuccessRateAttribute()
    {
        $total = $this->created_count + $this->updated_count + $this->error_count;
        if ($total === 0) return 0;

        return round(($this->created_count + $this->updated_count) / $total * 100, 2);
    }

    /**
     * ✅ بررسی تکراری بودن بر اساس hash فایل
     */
    public static function isDuplicateByFileHash($fileHash)
    {
        return static::where('file_hash', $fileHash)->exists();
    }

    /**
     * ✅ ایجاد لاگ جدید با اطلاعات کامل
     */
    public static function createAllocationLog(array $data)
    {
        return static::create([
            'user_id' => auth()->id(),
            'batch_id' => $data['batch_id'] ?? 'batch_' . time(),
            'description' => $data['description'] ?? 'تخصیص سهم‌بندی بیمه',
            'families_count' => count($data['family_ids'] ?? []),
            'family_ids' => $data['family_ids'] ?? [],
            'shares_data' => $data['shares_data'] ?? [],
            'total_amount' => $data['total_amount'] ?? 0,
            'status' => $data['status'] ?? 'pending',
            'file_hash' => $data['file_hash'] ?? null,
            'created_count' => $data['created_count'] ?? 0,
            'updated_count' => $data['updated_count'] ?? 0,
            'skipped_count' => $data['skipped_count'] ?? 0,
            'error_count' => $data['error_count'] ?? 0,
            'errors' => $data['errors'] ?? [],
        ]);
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
