<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Traits\HandlesImageUploads;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    use HandlesImageUploads;
    
    /**
     * نمایش صفحه تنظیمات بیمه
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('insurance.settings');
    }

    /**
     * نمایش صفحه تنظیمات عمومی
     *
     * @return \Illuminate\View\View
     */
    public function general()
    {
        return view('insurance.settings.general');
    }

    /**
     * ذخیره تنظیمات بیمه
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        // اعتبارسنجی داده‌های ورودی
        $validatedData = $request->validate([
            'insurance_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:2000',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
        ]);
    
        // دریافت سازمان کاربر جاری
        $organization = Auth::user()->organization;
        
        if (!$organization) {
            return redirect()->back()->with('error', 'شرکت بیمه شما یافت نشد.');
        }
    
        // به‌روزرسانی اطلاعات سازمان
        $organization->name = $validatedData['insurance_name'];
        $organization->email = $validatedData['email'];
        $organization->phone = $validatedData['phone'];
        $organization->address = $validatedData['address'];
        $organization->description = $validatedData['description'];
    
        // پردازش آپلود لوگو در صورت وجود
        if ($request->hasFile('logo')) {
            try {
                $logoPath = $organization->uploadLogo($request->file('logo'));
                if ($logoPath) {
                    $organization->logo_path = $logoPath;
                }
            } catch (\Exception $e) {
                Log::error('خطا در آپلود لوگوی بیمه', [
                    'organization_id' => $organization->id,
                    'error' => $e->getMessage()
                ]);
                return redirect()->back()->with('error', 'خطا در آپلود لوگو. لطفاً دوباره تلاش کنید.');
            }
        }
    
        // ذخیره تغییرات
        $organization->save();
    
        return redirect()->route('insurance.settings.general')
            ->with('success', 'تنظیمات بیمه با موفقیت به‌روزرسانی شد.')
            ->with('logo_updated', true);
    }
} 