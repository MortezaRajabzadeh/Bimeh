<x-app-layout>
<div class="container mx-auto py-6">
    <div class="mb-6">
        <div class="bg-white rounded shadow p-4 text-gray-700 text-sm leading-7">
            لیست مناطق محروم طبق جدول زیر تعیین شده است که در آن طبق برخی طبقه بندی های موجود در سطح ملی، برخی روستا/شهرستان ها به عنوان مناطق محروم تلقی میشوند. از این لیست جهت اولویت بندی افراد جهت تخصیص بیمه استفاده میشود. امکان تغییر این لیست بر عهده ادمین سیستم میکرو بیمه است و بر اساس آمار های کشوری تغییر خواهد کرد.
        </div>
    </div>
    <div class="mb-4 flex items-center gap-2">
        <input type="text" wire:model.debounce.500ms="search" placeholder="جستجو استان، شهرستان یا دهستان..." class="border rounded px-3 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-blue-400" />
        <span class="bg-green-500 text-white px-4 py-2 rounded-full text-sm font-bold">مناطق محروم</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300 bg-white">
            <thead>
                <tr class="bg-gray-100 text-center">
                    <th class="border px-4 py-2">استان</th>
                    <th class="border px-4 py-2">شهرستان</th>
                    <th class="border px-4 py-2">دهستان</th>
                </tr>
            </thead>
            <tbody>
                @forelse($provinces as $province)
                    @php $provinceRowSpan = $province->cities->sum(fn($city) => max($city->districts->count(), 1)) ?: 1; @endphp
                    @foreach($province->cities as $cityIndex => $city)
                        @php $cityRowSpan = max($city->districts->count(), 1); @endphp
                        @foreach($city->districts as $districtIndex => $district)
                            <tr class="text-center">
                                @if($cityIndex === 0 && $districtIndex === 0)
                                    <td class="border px-4 py-2 align-middle font-bold" rowspan="{{$provinceRowSpan}}">{{$province->name}}</td>
                                @endif
                                @if($districtIndex === 0)
                                    <td class="border px-4 py-2 align-middle font-semibold" rowspan="{{$cityRowSpan}}">{{$city->name}}</td>
                                @endif
                                <td class="border px-4 py-2 {{ $district->is_deprived ? 'bg-red-500 text-white font-bold' : '' }}">{{$district->name}}</td>
                            </tr>
                        @endforeach
                        @if($city->districts->isEmpty())
                            <tr class="text-center">
                                @if($cityIndex === 0)
                                    <td class="border px-4 py-2 align-middle font-bold" rowspan="{{$provinceRowSpan}}">{{$province->name}}</td>
                                @endif
                                <td class="border px-4 py-2 align-middle font-semibold">{{$city->name}}</td>
                                <td class="border px-4 py-2">—</td>
                            </tr>
                        @endif
                    @endforeach
                @empty
                    <tr><td colspan="3" class="text-center py-8 text-gray-400">موردی یافت نشد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-app-layout> 