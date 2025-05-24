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

                    <!-- تنظیمات خیریه -->
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">تنظیمات عمومی</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                تنظیمات عمومی خیریه را از اینجا مدیریت کنید.
                            </p>
                        </div>

                        <div class="mt-6 border-t border-gray-200 pt-6">
                            <div class="divide-y divide-gray-200">
                                <!-- بخش تنظیمات -->
                                <div class="py-4">
                                    <p class="text-gray-500 text-sm">
                                        این بخش در حال توسعه است...
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- فرم تنظیمات -->
                    <form method="POST" action="{{ route('charity.settings.update') }}" class="space-y-6">
                        @csrf

                        <!-- نام خیریه -->
                        <div>
                            <x-input-label for="charity_name" value="نام خیریه" />
                            <x-text-input id="charity_name" name="charity_name" type="text" class="mt-1 block w-full"
                                :value="old('charity_name', auth()->user()->charity->name ?? '')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('charity_name')" />
                        </div>

                        <!-- ایمیل تماس -->
                        <div>
                            <x-input-label for="contact_email" value="ایمیل تماس" />
                            <x-text-input id="contact_email" name="contact_email" type="email" class="mt-1 block w-full"
                                :value="old('contact_email', auth()->user()->charity->contact_email ?? '')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('contact_email')" />
                        </div>

                        <!-- تلفن تماس -->
                        <div>
                            <x-input-label for="contact_phone" value="تلفن تماس" />
                            <x-text-input id="contact_phone" name="contact_phone" type="text" class="mt-1 block w-full"
                                :value="old('contact_phone', auth()->user()->charity->contact_phone ?? '')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('contact_phone')" />
                        </div>

                        <!-- آدرس -->
                        <div>
                            <x-input-label for="address" value="آدرس" />
                            <x-textarea-input id="address" name="address" class="mt-1 block w-full" required>
                                {{ old('address', auth()->user()->charity->address ?? '') }}
                            </x-textarea-input>
                            <x-input-error class="mt-2" :messages="$errors->get('address')" />
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