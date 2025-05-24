<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /**
     * نمایش صفحه انتخاب نوع کاربر
     *
     * @return \Illuminate\View\View
     */
    public function showUserTypeSelection()
    {
        return view('auth.select-user-type');
    }
} 