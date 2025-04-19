@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="alert alert-danger mb-4">
                توجه: سیستم شناسایی خیریه‌ها و بیمه‌ها در حال تکمیل است. لطفا پس از ورود، اطلاعات خود را با دقت کامل نمایید.
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">انتخاب نوع کاربر</div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('login') }}?type=charity" class="btn btn-outline-primary btn-lg btn-block p-4">
                                <i class="fas fa-hands-helping mb-2 d-block" style="font-size: 2rem;"></i>
                                ورود با دسترسی خیریه
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('login') }}?type=insurance" class="btn btn-outline-primary btn-lg btn-block p-4">
                                <i class="fas fa-shield-alt mb-2 d-block" style="font-size: 2rem;"></i>
                                ورود با دسترسی شرکت بیمه
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('login') }}?type=admin" class="btn btn-outline-primary btn-lg btn-block p-4">
                                <i class="fas fa-cogs mb-2 d-block" style="font-size: 2rem;"></i>
                                ورود برای انجام تنظیمات
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ route('logs') }}" class="btn btn-outline-primary btn-lg btn-block p-4">
                                <i class="fas fa-history mb-2 d-block" style="font-size: 2rem;"></i>
                                لاگ تغییرات
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 