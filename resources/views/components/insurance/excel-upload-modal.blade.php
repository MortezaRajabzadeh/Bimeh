@props([
    'showModal' => '$wire.showExcelUploadModal',
    'totalMembers' => 0,
    'selectedCount' => '$wire.selected.length',
    'insuranceExcelFile' => null,
    'wireModel' => 'insuranceExcelFile',
    'downloadMethod' => 'downloadSampleTemplate',
    'uploadMethod' => 'uploadInsuranceExcel',
    'closeMethod' => 'closeExcelUploadModal'
])

<div x-data="{
    // File validation properties
    fileSelected: false,
    fileName: '',
    fileSize: 0,
    fileSizeFormatted: '',
    isValidFile: false,
    validationError: '',
    maxFileSize: 10485760, // 10MB
    allowedExtensions: ['xlsx', 'xls'],
    
    // Progress tracking properties
    showProgress: false,
    jobId: null,
    progressData: {
        percentage: 0,
        status: 'queued',
        message: 'آماده‌سازی...'
    },
    pollingInterval: null,
    isPolling: false,

    // File validation methods
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
        
        // Validate file extension
        const extension = file.name.split('.').pop().toLowerCase();
        if (!this.allowedExtensions.includes(extension)) {
            this.validationError = 'فرمت فایل مجاز نیست. لطفاً فایل‌های .xlsx یا .xls انتخاب کنید.';
            this.isValidFile = false;
            return;
        }
        
        // Validate file size
        if (file.size > this.maxFileSize) {
            this.validationError = 'حجم فایل بیش از حد مجاز است. حداکثر 10 مگابایت مجاز است.';
            this.isValidFile = false;
            return;
        }
        
        this.validationError = '';
        this.isValidFile = true;
    },
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    clearFile() {
        this.fileSelected = false;
        this.fileName = '';
        this.fileSize = 0;
        this.fileSizeFormatted = '';
        this.isValidFile = false;
        this.validationError = '';
    },

    // Progress tracking methods
    startPolling() {
        if (this.isPolling) return;
        
        this.isPolling = true;
        this.pollingInterval = setInterval(() => {
            this.checkProgress();
        }, 1000);
    },

    checkProgress() {
        if (!this.jobId) return;
        
        fetch(`/insurance/import-progress/${this.jobId}`)
            .then(response => response.json())
            .then(data => {
                this.progressData = data;
                if (data.status === 'completed' || data.status === 'failed') {
                    this.stopPolling();
                }
            })
            .catch(error => {
                console.error('Progress check error:', error);
                this.stopPolling();
            });
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
        $watch('showProgress', (value) => {
            if (value) {
                startPolling();
            } else {
                stopPolling();
            }
        });
        
        $wire.on('insurance-excel-queued', (event) => {
            jobId = event.jobId;
            showProgress = true;
            progressData = {
                percentage: 0,
                status: 'queued',
                message: 'فایل در نوبت پردازش قرار گرفت...'
            };
        });
        
        $wire.on('insurance-excel-completed', () => {
            showProgress = false;
            $wire.{{ $showModal }} = false;
            jobId = null;
        });
        
        $wire.on('insurance-excel-failed', (event) => {
            showProgress = false;
            progressData.status = 'failed';
            progressData.message = event.message || 'خطا در پردازش فایل';
        });
        
        $watch('{{ $showModal }}', (value) => {
            if (!value) {
                showProgress = false;
                stopPolling();
                jobId = null;
                clearFile();
            }
        });
    "
    x-show="{{ $showModal }}"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="$wire.{{ $closeMethod }}()">

    <!-- Background overlay -->
    <div class="fixed inset-0 bg-black bg-opacity-50" @click="$wire.{{ $closeMethod }}()"></div>
    
    <!-- Modal container -->
    <div class="flex min-h-full items-center justify-center p-2 sm:p-4">
        <div class="relative w-full max-w-4xl mx-auto">
            <div class="bg-white rounded-xl shadow-2xl overflow-hidden max-h-[90vh] overflow-y-auto"
                 @click.away="$wire.{{ $closeMethod }}()"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
        
        <!-- Progress Overlay -->
        <div x-show="showProgress" 
             class="absolute inset-0 bg-white bg-opacity-95 rounded-xl flex items-center justify-center z-10"
             x-transition>
            <div class="text-center p-4 sm:p-6">
                <div class="w-12 h-12 sm:w-16 sm:h-16 mx-auto mb-4 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-2">پردازش فایل</h3>
                <p class="text-xs sm:text-sm text-gray-600 mb-4" x-text="progressData.message"></p>
                
                <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                         :style="`width: ${progressData.percentage}%`"></div>
                </div>
                <span class="text-xs text-gray-500" x-text="`${progressData.percentage}% تکمیل شده`"></span>
            </div>
        </div>

        <!-- Header -->
        <div class="flex items-center justify-between p-4 sm:p-6 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-blue-50">
            <div class="flex items-center space-x-2 sm:space-x-3 space-x-reverse">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg sm:text-xl font-bold text-gray-800">آپلود فایل اطلاعات صدور</h3>
            </div>
            <button wire:click="{{ $closeMethod }}" 
                    class="text-gray-400 hover:text-gray-600 transition-colors p-1 sm:p-2 hover:bg-gray-100 rounded-lg">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Content -->
        <div class="p-4 sm:p-6 space-y-4 sm:space-y-6">
            <!-- Info Box -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-4 sm:p-5 border border-green-200">
                <div class="flex items-center space-x-2 sm:space-x-3 space-x-reverse">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-base sm:text-lg font-semibold text-green-800">
                            <span x-text="{{ $selectedCount }}"></span> خانواده انتخاب شده
                        </p>
                        <p class="text-xs sm:text-sm text-green-600 mt-1">
                            معادل {{ $totalMembers }} نفر آماده بیمه
                        </p>
                    </div>
                </div>
            </div>

            <!-- Instructions Box -->
            <div class="bg-blue-50 rounded-xl p-4 sm:p-5 border border-blue-200">
                <h4 class="text-base sm:text-lg font-bold text-blue-800 mb-3 sm:mb-4 flex items-center">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    مراحل تکمیل فرآیند بیمه:
                </h4>
                <div class="space-y-2 sm:space-y-3">
                    <div class="flex items-center space-x-2 sm:space-x-3 space-x-reverse">
                        <div class="flex-shrink-0 w-6 h-6 sm:w-8 sm:h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs sm:text-sm font-bold">1</div>
                        <p class="text-sm sm:text-base text-blue-700 font-medium">دانلود فایل نمونه</p>
                    </div>
                    <div class="flex items-center space-x-2 sm:space-x-3 space-x-reverse">
                        <div class="flex-shrink-0 w-6 h-6 sm:w-8 sm:h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs sm:text-sm font-bold">2</div>
                        <p class="text-sm sm:text-base text-blue-700 font-medium">تکمیل فایل با اطلاعات بیمه</p>
                    </div>
                    <div class="flex items-center space-x-2 sm:space-x-3 space-x-reverse">
                        <div class="flex-shrink-0 w-6 h-6 sm:w-8 sm:h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs sm:text-sm font-bold">3</div>
                        <p class="text-sm sm:text-base text-blue-700 font-medium">آپلود فایل تکمیل شده</p>
                    </div>
                </div>
            </div>

            <!-- Download Button -->
            <div class="text-center">
                <button wire:click="{{ $downloadMethod }}" 
                        class="inline-flex items-center px-4 sm:px-6 py-2 sm:py-3 bg-blue-600 border border-transparent rounded-xl text-sm sm:text-base font-bold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                        wire:loading.attr="disabled" 
                        wire:target="{{ $downloadMethod }}">
                    <div wire:loading wire:target="{{ $downloadMethod }}" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 sm:h-5 sm:w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        در حال دانلود...
                    </div>
                    <span wire:loading.remove wire:target="{{ $downloadMethod }}" class="flex items-center">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        دانلود فایل نمونه
                    </span>
                </button>
            </div>

            <!-- Divider -->
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="bg-white px-4 text-gray-500 font-medium">پس از تکمیل فایل</span>
                </div>
            </div>

            <!-- Upload Form -->
            <form wire:submit.prevent="{{ $uploadMethod }}" class="space-y-4 sm:space-y-6">
                <!-- File Requirements -->
                <div class="bg-gray-50 rounded-xl p-3 sm:p-4 border border-gray-200">
                    <div class="flex items-center space-x-2 sm:space-x-3 space-x-reverse mb-2 sm:mb-3">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h5 class="text-xs sm:text-sm font-semibold text-gray-700">الزامات فایل:</h5>
                    </div>
                    <ul class="text-xs sm:text-sm text-gray-600 space-y-1">
                        <li class="flex items-center">
                            <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full ml-2"></span>
                            فرمت‌های مجاز: .xlsx, .xls
                        </li>
                        <li class="flex items-center">
                            <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-gray-400 rounded-full ml-2"></span>
                            حداکثر حجم: 10 مگابایت
                        </li>
                    </ul>
                </div>

                <!-- File Input -->
                <div class="space-y-3 sm:space-y-4">
                    <input type="file" 
                           wire:model="{{ $wireModel }}" 
                           @change="validateFile($event)"
                           accept=".xlsx,.xls"
                           id="excel-file-input"
                           class="hidden">
                    
                    <label for="excel-file-input" 
                           class="block w-full p-6 sm:p-8 border-2 border-dashed border-gray-300 rounded-xl text-center cursor-pointer transition-all duration-200 hover:border-green-400 hover:bg-green-50">
                        <div class="flex flex-col items-center">
                            <svg class="w-8 h-8 sm:w-12 sm:h-12 text-gray-400 mb-3 sm:mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            <span class="text-base sm:text-lg font-medium text-gray-700 mb-1 sm:mb-2">آپلود فایل اکسل تکمیل شده</span>
                            <span class="text-xs sm:text-sm text-gray-500">فایل خود را انتخاب کنید یا اینجا بکشید</span>
                        </div>
                    </label>

                    <!-- Upload Progress -->
                    <div wire:loading wire:target="{{ $uploadMethod }}" class="w-full">
                        <div class="flex items-center justify-center p-3 sm:p-4 bg-blue-50 rounded-xl border border-blue-200">
                            <svg class="animate-spin -ml-1 mr-3 h-4 w-4 sm:h-5 sm:w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm sm:text-base text-blue-600 font-medium">در حال آپلود فایل...</span>
                        </div>
                    </div>

                    <!-- File Preview -->
                    <div x-show="fileSelected" 
                         x-transition
                         class="p-3 sm:p-4 rounded-xl border-2 transition-all duration-200"
                         :class="isValidFile ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50'">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2 sm:space-x-3 space-x-reverse min-w-0 flex-1">
                                <svg class="w-6 h-6 sm:w-8 sm:h-8 flex-shrink-0" :class="isValidFile ? 'text-green-600' : 'text-red-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm sm:text-base font-medium truncate" :class="isValidFile ? 'text-green-800' : 'text-red-800'" x-text="fileName"></p>
                                    <p class="text-xs sm:text-sm" :class="isValidFile ? 'text-green-600' : 'text-red-600'" x-text="fileSizeFormatted"></p>
                                </div>
                            </div>
                            <div class="flex items-center ml-2 flex-shrink-0">
                                <svg x-show="isValidFile" class="w-5 h-5 sm:w-6 sm:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <svg x-show="!isValidFile" class="w-5 h-5 sm:w-6 sm:h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Validation Messages -->
                    <div x-show="validationError" 
                         x-transition
                         class="p-3 sm:p-4 bg-red-50 border border-red-200 rounded-xl">
                        <div class="flex items-start">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-600 ml-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.664-.833-2.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <span class="text-sm sm:text-base text-red-700 font-medium" x-text="validationError"></span>
                        </div>
                    </div>

                    <div x-show="isValidFile && fileSelected" 
                         x-transition
                         class="p-3 sm:p-4 bg-green-50 border border-green-200 rounded-xl">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-green-600 ml-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-sm sm:text-base text-green-700 font-medium">فایل معتبر است و آماده آپلود</span>
                        </div>
                    </div>

                    <!-- Livewire Error Display -->
                    @error('{{ $wireModel }}')
                        <div class="p-3 sm:p-4 bg-red-50 border border-red-200 rounded-xl">
                            <div class="flex items-start">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-red-600 ml-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.664-.833-2.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <span class="text-sm sm:text-base text-red-700 font-medium">{{ $message }}</span>
                            </div>
                        </div>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div x-show="$wire.{{ $wireModel }}" 
                     x-transition
                     class="space-y-3 sm:space-y-4">
                    <button type="submit" 
                            x-bind:disabled="!isValidFile"
                            wire:loading.attr="disabled" 
                            wire:target="{{ $uploadMethod }}"
                            class="w-full flex justify-center py-2.5 sm:py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm sm:text-base font-bold text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                        <span wire:loading.remove wire:target="{{ $uploadMethod }}" class="flex items-center">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            آپلود فایل بیمه
                        </span>
                        <div wire:loading wire:target="{{ $uploadMethod }}" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-4 w-4 sm:h-5 sm:w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            در حال آپلود...
                        </div>
                    </button>
                </div>
            </form>

            <!-- Footer -->
            <div class="flex justify-end pt-3 sm:pt-4 border-t border-gray-200">
                <button wire:click="{{ $closeMethod }}"
                        class="px-4 sm:px-6 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    انصراف
                </button>
            </div>
        </div>
        </div>
    </div>
</div>