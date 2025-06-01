<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('تنظیمات') }}
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

                    <!-- تنظیمات خیریه -->
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">تنظیمات عمومی</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                تنظیمات عمومی خیریه را از اینجا مدیریت کنید.
                            </p>
                        </div>
                    </div>

                    <!-- فرم تنظیمات -->
                    <form method="POST" action="{{ route('charity.settings.update') }}" class="space-y-6" enctype="multipart/form-data">
                        @csrf

                        <!-- نام خیریه -->
                        <div>
                            <x-input-label for="charity_name" value="نام خیریه" />
                            <x-text-input id="charity_name" name="charity_name" type="text" class="mt-1 block w-full"
                                :value="old('charity_name', auth()->user()->organization->name ?? '')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('charity_name')" />
                        </div>

                        <!-- آپلود لوگو -->
                        <div x-data="{ fileName: null, showNotification: false }">
                            <x-input-label for="logo" value="لوگوی خیریه" />
                            
                            <div class="mt-2 flex items-start space-x-4 space-x-reverse">
                                <!-- نمایش لوگوی فعلی -->
                                <div class="flex-shrink-0 w-24 h-24 bg-gray-100 rounded-lg overflow-hidden">
                                    @if(auth()->user()->organization && auth()->user()->organization->logo_path)
                                        <img src="{{ asset('storage/' . auth()->user()->organization->logo_path) }}" alt="لوگوی خیریه" class="w-full h-full object-contain">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-gray-200 text-gray-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="space-y-2 flex-grow">
                                    <div class="relative">
                                        <input type="file" id="logo" name="logo" accept="image/*" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                            @change="fileName = $event.target.files[0].name; showNotification = true; setTimeout(() => showNotification = false, 3000)">
                                        <p class="mt-1 text-sm text-gray-500">
                                            لوگوی خیریه را آپلود کنید. فرمت‌های مجاز: JPG، PNG، SVG (حداکثر 2 مگابایت)
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
                                اطلاعات بیشتر درباره خیریه شما (اختیاری)
                            </p>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>ذخیره تغییرات</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 