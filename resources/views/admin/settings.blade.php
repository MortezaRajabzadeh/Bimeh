<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold mb-4">تنظیمات سیستم</h2>
            <!-- منوی ناوبری -->
            <x-admin-nav />
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- کارت سازمان‌ها -->
            <div class="bg-white rounded-xl p-8 flex flex-col items-center justify-center shadow-sm border border-gray-100">
                <div class="mb-6 w-20 h-20 flex items-center justify-center">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 7V5.5C19 4.4 18.1 3.5 17 3.5H7C5.9 3.5 5 4.4 5 5.5V7" stroke="black" stroke-width="1.5"/>
                        <path d="M12 12V16" stroke="black" stroke-width="1.5"/>
                        <path d="M12 8V8.01" stroke="black" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M3 9.5V14.5C3 16.5 4 17.5 6 17.5H18C20 17.5 21 16.5 21 14.5V9.5C21 7.5 20 6.5 18 6.5H6C4 6.5 3 7.5 3 9.5Z" stroke="black" stroke-width="1.5"/>
                        <path d="M17 21.5H7" stroke="black" stroke-width="1.5"/>
                        <path d="M14 17.5V21.5" stroke="black" stroke-width="1.5"/>
                        <path d="M10 17.5V21.5" stroke="black" stroke-width="1.5"/>
                    </svg>
                </div>
                <a href="{{ route('admin.organizations.index') }}" class="bg-green-400 text-white px-6 py-2 rounded-full text-base hover:bg-green-500 transition">سازمان‌ها</a>
            </div>
            
            <!-- کارت کاربران -->
            <div class="bg-white rounded-xl p-8 flex flex-col items-center justify-center shadow-sm border border-gray-100">
                <div class="mb-6 w-20 h-20 flex items-center justify-center">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" stroke="black" stroke-width="1.5"/>
                        <path d="M20.5899 22C20.5899 18.13 16.7399 15 11.9999 15C7.25991 15 3.40991 18.13 3.40991 22" stroke="black" stroke-width="1.5"/>
                    </svg>
                </div>
                <a href="{{ route('admin.users.index') }}" class="bg-green-400 text-white px-6 py-2 rounded-full text-base hover:bg-green-500 transition">کاربران</a>
            </div>
            
            <!-- کارت تنظیمات سطح دسترسی -->
            <div class="bg-white rounded-xl p-8 flex flex-col items-center justify-center shadow-sm border border-gray-100">
                <div class="mb-6 w-20 h-20 flex items-center justify-center">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.9892 2.90039H10.9892C6.9892 2.90039 5.9892 3.90039 5.9892 7.90039V11.9004C5.9892 15.9004 6.9892 16.9004 10.9892 16.9004H14.9892C18.9892 16.9004 19.9892 15.9004 19.9892 11.9004V9.90039" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12.9 19.9V16.9" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12.9 22H9.90002" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15.9 22H15.89" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M19.97 7.90039C19.97 6.80039 19.07 5.90039 17.97 5.90039C16.87 5.90039 15.97 6.80039 15.97 7.90039C15.97 9.00039 16.87 9.90039 17.97 9.90039" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M19.97 7.90039H21.97" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M13.97 7.90039H15.97" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M17.97 5.90039V3.90039" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M17.97 11.9004V9.90039" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <a href="{{ route('admin.access-levels.index') }}" class="bg-green-400 text-white px-6 py-2 rounded-full text-base hover:bg-green-500 transition">تنظیمات سطح دسترسی</a>
            </div>
            
            <!-- کارت تنظیمات مناطق محروم -->
            <div class="bg-white rounded-xl p-8 flex flex-col items-center justify-center shadow-sm border border-gray-100">
                <div class="mb-6 w-20 h-20 flex items-center justify-center">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22 9.48047C22 15.2605 15.52 21.2005 12.57 21.2005C10.03 21.2005 2 14.2404 2 8.48047C2 4.0205 6.33 2.0105 9.64 5.0504C10.84 6.0704 11.48 7.80049 11.55 9.56049C13.21 9.55049 14.86 10.2105 16.06 11.2205C19.4 14.2504 22 9.48047 22 9.48047Z" stroke="black" stroke-width="1.5"/>
                        <path d="M9.5 13H12.5" stroke="black" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M11 14.5V11.5" stroke="black" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <a href="{{ route('admin.regions.index') }}" class="bg-green-400 text-white px-6 py-2 rounded-full text-base hover:bg-green-500 transition">تنظیمات مناطق محروم</a>
            </div>
        </div>
    </div>
</x-app-layout> 