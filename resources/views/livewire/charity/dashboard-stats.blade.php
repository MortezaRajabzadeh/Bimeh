<div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- کارت خانواده‌های بیمه شده -->
        <div class="bg-green-50 p-6 rounded-lg shadow-sm border border-green-100">
            <div class="flex items-center mb-4">
                <div class="bg-green-500 p-3 rounded-full mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">خانواده‌های بیمه شده</h3>
                    <p class="text-sm text-gray-600">{{ $insuredFamilies }} خانواده - {{ $insuredMembers }} نفر</p>
                </div>
            </div>
            <a href="{{ route('charity.insured-families') }}" class="w-full block text-center py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition">مشاهده</a>
        </div>

        <!-- کارت خانواده‌های بدون پوشش -->
        <div class="bg-red-50 p-6 rounded-lg shadow-sm border border-red-100">
            <div class="flex items-center mb-4">
                <div class="bg-red-500 p-3 rounded-full mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">خانواده‌های بدون پوشش</h3>
                    <p class="text-sm text-gray-600">{{ $uninsuredFamilies }} خانواده - {{ $uninsuredMembers }} نفر</p>
                </div>
            </div>
            <a href="{{ route('charity.uninsured-families') }}" class="w-full block text-center py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">مشاهده</a>
        </div>

        <!-- کارت افزودن خانواده جدید -->
        <div class="bg-blue-50 p-6 rounded-lg shadow-sm border border-blue-100">
            <div class="flex items-center mb-4">
                <div class="bg-blue-500 p-3 rounded-full mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">افزودن خانواده جدید</h3>
                    <p class="text-sm text-gray-600">ثبت خانواده نیازمند جدید</p>
                </div>
            </div>
            <a href="{{ route('charity.add-family') }}" class="w-full block text-center py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">افزودن</a>
        </div>
    </div>
</div> 