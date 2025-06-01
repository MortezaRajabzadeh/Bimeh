<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RoleSwitchController extends Controller
{
    /**
     * ذخیره نقش انتخاب شده در سشن و ریدایرکت به داشبورد مربوطه
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // بررسی دسترسی - فقط ادمین‌ها می‌توانند نقش خود را تغییر دهند
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'شما اجازه دسترسی به این بخش را ندارید.');
        }

        // اعتبارسنجی نقش ورودی
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:charity,insurance'],
        ]);

        // ذخیره نقش در سشن
        session(['impersonate_as' => $validated['role']]);

        // ریدایرکت به داشبورد مربوطه
        return redirect()->route($validated['role'] . '.dashboard')
            ->with('status', 'نقش شما موقتاً به ' . $validated['role'] . ' تغییر کرد.');
    }

    /**
     * خروج از حالت نقش موقت و بازگشت به نقش ادمین
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        // حذف نقش از سشن
        $request->session()->forget('impersonate_as');

        // ریدایرکت به داشبورد ادمین
        return redirect()->route('admin.dashboard')
            ->with('status', 'شما به نقش ادمین بازگشتید.');
    }
}