<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>میکروبیمه - شفافیت در خدمت نیکوکاری</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Vazir', sans-serif;
            direction: rtl;
            text-align: right;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #00A22B 0%, #24C233 100%);
            color: white;
            padding: 100px 0;
            min-height: 80vh;
            display: flex;
            align-items: center;
        }
        
        .section-padding {
            padding: 80px 0;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .step-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            border-right: 4px solid #00A22B;
        }
        
        .stats-section {
            background: #1a1a2e;
            color: white;
        }
        
        .stat-item {
            text-align: center;
            color: white;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #00A22B;
        }
        
        .cta-section {
            background: linear-gradient(45deg, #00A22B, #24C233);
            color: white;
        }
        
        .partner-logo {
            max-height: 60px;
            filter: grayscale(100%);
            transition: filter 0.3s ease;
        }
        
        .partner-logo:hover {
            filter: grayscale(0%);
        }
        
        .btn-primary-custom {
            background: linear-gradient(45deg, #00A22B, #24C233);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: bold;
        }
        
        .btn-outline-custom {
            border: 2px solid #00A22B;
            color: #00A22B;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: bold;
        }
        
        .dashboard-image {
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .tech-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #00A22B, #24C233);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 20px auto;
        }
        
        .logo-microbime {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }
        
        .logo-microbime-footer {
            width: 35px;
            height: 35px;
            object-fit: contain;
        }
        
        .process-funnel {
            padding: 20px;
        }
        
        .funnel-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .funnel-step {
            width: 100%;
            max-width: 350px;
            padding: 15px 20px;
            margin: 5px 0;
            border-radius: 25px;
            color: white;
            font-weight: bold;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }
        
        .funnel-step:hover {
            transform: scale(1.05);
        }
        
        .funnel-step:first-child {
            max-width: 400px;
            font-size: 1.1rem;
        }
        
        .funnel-step:nth-child(2) {
            max-width: 320px;
        }
        
        .funnel-step:nth-child(3) {
            max-width: 290px;
        }
        
        .funnel-step:nth-child(4) {
            max-width: 260px;
        }
        
        .funnel-step:nth-child(5) {
            max-width: 230px;
        }
        
        .funnel-step:nth-child(6) {
            max-width: 200px;
        }
        
        .funnel-step i {
            font-size: 1.5rem;
            margin-bottom: 8px;
            display: block;
        }
        
        .funnel-step p {
            margin: 0;
            font-size: 0.9rem;
        }
        
        .feature-icon-csr, .feature-icon-donate, .feature-icon-charity {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            color: white;
            font-size: 3rem;
        }
        
        .feature-icon-csr {
            background: linear-gradient(45deg, #00A22B, #24C233);
        }
        
        .feature-icon-donate {
            background: linear-gradient(45deg, #dc3545, #e83e8c);
        }
        
        .feature-icon-charity {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
        }
        
        .success-story-visual {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .insurance-icon-large {
            width: 150px;
            height: 150px;
            background: linear-gradient(45deg, #00A22B, #24C233);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px auto;
            color: white;
            font-size: 4rem;
            box-shadow: 0 10px 25px rgba(0, 162, 43, 0.3);
        }
        
        .success-metrics {
            display: flex;
            justify-content: space-around;
            gap: 20px;
        }
        
        .metric-item {
            text-align: center;
        }
        
        .metric-number {
            display: block;
            font-size: 2rem;
            font-weight: bold;
            color: #00A22B;
            margin-bottom: 5px;
        }
        
        .metric-label {
            display: block;
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
                <img src="{{ asset('images/MICROBIME1.png') }}" alt="میکروبیمه" class="logo-microbime ms-2 me-3">
                میکروبیمه
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">چگونه کار می‌کند</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#solutions">راهکارها</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#impact">تأثیر</a>
                    </li>
                </ul>
                <div class="d-flex gap-3">
                    <a href="{{ route('home.login') }}" class="btn btn-outline-custom">ورود</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">
                        میکروبیمه: شفافیت در خدمت نیکوکاری
                    </h1>
                    <p class="lead mb-4">
                        پلتفرم شفاف اتصال بودجه‌های مسئولیت اجتماعی شرکت‌ها به بیمه درمان خانواده‌های نیازمند
                    </p>
                    <div class="d-flex gap-3 mb-4">
                        <button class="btn btn-light btn-lg px-4">
                            <i class="fas fa-heart me-2"></i>
                            از ۱۰ هزار تومان شروع کنید
                        </button>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="me-4">
                            <small class="d-block">بیش از</small>
                            <strong class="h4">۱۰ میلیارد تومان</strong>
                            <small class="d-block">کمک هدفمند</small>
                        </div>
                        <div class="me-4">
                            <small class="d-block">بیش از</small>
                            <strong class="h4">۵۰۰</strong>
                            <small class="d-block">خانواده تحت پوشش</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="process-funnel text-center">
                        <div class="funnel-container">
                            <div class="funnel-step" style="background: linear-gradient(45deg, #24C233, #00A22B);">
                                <h6>فرآیند تخصیص بودجه میکروبیمه</h6>
                            </div>
                            <div class="funnel-step" style="background: linear-gradient(45deg, #17a2b8, #20c997);">
                                <i class="fas fa-handshake"></i>
                                <p>تخصیص بودجه مسئولیت اجتماعی</p>
                            </div>
                            <div class="funnel-step" style="background: linear-gradient(45deg, #28a745, #6f42c1);">
                                <i class="fas fa-search"></i>
                                <p>شناسایی نیازمندان</p>
                            </div>
                            <div class="funnel-step" style="background: linear-gradient(45deg, #ffc107, #fd7e14);">
                                <i class="fas fa-robot"></i>
                                <p>انتخاب هوشمند</p>
                            </div>
                            <div class="funnel-step" style="background: linear-gradient(45deg, #dc3545, #e83e8c);">
                                <i class="fas fa-chart-line"></i>
                                <p>گزارش‌دهی شفاف</p>
                            </div>
                            <div class="funnel-step" style="background: linear-gradient(45deg, #6610f2, #6f42c1);">
                                <i class="fas fa-shield-alt"></i>
                                <p>همکاری با بیمه‌گر</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="section-padding bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="h1 fw-bold mb-4">چگونه کار می‌کند؟</h2>
                    <p class="lead">فرآیند هوشمند ما در ۴ مرحله ساده</p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="step-card text-center">
                        <div class="tech-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h5 class="fw-bold">شناسایی شفاف نیاز</h5>
                        <p class="text-muted">خیریه‌ها نیازمندان واقعی را با شفافیت کامل معرفی می‌کنند</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="step-card text-center">
                        <div class="tech-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h5 class="fw-bold">تطبیق دقیق منابع</h5>
                        <p class="text-muted">هوش مصنوعی بهترین تطبیق را پیدا می‌کند</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="step-card text-center">
                        <div class="tech-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h5 class="fw-bold">صدور فوری بیمه‌نامه</h5>
                        <p class="text-muted">بیمه‌نامه برای خانواده‌ها صادر می‌شود</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="step-card text-center">
                        <div class="tech-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h5 class="fw-bold">رصد شفاف تأثیر</h5>
                        <p class="text-muted">تأثیر کمک خود را لحظه‌ای ببینید</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Solutions Section -->
    <section id="solutions" class="section-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="h1 fw-bold mb-4">راهکار ما برای شما</h2>
                    <p class="lead">راهکارهای متنوع برای مخاطبان مختلف</p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="feature-card">
                        <div class="text-center mb-4">
                            <div class="feature-icon-csr">
                                <i class="fas fa-building"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-3">برای شرکت‌ها</h4>
                        <p class="text-muted mb-4">
                            بودجه CSR خود را به یک سرمایه‌گذاری اجتماعی پایدار تبدیل کنید. 
                            گزارش‌های شفاف دریافت کرده و تأثیر برند خود را افزایش دهید.
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>گزارش‌دهی realtime</li>
                            <li><i class="fas fa-check text-success me-2"></i>شفافیت کامل</li>
                            <li><i class="fas fa-check text-success me-2"></i>تأثیر قابل اندازه‌گیری</li>
                        </ul>
                        <button class="btn btn-primary-custom w-100">بیشتر بدانید</button>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="feature-card">
                        <div class="text-center mb-4">
                            <div class="feature-icon-donate">
                                <i class="fas fa-heart"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-3">برای نیکوکاران</h4>
                        <p class="text-muted mb-4">
                            با هر مبلغی، در تأمین هزینه بیمه یک خانواده شریک شوید. 
                            کمک شما مستقیم، هوشمندانه و قابل رصد است.
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>شروع از ۱۰ هزار تومان</li>
                            <li><i class="fas fa-check text-success me-2"></i>رصد مستقیم تأثیر</li>
                            <li><i class="fas fa-check text-success me-2"></i>امنیت کامل</li>
                        </ul>
                        <button class="btn btn-primary-custom w-100">همین حالا اهدا کنید</button>
                    </div>
                </div>
                <div class="col-lg-4 mb-4">
                    <div class="feature-card">
                        <div class="text-center mb-4">
                            <div class="feature-icon-charity">
                                <i class="fas fa-hands-helping"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-3">برای خیریه‌ها</h4>
                        <p class="text-muted mb-4">
                            مددجویان خود را برای دریافت حمایت‌های درمانی پایدار به ما معرفی کنید. 
                            فرآیند ساده و رایگان است.
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i>ثبت رایگان در ۵ دقیقه</li>
                            <li><i class="fas fa-check text-success me-2"></i>حمایت پایدار</li>
                            <li><i class="fas fa-check text-success me-2"></i>پشتیبانی ۲۴/۷</li>
                        </ul>
                        <button class="btn btn-primary-custom w-100">ثبت موسسه خیریه</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section id="impact" class="stats-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="h1 fw-bold mb-4">تأثیر واقعی، آمار زنده</h2>
                    <p class="lead">نتایج ملموس پلتفرم میکروبیمه</p>
                </div>
            </div>
            <div class="row text-center">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <div class="stat-number">+۵۰۰</div>
                        <p>خانواده تحت پوشش</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <div class="stat-number">۱۰+</div>
                        <p>میلیارد تومان کمک</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <div class="stat-number">۹۰%</div>
                        <p>کاهش زمان رسیدن کمک</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-item">
                        <div class="stat-number">+۵</div>
                        <p>شریک استراتژیک</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Success Story -->
    <section class="section-padding">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="success-story-visual">
                        <div class="insurance-icon-large">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="success-metrics">
                            <div class="metric-item">
                                <span class="metric-number">۵۰۰</span>
                                <span class="metric-label">خانواده تحت پوشش</span>
                            </div>
                            <div class="metric-item">
                                <span class="metric-number">۲ میلیارد</span>
                                <span class="metric-label">تومان بودجه</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="pe-lg-5">
                        <h3 class="h2 fw-bold mb-4">موفقیت ملموس</h3>
                        <blockquote class="blockquote">
                            <p class="mb-4 fs-5">
                                "چگونه شرکت بیمه سامان با ۲ میلیارد تومان، ۵۰۰ خانواده را تحت پوشش قرار داد"
                            </p>
                        </blockquote>
                        <p class="text-muted mb-4">
                            شرکت بیمه سامان با استفاده از پلتفرم میکروبیمه، 
                            بودجه مسئولیت اجتماعی خود را به شکلی شفاف هزینه کرد که منجر به 
                            تحت پوشش قرار گرفتن ۵۰۰ خانواده نیازمند شد.
                        </p>
                        <div class="d-flex align-items-center">
                            <div class="me-4">
                                <strong class="h5 text-success">۹۵%</strong>
                                <small class="d-block text-muted">رضایت کاربران</small>
                            </div>
                            <div class="me-4">
                                <strong class="h5 text-success">۳ماه</strong>
                                <small class="d-block text-muted">متوسط زمان بازگشت سرمایه</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="h1 fw-bold mb-4">
                        بیایید با هم، دغدغه درمان را از دوش خانواده‌های کم‌برخوردار برداریم
                    </h2>
                    <p class="lead mb-5">
                        هم‌اکنون به جمع حامیان میکروبیمه بپیوندید و تأثیر واقعی بگذارید
                    </p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <button class="btn btn-light btn-lg px-5">
                            <i class="fas fa-building me-2"></i>
                            درخواست دمو برای شرکت‌ها
                        </button>
                        <button class="btn btn-outline-light btn-lg px-5">
                            <i class="fas fa-heart me-2"></i>
                            حمایت می‌کنم
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="section-padding bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <h2 class="h1 fw-bold text-center mb-5">سوالات متداول</h2>
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    چگونه می‌توانم از رسیدن کمک خود مطمئن شوم؟
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    با گزارش‌دهی لحظه‌ای و داشبورد شفاف، شما می‌توانید مسیر کمک خود را از ابتدا تا انتها رصد کنید.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    حداقل مبلغ برای اهدا چقدر است؟
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    شما می‌توانید از ۱۰ هزار تومان شروع کنید. هر مبلغی ارزشمند است و تأثیر مثبت خواهد داشت.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    آیا میکروبیمه سود می‌برد؟
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    میکروبیمه یک پلتفرم غیرانتفاعی است که تنها از محل ارائه خدمات تکنولوژیک درآمد کسب می‌کند. ۱۰۰% کمک‌ها به مصرف هدف می‌رسد.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="fw-bold mb-0 d-flex align-items-center">
                        <img src="{{ asset('images/MICROBIME1.png') }}" alt="میکروبیمه" class="logo-microbime-footer ms-2 me-3">
                        میکروبیمه
                    </h5>
                </div>
                <div class="col-md-6 text-md-end">
                    <button id="backToTop" class="btn btn-primary-custom">
                        <i class="fas fa-arrow-up me-2"></i>
                        برگشت به بالا
                    </button>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // برگشت به بالا
        document.getElementById('backToTop').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>
