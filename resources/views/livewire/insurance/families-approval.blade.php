<div class="bg-white rounded-lg shadow p-2">
    <!-- Tabs -->
    <div class="flex border-b text-center text-sm font-bold mb-4">
        <button class="flex-1 py-3 border-b-4 border-transparent focus:outline-none {{ $tab === 'pending' ? 'border-green-700 bg-green-700 text-white' : '' }}" wire:click="setTab('pending')">در انتظار تایید</button>
        <button class="flex-1 py-3 border-b-4 border-transparent focus:outline-none {{ $tab === 'reviewing' ? 'border-green-700 bg-green-700 text-white' : '' }}" wire:click="setTab('reviewing')">در انتظار حمایت</button>
        <button class="flex-1 py-3 border-b-4 border-transparent focus:outline-none {{ $tab === 'approved' ? 'border-green-700 bg-green-700 text-white' : '' }}" wire:click="setTab('approved')">در انتظار صدور</button>
        <button class="flex-1 py-3 border-b-4 border-transparent focus:outline-none {{ $tab === 'renewal' ? 'border-green-700 bg-green-700 text-white' : '' }}" wire:click="setTab('renewal')">در انتظار تمدید</button>
        <button class="flex-1 py-3 border-b-4 border-transparent focus:outline-none {{ $tab === 'deleted' ? 'border-green-700 bg-green-700 text-white' : '' }}" wire:click="setTab('deleted')">حذف شده ها</button>
    </div>
    <!-- نوار ابزار عملیات دسته جمعی -->
    <div x-data="{ showApproveModal: false, showReturnModal: false, showApproveAndContinueModal: false, showExcelUploadModal: false }" @keydown.escape.window="showApproveModal = false; showReturnModal = false; showApproveAndContinueModal = false">
        <!-- Modal -->
        <div x-show="showApproveModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30">
            <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-8 relative">
                <button @click="showApproveModal = false" class="absolute left-4 top-4 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                <div class="text-center">
                    <h2 class="text-2xl font-bold mb-4">تایید و ادامه</h2>
                    <div class="text-green-700 text-lg font-bold mb-2">اطلاعات هویتی تعداد <span x-text="$wire.selected.length"></span> خانواده معادل <span>{{ $totalSelectedMembers }}</span> نفر مورد تایید است</div>
                    <div class="text-gray-500 text-base mb-6">تایید این خانواده ها به منزله بررسی و تایید اطلاعات هویتی و مدارک مورد نیاز این افراد از نظر شما می‌باشد. پس از تایید این افراد در قسمت "در انتظار حمایت" قرار می‌گیرند تا در زمان مقتضی فرایند تایید جهت صدور بیمه نامه برای آنها انجام گردد.</div>
                    <div class="flex flex-row-reverse gap-2 mt-6">
                        <button @click="$wire.approveSelected(); showApproveModal = false" class="flex-1 bg-green-600 hover:bg-green-700 text-white rounded-lg py-3 text-lg font-bold flex items-center justify-center gap-2 transition">
                            <svg xmlns='http://www.w3.org/2000/svg' class='h-6 w-6' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7' /></svg>
                            تایید نهایی و ادامه
                        </button>
                        <button @click="showApproveModal = false" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg py-3 text-lg font-bold">بازگشت و ایجاد تغییر</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Return to Pending Modal -->
        <div x-show="showReturnModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30">
            <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-8 relative">
                <button @click="showReturnModal = false" class="absolute left-4 top-4 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                <div class="text-center">
                    <h2 class="text-2xl font-bold mb-4">بازگشت به مرحله قبل</h2>
                    <div class="text-indigo-600 text-lg font-bold mb-2">
                        با بازگشت <span x-text="$wire.selected.length"></span> خانواده (<span>{{ $totalSelectedMembers }}</span> نفر) به مرحله "در انتظار تایید" موافقم
                    </div>
                    <div class="text-gray-500 text-base mb-6">با تایید این کار افراد و خانواده ها به مرحله "در انتظار تایید" منتقل خواهند شد.<br>این کار درصورتی انجام می‌گیرد که افراد به اشتباه به این قسمت انتقال پیدا کرده باشند.</div>
                    <div class="flex flex-row-reverse gap-2 mt-6">
                        <button @click="$wire.returnToPendingSelected(); showReturnModal = false" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg py-3 text-lg font-bold flex items-center justify-center gap-2 transition">
                            <svg xmlns='http://www.w3.org/2000/svg' class='h-6 w-6' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7' /></svg>
                            تایید و بازگرداندن خانواده
                        </button>
                        <button @click="showReturnModal = false" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg py-3 text-lg font-bold">بازگشت و ایجاد تغییر</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Approve & Continue Modal (for reviewing tab) -->
        <div x-show="showApproveAndContinueModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30">
            <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-8 relative">
                <button @click="showApproveAndContinueModal = false" class="absolute left-4 top-4 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                <div class="text-center">
                    <h2 class="text-2xl font-bold mb-4">تایید و ادامه</h2>
                    <div class="text-green-700 text-lg font-bold mb-2">اطلاعات هویتی تعداد <span x-text="$wire.selected.length"></span> خانواده معادل <span>{{ $totalSelectedMembers }}</span> نفر مورد تایید است</div>
                    <div class="text-gray-500 text-base mb-6">تایید این خانواده ها به منزله بررسی و تایید اطلاعات هویتی و مدارک مورد نیاز این افراد از نظر شما می‌باشد. پس از تایید این افراد در قسمت "در انتظار صدور" قرار می‌گیرند تا در زمان مقتضی فرایند تایید جهت صدور بیمه نامه برای آنها انجام گردد.</div>
                    <div class="flex flex-row-reverse gap-2 mt-6">
                        <button @click="$wire.approveAndContinueSelected(); showApproveAndContinueModal = false" class="flex-1 bg-green-600 hover:bg-green-700 text-white rounded-lg py-3 text-lg font-bold flex items-center justify-center gap-2 transition">
                            <svg xmlns='http://www.w3.org/2000/svg' class='h-6 w-6' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M5 13l4 4L19 7' /></svg>
                            تایید نهایی و ادامه
                        </button>
                        <button @click="showApproveAndContinueModal = false" wire:click.prevent="downloadInsuranceExcel" wire:loading.attr="disabled" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg py-3 text-lg font-bold flex items-center justify-center gap-2">
                            دریافت فایل اکسل
                            <svg xmlns='http://www.w3.org/2000/svg' class='h-6 w-6 inline-block mr-2' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4' /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Excel Upload Modal -->
        <div x-show="showExcelUploadModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30">
            <div class="bg-white rounded-2xl shadow-xl max-w-xl w-full p-8 relative">
                <button @click="showExcelUploadModal = false" class="absolute left-4 top-4 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                <div class="text-center">
                    {{-- پیام موفقیت/خطا داخل پاپ‌آپ اکسل --}}
                    @if (session()->has('success'))
                        <div class="bg-green-100 text-green-800 rounded px-4 py-2 mb-4">{{ session('success') }}</div>
                    @endif
                    @if (session()->has('error'))
                        <div class="bg-red-100 text-red-800 rounded px-4 py-2 mb-4">{{ session('error') }}</div>
                    @endif
                    <h2 class="text-2xl font-bold mb-4">تایید و ادامه</h2>
                    <div class="text-green-700 text-xl font-bold mb-2">
                        اطلاعات <span x-text="$wire.selected.length"></span> خانواده معادل <span>{{ $totalSelectedMembers }}</span> نفر جهت تخصیص بیمه مورد تایید است
                    </div>
                    <div class="text-gray-600 text-base mb-6 leading-8">
                        تایید این خانواده ها به منزله این است که بیمه برای این افراد صادر شده است لذا لازم است فایل حاوی اطلاعات مربوط به نوع، مبلغ، زمان صدور در فایل اکسل که در مرحله قبل دانلود شده، پر شود و سپس در اینجا بارگذاری شود.<br>
                        پس از بارگذاری و در صورت تکمیل بودن اطلاعات لیست افراد و خانواده های بیمه شده به قسمت "خانواده های بیمه شده" انتقال پیدا خواهد کرد.
                    </div>
                    <form wire:submit.prevent="uploadInsuranceExcel" class="mt-8">
                        <input type="file" wire:model="insuranceExcelFile" accept=".xlsx,.xls" class="hidden" id="excel-upload-input">
                        <label for="excel-upload-input" class="block cursor-pointer">
                            <span class="flex items-center justify-center gap-2 {{ $insuranceExcelFile ? 'bg-green-700' : 'bg-green-600' }} hover:bg-green-700 text-white rounded-xl py-3 px-8 text-lg font-bold transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" /></svg>
                                بارگذاری فایل اکسل
                            </span>
                        </label>
                        @if($insuranceExcelFile)
                            <div class="mt-3 text-green-700 text-sm font-bold flex items-center justify-center gap-2 animate-fade-in">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                فایل انتخاب شد: {{ $insuranceExcelFile->getClientOriginalName() }}
                            </div>
                            <button type="submit" class="mt-4 w-full bg-green-600 hover:bg-green-700 text-white rounded-xl py-3 text-lg font-bold transition animate-fade-in">تایید و ارسال فایل</button>
                        @endif
                        @error('insuranceExcelFile')
                            <div class="text-red-500 mt-2 text-sm">{{ $message }}</div>
                        @enderror
                    </form>
                </div>
            </div>
        </div>
        <div x-data="{
            selectAll: false,
            selectedFamilies: [],
            toggleAllFamilies() {
                if (this.selectAll) {
                    this.selectedFamilies = this.getIds();
                } else {
                    this.selectedFamilies = [];
                }
            },
            getIds() {
                return Array.from(document.querySelectorAll('input[name=\'family_ids[]\']')).map(el => el.value);
            },
            get hasSelected() {
                return this.selectedFamilies.length > 0;
            }
        }" x-init="$watch('selectedFamilies', value => $wire.set('selected', value)); window.addEventListener('reset-checkboxes', () => { selectedFamilies = []; selectAll = false; });" class="mb-4">
            <div x-show="hasSelected" x-cloak class="mb-4 p-3 bg-gray-100 rounded-lg flex justify-between items-center">
                <div>
                    <span class="text-sm text-gray-700">
                        <span x-text="selectedFamilies.length"></span> خانواده انتخاب شده است
                    </span>
                </div>
                <div class="flex gap-2">
                    <button @click="selectAll = false; selectedFamilies = []"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 text-xs px-3 py-1 rounded">
                        لغو انتخاب
                    </button>
                    <button type="button"
                        wire:click="deleteSelected"
                        class="bg-red-500 hover:bg-red-600 text-white text-xs px-3 py-1 rounded"
                        x-bind:disabled="selectedFamilies.length === 0">
                        حذف انتخاب شده
                    </button>
                    @if($tab === 'reviewing')
                        <button type="button"
                            @click="showApproveAndContinueModal = true"
                            class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded"
                            x-bind:disabled="selectedFamilies.length === 0">
                            تایید و ادامه
                        </button>
                        <button type="button"
                            @click="showReturnModal = true"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-1 rounded"
                            x-bind:disabled="selectedFamilies.length === 0">
                            بازگرداندن به مرحله قبل
                        </button>
                    @elseif($tab === 'approved')
                        <button type="button"
                            @click="showExcelUploadModal = true"
                            class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded"
                            x-bind:disabled="selectedFamilies.length === 0">
                            تایید و ادامه
                        </button>
                    @else
                        <button type="button"
                            @click="showApproveModal = true"
                            class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded"
                            x-bind:disabled="selectedFamilies.length === 0">
                            تایید انتخاب شده
                        </button>
                    @endif
                </div>
            </div>
            <!-- جدول -->
            <div class="w-full overflow-hidden shadow-sm border border-gray-200 rounded-lg">
                <div class="w-full overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50 text-xs text-gray-700">
                                <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">
                                    <input type="checkbox"
                                        x-model="selectAll"
                                        @change="toggleAllFamilies()"
                                        class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                </th>
                                <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">شناسه</th>
                                <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">رتبه محرومیت</th>
                                <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">استان</th>
                                <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">شهر/روستا</th>
                                <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">تعداد بیمه‌ها</th>
                                <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">معیار پذیرش</th>
                                <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">تعداد اعضا</th>
                                <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">سرپرست خانوار</th>
                                <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">خیریه معرف</th>
                                <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                    @if(in_array($tab, ['insured', 'approved']))
                                        تاریخ پایان بیمه
                                    @else
                                        تاریخ ثبت
                                    @endif
                                </th>
                                <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">وضعیت</th>
                                <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">آیکون‌های اعتبارسنجی</th>
                                <th scope="col" class="px-5 py-3 text-center border-b border-gray-200 font-medium">جزئیات</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($families as $family)
                            <tr class="hover:bg-gray-50" data-family-id="{{ $family->id }}">
                                <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                    <input type="checkbox" name="family_ids[]" value="{{ $family->id }}" x-model="selectedFamilies" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">{{ $family->id }}</td>
                                <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                    @if($family->province && isset($family->province->deprivation_rank))
                                        <div class="flex items-center justify-center">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center
                                                {{ $family->province->deprivation_rank <= 3 ? 'bg-red-100 text-red-800' : 
                                                   ($family->province->deprivation_rank <= 6 ? 'bg-yellow-100 text-yellow-800' : 
                                                    'bg-green-100 text-green-800') }}">
                                                {{ $family->province->deprivation_rank }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-100 text-gray-800">
                                                {{ $family->rank ?? '-' }}
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">{{ $family->province->name ?? '-' }}</td>
                                <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">{{ $family->city->name ?? '-' }}</td>
                                <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                    <div class="flex flex-col items-center">
                                        <span class="text-lg font-bold {{ $family->insurances_count > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                            {{ $family->insurances_count ?? 0 }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            {{ $family->insurances_count > 0 ? 'بیمه فعال' : 'بدون بیمه' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                    @php
                                        // تجمیع مشکلات از همه اعضای خانواده
                                        $problemCounts = [
                                            'addiction' => 0,
                                            'special_disease' => 0,
                                            'unemployment' => 0,
                                            'work_disability' => 0
                                        ];
                                        
                                        foreach ($family->members as $member) {
                                            if (is_array($member->problem_type)) {
                                                foreach ($member->problem_type as $problem) {
                                                    if (isset($problemCounts[$problem])) {
                                                        $problemCounts[$problem]++;
                                                    }
                                                }
                                            }
                                        }
                                        
                                        // فیلتر کردن مشکلاتی که حداقل یک عضو دارد
                                        $activeProblems = array_filter($problemCounts, fn($count) => $count > 0);
                                        
                                        // نام‌های فارسی مشکلات
                                        $problemLabels = [
                                            'addiction' => 'اعتیاد',
                                            'special_disease' => 'بیماری خاص',
                                            'unemployment' => 'بیکاری', 
                                            'work_disability' => 'ازکارافتادگی'
                                        ];
                                        
                                        // رنگ‌های مختلف برای هر مشکل
                                        $problemColors = [
                                            'addiction' => ['bg' => 'bg-red-100', 'text' => 'text-red-800'],
                                            'special_disease' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800'],
                                            'unemployment' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800'],
                                            'work_disability' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800']
                                        ];
                                    @endphp

                                    @if(!empty($activeProblems))
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($activeProblems as $problem => $count)
                                                @php $colors = $problemColors[$problem]; @endphp
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $colors['bg'] }} {{ $colors['text'] }} mr-1 mb-1">
                                                    {{ $problemLabels[$problem] }}
                                                    @if($count > 1)
                                                        <span class="mr-1 bg-white bg-opacity-60 rounded-full px-1 text-xs">×{{ $count }}</span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            بدون مشکل خاص
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">{{ $family->members->count() ?? 0 }}</td>
                                <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                    @php
                                        $head = $family->members?->where('is_head', true)->first();
                                    @endphp
                                    @if($head)
                                        <div class="flex items-center justify-center">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                {{ $head->first_name }} {{ $head->last_name }}
                                            </span>
                                        </div>
                                        @if($head->national_code)
                                            <div class="text-center mt-1">
                                                <span class="text-xs text-gray-500">کد ملی: {{ $head->national_code }}</span>
                                            </div>
                                        @endif
                                    @else
                                        <div class="flex items-center justify-center">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                                ⚠️ بدون سرپرست
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                    <div class="flex items-center justify-end">
                                        @if($family->organization)
                                            <span class="ml-2">{{ $family->organization->name }}</span>
                                            @if($family->organization->logo)
                                                <img src="{{ $family->organization->logo }}" alt="لوگوی خیریه" class="w-6 h-6 rounded-full object-cover">
                                            @else
                                                <img src="/images/sample-logo.png" alt="logo" class="w-6 h-6 rounded-full">
                                            @endif
                                        @else
                                            <img src="/images/sample-logo.png" alt="logo" class="w-6 h-6 rounded-full">
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                    @if($family->created_at)
                                        @php
                                            try {
                                                echo jdate($family->created_at)->format('Y/m/d');
                                            } catch (\Exception $e) {
                                                echo $family->created_at->format('Y/m/d');
                                            }
                                        @endphp
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                    @switch($family->status)
                                        @case('pending')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">در انتظار بررسی</span>
                                            @break
                                        @case('reviewing')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">در حال بررسی</span>
                                            @break
                                        @case('approved')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">تایید شده</span>
                                            @break
                                        @case('insured')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">بیمه شده</span>
                                            @break
                                        @case('renewal')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">در انتظار تمدید</span>
                                            @break
                                        @case('rejected')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">رد شده</span>
                                            @break
                                        @default
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $family->status_label ?? '-' }}</span>
                                    @endswitch
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                    <div class="flex items-center justify-center">
                                        <x-family-validation-icons :family="$family" size="sm" />
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                    <button wire:click="toggleFamily({{ $family->id }})"
                                        class="bg-green-200 hover:bg-green-300 text-green-800 text-xs py-1 px-2 rounded-full transition-colors duration-150 ease-in-out toggle-family-btn"
                                        data-family-id="{{ $family->id }}">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4 inline-block transition-transform duration-200 {{ $expandedFamily === $family->id ? 'rotate-180' : '' }}"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            @if($expandedFamily === $family->id)
                            <tr class="bg-green-50">
                                <td colspan="14" class="p-0">
                                    <div class="overflow-hidden shadow-inner rounded-lg bg-green-50 p-2">
                                        <div class="overflow-x-auto w-full max-h-96 scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100">
                                            <table class="min-w-full table-auto bg-green-50 border border-green-100 rounded-lg family-members-table" wire:key="family-{{ $family->id }}">
                                                <thead>
                                                    <tr class="bg-green-100 border-b border-green-200">
                                                        <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right sticky left-0 bg-green-100">سرپرست؟</th>
                                                        <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">نسبت</th>
                                                        <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">نام و نام خانوادگی</th>
                                                        <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">کد ملی</th>
                                                        <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">تاریخ تولد</th>
                                                        <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">جنسیت</th>
                                                        <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">شغل</th>
                                                        <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">نوع مشکل</th>
                                                        <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">
                                                            <span class="text-lg" title="خیریه معرف" aria-label="خیریه معرف">🏷️</span>
                                                        </th>
                                                        <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">نوع بیمه</th>
                                                        <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">پرداخت کننده حق بیمه</th>
                                                        <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">درصد مشارکت</th>
                                                        <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">اعتبارسنجی</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($family->members as $member)
                                                    <tr class="bg-green-100 border-b border-green-200 hover:bg-green-200" wire:key="member-{{ $member->id }}">
                                                        <td class="px-3 py-3 text-sm text-gray-800 text-center sticky left-0 bg-green-100">
                                                            @if($member->is_head)
                                                                <span class="text-blue-500 font-bold inline-flex items-center">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                    سرپرست
                                                                </span>
                                                            @else
                                                                <span class="text-gray-400">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-3 text-sm text-gray-800">
                                                            {{ $member->relationship_fa ?? '-' }}
                                                        </td>
                                                        <td class="px-3 py-3 text-sm text-gray-800">
                                                            {{ $member->first_name }} {{ $member->last_name }}
                                                        </td>
                                                        <td class="px-3 py-3 text-sm text-gray-800">{{ $member->national_code ?? '-' }}</td>
                                                        <td class="px-3 py-3 text-sm text-gray-800">
                                                            @if($member->birth_date)
                                                                @php
                                                                    try {
                                                                        echo jdate($member->birth_date)->format('Y/m/d');
                                                                    } catch (\Exception $e) {
                                                                        echo \Carbon\Carbon::parse($member->birth_date)->format('Y/m/d');
                                                                    }
                                                                @endphp
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-3 text-sm text-gray-800">
                                                            @if($member->gender === 'male')
                                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">مرد</span>
                                                            @elseif($member->gender === 'female')
                                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-pink-100 text-pink-800">زن</span>
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-3 text-sm text-gray-800">{{ $member->occupation ?? 'بیکار' }}</td>
                                                        <td class="px-3 py-3 text-sm text-gray-800">
                                                            @php
                                                                $memberProblems = [];
                                                                $problemLabels = [
                                                                    'addiction' => ['label' => 'اعتیاد', 'color' => 'bg-red-100 text-red-800'],
                                                                    'special_disease' => ['label' => 'بیماری خاص', 'color' => 'bg-purple-100 text-purple-800'],
                                                                    'unemployment' => ['label' => 'بیکاری', 'color' => 'bg-orange-100 text-orange-800'],
                                                                    'work_disability' => ['label' => 'ازکارافتادگی', 'color' => 'bg-blue-100 text-blue-800']
                                                                ];
                                                                
                                                                if (is_array($member->problem_type)) {
                                                                    foreach ($member->problem_type as $problem) {
                                                                        if (isset($problemLabels[$problem])) {
                                                                            $memberProblems[] = $problemLabels[$problem];
                                                                        }
                                                                    }
                                                                }
                                                            @endphp
                                                            
                                                            @if(count($memberProblems) > 0)
                                                                <div class="flex flex-wrap gap-1">
                                                                    @foreach($memberProblems as $problem)
                                                                        <span class="px-2 py-0.5 rounded-md text-xs {{ $problem['color'] }}">
                                                                            {{ $problem['label'] }}
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <span class="px-2 py-0.5 rounded-md text-xs bg-gray-100 text-gray-800">
                                                                    بدون مشکل
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-3 text-sm text-gray-800">
                                                            <div class="flex items-center gap-2">
                                                                @if($member->organization)
                                                                    @if($member->organization->logo)
                                                                        <img src="{{ $member->organization->logo }}" alt="لوگوی {{ $member->organization->name }}" class="w-6 h-6 rounded-full object-cover" title="{{ $member->organization->name }}">
                                                                    @else
                                                                        <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center text-xs text-green-800" title="{{ $member->organization->name }}">
                                                                            {{ substr($member->organization->name, 0, 1) }}
                                                                        </div>
                                                                    @endif
                                                                    <span class="text-sm">{{ $member->organization->name }}</span>
                                                                @elseif($family->organization)
                                                                    @if($family->organization->logo)
                                                                        <img src="{{ $family->organization->logo }}" alt="لوگوی {{ $family->organization->name }}" class="w-6 h-6 rounded-full object-cover" title="{{ $family->organization->name }}">
                                                                    @else
                                                                        <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center text-xs text-green-800" title="{{ $family->organization->name }}">
                                                                            {{ substr($family->organization->name, 0, 1) }}
                                                                        </div>
                                                                    @endif
                                                                    <span class="text-sm">{{ $family->organization->name }}</span>
                                                                @else
                                                                    <span class="text-gray-400">-</span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="px-3 py-3 text-sm text-gray-800">
                                                            @php $types = $family->insuranceTypes(); @endphp
                                                            @if($types->count())
                                                                @foreach($types as $type)
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mr-1 mb-1">{{ $type }}</span>
                                                                @endforeach
                                                            @else
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1 mb-1">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-3 text-sm text-gray-800">
                                                            @php $payers = $family->insurancePayers(); @endphp
                                                            @if($payers->count())
                                                                @foreach($payers as $payer)
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mr-1 mb-1">{{ $payer }}</span>
                                                                @endforeach
                                                            @else
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1 mb-1">-</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-3 text-sm text-gray-800">۱۰۰٪</td>
                                                        <td class="px-3 py-3 text-sm text-gray-800 text-center">
                                                            @php
                                                                // چک کنیم آیا این عضو نیاز به مدرک دارد
                                                                $needsDocument = isset($member->needs_document) && $member->needs_document;
                                                            @endphp
                                                            
                                                            @if($needsDocument)
                                                                <a href="{{ route('charity.family.members.documents.upload', ['family' => $family->id, 'member' => $member->id]) }}" 
                                                                   class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full hover:bg-yellow-200 transition-colors">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                    </svg>
                                                                    آپلود مدرک
                                                                </a>
                                                            @else
                                                                <x-member-validation-icons :member="$member" size="sm" />
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @empty
                                                    <tr>
                                                        <td colspan="13" class="px-3 py-3 text-sm text-gray-500 text-center border-b border-gray-100">
                                                            عضوی برای این خانواده ثبت نشده است.
                                                        </td>
                                                    </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                            
                                            <div class="bg-green-100 py-4 px-4 rounded-b border-r border-l border-b border-green-100 flex flex-wrap justify-between items-center gap-4">
                                                <div class="flex items-center">
                                                    <span class="text-sm text-gray-600 ml-2">شماره موبایل سرپرست:</span>
                                                    <div class="bg-white rounded px-3 py-2 flex items-center">
                                                        <span class="text-sm text-gray-800">{{ $family->head()?->mobile ?? '09347964873' }}</span>
                                                        <button type="button" wire:click="copyText('{{ $family->head()?->mobile ?? '09347964873' }}')" class="text-blue-500 mr-2 cursor-pointer">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex items-center">
                                                    <span class="text-sm text-gray-600 ml-2">شماره شبا جهت پرداخت خسارت:</span>
                                                    <div class="bg-white rounded px-3 py-2 flex items-center">
                                                        <span class="text-sm text-gray-800 ltr">{{ $family->head()?->sheba ?? 'IR056216845813188' }}</span>
                                                        <button type="button" wire:click="copyText('{{ $family->head()?->sheba ?? 'IR056216845813188' }}')" class="text-blue-500 mr-2 cursor-pointer">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endif
                            @empty
                            <tr>
                                <td colspan="14" class="py-4 text-gray-400">داده‌ای یافت نشد.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- صفحه‌بندی -->
            @if($families->hasPages())
            <div class="mt-6 border-t border-gray-200 pt-4" id="pagination-section">
                <div class="flex flex-wrap items-center justify-between">
                    <!-- تعداد نمایش - سمت راست -->
                    <div class="flex items-center order-1">
                        <span class="text-sm text-gray-600 ml-2">تعداد نمایش:</span>
                        <select wire:model.live="perPage" 
                                class="h-9 w-16 border border-gray-300 rounded-md px-2 py-1 text-sm bg-white shadow-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500"
                                style="appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; background-image: none !important;">
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="30">30</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <!-- شماره صفحات - وسط -->
                    <div class="flex items-center justify-center order-2 flex-grow mx-4">
                        <!-- دکمه صفحه قبل -->
                        <button type="button" wire:click="{{ !$families->onFirstPage() ? 'previousPage' : '' }}" 
                           class="{{ !$families->onFirstPage() ? 'text-green-600 hover:bg-green-50 cursor-pointer' : 'text-gray-400 opacity-50 cursor-not-allowed' }} bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm mr-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L10.586 10 7.293 6.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        
                        <!-- شماره صفحات -->
                        <div class="flex h-9 border border-gray-300 rounded-md overflow-hidden shadow-sm divide-x divide-gray-300">
                            @php
                                $start = max($families->currentPage() - 2, 1);
                                $end = min($start + 4, $families->lastPage());
                                if ($end - $start < 4 && $start > 1) {
                                    $start = max(1, $end - 4);
                                }
                            @endphp
                            
                            @if($start > 1)
                                <button type="button" wire:click="gotoPage(1)" class="bg-white text-gray-600 hover:bg-gray-50 h-full px-3 inline-flex items-center justify-center text-sm">1</button>
                                @if($start > 2)
                                    <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                                @endif
                            @endif
                            
                            @for($i = $start; $i <= $end; $i++)
                                <button type="button" wire:click="gotoPage({{ $i }})" 
                                   class="{{ $families->currentPage() == $i ? 'bg-green-100 text-green-800 font-medium' : 'bg-white text-gray-600 hover:bg-gray-50' }} h-full px-3 inline-flex items-center justify-center text-sm">
                                    {{ $i }}
                                </button>
                            @endfor
                            
                            @if($end < $families->lastPage())
                                @if($end < $families->lastPage() - 1)
                                    <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                                @endif
                                <button type="button" wire:click="gotoPage({{ $families->lastPage() }})" class="bg-white text-gray-600 hover:bg-gray-50 h-full px-3 inline-flex items-center justify-center text-sm">{{ $families->lastPage() }}</button>
                            @endif
                        </div>
                        
                        <!-- دکمه صفحه بعد -->
                        <button type="button" wire:click="{{ $families->hasMorePages() ? 'nextPage' : '' }}" 
                           class="{{ $families->hasMorePages() ? 'text-green-600 hover:bg-green-50 cursor-pointer' : 'text-gray-400 opacity-50 cursor-not-allowed' }} bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm ml-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <!-- شمارنده - سمت چپ -->
                    <div class="text-sm text-gray-600 order-3">
                        نمایش {{ $families->firstItem() ?? 0 }} تا {{ $families->lastItem() ?? 0 }} از {{ $families->total() ?? 0 }} خانواده
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
<!-- Trigger for Excel Modal (example, should be called after approveAndContinue) -->
<script>
    window.addEventListener('show-excel-upload-modal', () => {
        document.querySelector('[x-data]')?.__x.$data.showExcelUploadModal = true;
    });
</script>

<style>
    /* استایل‌های مربوط به جدول اعضای خانواده */
    .family-members-table {
        table-layout: auto;
        width: 100%;
        min-width: 1200px;
    }
    
    .family-members-table th,
    .family-members-table td {
        white-space: nowrap;
        min-width: 100px;
    }
    
    /* استایل برای اسکرول افقی */
    .scrollbar-thin::-webkit-scrollbar {
        height: 8px;
        width: 8px;
    }
    
    .scrollbar-thin::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .scrollbar-thin::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 4px;
    }
    
    .scrollbar-thin::-webkit-scrollbar-thumb:hover {
        background: #a0aec0;
    }
    
    /* انیمیشن fade-in */
    .animate-fade-in {
        animation: fadeIn 0.3s ease-in-out;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
