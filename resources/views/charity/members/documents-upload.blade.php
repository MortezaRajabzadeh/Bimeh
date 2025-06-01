<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                آپلود مدارک: {{ $member->full_name }}
            </h2>
            <div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    بازگشت به داشبورد
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <!-- اطلاعات اصلی عضو -->
                <div class="mb-8 border-b border-gray-200 pb-5">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">اطلاعات عضو</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <span class="block text-sm font-medium text-gray-700">نام و نام خانوادگی:</span>
                            <span class="mt-1 block text-sm text-gray-900">{{ $member->full_name }}</span>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-700">کد ملی:</span>
                            <span class="mt-1 block text-sm text-gray-900">{{ $member->national_code ?? 'ثبت نشده' }}</span>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-700">نسبت با سرپرست:</span>
                            <span class="mt-1 block text-sm text-gray-900">{{ $member->relationship_fa }}</span>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-700">وضعیت در خانواده:</span>
                            <span class="mt-1 block text-sm text-gray-900">{{ $member->is_head ? 'سرپرست خانوار' : 'عضو خانواده' }}</span>
                        </div>
                    </div>
                </div>

                <div class="mx-auto max-w-3xl">
                    <!-- آپلود مدرک بیماری خاص -->
                    <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            مدرک بیماری خاص
                        </h3>

                        @if($specialDiseaseDocument)
                            <!-- نمایش فایل آپلود شده -->
                            <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-4">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="mr-3">
                                        <h3 class="text-sm font-medium text-green-800">مدرک بیماری خاص آپلود شده است</h3>
                                        <div class="mt-2 text-sm text-green-700">
                                            <ul class="list-disc space-y-1 mr-5">
                                                <li>نام فایل: {{ $specialDiseaseDocument->file_name }}</li>
                                                <li>تاریخ آپلود: {{ jdate($specialDiseaseDocument->created_at)->format('Y/m/d H:i') }}</li>
                                                <li>حجم فایل: {{ round($specialDiseaseDocument->size / 1024) }} کیلوبایت</li>
                                            </ul>
                                        </div>
                                        <div class="mt-4">
                                            <a href="{{ route('family.members.documents.show', ['family' => $family->id, 'member' => $member->id, 'collection' => 'special_disease_documents', 'media' => $specialDiseaseDocument->id]) }}" target="_blank" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                مشاهده فایل
                                            </a>
                                            <p class="mt-3 text-xs text-green-600">می‌توانید در صورت نیاز، فایل جدیدی آپلود کنید تا جایگزین فایل قبلی شود.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- راهنمای آپلود -->
                            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="mr-3">
                                        <p class="text-sm text-yellow-700">لطفاً مدرک بیماری خاص (گواهی پزشک متخصص یا نتایج آزمایشات) را در این قسمت آپلود کنید.</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <form action="{{ route('family.members.documents.store', ['family' => $family->id, 'member' => $member->id]) }}" method="POST" enctype="multipart/form-data" class="mt-4">
                            @csrf
                            <input type="hidden" name="document_type" value="special_disease">
                            
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:bg-gray-50 transition duration-150">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="special_disease_document" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                                            <span>انتخاب فایل</span>
                                            <input id="special_disease_document" name="document" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.pdf">
                                        </label>
                                        <p class="pr-1">یا فایل را اینجا رها کنید</p>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        فرمت‌های مجاز: JPG، PNG، PDF (حداکثر 5 مگابایت)
                                    </p>
                                </div>
                            </div>
                            @error('document')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            <div class="mt-4">
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    آپلود مدرک بیماری خاص
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- راهنمای آپلود مدارک -->
                <div class="mt-8 bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mr-3">
                            <h3 class="text-sm font-medium text-blue-800">راهنمای آپلود مدارک</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc space-y-1 mr-5">
                                    <li>مدارک باید خوانا و با کیفیت مناسب باشند.</li>
                                    <li>فرمت‌های مجاز شامل JPG، PNG و PDF است.</li>
                                    <li>حداکثر حجم هر فایل 5 مگابایت است.</li>
                                    <li>در صورت آپلود مجدد، فایل قبلی حذف خواهد شد.</li>
                                    <li>پس از آپلود، مدارک توسط کارشناسان بررسی خواهند شد.</li>
                                    <li>برای <strong>بیماری خاص</strong>، گواهی پزشک متخصص یا نتایج آزمایشات را آپلود کنید.</li>
                                </ul>
                            </div>
                            <div class="mt-3 text-xs text-blue-600">
                                <span class="font-bold">توجه:</span> آپلود مدارک برای تأیید وضعیت و بهره‌مندی از خدمات حمایتی ضروری است.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- اسکریپت‌های مربوط به Drag & Drop -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // اضافه کردن قابلیت Drag & Drop برای فیلدهای آپلود
            ['special_disease_document'].forEach(function(id) {
                const fileInput = document.getElementById(id);
                const dropZone = fileInput.closest('div.border-dashed');
                const submitBtn = fileInput.closest('form').querySelector('button[type="submit"]');
                
                // نمایش نام فایل انتخاب شده
                fileInput.addEventListener('change', function() {
                    if (this.files.length) {
                        const fileName = this.files[0].name;
                        const fileSize = Math.round(this.files[0].size / 1024); // کیلوبایت
                        const fileType = this.files[0].type;
                        const isValidType = [
                            'image/jpeg', 
                            'image/png', 
                            'application/pdf'
                        ].includes(fileType);
                        
                        // حذف اطلاعات فایل قبلی (اگر وجود دارد)
                        const existingInfo = dropZone.querySelector('.file-info');
                        if (existingInfo) {
                            existingInfo.remove();
                        }
                        
                        const fileInfo = document.createElement('div');
                        fileInfo.className = 'mt-3 text-sm text-gray-900 file-info';
                        
                        if (isValidType && fileSize <= 5120) {
                            // فایل معتبر است
                            fileInfo.innerHTML = `
                                <div class="flex items-center p-2 bg-green-50 rounded-lg">
                                    <svg class="h-5 w-5 text-green-500 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <div>
                                        <span class="font-medium">فایل انتخاب شده:</span> ${fileName} (${fileSize} KB)
                                    </div>
                                </div>
                            `;
                            
                            dropZone.classList.add('border-green-300', 'bg-green-50');
                            dropZone.classList.remove('border-gray-300', 'border-red-300', 'bg-red-50');
                            
                            // فعال کردن دکمه ارسال
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                            submitBtn.classList.add('hover:bg-indigo-700');
                        } else {
                            // فایل نامعتبر است
                            let errorMsg = '';
                            
                            if (!isValidType) {
                                errorMsg = 'فرمت فایل نامعتبر است. لطفاً از فرمت‌های JPG، PNG یا PDF استفاده کنید.';
                            } else if (fileSize > 5120) {
                                errorMsg = 'حجم فایل بیش از 5 مگابایت است. لطفاً فایل کوچکتری انتخاب کنید.';
                            }
                            
                            fileInfo.innerHTML = `
                                <div class="flex items-center p-2 bg-red-50 rounded-lg">
                                    <svg class="h-5 w-5 text-red-500 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <span class="font-medium text-red-700">خطا:</span> ${errorMsg}
                                    </div>
                                </div>
                            `;
                            
                            dropZone.classList.add('border-red-300', 'bg-red-50');
                            dropZone.classList.remove('border-gray-300', 'border-green-300', 'bg-green-50');
                            
                            // غیرفعال کردن دکمه ارسال
                            submitBtn.disabled = true;
                            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                            submitBtn.classList.remove('hover:bg-indigo-700');
                        }
                        
                        dropZone.appendChild(fileInfo);
                    }
                });
                
                // رویدادهای Drag & Drop
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, preventDefaults, false);
                });
                
                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, function() {
                        dropZone.classList.add('border-indigo-300', 'bg-indigo-50');
                        dropZone.classList.remove('border-gray-300', 'border-green-300', 'border-red-300', 'bg-green-50', 'bg-red-50');
                    }, false);
                });
                
                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, function() {
                        dropZone.classList.remove('border-indigo-300', 'bg-indigo-50');
                        dropZone.classList.add('border-gray-300');
                    }, false);
                });
                
                dropZone.addEventListener('drop', function(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    
                    if (files.length) {
                        fileInput.files = files;
                        const event = new Event('change');
                        fileInput.dispatchEvent(event);
                    }
                }, false);
            });
        });
    </script>
</x-app-layout> 