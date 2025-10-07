@props([
    'insuranceExcelFile' => null,
    'wireModel' => 'insuranceExcelFile',
    'totalMembers' => 0,
    'selectedCount' => 0,
    'closeEvent' => 'closeExcelUploadModal'
])

<div x-data="{
    fileSelected: false,
    fileName: '',
    fileSize: 0,
    fileSizeFormatted: '',
    isValidFile: false,
    validationError: '',
    maxFileSize: 10485760,
    allowedExtensions: ['xlsx', 'xls'],
    
    // Progress tracking properties
    showProgress: @entangle('showImportProgress'),
    jobId: @entangle('currentImportJobId'),
    progressData: @entangle('importProgress'),
    pollingInterval: null,
    isPolling: false,
    
    validateFile(event) {
        const file = event.target.files[0];
        if (!file) {
            this.clearFile();
            return;
        }
        
        this.fileSelected = true;
        this.fileName = file.name;
        this.fileSize = file.size;
        this.fileSizeFormatted = this.formatFileSize(file.size);
        this.validationError = '';
        this.isValidFile = false;
        
        const fileExtension = file.name.split('.').pop().toLowerCase();
        
        if (!this.allowedExtensions.includes(fileExtension)) {
            this.validationError = 'فرمت فایل نامعتبر است. فقط فایل‌های xlsx و xls مجاز هستند.';
            return;
        }
        
        if (file.size > this.maxFileSize) {
            this.validationError = 'حجم فایل بیشتر از 10 مگابایت است.';
            return;
        }
        
        this.isValidFile = true;
    },
    formatFileSize(bytes) {
        return (bytes / 1048576).toFixed(2) + ' MB';
    },
    clearFile() {
        this.fileSelected = false;
        this.fileName = '';
        this.fileSize = 0;
        this.fileSizeFormatted = '';
        this.isValidFile = false;
        this.validationError = '';
    },
    
    // Progress polling methods
    startPolling() {
        if (this.isPolling) return;
        this.isPolling = true;
        this.checkProgress();
        this.pollingInterval = setInterval(() => {
            this.checkProgress();
        }, 3000); // Poll every 3 seconds
    },
    
    checkProgress() {
        $wire.checkInsuranceImportProgress();
    },
    
    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
        this.isPolling = false;
    }
}" 
x-init="
    // Listen for progress events
    $watch('showProgress', value => {
        if (value) {
            setTimeout(() => startPolling(), 1000); // Start polling after 1 second
        } else {
            stopPolling();
        }
    });
    
    // Listen for completion events
    window.addEventListener('insurance-excel-queued', () => {
        startPolling();
    });
    
    window.addEventListener('insurance-excel-completed', () => {
        stopPolling();
    });
    
    window.addEventListener('insurance-excel-failed', () => {
        stopPolling();
    });
    
    // Cleanup on modal close
    $watch('$wire.showExcelUploadModal', value => {
        if (!value) stopPolling();
    });
" 
x-show="$wire.showExcelUploadModal" x-cloak class="modal-container" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div @click.away="$dispatch('{{ $closeEvent }}')" class="modal-content p-8 animate-fade-in max-w-xl" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">آپلود فایل اکسل</h2>
            <button @click="$dispatch('{{ $closeEvent }}')" class="text-gray-400 hover:text-gray-600 focus:outline-none text-2xl">&times;</button>
        </div>
        
        {{-- پیام موفقیت/خطا داخل پاپ‌آپ اکسل --}}
        @if (session()->has('success'))
            <div class="bg-green-100 text-green-800 rounded-lg px-4 py-3 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </div>
        @endif
        
        @if (session()->has('error'))
            <div class="bg-red-100 text-red-800 rounded-lg px-4 py-3 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                {{ session('error') }}
            </div>
        @endif
        
        <div class="mb-6">
            <div class="text-green-700 text-lg font-bold mb-3 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                اطلاعات <span>{{ $selectedCount }}</span> خانواده معادل <span>{{ $totalMembers }}</span> نفر برای بیمه آماده شده است
            </div>
            
            <div class="text-gray-600 text-base leading-7 bg-blue-50 p-4 rounded-lg mb-6">
                برای تکمیل فرآیند بیمه، لطفا ابتدا با کلیک روی دکمه زیر، فایل نمونه اکسل را دانلود کنید.<br>
                سپس فایل را با اطلاعات بیمه (شماره بیمه‌نامه، تاریخ صدور و...) تکمیل کرده و در قسمت زیر آپلود نمایید.
            </div>
            
            <div class="flex justify-center mb-8">
                <button type="button" wire:click="downloadInsuranceExcel" wire:loading.attr="disabled" wire:target="downloadInsuranceExcel" class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-3 px-6 text-lg font-bold flex items-center justify-center gap-2 transition duration-200 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg wire:loading.remove wire:target="downloadInsuranceExcel" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                    </svg>
                    <svg wire:loading wire:target="downloadInsuranceExcel" class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="downloadInsuranceExcel">دانلود فایل نمونه اکسل</span>
                    <span wire:loading wire:target="downloadInsuranceExcel">در حال دانلود...</span>
                </button>
            </div>
        </div>
        
        <form wire:submit.prevent="uploadInsuranceExcel" class="mt-8">
            <div class="flex flex-col items-center">
                {{-- Validation Helper Text --}}
                <div class="w-full bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 ml-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-sm text-blue-800">
                            <p class="font-bold mb-2">الزامات فایل:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>فرمت‌های مجاز: .xlsx, .xls</li>
                                <li>حداکثر حجم: 10 مگابایت</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <input type="file" wire:model="{{ $wireModel }}" @change="validateFile($event)" accept=".xlsx,.xls" class="hidden" id="excel-upload-input">
                <label for="excel-upload-input" class="w-full cursor-pointer">
                    <div class="bg-green-600 hover:bg-green-700 text-white rounded-xl py-4 text-lg font-bold flex items-center justify-center gap-2 transition duration-200 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        آپلود فایل اکسل تکمیل شده
                    </div>
                </label>
                
                {{-- Progress Bar --}}
                <div wire:loading wire:target="uploadInsuranceExcel" class="w-full mt-4">
                    <div class="bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div class="bg-green-600 h-full animate-pulse" style="width: 100%; transition: width 0.3s ease;"></div>
                    </div>
                    <div class="flex items-center justify-center mt-2 text-green-700">
                        <svg class="animate-spin h-5 w-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-bold">در حال آپلود فایل...</span>
                    </div>
                </div>
                
