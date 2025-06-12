<?php

namespace App\Helpers;

use App\Models\Family;

class FamilyValidationHelper
{
    /**
     * دریافت کلاس‌های CSS برای وضعیت
     */
    public static function getStatusClasses(string $status): array
    {
        return config("ui.status_colors.{$status}", config('ui.status_colors.unknown'));
    }

    /**
     * دریافت توضیحات وضعیت
     */
    public static function getStatusDescription(string $status): string
    {
        $descriptions = [
            'complete' => 'تمام اطلاعات کامل و معتبر است',
            'partial' => 'برخی اطلاعات ناقص است',
            'none' => 'اطلاعات ناقص یا نامعتبر است',
            'unknown' => 'وضعیت نامشخص است'
        ];

        return $descriptions[$status] ?? $descriptions['unknown'];
    }

    /**
     * محاسبه نمره کلی خانواده بر اساس تمام معیارها
     */
    public static function calculateOverallScore(Family $family): array
    {
        $validationData = $family->getAllValidationStatuses();
        
        $scores = [
            'identity' => self::getScoreForStatus($validationData['identity']['status'], $validationData['identity']['percentage'] ?? 0),
            'location' => self::getScoreForStatus($validationData['location']['status']),
            'documents' => self::getScoreForStatus($validationData['documents']['status'], $validationData['documents']['percentage'] ?? 0),
        ];

        $averageScore = array_sum($scores) / count($scores);
        
        // تعیین وضعیت کلی
        if ($averageScore >= 80) {
            $overallStatus = 'complete';
        } elseif ($averageScore >= 50) {
            $overallStatus = 'partial';
        } else {
            $overallStatus = 'none';
        }

        return [
            'overall_status' => $overallStatus,
            'overall_score' => round($averageScore),
            'individual_scores' => $scores,
            'validation_data' => $validationData
        ];
    }

    /**
     * تبدیل وضعیت به نمره عددی
     */
    private static function getScoreForStatus(string $status, int $percentage = null): int
    {
        switch ($status) {
            case 'complete':
                return 100;
            case 'partial':
                return $percentage ?? 50;
            case 'none':
                return $percentage ?? 0;
            case 'unknown':
                return 25; // نمره میانه برای حالت نامشخص
            default:
                return 0;
        }
    }

    /**
     * دریافت پیام‌های کاربرپسند برای نمایش
     */
    public static function getUserFriendlyMessages(array $validationData): array
    {
        $messages = [];

        // پیام اطلاعات هویتی
        $identity = $validationData['identity'];
        if ($identity['status'] === 'complete') {
            $messages[] = '✅ اطلاعات هویتی همه اعضا کامل است';
        } elseif ($identity['status'] === 'partial') {
            $messages[] = "⚠️ اطلاعات {$identity['complete_members']} از {$identity['total_members']} عضو کامل است";
        } else {
            $messages[] = '❌ اطلاعات هویتی اعضا ناقص است';
        }

        // پیام موقعیت جغرافیایی
        $location = $validationData['location'];
        if ($location['status'] === 'complete') {
            $messages[] = '✅ منطقه غیرمحروم تایید شده';
        } elseif ($location['status'] === 'none') {
            $messages[] = '🔴 منطقه محروم - نیاز به توجه ویژه';
        } else {
            $messages[] = '⚪ وضعیت منطقه جغرافیایی نامشخص';
        }

        // پیام مدارک
        $documents = $validationData['documents'];
        if ($documents['members_requiring_docs'] === 0) {
            $messages[] = '✅ نیازی به مدرک خاص نیست';
        } elseif ($documents['status'] === 'complete') {
            $messages[] = '✅ مدارک تمام اعضای نیازمند کامل است';
        } elseif ($documents['status'] === 'partial') {
            $messages[] = "⚠️ مدارک {$documents['members_with_complete_docs']} از {$documents['members_requiring_docs']} عضو نیازمند کامل است";
        } else {
            $messages[] = '❌ مدارک مورد نیاز آپلود نشده';
        }

        return $messages;
    }

    /**
     * دریافت لیست اقدامات پیشنهادی برای بهبود وضعیت
     */
    public static function getSuggestedActions(array $validationData): array
    {
        $actions = [];

        // اقدامات برای اطلاعات هویتی
        $identity = $validationData['identity'];
        if ($identity['status'] !== 'complete') {
            foreach ($identity['details'] as $member) {
                if ($member['completion_rate'] < 100) {
                    $missingFields = [];
                    foreach ($member['field_status'] as $field => $isComplete) {
                        if (!$isComplete) {
                            $fieldLabels = [
                                'first_name' => 'نام',
                                'last_name' => 'نام خانوادگی',
                                'national_code' => 'کد ملی',
                                'birth_date' => 'تاریخ تولد'
                            ];
                            $missingFields[] = $fieldLabels[$field] ?? $field;
                        }
                    }
                    if (!empty($missingFields)) {
                        $actions[] = "تکمیل " . implode('، ', $missingFields) . " برای " . $member['name'];
                    }
                }
            }
        }

        // اقدامات برای موقعیت جغرافیایی
        $location = $validationData['location'];
        if ($location['status'] === 'unknown') {
            $actions[] = 'تعیین و ثبت موقعیت جغرافیایی صحیح خانواده';
        }

        // اقدامات برای مدارک
        $documents = $validationData['documents'];
        if ($documents['status'] !== 'complete' && $documents['members_requiring_docs'] > 0) {
            foreach ($documents['details'] as $member) {
                if ($member['completion_rate'] < 100) {
                    $missingDocs = [];
                    foreach ($member['doc_status'] as $docType => $docInfo) {
                        if ($docInfo['required'] && !$docInfo['uploaded']) {
                            $missingDocs[] = $docInfo['label'];
                        }
                    }
                    if (!empty($missingDocs)) {
                        $actions[] = "آپلود " . implode('، ', $missingDocs) . " برای " . $member['name'];
                    }
                }
            }
        }

        return $actions;
    }

    /**
     * بررسی اینکه آیا خانواده آماده تایید نهایی است
     */
    public static function isReadyForApproval(Family $family): array
    {
        $overallData = self::calculateOverallScore($family);
        $isReady = $overallData['overall_score'] >= 75; // حداقل ۷۵ درصد برای تایید

        return [
            'is_ready' => $isReady,
            'score' => $overallData['overall_score'],
            'status' => $overallData['overall_status'],
            'message' => $isReady 
                ? 'خانواده آماده تایید نهایی است' 
                : 'نیاز به تکمیل اطلاعات بیشتر دارد',
            'required_actions' => $isReady 
                ? [] 
                : self::getSuggestedActions($overallData['validation_data'])
        ];
    }
} 
