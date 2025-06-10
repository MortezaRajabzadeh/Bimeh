<div class="h-screen flex items-center justify-center bg-gray-800">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <div class="text-center mb-6">
            <div class="flex justify-center mb-2">
                <img src="{{ asset('images/mb7.jpg') }}" alt="Logo" class="h-24 w-24">
            </div>
            <h2 class="text-xl font-bold text-green-600 mb-1">به میکرو بیمه خوش آمدید</h2>
            <br>
            <p class="text-sm text-gray-600">لطفا با استفاده از نام کاربری، ایمیل یا شماره موبایل وارد سامانه شوید</p>
        </div>
        
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif
        
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif
        
        <form wire:submit.prevent="login">
            <div class="mb-4">
                <label for="identifier" class="block text-sm font-medium text-gray-700 mb-1">نام کاربری / ایمیل / موبایل</label>
                <input type="text" 
                    id="identifier" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                    wire:model="identifier" 
                    placeholder="نام کاربری، ایمیل یا موبایل خود را وارد کنید" 
                    autocomplete="username">
                @error('identifier') <div class="mt-1 text-red-500 text-sm">{{ $message }}</div> @enderror
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">رمز عبور</label>
                <input type="password" 
                    id="password" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                    wire:model="password" 
                    placeholder="رمز عبور را وارد کنید" 
                    autocomplete="current-password">
                @error('password') <div class="mt-1 text-red-500 text-sm">{{ $message }}</div> @enderror
            </div>
            
            <button type="submit" class="w-full flex justify-center items-center bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" />
                </svg>
                ورود به سامانه
            </button>
            
            <div class="mt-4 text-center">
                <div class="text-sm text-gray-600">
                    می‌توانید با استفاده از اطلاعات زیر وارد شوید:
                </div>
                <div class="mt-1 text-xs text-gray-500">
                    <div><strong>مدیر سیستم:</strong> admin@microbime.com / Admin@123456</div>
                    <div><strong>خیریه:</strong> charity@microbime.com / Charity@123456</div>
                    <div><strong>بیمه:</strong> insurance@microbime.com / Insurance@123456</div>
                </div>
            </div>
        
        </form>
    </div>
</div>

@livewireScripts 