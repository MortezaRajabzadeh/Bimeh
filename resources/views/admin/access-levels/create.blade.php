<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-semibold text-gray-700">سطوح دسترسی</h2>
                    <a href="{{ route('admin.access-levels.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg flex items-center justify-center text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        بازگشت به لیست
                    </a>
                </div>

                <div class="p-8 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-yellow-500 ml-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">امکان ایجاد سطح دسترسی جدید وجود ندارد</h3>
                            <p class="text-gray-600">
                                سیستم دارای سه سطح دسترسی ثابت است:
                            </p>
                            <ul class="mt-3 list-disc mr-6 text-gray-600">
                                <li class="mb-1">ادمین (دسترسی کامل به تمام بخش‌ها)</li>
                                <li class="mb-1">خیریه</li>
                                <li class="mb-1">بیمه</li>
                            </ul>
                            <p class="mt-4 text-gray-600">
                                برای مدیریت مجوزهای هر سطح دسترسی، به صفحه لیست سطوح دسترسی مراجعه کرده و سطح مورد نظر را ویرایش کنید.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 