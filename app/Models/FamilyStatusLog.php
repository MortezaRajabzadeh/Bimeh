<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\InsuranceWizardStep;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class FamilyStatusLog extends Model
{
    use HasFactory;

    /**
     * ویژگی‌های قابل پر شدن
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'family_id',
        'user_id',
        'from_status',
        'to_status',
        'comments',
        'extra_data',
        'excel_file_id',
        'insurance_share_id',
        'batch_id',
    ];

    /**
     * ویژگی‌هایی که باید به نوع‌های مورد نظر تبدیل شوند.
     *
     * @var array
     */
    protected $casts = [
        'extra_data' => 'array',
    ];

    /**
     * رابطه با خانواده
     *
     * @return BelongsTo
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * رابطه با کاربر
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * رابطه با فایل اکسل
     *
     * @return BelongsTo
     */
    public function excelFile(): BelongsTo
    {
    }

    /**
     * رابطه با سهم بیمه
     *
     * @return BelongsTo
     */
    public function insuranceShare(): BelongsTo
    {
        return $this->belongsTo(InsuranceShare::class);
    }

    /**
     * ثبت لاگ تغییر مرحله
     *
     * @param Family $family
     * @param InsuranceWizardStep|string $fromStatus
     * @param InsuranceWizardStep|string $toStatus
     * @param string|null $comments
     * @param array $extraData
     * @return self
     */
    public static function logTransition(
        Family $family, 
        $fromStatus, 
        $toStatus, 
        ?string $comments = null,
        array $extraData = []
    ) {
        // تبدیل وضعیت‌ها به enum اگر رشته هستند
        if (is_string($fromStatus)) {
            try {
                $fromStatus = InsuranceWizardStep::from($fromStatus);
            } catch (\ValueError $e) {
                // اگر تبدیل ناموفق بود، رشته را به عنوان مقدار استفاده می‌کنیم
                $fromStatusValue = $fromStatus;
            }
        }
        
        if (is_string($toStatus)) {
            try {
                $toStatus = InsuranceWizardStep::from($toStatus);
            } catch (\ValueError $e) {
                // اگر تبدیل ناموفق بود، رشته را به عنوان مقدار استفاده می‌کنیم
                $toStatusValue = $toStatus;
            }
        }
        
        // استخراج مقادیر رشته‌ای از وضعیت‌ها
        if (isset($fromStatusValue)) {
            $fromStatusValue = $fromStatusValue;
        } elseif ($fromStatus instanceof InsuranceWizardStep) {
            $fromStatusValue = $fromStatus->value;
        } else {
            $fromStatusValue = null;
        }
        
        if (isset($toStatusValue)) {
            $toStatusValue = $toStatusValue;
        } elseif ($toStatus instanceof InsuranceWizardStep) {
            $toStatusValue = $toStatus->value;
        } else {
            $toStatusValue = null;
        }
        
        // ایجاد رکورد لاگ
        return self::create([
            'family_id' => $family->id,
            'user_id' => Auth::id(),
            'from_status' => $fromStatusValue,
            'to_status' => $toStatusValue,
            'comments' => $comments,
            'extra_data' => $extraData,
            'excel_file_id' => $extraData['excel_file_id'] ?? null,
            'insurance_share_id' => $extraData['insurance_share_id'] ?? null,
            'batch_id' => $extraData['batch_id'] ?? null,
        ]);
    }

    /**
     * ثبت لاگ تغییر دسته‌جمعی مرحله
     *
     * @param array $familyIds
     * @param InsuranceWizardStep|string $fromStatus
     * @param InsuranceWizardStep|string $toStatus
     * @param string|null $comments
     * @param array $extraData
     * @return array
     */
    public static function logBatchTransition(
        array $familyIds, 
        $fromStatus, 
        $toStatus, 
        ?string $comments = null,
        array $extraData = []
    ): array {
        $batchId = $extraData['batch_id'] ?? 'batch_' . time() . '_' . uniqid();
        $logs = [];

        foreach ($familyIds as $familyId) {
            $family = Family::find($familyId);
            if ($family) {
                $extraData['batch_id'] = $batchId;
                $logs[] = self::logTransition($family, $fromStatus, $toStatus, $comments, $extraData);
            }
        }

        return $logs;
    }
}
