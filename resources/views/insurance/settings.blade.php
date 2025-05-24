<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <h2 class="text-2xl font-bold text-center mb-6">تنظیمات بیمه</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- کارت بودجه سازمان -->
            <div class="bg-white rounded-xl p-8 flex flex-col items-center justify-center shadow-sm border border-gray-100">
                <div class="mb-6 w-20 h-20 flex items-center justify-center">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="black" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.591 1.106c1.527-.878 3.286.88 2.408 2.408a1.724 1.724 0 001.107 2.592c1.755.425 1.755 2.923 0 3.349a1.724 1.724 0 00-1.107 2.592c.878 1.527-.881 3.286-2.408 2.408a1.724 1.724 0 00-2.592 1.107c-.425 1.755-2.923 1.755-3.349 0a1.724 1.724 0 00-2.592-1.107c-1.527.878-3.286-.881-2.408-2.408a1.724 1.724 0 00-1.107-2.592c-1.755-.426-1.755-2.924 0-3.35a1.724 1.724 0 001.107-2.592c-.878-1.527.881-3.286 2.408-2.408.996.574 2.25.072 2.592-1.106z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="black" d="M8 12h4m0 0l-2-2m2 2l-2 2" />
                    </svg>
                </div>
                <a href="{{ route('insurance.funding-manager') }}" class="bg-green-500 text-white px-4 py-2 rounded-full text-sm font-bold hover:bg-green-600 transition">بودجه سازمان</a>
            </div>
            <!-- کارت خسارات پرداخت شده -->
            <div class="bg-white rounded-xl p-8 flex flex-col items-center justify-center shadow-sm border border-gray-100">
                <div class="mb-6 w-20 h-20 flex items-center justify-center">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="black" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.591 1.106c1.527-.878 3.286.88 2.408 2.408a1.724 1.724 0 001.107 2.592c1.755.425 1.755 2.923 0 3.349a1.724 1.724 0 00-1.107 2.592c.878 1.527-.881 3.286-2.408 2.408a1.724 1.724 0 00-2.592 1.107c-.425 1.755-2.923 1.755-3.349 0a1.724 1.724 0 00-2.592-1.107c-1.527.878-3.286-.881-2.408-2.408a1.724 1.724 0 00-1.107-2.592c-1.755-.426-1.755-2.924 0-3.35a1.724 1.724 0 001.107-2.592c-.878-1.527.881-3.286 2.408-2.408.996.574 2.25.072 2.592-1.106z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="black" d="M16 12h-4m0 0l2-2m-2 2l2 2" />
                    </svg>
                </div>
                <a href="{{ route('insurance.paid-claims') }}" class="bg-green-500 text-white px-4 py-2 rounded-full text-sm font-bold hover:bg-green-600 transition">خسارات پرداخت شده</a>
            </div>
            <!-- کارت مناطق محروم -->
            <div class="bg-white rounded-xl p-8 flex flex-col items-center justify-center shadow-sm border border-gray-100">
                <div class="mb-6 w-20 h-20 flex items-center justify-center">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="black" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z" />
                    </svg>
                </div>
                <a href="{{ route('insurance.deprived-areas') }}" class="bg-green-500 text-white px-4 py-2 rounded-full text-sm font-bold hover:bg-green-600 transition">مناطق محروم</a>
            </div>
        </div>
    </div>
</x-app-layout> 