<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * تبدیل خودکار نوع داده‌ها.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'family_ids' => 'array',    // این ستون را به صورت آرایه PHP در نظر می‌گیرد
        'shares_data' => 'array',   // این ستون را هم به صورت آرایه PHP در نظر می‌گیرد
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * رابطه با کاربری که این عملیات را ثبت کرده است.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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