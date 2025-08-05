<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('تنظیمات عمومی بیمه') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- نمایش پیام موفقیت -->
                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- تنظیمات بیمه -->
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">تنظیمات عمومی</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                تنظیمات عمومی شرکت بیمه را از اینجا مدیریت کنید.
                            </p>
                        </div>
                    </div>

                    <!-- فرم تنظیمات -->
                    <form method="POST" action="{{ route('insurance.settings.update') }}" class="space-y-6" enctype="multipart/form-data">
                        @csrf

                        <!-- نام شرکت بیمه -->
                        <div>
                            <x-input-label for="insurance_name" value="نام شرکت بیمه" />
                            <x-text-input id="insurance_name" name="insurance_name" type="text" class="mt-1 block w-full"
                                :value="old('insurance_name', auth()->user()->organization->name ?? '')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('insurance_name')" />
                        </div>

                        <!-- آپلود لوگو -->
                        <div x-data="{ 
                            fileName: null, 
                            showNotification: false,
                            isUploading: false,
                            previewImage(event) {
                                const file = event.target.files[0];
                                if (file) {
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        const preview = document.getElementById('logo-preview');
                                        const placeholder = document.getElementById('logo-placeholder');
                                        
                                        preview.src = e.target.result;
                                        preview.style.display = 'block';
                                        preview.classList.remove('hidden');
                                        placeholder.classList.add('hidden');
                                        
                                        console.log('پیش‌نمایش لوگو جدید بارگذاری شد');
                                    };
                                    reader.readAsDataURL(file);
                                    
                                    this.fileName = file.name;
                                    this.showNotification = true;
                                    setTimeout(() => this.showNotification = false, 3000);
                                }
                            }
                        }" 
                        @if(session('logo_updated'))
                        x-init="setTimeout(() => {
                            const preview = document.getElementById('logo-preview');
                            if (preview) {
                                const baseUrl = preview.src.split('?')[0];
                                preview.src = baseUrl + '?v=' + Date.now();
                                preview.style.display = 'block';
                                preview.classList.remove('hidden');
                                document.getElementById('logo-placeholder').classList.add('hidden');
                            }
                        }, 100)"
                        @endif
                        >
                            <x-input-label for="logo" value="لوگوی شرکت بیمه" />
                            
                            <div class="mt-2 flex items-start space-x-4 space-x-reverse">
                                <!-- نمایش لوگوی فعلی یا پیش‌نمایش -->
                                <div class="flex-shrink-0 w-24 h-24 bg-gray-100 rounded-lg overflow-hidden border-2 border-dashed border-gray-300">
                                    @if (auth()->user()->organization && auth()->user()->organization->logo_path)
                                        <img id="logo-preview" 
                                             src="{{ url('/storage/' . auth()->user()->organization->logo_path) }}?v={{ time() }}" 
                                             alt="لوگوی شرکت بیمه" 
                                             class="w-full h-full object-contain"
                                             loading="eager"
                                             style="display: block;">
                                        <div id="logo-placeholder" class="w-full h-full hidden items-center justify-center bg-gray-200 text-gray-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @else
                                        <!-- تصویر مخفی برای پیش‌نمایش لوگوی جدید -->
                                        <img id="logo-preview" src="" class="hidden w-full h-full object-contain" />
                                        <div id="logo-placeholder" class="w-full h-full flex items-center justify-center bg-gray-200 text-gray-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="space-y-2 flex-grow">
                                    <div class="relative">
                                        <input type="file" 
                                               id="logo" 
                                               name="logo" 
                                               accept="image/jpeg,image/jpg,image/png,image/svg+xml" 
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                               @change="previewImage($event)">
                                        <p class="mt-1 text-sm text-gray-500">
                                            لوگوی شرکت بیمه را آپلود کنید. فرمت‌های مجاز: JPG، PNG، SVG (حداکثر 2 مگابایت)
                                        </p>
                                    </div>
                                    <x-input-error class="mt-2" :messages="$errors->get('logo')" />
                                    
                                    <!-- نوتیفیکیشن انتخاب فایل -->
                                    <div x-show="showNotification" 
                                         x-transition:enter="transition ease-out duration-300"
                                         x-transition:enter-start="opacity-0 transform scale-90"
                                         x-transition:enter-end="opacity-100 transform scale-100"
                                         x-transition:leave="transition ease-in duration-300"
                                         x-transition:leave-start="opacity-100 transform scale-100"
                                         x-transition:leave-end="opacity-0 transform scale-90"
                                         class="mt-2 p-2 bg-green-100 text-green-800 rounded-md flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span x-text="'فایل «' + fileName + '» انتخاب شد'"></span>
                                    </div>
                                 
                                </div>
                            </div>
                        </div>

                        <!-- ایمیل تماس -->
                        <div>
                            <x-input-label for="email" value="ایمیل تماس" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                :value="old('email', auth()->user()->organization->email ?? '')" />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>

                        <!-- تلفن تماس -->
                        <div>
                            <x-input-label for="phone" value="تلفن تماس" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                                :value="old('phone', auth()->user()->organization->phone ?? '')" />
                            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                        </div>

                        <!-- آدرس -->
                        <div>
                            <x-input-label for="address" value="آدرس" />
                            <x-textarea-input id="address" name="address" class="mt-1 block w-full">
                                {{ old('address', auth()->user()->organization->address ?? '') }}
                            </x-textarea-input>
                            <x-input-error class="mt-2" :messages="$errors->get('address')" />
                        </div>

                        <!-- توضیحات -->
                        <div>
                            <x-input-label for="description" value="توضیحات" />
                            <x-textarea-input id="description" name="description" class="mt-1 block w-full">
                                {{ old('description', auth()->user()->organization->description ?? '') }}
                            </x-textarea-input>
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                            <p class="mt-1 text-sm text-gray-500">
                                اطلاعات بیشتر درباره شرکت بیمه شما (اختیاری)
                            </p>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>ذخیره تغییرات</x-primary-button>
                            <a href="{{ route('insurance.settings') }}" class="text-gray-600 hover:text-gray-800">
                                بازگشت به تنظیمات
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 