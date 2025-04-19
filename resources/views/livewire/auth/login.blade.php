<div>
    <div class="text-center mb-6">
        <img src="{{ asset('images/logo.svg') }}" alt="میکرو بیمه" class="mx-auto h-16">
        <h2 class="text-xl mt-4 text-green-600 font-bold">به میکرو بیمه خوش آمدید</h2>
        <p class="text-sm mt-2 text-gray-600">
            لطفا با استفاده از نام کاربری و رمز عبوری که در اختیار شما گذاشته شده وارد شوید
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit.prevent="login">
        <!-- نام کاربری -->
        <div class="mb-4">
            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">نام کاربری</label>
            <input wire:model="username" id="username" type="text" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition" 
                   placeholder="لطفا نام کاربری خود را وارد کنید" required>
            @error('username') <span class="mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
        </div>

        <!-- رمز عبور -->
        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">رمز عبور</label>
            <input wire:model="password" id="password" type="password" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition" 
                   placeholder="لطفا رمز عبور خود را وارد کنید" required>
            @error('password') <span class="mt-1 text-xs text-red-600">{{ $message }}</span> @enderror
        </div>

        <!-- دکمه ارسال -->
        <button type="submit" class="w-full flex justify-center py-2 px-4 mt-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition">
            <span>ورود به سامانه</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </button>
    </form>
</div> 