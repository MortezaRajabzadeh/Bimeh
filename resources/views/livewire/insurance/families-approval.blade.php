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
            <div class="overflow-x-auto mt-2">
                <table class="min-w-full text-xs text-center">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2">
                                <input type="checkbox"
                                    x-model="selectAll"
                                    @change="toggleAllFamilies()"
                                    class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            </th>
                            <th class="py-2">شناسه</th>
                            <th class="py-2">رتبه</th>
                            <th class="py-2">استان</th>
                            <th class="py-2">شهر/روستا</th>
                            <th class="py-2">تعداد بیمه ها</th>
                            <th class="py-2">معیار پذیرش</th>
                            <th class="py-2">تعداد اعضا</th>
                            <th class="py-2">سرپرست خانوار</th>
                            <th class="py-2">خرید معرف</th>
                            <th class="py-2">تاریخ عضویت</th>
                            <th class="py-2">تاییدیه</th>
                            <th class="py-2">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($families as $family)
                        <tr class="hover:bg-gray-50" data-family-id="{{ $family->id }}">
                            <td class="py-2">
                                <input type="checkbox" name="family_ids[]" value="{{ $family->id }}" x-model="selectedFamilies" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            </td>
                            <td class="py-2">{{ $family->id }}</td>
                            <td class="py-2">{{ $family->rank ?? '-' }}</td>
                            <td class="py-2">{{ $family->province->name ?? '-' }}</td>
                            <td class="py-2">{{ $family->city->name ?? '-' }}</td>
                            <td class="py-2">{{ $family->insurances_count ?? '-' }}</td>
                            <td class="py-2">
                                @if(isset($family->acceptance_criteria) && is_array($family->acceptance_criteria))
                                    @foreach($family->acceptance_criteria as $criteria)
                                        <span class="inline-block bg-green-200 text-green-800 rounded px-2 py-1 text-xs">{{ $criteria }}</span>
                                    @endforeach
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-2">{{ $family->members->count() ?? '-' }}</td>
                            <td class="py-2">{{ $family->head->full_name ?? '-' }}</td>
                            <td class="py-2">
                                <img src="/images/sample-logo.png" alt="logo" class="inline w-8 h-8">
                            </td>
                            <td class="py-2">{{ jdate($family->created_at)->format('Y/m/d') }}</td>
                            <td class="py-2">
                                <span class="inline-block bg-yellow-100 text-yellow-700 rounded px-2 py-1 text-xs">{{ $family->status_label ?? '-' }}</span>
                            </td>
                            <td class="py-2">
                                <button wire:click="toggleFamily({{ $family->id }})"
                                    class="bg-green-200 hover:bg-green-300 text-green-800 text-xs py-1 px-2 rounded-full transition-colors duration-150 ease-in-out toggle-family-btn"
                                    data-family-id="{{ $family->id }}">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5 inline-block {{ $expandedFamily === $family->id ? 'rotate-180' : '' }}"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        @if($expandedFamily === $family->id)
                        <tr class="bg-green-50">
                            <td colspan="13" class="p-0">
                                <div class="overflow-hidden shadow-inner rounded-lg bg-green-50 p-2">
                                    <div class="overflow-x-auto w-full max-h-96 scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100">
                                        <table class="min-w-full table-auto bg-green-50 border border-green-100 rounded-lg family-members-table">
                                            <thead>
                                                <tr class="bg-green-700 text-white">
                                                    <th class="px-3 py-3 text-sm font-medium text-right">سرپرست؟</th>
                                                    <th class="px-3 py-3 text-sm font-medium text-right">اعضای خانواده</th>
                                                    <th class="px-3 py-3 text-sm font-medium text-right">نام</th>
                                                    <th class="px-3 py-3 text-sm font-medium text-right">نام خانوادگی</th>
                                                    <th class="px-3 py-3 text-sm font-medium text-right">کد ملی</th>
                                                    <th class="px-3 py-3 text-sm font-medium text-right">تاریخ تولد</th>
                                                    <th class="px-3 py-3 text-sm font-medium text-right">شغل</th>
                                                    <th class="px-3 py-3 text-sm font-medium text-right">نوع مشکل</th>
                                                    @if(in_array($family->status, ['insured','approved']))
                                                        <th class="px-3 py-3 text-sm font-medium text-right">نوع بیمه</th>
                                                        <th class="px-3 py-3 text-sm font-medium text-right">پرداخت کننده حق بیمه</th>
                                                        <th class="px-3 py-3 text-sm font-medium text-right">درصد مشارکت</th>
                                                    @endif
                                                    <th class="px-3 py-3 text-sm font-medium text-right">خیریه معرف</th>
                                                    <th class="px-3 py-3 text-sm font-medium text-right">تاییدیه</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($family->members as $member)
                                                <tr class="bg-green-100 border-b border-green-200 hover:bg-green-200">
                                                    <td class="px-3 py-3 text-sm text-gray-800 text-right">
                                                        @if($member->is_head)
                                                            <span class="text-blue-500 font-bold">سرپرست</span>
                                                        @else
                                                            عضو
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-3 text-sm text-gray-800 text-right">{{ $member->relationship_fa }}</td>
                                                    <td class="px-3 py-3 text-sm text-gray-800 text-right">{{ $member->full_name }}</td>
                                                    <td class="px-3 py-3 text-sm text-gray-800 text-right">{{ $member->last_name }}</td>
                                                    <td class="px-3 py-3 text-sm text-gray-800 text-right">{{ $member->national_code }}</td>
                                                    <td class="px-3 py-3 text-sm text-gray-800 text-right">{{ jdate($member->birth_date)->format('Y/m/d') }}</td>
                                                    <td class="px-3 py-3 text-sm text-gray-800 text-right">{{ $member->occupation ?? '-' }}</td>
                                                    <td class="px-3 py-3 text-sm text-gray-800 text-right">
                                                        @if(is_array($member->problem_type) && count($member->problem_type))
                                                            @foreach($member->problem_type as $problem)
                                                                <span class="inline-block bg-orange-100 text-orange-700 rounded px-2 py-1 text-xs ml-1">{{ $problem }}</span>
                                                            @endforeach
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    @if(in_array($family->status, ['insured','approved']))
                                                        <td class="px-3 py-3 text-sm text-gray-800 text-right">{{ $member->insurance_type ?? '-' }}</td>
                                                        <td class="px-3 py-3 text-sm text-gray-800 text-right">{{ $member->payment_method ?? '-' }}</td>
                                                        <td class="px-3 py-3 text-sm text-gray-800 text-right">{{ $member->participation_percentage ?? '-' }}%</td>
                                                    @endif
                                                    <td class="px-3 py-3 text-sm text-gray-800 text-right">{{ optional($family->organization)->name ?? '-' }}</td>
                                                    <td class="px-3 py-3 text-sm text-gray-800 text-right">
                                                        @if($member->is_head) <span class="text-blue-500">سرپرست</span> @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        @if($family->members->isEmpty())
                                            <div class="text-gray-400 text-xs py-2">عضوی برای این خانواده ثبت نشده است.</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="13" class="py-4 text-gray-400">داده‌ای یافت نشد.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
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
