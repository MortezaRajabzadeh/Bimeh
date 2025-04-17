<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('پروفایل') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('اطلاعات پروفایل') }}
                            </h2>

                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('به‌روزرسانی اطلاعات پروفایل و آدرس ایمیل خود.') }}
                            </p>
                        </header>

                        <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
                            @csrf
                            @method('patch')

                            <div>
                                <x-input-label for="name" :value="__('نام')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>

                            <div>
                                <x-input-label for="email" :value="__('ایمیل')" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                                <x-input-error class="mt-2" :messages="$errors->get('email')" />
                            </div>

                            <div>
                                <x-input-label for="mobile" :value="__('موبایل')" />
                                <x-text-input id="mobile" name="mobile" type="text" class="mt-1 block w-full" :value="old('mobile', $user->mobile)" required dir="ltr" />
                                <x-input-error class="mt-2" :messages="$errors->get('mobile')" />
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>{{ __('ذخیره') }}</x-primary-button>

                                @if (session('status') === 'profile-updated')
                                    <p
                                        x-data="{ show: true }"
                                        x-show="show"
                                        x-transition
                                        x-init="setTimeout(() => show = false, 2000)"
                                        class="text-sm text-gray-600"
                                    >{{ __('ذخیره شد.') }}</p>
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section class="space-y-6">
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ __('حذف حساب کاربری') }}
                            </h2>

                            <p class="mt-1 text-sm text-gray-600">
                                {{ __('پس از حذف حساب کاربری، تمام منابع و داده‌های آن به طور دائم حذف می‌شوند. قبل از حذف حساب کاربری خود، لطفاً هر گونه اطلاعاتی را که می‌خواهید حفظ کنید، دانلود کنید.') }}
                            </p>
                        </header>

                        <x-danger-button
                            x-data=""
                            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                        >{{ __('حذف حساب کاربری') }}</x-danger-button>

                        <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
                            <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
                                @csrf
                                @method('delete')

                                <h2 class="text-lg font-medium text-gray-900">
                                    {{ __('آیا مطمئن هستید که می‌خواهید حساب کاربری خود را حذف کنید؟') }}
                                </h2>

                                <p class="mt-1 text-sm text-gray-600">
                                    {{ __('پس از حذف حساب کاربری، تمام منابع و داده‌های آن به طور دائم حذف می‌شوند. لطفاً رمز عبور خود را وارد کنید تا تأیید کنید که می‌خواهید حساب کاربری خود را به طور دائم حذف کنید.') }}
                                </p>

                                <div class="mt-6">
                                    <x-input-label for="password" value="{{ __('رمز عبور') }}" class="sr-only" />

                                    <x-text-input
                                        id="password"
                                        name="password"
                                        type="password"
                                        class="mt-1 block w-3/4"
                                        placeholder="{{ __('رمز عبور') }}"
                                    />

                                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                                </div>

                                <div class="mt-6 flex justify-end">
                                    <x-secondary-button x-on:click="$dispatch('close')">
                                        {{ __('انصراف') }}
                                    </x-secondary-button>

                                    <x-danger-button class="ms-3">
                                        {{ __('حذف حساب کاربری') }}
                                    </x-danger-button>
                                </div>
                            </form>
                        </x-modal>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 