<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use App\Traits\HandlesImageUploads;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
            // حذف لوگوی قبلی در صورت وجود
            $this->deleteImageIfExists($organization->logo_path);
            
            // آپلود و بهینه‌سازی تصویر جدید
            $organization->logo_path = $this->uploadAndOptimizeImage(
                $request->file('logo'),
                'charities/logos',
                300, // عرض
                300, // ارتفاع
                80   // کیفیت (0-100)
            );
        }

        // ذخیره تغییرات
        $organization->save();

        return redirect()->route('charity.settings')->with('success', 'تنظیمات خیریه با موفقیت به‌روزرسانی شد.');
    }
} 
