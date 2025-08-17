<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LandingController extends Controller
{
    /**
     * نمایش صفحه لندینگ میکروبیمه
     */
    public function index()
    {
        return view('landing.index');
    }
}
