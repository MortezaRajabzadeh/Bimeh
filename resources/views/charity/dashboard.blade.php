<x-app-layout>
    <div class="py-6">
        <div class="container mx-auto px-4">
            <!-- نمایش پیام‌های سیستم -->
            @if(session('success'))
                <div class="mb-6 p-6 bg-green-50 border border-green-200 rounded-xl shadow-sm">
                    <div class="flex items-center text-green-600">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="mr-3 flex-1">
                            <h3 class="text-lg font-semibold text-green-800">موفقیت آمیز!</h3>
                            <p class="text-green-700 mt-1 whitespace-pre-line">{{ session('success') }}</p>
                        </div>
                    </div>
                    
                    @if(session('results'))
                        <div class="mt-4 p-4 bg-white rounded-lg border border-green-200">
                            <h4 class="font-medium text-green-800 mb-3">📊 گزارش تفصیلی:</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                @if(isset(session('results')['families_created']))
                                    <div class="text-center p-3 bg-green-100 rounded-lg">
                                        <div class="text-2xl font-bold text-green-600">{{ session('results')['families_created'] }}</div>
                                        <div class="text-sm text-green-700">خانواده جدید</div>
                                    </div>
                                    <div class="text-center p-3 bg-blue-100 rounded-lg">
                                        <div class="text-2xl font-bold text-blue-600">{{ session('results')['members_added'] }}</div>
                                        <div class="text-sm text-blue-700">عضو ثبت شده</div>
                                    </div>
                                @endif
                                
                                @if(session('results')['failed'] > 0)
                                    <div class="text-center p-3 bg-orange-100 rounded-lg">
                                        <div class="text-2xl font-bold text-orange-600">{{ session('results')['failed'] }}</div>
                                        <div class="text-sm text-orange-700">ردیف ناموفق</div>
                                    </div>
                                @endif
                            </div>
                            
                            @if(!empty(session('results')['errors']))
                                <details class="mt-4 bg-red-50 rounded-lg border border-red-200">
                                    <summary class="cursor-pointer p-3 font-medium text-red-800 hover:bg-red-100 rounded-lg transition-colors">
                                        🔍 مشاهده خطاها 
                                        @if(isset(session('results')['total_errors']) && session('results')['total_errors'] > session('results')['showing_count'])
                                            (نمایش {{ session('results')['showing_count'] }} از {{ session('results')['total_errors'] }} خطا)
                                        @endif
                                    </summary>
                                    <div class="p-3 pt-0">
                                        <ul class="space-y-2">
                                            @foreach(session('results')['errors'] as $error)
                                                <li class="flex items-start">
                                                    <span class="text-red-500 mr-2">•</span>
                                                    <span class="text-red-700 text-sm">{{ $error }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                        
                                        @if(isset(session('results')['total_errors']) && session('results')['total_errors'] > session('results')['showing_count'])
                                            <div class="mt-3 p-3 bg-orange-100 rounded-lg border border-orange-200">
                                                <p class="text-orange-800 text-sm">
                                                    ⚠️ <strong>توجه:</strong> {{ session('results')['total_errors'] - session('results')['showing_count'] }} خطای دیگر نیز وجود دارد. 
                                                    برای جلوگیری از ایجاد خانواده‌های خالی، خطاهای تکراری و نامعتبر قبل از ثبت شناسایی و رد می‌شوند.
                                                </p>
                                                <p class="text-orange-700 text-sm mt-2">
                                                    💡 <strong>راهنمایی:</strong> لطفاً فایل خود را با دقت بررسی کرده، اطلاعات تکراری را حذف کنید و مطابق نمونه اصلاح کنید.
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </details>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-6 bg-red-50 border border-red-200 rounded-xl shadow-sm">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="mr-3 flex-1">
                            <h3 class="text-lg font-semibold text-red-800">خطا در عملیات</h3>
                            <p class="text-red-700 mt-1 whitespace-pre-line">{{ session('error') }}</p>
                        </div>
                    </div>
                    
                    @if(session('results') && !empty(session('results')['errors']))
                        <div class="mt-4 p-4 bg-white rounded-lg border border-red-200">
                            <h4 class="font-medium text-red-800 mb-3">🔍 جزئیات خطاها:</h4>
                            <ul class="space-y-2">
                                @foreach(session('results')['errors'] as $error)
                                    <li class="flex items-start">
                                        <span class="text-red-500 mr-2">•</span>
                                        <span class="text-red-700 text-sm">{{ $error }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            
                            @if(isset(session('results')['total_errors']) && session('results')['total_errors'] > session('results')['showing_count'])
                                <div class="mt-3 p-3 bg-red-100 rounded-lg border border-red-200">
                                    <p class="text-red-800 text-sm">
                                        ⚠️ <strong>توجه:</strong> فقط {{ session('results')['showing_count'] }} خطای اول نمایش داده شده است. 
                                        در مجموع {{ session('results')['total_errors'] }} خطا وجود دارد. 
                                        لطفاً فایل خود را با دقت بررسی کرده و مطابق نمونه اصلاح کنید.
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            @if(session('info'))
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 mt-0.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="mr-3">
                            <strong class="font-bold text-blue-800">{{ session('info') }}</strong>
                            @if(session('details'))
                                <p class="mt-1 text-sm text-blue-700">{{ session('details') }}</p>
                            @endif
                            @if(session('job_id'))
                                <div class="mt-3 p-3 bg-blue-100 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-blue-800">وضعیت پردازش:</p>
                                            <p id="job-status" class="text-sm text-blue-600">در حال بررسی...</p>
                                            <div class="mt-2 w-full bg-white rounded-full h-2 border">
                                                <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                            </div>
                                        </div>
                                        <button onclick="refreshJobStatus()" class="mr-4 text-blue-600 hover:text-blue-800 text-sm font-medium p-2 rounded-md hover:bg-blue-100 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <script>
                                    const jobId = '{{ session("job_id") }}';
                                    let pollingInterval;
                                    
                                    function refreshJobStatus() {
                                        fetch(`{{ route('charity.import.status') }}?job_id=${jobId}`)
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.status === 'success') {
                                                    const jobData = data.data;
                                                    const statusElement = document.getElementById('job-status');
                                                    const progressBar = document.getElementById('progress-bar');
                                                    
                                                    // بروزرسانی وضعیت
                                                    let statusText = '';
                                                    switch(jobData.status) {
                                                        case 'queued':
                                                            statusText = '⏳ در صف انتظار...';
                                                            break;
                                                        case 'processing':
                                                            statusText = '⚙️ در حال پردازش...';
                                                            break;
                                                        case 'completed':
                                                            statusText = '✅ کامل شد!';
                                                            if (jobData.results) {
                                                                statusText += ` (${jobData.results.families_created} خانواده، ${jobData.results.members_added} عضو ایجاد شد)`;
                                                            }
                                                            clearInterval(pollingInterval);
                                                            // رفرش صفحه بعد از 3 ثانیه
                                                            setTimeout(() => window.location.reload(), 3000);
                                                            break;
                                                        case 'failed':
                                                            statusText = '❌ خطا در پردازش';
                                                            if (jobData.error) {
                                                                statusText += `: ${jobData.error}`;
                                                            }
                                                            clearInterval(pollingInterval);
                                                            break;
                                                    }
                                                    
                                                    if (statusElement) statusElement.textContent = statusText;
                                                    if (progressBar) progressBar.style.width = (jobData.progress || 0) + '%';
                                                    
                                                    // توقف polling اگر job کامل شد
                                                    if (['completed', 'failed'].includes(jobData.status)) {
                                                        clearInterval(pollingInterval);
                                                    }
                                                }
                                            })
                                            .catch(error => {
                                                console.error('خطا در دریافت وضعیت:', error);
                                                const statusElement = document.getElementById('job-status');
                                                if (statusElement) {
                                                    statusElement.textContent = '❌ خطا در دریافت وضعیت';
                                                }
                                            });
                                    }
                                    
                                    // شروع polling خودکار
                                    setTimeout(refreshJobStatus, 1000);
                                    
                                    // بروزرسانی هر 3 ثانیه
                                    pollingInterval = setInterval(refreshJobStatus, 3000);
                                </script>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- بخش آمارهای داشبورد -->
            <livewire:charity.dashboard-stats />
            
            <!-- فاصله و خط جداکننده بین دو بخش -->
            <div class="my-10 border-t border-gray-200"></div>
       
            <!-- جدول خانواده‌ها با Livewire -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <livewire:charity.family-search />
            </div>
        </div>
    </div>
</x-app-layout> 