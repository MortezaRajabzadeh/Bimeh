<x-app-layout>

<div class="container mx-auto px-4 py-6">
<div class="w-full overflow-x-auto">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200" 
                    x-data="{
                        selectAll: false,
                        selectedOrgs: [], 
                        showModal: false,
                        toggleAllOrgs() {
                            if (this.selectAll) {
                                this.selectedOrgs = this.getIds();
                            } else {
                                this.selectedOrgs = [];
                            }
                        },
                        getIds() {
                            return Array.from(document.querySelectorAll('input[name=\'org_ids[]\']')).map(el => el.value);
                        },
                        get hasSelected() {
                            return this.selectedOrgs.length > 0;
                        }
                    }">
                    
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-700">مدیریت سازمان‌ها</h2>
                    </div>

                    <!-- جستجو و دکمه‌ها -->
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                        <div class="w-full md:w-1/3 mb-4 md:mb-0">
                            <form action="{{ route('admin.organizations.index') }}" method="GET">
                                <div class="relative">
                                    <input type="text" name="search" placeholder="جستجو (نام، نوع، آدرس...)" 
                                        value="{{ request('search') }}"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <button type="submit" class="absolute inset-y-0 left-0 px-3 flex items-center">
                                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div>
                            <button @click="showModal = true" class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded-lg flex items-center justify-center text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                افزودن سازمان
                            </button>
                        </div>
                    </div>
                    
                    <!-- منوی ناوبری -->
                    <x-admin-nav />

                    <!-- نوار ابزار عملیات دسته جمعی -->
                    <div x-show="hasSelected" x-cloak
                        class="mb-4 p-3 bg-gray-100 rounded-lg flex justify-between items-center">
                        <div>
                            <span class="text-sm text-gray-700">
                                <span x-text="selectedOrgs.length"></span> سازمان انتخاب شده است
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <button @click="selectAll = false; selectedOrgs = []" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 text-xs px-3 py-1 rounded">
                                لغو انتخاب
                            </button>
                            <form action="{{ route('admin.organizations.bulk-destroy') }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <template x-for="id in selectedOrgs" :key="id">
                                    <input type="hidden" name="selected_ids[]" :value="id">
                                </template>
                                <button type="submit" 
                                    onclick="return confirm('آیا از حذف موارد انتخاب شده اطمینان دارید؟')"
                                    class="bg-red-500 hover:bg-red-600 text-white text-xs px-3 py-1 rounded">
                                    حذف انتخاب شده
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- جدول سازمان‌ها -->
                    <div class="overflow-x-auto bg-white rounded-lg shadow overflow-y-auto relative">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <input type="checkbox" 
                                                x-model="selectAll" 
                                                @change="toggleAllOrgs()"
                                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        نوع سازمان
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        اسم سازمان
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        لوگو
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        توضیحات
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        آدرس
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        دسترسی ها
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        عملیات
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($organizations ?? [] as $organization)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" 
                                            value="{{ $organization->id }}" 
                                            name="org_ids[]"
                                            x-model="selectedOrgs"
                                            class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            @if($organization->type === 'charity')
                                                خیریه
                                            @elseif($organization->type === 'insurance')
                                                بیمه
                                            @else
                                                {{ $organization->type }}
                                            @endif
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $organization->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <img src="{{ $organization->logo_url }}" alt="{{ $organization->name }}" class="h-10 w-10 rounded-full object-cover">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate">
                                            {{ $organization->description }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate">
                                            {{ $organization->address }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            {{ $organization->users_count ?? 0 }} کاربر
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                                        <div class="flex space-x-2 space-x-reverse justify-end">
                                            <a href="{{ route('admin.organizations.edit', $organization) }}" class="text-blue-600 hover:text-blue-900">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('admin.organizations.destroy', $organization) }}" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('آیا از حذف این سازمان اطمینان دارید؟')" class="text-red-600 hover:text-red-900">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                        هیچ سازمانی یافت نشد!
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- دکمه افزودن سازمان -->
                    <div class="mt-8 flex justify-center">
                        <button @click="showModal = true" class="w-16 h-16 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </button>
                    </div>

                    <!-- پیجینیشن -->
                    <div class="mt-4">
                        @if(isset($organizations) && $organizations->hasPages())
                            {{ $organizations->links() }}
                        @endif
                    </div>
                    
                    <!-- مودال افزودن سازمان جدید -->
                    <div x-show="showModal" 
                         x-cloak
                         class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center" 
                         @click.away="showModal = false"
                         @keydown.escape.window="showModal = false">
                        <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full max-h-screen overflow-y-auto" @click.stop>
                            <div class="p-6 bg-white border-b border-gray-200">
                                <div class="flex justify-between items-center mb-6">
                                    <h2 class="text-lg font-semibold text-gray-700">افزودن سازمان جدید</h2>
                                    <button @click="showModal = false" class="text-gray-500 hover:text-gray-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <form action="{{ route('admin.organizations.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                                    @csrf
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- نام سازمان -->
                                        <div>
                                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">نام سازمان</label>
                                            <input type="text" name="name" id="name" value="{{ old('name') }}" required 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                            @error('name')
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- نوع سازمان -->
                                        <div>
                                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">نوع سازمان</label>
                                            <select name="type" id="type" required
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                                <option value="">انتخاب کنید</option>
                                                <option value="خیریه" {{ old('type') === 'خیریه' ? 'selected' : '' }}>خیریه</option>
                                                <option value="بیمه" {{ old('type') === 'بیمه' ? 'selected' : '' }}>بیمه</option>
                                                <option value="دولتی" {{ old('type') === 'دولتی' ? 'selected' : '' }}>دولتی</option>
                                                <option value="خصوصی" {{ old('type') === 'خصوصی' ? 'selected' : '' }}>خصوصی</option>
                                            </select>
                                            @error('type')
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- آدرس -->
                                        <div>
                                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">آدرس</label>
                                            <textarea name="address" id="address" rows="3" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">{{ old('address') }}</textarea>
                                            @error('address')
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- توضیحات -->
                                        <div>
                                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">توضیحات</label>
                                            <textarea name="description" id="description" rows="3" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">{{ old('description') }}</textarea>
                                            @error('description')
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    <!-- لوگو -->
                                    <div>
                                        <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">لوگو</label>
                                        <div class="flex items-center space-x-4 space-x-reverse">
                                            <div class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center">
                                                <img id="modal-logo-preview" src="{{ asset('images/default-organization.png') }}" alt="Logo Preview" class="max-h-full max-w-full p-1">
                                            </div>
                                            <div class="flex-1">
                                                <input type="file" name="logo" id="logo" accept="image/*"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                    onchange="document.getElementById('modal-logo-preview').src = window.URL.createObjectURL(this.files[0])">
                                                <p class="mt-1 text-xs text-gray-500">فایل‌های مجاز: JPG، PNG با حداکثر حجم ۱ مگابایت</p>
                                            </div>
                                        </div>
                                        @error('logo')
                                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- دکمه ثبت -->
                                    <div class="flex justify-end">
                                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            ثبت سازمان جدید
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 