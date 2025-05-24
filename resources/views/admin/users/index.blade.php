<x-app-layout>

<div class="container mx-auto px-4 py-6">
<div class="w-full overflow-x-auto">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200" 
                    x-data="{
                        selectAll: false,
                        selectedUsers: [], 
                        showModal: false,
                        createOrganization: false,
                        toggleAllUsers() {
                            if (this.selectAll) {
                                this.selectedUsers = this.getIds();
                            } else {
                                this.selectedUsers = [];
                            }
                        },
                        getIds() {
                            return Array.from(document.querySelectorAll('input[name=\'user_ids[]\']')).map(el => el.value);
                        },
                        get hasSelected() {
                            return this.selectedUsers.length > 0;
                        },
                        toggleOrganizationForm() {
                            const orgSelect = document.getElementById('organization_id');
                            if (this.createOrganization) {
                                orgSelect.disabled = true;
                                orgSelect.value = ''; // پاک کردن مقدار فیلد انتخاب سازمان
                            } else {
                                orgSelect.disabled = false;
                            }
                        }
                    }">
                    
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-700">مدیریت کاربران</h2>
                    </div>

                    <!-- جستجو و دکمه‌ها -->
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                        <div class="w-full md:w-1/3 mb-4 md:mb-0">
                            <form action="{{ route('admin.users.index') }}" method="GET">
                                <div class="relative">
                                    <input type="text" name="search" placeholder="جستجو (نام، ایمیل، شماره تلفن...)" 
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
                                افزودن کاربر
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
                                <span x-text="selectedUsers.length"></span> کاربر انتخاب شده است
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <button @click="selectAll = false; selectedUsers = []" 
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 text-xs px-3 py-1 rounded">
                                لغو انتخاب
                            </button>
                            <form action="{{ route('admin.users.bulk-destroy') }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <template x-for="id in selectedUsers" :key="id">
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

                    <!-- جدول کاربران -->
                    <div class="overflow-x-auto bg-white rounded-lg shadow overflow-y-auto relative">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div class="flex items-center">
                                            <input type="checkbox" 
                                                x-model="selectAll" 
                                                @change="toggleAllUsers()"
                                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        نام
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        نام خانوادگی
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        شماره همراه
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ایمیل
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        نام کاربری
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
                                @forelse($users ?? [] as $user)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" 
                                            value="{{ $user->id }}" 
                                            name="user_ids[]"
                                            x-model="selectedUsers"
                                            class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $user->first_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $user->last_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span dir="ltr" class="inline-block">{{ $user->mobile }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span dir="ltr" class="inline-block text-xs">{{ $user->email }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span dir="ltr" class="inline-block">{{ $user->username }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @foreach($user->roles as $role)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 mr-1">
                                                {{ $role->name }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                                        <div class="flex space-x-2 space-x-reverse justify-end">
                                            <a href="{{ route('admin.users.edit', $user) }}" class="text-blue-600 hover:text-blue-900">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('آیا از حذف این کاربر اطمینان دارید؟')" class="text-red-600 hover:text-red-900">
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
                                    <td colspan="10" class="px-6 py-4 text-center text-gray-500">
                                        هیچ کاربری یافت نشد!
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- دکمه افزودن کاربر -->
                    <div class="mt-8 flex justify-center">
                        <button @click="showModal = true" class="w-16 h-16 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center shadow-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </button>
                    </div>

                    <!-- پیجینیشن -->
                    <div class="mt-4">
                        @if(isset($users) && $users->hasPages())
                            {{ $users->links() }}
                        @endif
                    </div>
                    
                    <!-- مودال افزودن کاربر جدید -->
                    <div x-show="showModal" 
                         x-cloak
                         class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center" 
                         @click.away="showModal = false"
                         @keydown.escape.window="showModal = false">
                        <div class="bg-white rounded-lg shadow-lg max-w-5xl w-full max-h-screen overflow-y-auto" @click.stop>
                            <div class="p-6 bg-white border-b border-gray-200">
                                <div class="flex justify-between items-center mb-6">
                                    <h2 class="text-lg font-semibold text-gray-700">افزودن کاربر جدید</h2>
                                    <button @click="showModal = false" class="text-gray-500 hover:text-gray-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                                    @csrf
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- نام -->
                                        <div>
                                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">نام</label>
                                            <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                            @error('first_name')
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- نام خانوادگی -->
                                        <div>
                                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">نام خانوادگی</label>
                                            <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                            @error('last_name')
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- کد ملی / شناسه -->
                                        <div>
                                            <label for="national_code" class="block text-sm font-medium text-gray-700 mb-1">کد ملی</label>
                                            <input type="text" name="national_code" id="national_code" value="{{ old('national_code') }}" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                            @error('national_code')
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- موبایل -->
                                        <div>
                                            <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1">شماره موبایل</label>
                                            <input type="text" name="mobile" id="mobile" value="{{ old('mobile') }}" dir="ltr" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                            @error('mobile')
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- ایمیل -->
                                        <div>
                                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">ایمیل</label>
                                            <input type="email" name="email" id="email" value="{{ old('email') }}" dir="ltr" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                            @error('email')
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- نام کاربری -->
                                        <div>
                                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">نام کاربری</label>
                                            <input type="text" name="username" id="username" value="{{ old('username') }}" dir="ltr" required
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                            @error('username')
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- سطح دسترسی -->
                                        <div>
                                            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">سطح دسترسی</label>
                                            <select name="role" id="role"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                                <option value="">انتخاب کنید</option>
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
                                                        {{ $role->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('role')
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- کلمه عبور -->
                                        <div>
                                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">کلمه عبور</label>
                                            <input type="password" name="password" id="password" dir="ltr" required
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                            @error('password')
                                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- تایید کلمه عبور -->
                                        <div>
                                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">تایید کلمه عبور</label>
                                            <input type="password" name="password_confirmation" id="password_confirmation" dir="ltr" required
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                                        </div>
                                    </div>

                                    <!-- دکمه ثبت -->
                                    <div class="flex justify-end">
                                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            ثبت کاربر جدید
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