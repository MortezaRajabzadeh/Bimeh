@props(['totalMembers', 'insuranceExcelFile'])

<div x-show="showExcelUploadModal" x-cloak class="modal-container" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div @click.away="showExcelUploadModal = false" class="modal-content p-8 animate-fade-in max-w-xl" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">آپلود فایل اکسل</h2>
            <button @click="showExcelUploadModal = false" class="text-gray-400 hover:text-gray-600 focus:outline-none text-2xl">&times;</button>
        </div>
        
        {{-- پیام موفقیت/خطا داخل پاپ‌آپ اکسل --}}
        @if (session()->has('success'))
            <div class="bg-green-100 text-green-800 rounded-lg px-4 py-3 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </div>
        @endif
        
        @if (session()->has('error'))
            <div class="bg-red-100 text-red-800 rounded-lg px-4 py-3 mb-4 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                {{ session('error') }}
            </div>
        @endif
        
        <div class="mb-6">
            <div class="text-green-700 text-lg font-bold mb-3 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                اطلاعات <span x-text="$wire.selected.length"></span> خانواده معادل <span>{{ $totalMembers }}</span> نفر برای بیمه آماده شده است
            </div>
            
            <div class="text-gray-600 text-base leading-7 bg-blue-50 p-4 rounded-lg mb-6">
                برای تکمیل فرآیند بیمه، لطفا ابتدا با کلیک روی دکمه زیر، فایل نمونه اکسل را دانلود کنید.<br>
                سپس فایل را با اطلاعات بیمه (شماره بیمه‌نامه، تاریخ صدور و...) تکمیل کرده و در قسمت زیر آپلود نمایید.
            </div>
            
            <div class="flex justify-center mb-8">
                <button type="button" wire:click="downloadInsuranceExcel" class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-3 px-6 text-lg font-bold flex items-center justify-center gap-2 transition duration-200 ease-in-out">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                    </svg>
                    دانلود فایل نمونه اکسل
                </button>
            </div>
        </div>
        
        <form wire:submit.prevent="uploadInsuranceExcel" class="mt-8">
            <div class="flex flex-col items-center">
                <input type="file" wire:model="insuranceExcelFile" accept=".xlsx,.xls" class="hidden" id="excel-upload-input">
                <label for="excel-upload-input" class="w-full cursor-pointer">
                    <div class="bg-green-600 hover:bg-green-700 text-white rounded-xl py-4 text-lg font-bold flex items-center justify-center gap-2 transition duration-200 ease-in-out">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        آپلود فایل اکسل تکمیل شده
                    </div>
                </label>
                
                @if($insuranceExcelFile)
                    <div class="mt-4 text-green-700 text-sm font-bold flex items-center justify-center gap-2 animate-fade-in">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        فایل انتخاب شد: {{ $insuranceExcelFile->getClientOriginalName() }}
                    </div>
                    <button type="submit" class="mt-4 w-full bg-green-700 hover:bg-green-800 text-white rounded-xl py-3 text-lg font-bold transition duration-200 ease-in-out animate-fade-in">
                        تایید و ارسال فایل
                    </button>
                @endif
                
                @error('insuranceExcelFile')
                    <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                @enderror
            </div>
        </form>
    </div>
</div> 