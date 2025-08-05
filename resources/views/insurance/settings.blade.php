<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <h2 class="text-2xl font-bold text-center mb-6">تنظیمات بیمه</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- کارت تنظیمات عمومی -->
            <div class="bg-white rounded-xl p-8 flex flex-col items-center justify-center shadow-sm border border-gray-100">
                <div class="mb-6 w-20 h-20 flex items-center justify-center">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="black" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.591 1.106c1.527-.878 3.286.88 2.408 2.408a1.724 1.724 0 001.107 2.592c1.755.425 1.755 2.923 0 3.349a1.724 1.724 0 00-1.107 2.592c.878 1.527-.881 3.286-2.408 2.408a1.724 1.724 0 00-2.592 1.107c-.425 1.755-2.923 1.755-3.349 0a1.724 1.724 0 00-2.592-1.107c-1.527.878-3.286-.881-2.408-2.408a1.724 1.724 0 00-1.107-2.592c-1.755-.426-1.755-2.924 0-3.35a1.724 1.724 0 001.107-2.592c-.878-1.527.881-3.286 2.408-2.408.996.574 2.25.072 2.592-1.106z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="black" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <a href="{{ route('insurance.settings.general') }}" class="bg-blue-500 text-white px-4 py-2 rounded-full text-sm font-bold hover:bg-blue-600 transition">تنظیمات عمومی</a>
            </div>
            
            <!-- کارت بودجه سازمان -->
            <div class="bg-white rounded-xl p-8 flex flex-col items-center justify-center shadow-sm border border-gray-100">
                <div class="mb-6 w-20 h-20 flex items-center justify-center">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="black" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <a href="{{ route('insurance.funding-manager') }}" class="bg-green-500 text-white px-4 py-2 rounded-full text-sm font-bold hover:bg-green-600 transition">بودجه سازمان</a>
            </div>
            
            <!-- کارت خسارات پرداخت شده -->
            <div class="bg-white rounded-xl p-8 flex flex-col items-center justify-center shadow-sm border border-gray-100">
                <div class="mb-6 w-20 h-20 flex items-center justify-center">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="black" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <a href="{{ route('insurance.paid-claims') }}" class="bg-green-500 text-white px-4 py-2 rounded-full text-sm font-bold hover:bg-green-600 transition">خسارات پرداخت شده</a>
            </div>
            
            <!-- کارت مناطق محروم -->
            <div class="bg-white rounded-xl p-8 flex flex-col items-center justify-center shadow-sm border border-gray-100">
                <div class="mb-6 w-20 h-20 flex items-center justify-center">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="black" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="black" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <a href="{{ route('insurance.deprived-areas') }}" class="bg-green-500 text-white px-4 py-2 rounded-full text-sm font-bold hover:bg-green-600 transition">مناطق محروم</a>
            </div>
        </div>
    </div>
</x-app-layout>