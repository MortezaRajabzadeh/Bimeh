<?php

namespace App\Http\Controllers\Charity;

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
     * نمایش صفحه تنظیمات خیریه
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('charity.settings.index');
    }

    /**
     * ذخیره تنظیمات خیریه
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        // اعتبارسنجی داده‌های ورودی
        $validatedData = $request->validate([
            'charity_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:2000',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
        ]);
    
        // دریافت سازمان کاربر جاری
        $organization = Auth::user()->organization;
        
        if (!$organization) {
            return redirect()->back()->with('error', 'خیریه شما یافت نشد.');
        }
    
        // به‌روزرسانی اطلاعات سازمان
        $organization->name = $validatedData['charity_name'];
        $organization->email = $validatedData['email'];
        $organization->phone = $validatedData['phone'];
        $organization->address = $validatedData['address'];
        $organization->description = $validatedData['description'];
    
        // پردازش آپلود لوگو در صورت وجود
        if ($request->hasFile('logo')) {
            try {
                // حذف لوگوی قبلی در صورت وجود
                if ($organization->logo_path) {
                    // بررسی وجود فایل در storage قبل از حذف
                    if (Storage::disk('public')->exists($organization->logo_path)) {
                        Storage::disk('public')->delete($organization->logo_path);
                    }
                }
                
                // آپلود فایل جدید با استفاده از Storage disk public
                $file = $request->file('logo');
                $filename = 'org-' . $organization->id . '-' . uniqid() . '.webp';
                $path = 'organizations/logos/' . $filename;
                
                // بهینه‌سازی و ذخیره تصویر با Intervention Image
                $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                $image = $manager->read($file)
                    ->resize(300, 300, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })
                    ->toWebp(80);
                
                // ذخیره در storage/app/public. متد put باید به صورت خودکار پوشه را ایجاد کند.
                Storage::disk('public')->put($path, (string) $image);

                // بررسی مجدد برای اطمینان از ذخیره شدن فایل
                if (!Storage::disk('public')->exists($path)) {
                    // اگر فایل وجود نداشت، یک خطای دقیق‌تر ثبت می‌کنیم
                    Log::error('فایل پس از آپلود یافت نشد', [
                        'organization_id' => $organization->id,
                        'path' => $path,
                        'storage_path' => Storage::disk('public')->path($path)
                    ]);
                    throw new \Exception('فایل پس از آپلود در مسیر مورد نظر یافت نشد. لطفاً مجوزهای پوشه storage را بررسی کنید.');
                }
                
                $organization->logo_path = $path;
                
                Log::info('لوگو با موفقیت در storage آپلود شد', [
                    'organization_id' => $organization->id,
                    'path' => $path,
                    'storage_path' => Storage::disk('public')->path($path)
                ]);
            } catch (\Exception $e) {
                Log::error('خطا در آپلود لوگو', [
                    'error' => $e->getMessage(),
                    'organization_id' => $organization->id,
                    'trace' => $e->getTraceAsString()
                ]);
                
                return redirect()->back()->with('error', 'خطا در آپلود لوگو: ' . $e->getMessage());
            }
            
            // به‌روزرسانی timestamp برای cache busting
            $organization->touch();
        }
    
        // ذخیره تغییرات
        $organization->save();
    
        return redirect()->route('charity.settings')
            ->with('success', 'تنظیمات خیریه با موفقیت به‌روزرسانی شد.')
            ->with('logo_updated', true); // اضافه کردن flag برای refresh
    }
}
