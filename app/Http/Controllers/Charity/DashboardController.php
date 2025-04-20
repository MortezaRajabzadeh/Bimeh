<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * نمایش داشبورد خیریه
     * کامپوننت‌های لایوویر به صورت خودکار بارگذاری می‌شوند
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('charity.dashboard');
    }
    
    /**
     * نمایش خانواده‌های بیمه شده
     * 
     * @return \Illuminate\View\View
     */
    public function insuredFamilies()
    {
        return view('charity.insured-families');
    }
    
    /**
     * نمایش خانواده‌های بدون پوشش بیمه
     * 
     * @return \Illuminate\View\View
     */
    public function uninsuredFamilies()
    {
        return view('charity.uninsured-families');
    }
    
    /**
     * فرم افزودن خانواده جدید
     * 
     * @return \Illuminate\View\View
     */
    public function addFamily()
    {
        return view('charity.add-family');
    }
}
