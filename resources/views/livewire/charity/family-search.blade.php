<div>
    {{-- Knowing others is intelligence; knowing yourself is true wisdom. --}}
    
    <!-- جستجو و فیلتر -->
    <div class="mb-8 flex gap-2">
        <div class="w-full flex flex-wrap items-center gap-2">
            <div class="relative flex-grow">
                <input wire:model.live="search" type="text" placeholder="جستجو در تمام فیلدها..." class="border border-gray-300 rounded p-2 w-full">
            </div>
            
            <div class="relative">
                <select wire:model.live="status" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="insured">بیمه شده</option>
                    <option value="uninsured">بدون بیمه</option>

                </select>
            </div>
            
            <div class="relative">
                <select wire:model.live="province" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="">همه استان‌ها</option>
                    @foreach(($provinces ?? []) as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="relative">
                <select wire:model.live="deprivation_rank" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="">همه رتبه‌های محرومیت</option>
                    <option value="high">محرومیت بالا (1-3)</option>
                    <option value="medium">محرومیت متوسط (4-6)</option>
                    <option value="low">محرومیت پایین (7-10)</option>
                </select>
                @if(($deprivation_rank ?? false))
                    <button wire:click="$set('deprivation_rank', '')" class="absolute right-20 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>
            
            <div class="relative">
                <select wire:model.live="city" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="">همه شهرها</option>
                    @foreach(($cities ?? []) as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="relative">
                <select wire:model.live="charity" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="">همه خیریه‌ها</option>
                    <option value="1">مهرآفرینان</option>
                    <option value="2">محک</option>
                    <option value="3">کودکان کار</option>
                    <option value="4">نیکوکاران شریف</option>
                    <option value="5">خیریه امام علی (ع)</option>
                </select>
            </div>
            
            <div class="relative">
                <select wire:model.live="sortField" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="created_at">مرتب‌سازی بر اساس...</option>
                    <option value="province_deprivation_rank">رتبه محرومیت</option>
                    <option value="id">شماره</option>
                    <option value="province_id">استان</option>
                    <option value="city_id">شهر</option>
                    <option value="is_insured">تعداد بیمه ها</option>
                    <option value="acceptance_criteria">معیار پذیرش</option>
                    <option value="members_count">تعداد اعضا</option>
                    <option value="head_name">سرپرست خانوار</option>
                    <option value="charity_name">خیریه معرف</option>
                    <option value="consumption_coefficient">ضریبه مصرف</option>
                    <option value="created_at">تاریخ عضویت</option>
                    <option value="verified_at">تاییدیه</option>
                </select>
            </div>
            
            <div class="relative">
                <select wire:model.live="sortDirection" class="border border-gray-300 rounded p-2 bg-white pr-8">
                    <option value="asc">صعودی</option>
                    <option value="desc">نزولی</option>
                </select>
            </div>
        </div>
    </div>

    <!-- جدول خانواده‌ها -->
    <div class="w-full overflow-hidden shadow-sm border border-gray-200 rounded-lg">
        <div class="w-full overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50 text-xs text-gray-700">
                        <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                            <button wire:click="sortBy('province_deprivation_rank')" class="flex items-center justify-end w-full">
                                رتبه محرومیت
                                @php $sf = $sortField ?? ''; $sd = $sortDirection ?? ''; @endphp
                                @if($sf === 'province_deprivation_rank')
                                    <span class="mr-1 text-[0.5rem]">
                                        @if($sd === 'asc')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </span>
                                @else
                                    <span class="mr-1 text-[0.5rem]">▼</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                            <button wire:click="sortBy('province_id')" class="flex items-center justify-end w-full">
                                استان
                                @if($sf === 'province_id')
                                    <span class="mr-1 text-[0.5rem]">
                                        @if($sd === 'asc')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </span>
                                @else
                                    <span class="mr-1 text-[0.5rem]">▼</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                            <button wire:click="sortBy('city_id')" class="flex items-center justify-end w-full">
                                شهر
                                @if($sf === 'city_id')
                                    <span class="mr-1 text-[0.5rem]">
                                        @if($sd === 'asc')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </span>
                                @else
                                    <span class="mr-1 text-[0.5rem]">▼</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                            <button wire:click="sortBy('is_insured')" class="flex items-center justify-end w-full">
                                تعداد بیمه ها
                                @if($sf === 'is_insured')
                                    <span class="mr-1 text-[0.5rem]">
                                        @if($sd === 'asc')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </span>
                                @else
                                    <span class="mr-1 text-[0.5rem]">▼</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                            <button wire:click="sortBy('acceptance_criteria')" class="flex items-center justify-end w-full">
                                معیار پذیرش
                                @if($sf === 'acceptance_criteria')
                                    <span class="mr-1 text-[0.5rem]">
                                        @if($sd === 'asc')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </span>
                                @else
                                    <span class="mr-1 text-[0.5rem]">▼</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                            <button wire:click="sortBy('members_count')" class="flex items-center justify-end w-full">
                                تعداد اعضا
                                @if($sf === 'members_count')
                                    <span class="mr-1 text-[0.5rem]">
                                        @if($sd === 'asc')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </span>
                                @else
                                    <span class="mr-1 text-[0.5rem]">▼</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                            <button wire:click="sortBy('head_name')" class="flex items-center justify-end w-full">
                                سرپرست خانوار
                                @if($sf === 'head_name')
                                    <span class="mr-1 text-[0.5rem]">
                                        @if($sd === 'asc')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </span>
                                @else
                                    <span class="mr-1 text-[0.5rem]">▼</span>
                                @endif
                            </button>
                        </th>
                        <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                            <button wire:click="sortBy('insurance_payer')" class="flex items-center justify-end w-full">
                                پرداخت کننده حق بیمه
                                @if($sf === 'insurance_payer')
                                    <span class="mr-1 text-[0.5rem]">
                                        @if($sd === 'asc')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </span>
                                @else
                                    <span class="mr-1 text-[0.5rem]">▼</span>
                                @endif
                            </button>
                        </th>
                        @if(auth()->user()->hasRole('admin'))
                            <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                <button wire:click="sortBy('total_paid_premium')" class="flex items-center justify-end w-full">
                                    مجموع حق بیمه پرداختی
                                    @if($sf === 'total_paid_premium')
                                        <span class="mr-1 text-[0.5rem]">
                                            @if($sd === 'asc')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        </span>
                                    @else
                                        <span class="mr-1 text-[0.5rem]">▼</span>
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                <button wire:click="sortBy('total_paid_claims')" class="flex items-center justify-end w-full">
                                    مجموع خسارات پرداخت شده
                                    @if($sf === 'total_paid_claims')
                                        <span class="mr-1 text-[0.5rem]">
                                            @if($sd === 'asc')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        </span>
                                    @else
                                        <span class="mr-1 text-[0.5rem]">▼</span>
                                    @endif
                                </button>
                            </th>
                        @else
                            <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                <button wire:click="sortBy('charity')" class="flex items-center justify-end w-full">
                                    خیریه معرف
                                    @if($sf === 'charity')
                                        <span class="mr-1 text-[0.5rem]">
                                            @if($sd === 'asc')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        </span>
                                    @else
                                        <span class="mr-1 text-[0.5rem]">▼</span>
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                                <button wire:click="sortBy('consumption_coefficient')" class="flex items-center justify-end w-full">
                                    ضریبه مصرف
                                    @if($sf === 'consumption_coefficient')
                                        <span class="mr-1 text-[0.5rem]">
                                            @if($sd === 'asc')
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        </span>
                                    @else
                                        <span class="mr-1 text-[0.5rem]">▼</span>
                                    @endif
                                </button>
                            </th>
                        @endif
                        <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                            <button wire:click="sortBy('created_at')" class="flex items-center justify-end w-full">
                                تاریخ عضویت
                                @if($sf === 'created_at')
                                    <span class="mr-1 text-[0.5rem]">
                                        @if($sd === 'asc')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </span>
                                @else
                                    <span class="mr-1 text-[0.5rem]">▼</span>
                                @endif
                            </button>
                        </th>
                        @if(!auth()->user()->hasRole('admin'))
                        <th scope="col" class="px-5 py-3 text-right border-b border-gray-200 font-medium">
                            <button wire:click="sortBy('verified_at')" class="flex items-center justify-end w-full">
                                تاییدیه / اعضا
                                @if($sf === 'verified_at')
                                    <span class="mr-1 text-[0.5rem]">
                                        @if($sd === 'asc')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </span>
                                @else
                                    <span class="mr-1 text-[0.5rem]">▼</span>
                                @endif
                            </button>
                        </th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse(($families ?? collect([])) as $family)
                    <tr class="hover:bg-gray-50" data-family-id="{{ $family->id }}">
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
                                        {{ $loop->iteration }}
                                    </div>
                                </div>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                            {{ $family->province->name ?? 'نامشخص' }}
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                            {{ $family->city->name ?? 'نامشخص' }}
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                            {{ $family->insuranceCount() ?? 0 }}
                            
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                            @if(is_array($family->acceptance_criteria) || $family->acceptance_criteria instanceof \Illuminate\Support\Collection)
                                @if(count($family->acceptance_criteria))
                                    @foreach($family->acceptance_criteria as $criteria)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1 mb-1">
                                            {{ $criteria }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1">-</span>
                                @endif
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1">
                                    {{ $family->acceptance_criteria ? $family->acceptance_criteria : '-' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                            {{ $family->members->count() ?? 0 }}
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                            @if($family->members && $family->members->count())
                                @foreach($family->members as $member)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-1 mb-1">
                                        {{ $member->first_name }} {{ $member->last_name }}
                                        @if($member->relationship_fa)
                                            <span class="mx-1 text-gray-500">({{ $member->relationship_fa }})</span>
                                        @endif
                                        @if($member->national_code)
                                            <span class="mx-1 text-gray-400">{{ $member->national_code }}</span>
                                        @endif
                                    </span>
                                @endforeach
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1 mb-1">-</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                            @php
                                $types = $family->insuranceTypes();
                                $payers = $family->insurancePayers();
                            @endphp
                            <div>
                                <span class="font-bold">نوع بیمه:</span>
                                @if($types->count())
                                    @foreach($types as $type)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mr-1 mb-1">{{ $type }}</span>
                                    @endforeach
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1 mb-1">-</span>
                                @endif
                            </div>
                            <div>
                                <span class="font-bold">پرداخت‌کننده:</span>
                                @if($payers->count())
                                    @foreach($payers as $payer)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mr-1 mb-1">{{ $payer }}</span>
                                    @endforeach
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1 mb-1">-</span>
                                @endif
                            </div>
                        </td>
                        @if(auth()->user()->hasRole('admin'))
                            <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                {{ number_format($family->total_paid_premium ?? 0) }} تومان
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                {{ number_format($family->total_paid_claims ?? 0) }} تومان
                            </td>
                        @else
                            <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                <div class="flex items-center justify-end">
                                    @if($family->organization)
                                        <span class="ml-2">{{ $family->organization->name }}</span>
                                        @if($family->organization->logo)
                                            <img src="{{ $family->organization->logo }}" alt="لوگوی خیریه" class="w-6 h-6 rounded-full object-cover">
                                        @endif
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                                ۵۰٪
                            </td>
                        @endif
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                            {{ $family->created_at ? jdate($family->created_at)->format('Y/m/d') : '-' }}
                        </td>
                        @if(!auth()->user()->hasRole('admin'))
                        <td class="px-5 py-4 text-sm text-gray-900 border-b border-gray-200">
                            <div class="flex space-x-2 space-x-reverse">
                                @switch($family->status)
                                    @case('pending')
                                        <span class="bg-orange-100 text-orange-800 text-xs py-1 px-2 rounded-full flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>در انتظار بررسی</span>
                                        </span>
                                        @break
                                    @case('reviewing')
                                        <span class="bg-yellow-100 text-yellow-800 text-xs py-1 px-2 rounded-full flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>در حال بررسی</span>
                                        </span>
                                        @break
                                    @case('approved')
                                        <span class="bg-blue-100 text-blue-800 text-xs py-1 px-2 rounded-full flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>تایید شده</span>
                                        </span> 
                                        @break
                                    @case('insured')
                                        <span class="bg-green-100 text-green-800 text-xs py-1 px-2 rounded-full flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>بیمه شده</span>
                                        </span>
                                        @break
                                    @case('renewal')
                                        <span class="bg-indigo-100 text-indigo-800 text-xs py-1 px-2 rounded-full flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span>در انتظار تمدید</span>
                                        </span>
                                        @break
                                    @case('rejected')
                                        <span class="bg-red-100 text-red-800 text-xs py-1 px-2 rounded-full flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            <span>رد شده</span>
                                        </span>
                                        @break
                                    @case('deleted')
                                        <span class="bg-gray-200 text-gray-500 text-xs py-1 px-2 rounded-full flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            <span>حذف شده</span>
                                        </span>
                                        @break
                                @endswitch
                                
                                <button wire:click="toggleFamily({{ $family->id }})" class="bg-green-200 hover:bg-green-300 text-green-800 text-xs py-1 px-2 rounded-full transition-all duration-200 ease-in-out toggle-family-btn" data-family-id="{{ $family->id }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block transition-transform duration-200 {{ $expandedFamily === $family->id ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                        @endif
                    </tr>
                    
                    @if($expandedFamily === $family->id && !auth()->user()->hasRole('admin'))
                    <tr class="bg-green-50">
                        <td colspan="{{ auth()->user()->hasRole('admin') ? 10 : 12 }}" class="p-0">
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
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">شغل</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">نوع مشکل</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">خیریه معرف</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">نوع بیمه</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">پرداخت کننده حق بیمه</th>
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">درصد مشارکت</th>
                                            @if(!auth()->user()->hasRole('admin'))
                                            <th class="px-3 py-3 text-sm font-medium text-gray-700 text-right">تاییدیه</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($familyMembers as $member)
                                        <tr class="bg-green-100 border-b border-green-200 hover:bg-green-200" wire:key="member-{{ $member->id }}">
                                            <td class="px-3 py-3 text-sm text-gray-800 text-center sticky left-0 bg-green-100">
                                                @if($family->verified_at)
                                                    @if($member->is_head)
                                                        <span class="text-blue-500 inline-block">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        </span>
                                                    @endif
                                                @else
                                                    <input 
                                                        type="radio" 
                                                        name="family_head_{{ $family->id }}" 
                                                        value="{{ $member->id }}" 
                                                        wire:model="selectedHead" 
                                                        {{ $member->is_head ? 'checked' : '' }}
                                                        wire:change="setFamilyHead({{ $family->id }}, {{ $member->id }})" 
                                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 cursor-pointer"
                                                    >
                                                @endif
                                            </td>
                                            <td class="px-3 py-3 text-sm text-gray-800">
                                                {{ $member->relationship_fa }}
                                            </td>
                                            <td class="px-3 py-3 text-sm text-gray-800">
                                                {{ $member->first_name }} {{ $member->last_name }}
                                            </td>
                                            <td class="px-3 py-3 text-sm text-gray-800">{{ $member->national_code ?? '-' }}</td>
                                            <td class="px-3 py-3 text-sm text-gray-800">{{ $member->birth_date}}</td>
                                            <td class="px-3 py-3 text-sm text-gray-800">{{ $member->occupation ?? 'بیکار' }}</td>
                                            <td class="px-3 py-3 text-sm text-gray-800">
                                                @if($member->has_disability)
                                                    <span class="px-2 py-0.5 rounded-md text-xs bg-orange-100 text-orange-800">
                                                        از کار افتادگی
                                                    </span>
                                                @elseif($member->has_chronic_disease)
                                                    <span class="px-2 py-0.5 rounded-md text-xs bg-pink-100 text-red-800">
                                                        بیماری خاص
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-md text-xs bg-gray-100 text-gray-800">
                                                        اعتیاد
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3 text-sm text-gray-800">
                                                <div class="flex items-center">
                                                    @if($member->organization)
                                                        <span class="ml-1">{{ $member->organization->name }}</span>
                                                        @if($member->organization->logo)
                                                            <img src="{{ $member->organization->logo }}" alt="لوگوی خیریه" class="w-5 h-5 rounded-full object-cover">
                                                        @else
                                                            <div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center text-xs text-green-800">
                                                                {{ substr($member->organization->name, 0, 1) }}
                                                            </div>
                                                        @endif
                                                    @elseif($family->organization)
                                                        <span class="ml-1">{{ $family->organization->name }}</span>
                                                        @if($family->organization->logo)
                                                            <img src="{{ $family->organization->logo }}" alt="لوگوی خیریه" class="w-5 h-5 rounded-full object-cover">
                                                        @else
                                                            <div class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center text-xs text-green-800">
                                                                {{ substr($family->organization->name, 0, 1) }}
                                                            </div>
                                                        @endif
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
                                            @if(!auth()->user()->hasRole('admin'))
                                            <td class="px-3 py-3 text-sm text-gray-800 text-center">
                                                ✓
                                            </td>
                                            @endif
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="{{ auth()->user()->hasRole('admin') ? 10 : 12 }}" class="px-3 py-3 text-sm text-gray-500 text-center border-b border-gray-100">
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
                                            <button type="button" wire:click="copyText('09347964873')" class="text-blue-500 mr-2 cursor-pointer">
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
                                            <button type="button" wire:click="copyText('IR056216845813188')" class="text-blue-500 mr-2 cursor-pointer">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endif
                    
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->hasRole('admin') ? 10 : 12 }}" class="px-5 py-4 text-sm text-gray-500 border-b border-gray-200 text-center">
                            هیچ خانواده‌ای یافت نشد.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- اعلان کپی -->
    <div id="copy-notification" class="hidden fixed top-4 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-md shadow-lg z-50 flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span id="copy-notification-text">متن با موفقیت کپی شد</span>
    </div>
    
    <!-- پیجینیشن -->
    @if(($families ?? null) && ($families->hasPages() ?? false))
    <div class="mt-6 border-t border-gray-200 pt-4" id="pagination-section">
        <div class="flex flex-wrap items-center justify-between">
            <!-- شمارنده - سمت راست -->
            <div class="text-sm text-gray-600 order-1 ml-auto">
                نمایش {{ $families->firstItem() ?? 0 }} تا {{ $families->lastItem() ?? 0 }} از {{ $families->total() ?? 0 }} خانواده
            </div>

            <!-- شماره صفحات - وسط -->
            <div class="flex items-center justify-center order-2 flex-grow mx-4">
                <button type="button" wire:click="{{ !$families->onFirstPage() ? 'previousPage' : '' }}" 
                   class="{{ !$families->onFirstPage() ? 'text-green-600 hover:bg-green-50 cursor-pointer' : 'text-gray-400 opacity-50 cursor-not-allowed' }} bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L10.586 10 7.293 6.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                
                <div class="flex h-9 border border-gray-300 rounded-md overflow-hidden shadow-sm divide-x divide-gray-300 mx-1">
                    @php
                        $start = isset($families) ? max($families->currentPage() - 2, 1) : 1;
                        $end = isset($families) ? min($start + 4, $families->lastPage()) : 1;
                        if (isset($families) && $end - $start < 4 && $start > 1) {
                            $start = max(1, $end - 4);
                        }
                    @endphp
                    
                    @if(isset($families) && $start > 1)
                        <button type="button" wire:click="gotoPage(1)" class="bg-white text-gray-600 hover:bg-gray-50 h-full px-3 inline-flex items-center justify-center text-sm">1</button>
                        @if(isset($families) && $start > 2)
                            <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                        @endif
                    @endif
                    
                    @for($i = $start; $i <= $end; $i++)
                        <button type="button" wire:click="gotoPage({{ $i }})" 
                           class="{{ (isset($families) && $families->currentPage() == $i) ? 'bg-green-100 text-green-800 font-medium' : 'bg-white text-gray-600 hover:bg-gray-50' }} h-full px-3 inline-flex items-center justify-center text-sm">
                            {{ $i }}
                        </button>
                    @endfor
                    
                    @if(isset($families) && $end < $families->lastPage())
                        @if(isset($families) && $end < $families->lastPage() - 1)
                            <span class="bg-white text-gray-600 h-full px-2 inline-flex items-center justify-center text-sm">...</span>
                        @endif
                        <button type="button" wire:click="gotoPage({{ isset($families) ? $families->lastPage() : 1 }})" class="bg-white text-gray-600 hover:bg-gray-50 h-full px-3 inline-flex items-center justify-center text-sm">{{ isset($families) ? $families->lastPage() : 1 }}</button>
                    @endif
                </div>
                
                <button type="button" wire:click="{{ (isset($families) && $families->hasMorePages()) ? 'nextPage' : '' }}" 
                   class="{{ (isset($families) && $families->hasMorePages()) ? 'text-green-600 hover:bg-green-50 cursor-pointer' : 'text-gray-400 opacity-50 cursor-not-allowed' }} bg-white rounded-md h-9 w-9 flex items-center justify-center border border-gray-300 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <!-- تعداد نمایش - سمت چپ -->
            <div class="flex items-center order-3 mr-auto">
                <span class="text-sm text-gray-600 ml-2">تعداد نمایش:</span>
                <select wire:model.live="perPage" class="h-9 w-16 border border-gray-300 rounded-md px-2 py-1 text-sm bg-white shadow-sm focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>
    @endif
    
    <!-- اعلان toast -->
    <div id="toast-notification" class="hidden fixed top-4 left-1/2 transform -translate-x-1/2 px-6 py-3 rounded-md shadow-lg z-50 flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span id="toast-notification-text"></span>
    </div>
    
    <script>
    document.addEventListener('livewire:initialized', function () {
        let notificationTimeout = null;
        
        // تابع اسکرول به محتوای باز شده
        function scrollToExpandedContent(familyId, delay = 300) {
            setTimeout(() => {
                const familyRow = document.querySelector(`tr[data-family-id="${familyId}"]`);
                const expandedContent = document.querySelector(`tr[data-family-id="${familyId}"] + tr`);
                
                if (expandedContent && familyRow) {
                    const rect = expandedContent.getBoundingClientRect();
                    const isInViewport = (
                        rect.top >= 0 &&
                        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight)
                    );
                    
                    if (!isInViewport) {
                        familyRow.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            }, delay);
        }
        
        // مدیریت کلیک روی دکمه‌های توگل
        document.addEventListener('click', function(e) {
            const toggleBtn = e.target.closest('.toggle-family-btn');
            if (toggleBtn) {
                const familyId = toggleBtn.getAttribute('data-family-id');
                if (familyId) {
                    scrollToExpandedContent(familyId, 500);
                }
            }
        });
        
        // نمایش toast notification
        Livewire.on('notify', params => {
            const toast = document.getElementById('toast-notification');
            const toastText = document.getElementById('toast-notification-text');
            
            if (!toast || !toastText) return;
            
            toastText.textContent = params.message;
            
            // تنظیم رنگ بر اساس نوع اعلان
            toast.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 flex items-center p-4 rounded-lg shadow-lg z-50';
            toast.classList.add(params.type === 'success' ? 'bg-green-500' : 'bg-red-500', 'text-white');
            
            clearTimeout(notificationTimeout);
            
            toast.classList.remove('hidden');
            
            // نمایش اعلان برای 3 ثانیه
            notificationTimeout = setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        });
        
        // اسکرول به خانواده باز شده
        Livewire.on('family-expanded', familyId => {
            scrollToExpandedContent(familyId);
        });
        
        // کپی متن
        Livewire.on('copy-text', params => {
            const text = typeof params === 'object' ? (params.text || String(params)) : String(params);
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text)
                    .then(() => showCopyNotification(text))
                    .catch(() => fallbackCopyTextToClipboard(text));
            } else {
                fallbackCopyTextToClipboard(text);
            }
        });
        
        function fallbackCopyTextToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.cssText = 'position:fixed;opacity:0';
            document.body.appendChild(textarea);
            
            textarea.focus();
            textarea.select();
            
            try {
                if (document.execCommand('copy')) {
                    showCopyNotification(text);
                }
            } catch (err) {}
            
            document.body.removeChild(textarea);
        }
        
        function showCopyNotification(text) {
            const notification = document.getElementById('copy-notification');
            const notificationText = document.getElementById('copy-notification-text');
            
            if (!notification || !notificationText) return;
            
            notificationText.textContent = 'متن با موفقیت کپی شد: ' + text;
            
            clearTimeout(notificationTimeout);
            
            notification.classList.remove('hidden');
            
            // نمایش اعلان برای 3 ثانیه
            notificationTimeout = setTimeout(() => {
                notification.classList.add('hidden');
            }, 3000);
        }
    });
    </script>
    
    <style>
    @keyframes slideIn {
        from {
            transform: translate(-50%, -20px);
            opacity: 0;
        }
        to {
            transform: translate(-50%, 0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translate(-50%, 0);
            opacity: 1;
        }
        to {
            transform: translate(-50%, -20px);
            opacity: 0;
        }
    }
    
    .notification-show {
        animation: slideIn 0.3s ease forwards;
    }
    
    .notification-hide {
        animation: slideOut 0.3s ease forwards;
    }
    
    #copy-notification {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 2px 4px rgba(0, 0, 0, 0.12);
    }
    
    /* اضافه کردن استایل جدید برای چرخش ایکون */
    .icon-rotate-180 {
        transform: rotate(180deg);
        transition: transform 0.3s ease;
    }
    
    /* انیمیشن‌های مربوط به toast */
    .toast-show {
        animation: slideIn 0.3s ease forwards;
    }
    
    .toast-hide {
        animation: slideOut 0.3s ease forwards;
    }
    
    #toast-notification {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 2px 4px rgba(0, 0, 0, 0.12);
    }
    
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
    </style>
</div>