{{-- Enhanced File Preview with Validation --}}
                <div x-show="fileSelected" x-cloak class="w-full mt-4 animate-fade-in">
                    <div class="bg-white border-2 rounded-lg p-4" :class="isValidFile ? 'border-green-500' : 'border-red-500'">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start flex-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 ml-3" :class="isValidFile ? 'text-green-600' : 'text-red-600'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <div class="flex-1">
                                    <p class="font-bold text-gray-800" x-text="fileName"></p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <span>حجم: </span>
                                        <span class="font-semibold" x-text="fileSizeFormatted"></span>
                                    </p>
                                </div>
                            </div>
                            <div>
                                <svg x-show="isValidFile" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg x-show="!isValidFile && validationError" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        
                        {{-- Validation Error Message --}}
                        <div x-show="validationError" x-cloak class="mt-3 bg-red-50 border border-red-200 rounded-lg p-3">
                            <div class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600 ml-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <p class="text-sm text-red-800 font-semibold" x-text="validationError"></p>
                            </div>
                        </div>
                        
                        {{-- Success Message --}}
                        <div x-show="isValidFile" x-cloak class="mt-3 bg-green-50 border border-green-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <p class="text-sm text-green-800 font-semibold">فایل معتبر است و آماده آپلود می‌باشد</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Submit Button with Enhanced States --}}
                @if($insuranceExcelFile)
                    <button type="submit" :disabled="!isValidFile" wire:loading.attr="disabled" wire:target="uploadInsuranceExcel" x-show="!showProgress" class="mt-4 w-full bg-green-700 hover:bg-green-800 text-white rounded-xl py-3 text-lg font-bold transition duration-200 ease-in-out animate-fade-in disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-400 flex items-center justify-center gap-2">
                        <svg wire:loading wire:target="uploadInsuranceExcel" class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="uploadInsuranceExcel">تایید و ارسال فایل</span>
                        <span wire:loading wire:target="uploadInsuranceExcel">در حال آپلود...</span>
                    </button>
                @endif
                
                {{-- Loading Overlay for File Input --}}
                <div x-show="showProgress" class="mt-4 w-full pointer-events-none opacity-50" x-cloak>
                    <div class="bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                        <p class="text-gray-500 text-sm">فایل در حال پردازش است...</p>
                    </div>
                </div>
                
                @error($wireModel)
                    <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                @enderror
            </div>
        </form>
        
        {{-- Progress UI Section --}}
        <div x-show="showProgress" x-cloak class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            <div class="text-center mb-4">
                <div class="flex items-center justify-center mb-3">
                    <svg class="animate-spin h-6 w-6 text-blue-600 ml-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <h3 class="text-lg font-bold text-blue-900">در حال پردازش فایل...</h3>
                </div>
                
                {{-- Progress Bar --}}
                <div class="bg-gray-200 rounded-full h-4 overflow-hidden mb-4">
                    <div class="bg-blue-600 h-full transition-all duration-500 ease-out" :style="'width: ' + (progressData.progress || 0) + '%'"></div>
                </div>
                
                {{-- Progress Percentage --}}
                <p class="text-sm font-bold text-blue-700 mb-2" x-text="(progressData.progress || 0) + '%'"></p>
                
                {{-- Status Message --}}
                <p class="text-base text-blue-800" x-text="progressData.message || 'در حال پردازش...'"></p>
                
                {{-- Status Indicators --}}
                <div class="mt-4 flex items-center justify-center">
                    <div x-show="progressData.status === 'queued'" class="flex items-center text-yellow-600">
                        <svg class="h-5 w-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm font-medium">در صف پردازش</span>
                    </div>
                    
                    <div x-show="progressData.status === 'processing'" class="flex items-center text-blue-600">
                        <svg class="animate-spin h-5 w-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium">در حال پردازش</span>
                    </div>
                    
                    <div x-show="progressData.status === 'completed'" class="flex items-center text-green-600">
                        <svg class="h-5 w-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm font-medium">تکمیل شد</span>
                    </div>
                    
                    <div x-show="progressData.status === 'failed'" class="flex items-center text-red-600">
                        <svg class="h-5 w-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm font-medium">خطا رخ داد</span>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Loading Overlay --}}
        <div wire:loading wire:target="uploadInsuranceExcel" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center" style="z-index: 9999;">
            <div class="bg-white rounded-lg p-8 max-w-sm mx-4 text-center">
                <svg class="animate-spin h-16 w-16 text-green-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <h3 class="text-xl font-bold text-gray-800 mb-2">در حال پردازش...</h3>
                <p class="text-gray-600">لطفاً صبر کنید، فایل در حال پردازش است...</p>
            </div>
        </div>
    </div>
</div>
