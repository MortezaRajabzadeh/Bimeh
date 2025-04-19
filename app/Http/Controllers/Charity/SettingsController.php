<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * نمایش صفحه تنظیمات خیریه
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('charity.settings');
    }
} 