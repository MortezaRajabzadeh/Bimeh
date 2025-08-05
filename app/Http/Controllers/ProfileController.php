<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * نمایش فرم ویرایش پروفایل کاربر
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * بروزرسانی اطلاعات پروفایل کاربر
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // پردازش آپلود لوگوی سازمان در صورت وجود
        if ($request->hasFile('organization_logo')) {
            try {
                $organization = $request->user()->organization;
                if ($organization) {
                    $logoPath = $organization->uploadLogo($request->file('organization_logo'));
                    if ($logoPath) {
                        $organization->logo_path = $logoPath;
                        $organization->save();
                    }
                }
            } catch (\Exception $e) {
                Log::error('خطا در آپلود لوگوی سازمان', [
                    'user_id' => $request->user()->id,
                    'organization_id' => $request->user()->organization?->id,
                    'error' => $e->getMessage()
                ]);
                return Redirect::route('profile.edit')->with('error', 'خطا در آپلود لوگو. لطفاً دوباره تلاش کنید.');
            }
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * حذف حساب کاربری
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
