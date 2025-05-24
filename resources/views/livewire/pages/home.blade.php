<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.app')] class extends Component
{
    public $appToggle = false;
    
    public function mount(): void
    {
        // اینجا می‌توانید داده‌های اولیه را از دیتابیس بارگذاری کنید
        // مثلاً وضعیت فعال بودن اپلیکیشن برای کاربر فعلی
    }
    
    public function toggleApp(): void
    {
        $this->appToggle = !$this->appToggle;
        // در اینجا می‌توانید وضعیت را در دیتابیس ذخیره کنید
        session()->flash('message', $this->appToggle ? 'اپلیکیشن با موفقیت فعال شد' : 'اپلیکیشن غیرفعال شد');
    }
    
    public function addNewTrip(): void
    {
        // بررسی فعال بودن اپلیکیشن
        if (!$this->appToggle) {
            session()->flash('message', 'برای ثبت سفر جدید، ابتدا اپلیکیشن را فعال کنید');
            return;
        }
        
        // منطق ثبت سفر جدید
        session()->flash('message', 'در حال انتقال به صفحه ثبت سفر جدید');
    }
}; ?>

<div>
    <div class="container">
        <header class="app-header">
            <div class="logo-container">
                <div class="logo">
                    <div class="map-pin">
                        <div class="pin-outer"></div>
                        <div class="pin-inner"></div>
                    </div>
                </div>
                <div class="brand-info">
                    <h1 class="brand-name">پینوتو</h1>
                    <h2 class="tagline">بیمه بدنه سفر محور خودرو</h2>
                </div>
            </div>
        </header>

        <main class="content">
            <div class="add-trip-button" wire:click="addNewTrip">
                <div class="plus-icon">+</div>
                <span>ثبت سفر جدید</span>
            </div>

            <div class="toggle-container">
                <label class="toggle-label">فعال سازی اپلیکیشن</label>
                <div class="toggle-switch">
                    <input type="checkbox" id="app-toggle" wire:model.live="appToggle">
                    <label for="app-toggle" class="slider"></label>
                </div>
            </div>

            @if (session()->has('message'))
                <div class="alert {{ str_contains(session('message'), 'فعال کنید') ? 'alert-error' : 'alert-success' }}">
                    {{ session('message') }}
                </div>
            @endif

            <div class="info-box">
                <div class="info-icon">!</div>
                <p class="info-text">
                    فعال بودن پینوتو در هنگام سفر و در حال رانندگی، شرط بهره‌مندی از مزایای بیمه بدنه سفر محور خودرو است
                </p>
            </div>
        </main>

        <footer class="app-footer">
            <div class="nav-item active">
              <div class="nav-icon home-icon"></div>
                <span class="nav-text">خانه</span>
            </div>
            <div class="nav-item">
                <div class="nav-icon car-icon"></div>
                <span class="nav-text">خودرو</span>
            </div>
            <div class="nav-item">
                <div class="nav-icon profile-icon"></div>
                <span class="nav-text">پروفایل</span>
            </div>
            <div class="nav-item">
                <div class="nav-icon history-icon"></div>
                <span class="nav-text">تاریخچه</span>
            </div>
        </footer>
    </div>
    
    <link rel="stylesheet" href="{{ asset('css/pinoto-home.css') }}">
    <script src="{{ asset('js/pinoto-home.js') }}"></script>
</div>