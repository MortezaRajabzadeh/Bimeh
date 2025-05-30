<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class MemberDocumentController extends Controller
{
    /**
     * نمایش فرم آپلود مدارک
     */
    public function showUploadForm(Family $family, Member $member)
    {
        // بررسی احراز هویت کاربر
        if (!Auth::check()) {
            abort(401, 'برای دسترسی به این صفحه باید وارد شوید');
        }

        // فقط بررسی می‌کنیم که عضو متعلق به خانواده باشد
        if ($member->family_id !== $family->id) {
            abort(404, 'عضو خانواده پیدا نشد');
        }

        return view('charity.members.documents-upload', [
            'family' => $family,
            'member' => $member,
            'specialDiseaseDocument' => $member->getFirstMedia('special_disease_documents'),
            'disabilityDocument' => $member->getFirstMedia('disability_documents'),
        ]);
    }

    /**
     * آپلود مدارک
     */
    public function store(Request $request, Family $family, Member $member)
    {
        // بررسی احراز هویت کاربر
        if (!Auth::check()) {
            abort(401, 'برای دسترسی به این صفحه باید وارد شوید');
        }

        // فقط بررسی می‌کنیم که عضو متعلق به خانواده باشد
        if ($member->family_id !== $family->id) {
            abort(404, 'عضو خانواده پیدا نشد');
        }

        // اعتبارسنجی داده‌ها
        $validated = $request->validate([
            'document_type' => ['required', 'in:special_disease,disability'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // حداکثر 5 مگابایت
        ]);

        // تعیین مجموعه مدیا برای آپلود
        $mediaCollection = $validated['document_type'] === 'special_disease' 
            ? 'special_disease_documents' 
            : 'disability_documents';

        // حذف فایل قبلی (در صورت وجود)
        $member->clearMediaCollection($mediaCollection);

        // آپلود فایل جدید
        $member->addMediaFromRequest('document')
            ->usingName($member->full_name . ' - ' . ($validated['document_type'] === 'special_disease' ? 'مدرک بیماری خاص' : 'مدرک معلولیت'))
            ->usingFileName(uniqid() . '-' . $request->file('document')->getClientOriginalName())
            ->toMediaCollection($mediaCollection);

        // بروزرسانی وضعیت اطلاعات ناقص عضو (در صورت نیاز)
        $this->updateMemberIncompleteStatus($member, $validated['document_type']);

        return redirect()->route('charity.families.show', $family)
            ->with('success', 'مدرک با موفقیت آپلود شد');
    }

    /**
     * بروزرسانی وضعیت اطلاعات ناقص عضو
     */
    private function updateMemberIncompleteStatus(Member $member, string $documentType)
    {
        // اگر عضو دارای اطلاعات ناقص باشد
        if ($member->has_incomplete_data) {
            $incompleteDataDetails = $member->incomplete_data_details ?? [];
            
            // بررسی نوع مدرک
            if ($documentType === 'special_disease' && in_array('special_disease_document', $incompleteDataDetails)) {
                // حذف مورد از لیست اطلاعات ناقص
                $incompleteDataDetails = array_filter($incompleteDataDetails, function ($item) {
                    return $item !== 'special_disease_document';
                });
            } elseif ($documentType === 'disability' && in_array('disability_document', $incompleteDataDetails)) {
                // حذف مورد از لیست اطلاعات ناقص
                $incompleteDataDetails = array_filter($incompleteDataDetails, function ($item) {
                    return $item !== 'disability_document';
                });
            }
            
            // بروزرسانی اطلاعات عضو
            $member->incomplete_data_details = array_values($incompleteDataDetails);
            $member->has_incomplete_data = count($incompleteDataDetails) > 0;
            $member->incomplete_data_updated_at = now();
            $member->save();
        }
    }
} 