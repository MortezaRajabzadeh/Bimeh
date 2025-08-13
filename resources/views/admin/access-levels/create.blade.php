<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-semibold text-gray-700">افزودن نقش جدید</h2>
                <a href="{{ route('admin.access-levels.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg flex items-center justify-center text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                    </svg>
                    بازگشت به لیست
                </a>
            </div>

            <form action="{{ route('admin.access-levels.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            نام نقش (انگلیسی) *
                        </label>
                        <input type="text" id="name" name="name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="{{ old('name') }}"
                               placeholder="admin, manager, user">
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="display_name" class="block text-sm font-medium text-gray-700 mb-2">
                            نام نمایشی (فارسی) *
                        </label>
                        <input type="text" id="display_name" name="display_name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="{{ old('display_name') }}"
                               placeholder="ادمین، مدیر، کاربر">
                        @error('display_name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        توضیحات
                    </label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="توضیح مختصری درباره این نقش...">{{ old('description') }}</textarea>
                </div>

                <!-- Parent Role -->
                <div>
                    <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-2">
                        نقش والد
                    </label>

                    <!-- Custom Dropdown -->
                    <div class="relative" x-data="{
                        open: false,
                        selected: {{ old('parent_id') ?? 'null' }},
                        selectedText: '{{ old('parent_id') ? ($roles->where('id', old('parent_id'))->first()->display_name ?? $roles->where('id', old('parent_id'))->first()->name ?? 'نقش نامشخص') : 'بدون والد (نقش اصلی)' }}'
                    }">
                        <!-- Hidden Input -->
                        <input type="hidden" name="parent_id" :value="selected">

                        <!-- Dropdown Button -->
                        <button type="button" @click="open = !open"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-right flex items-center justify-between">
                            <span x-text="selectedText" class="text-gray-900"></span>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Dropdown Options -->
                        <div x-show="open" @click.away="open = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">

                            <!-- بدون والد -->
                            <button type="button" @click="selected = null; selectedText = 'بدون والد (نقش اصلی)'; open = false"
                                    class="w-full px-3 py-2 text-right hover:bg-gray-50 flex items-center justify-between">
                                <span class="text-gray-900">بدون والد (نقش اصلی)</span>
                                <div class="w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center">
                                    <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </div>
                            </button>

                            <!-- نقش‌های والد -->
                            @foreach($roles as $role)
                            <button type="button"
                                    @click="selected = {{ $role->id }}; selectedText = '{{ $role->display_name ?: $role->name }}'; open = false"
                                    class="w-full px-3 py-2 text-right hover:bg-gray-50 flex items-center justify-between">
                                <span class="text-gray-900">{{ $role->display_name ?: $role->name }}</span>
                                <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Inherit Permissions -->
                <div class="flex items-center">
                    <input type="checkbox" id="inherit_permissions" name="inherit_permissions" value="1"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                           {{ old('inherit_permissions') ? 'checked' : '' }}>
                    <label for="inherit_permissions" class="mr-2 block text-sm text-gray-900">
                        وراثت مجوزها از نقش والد
                    </label>
                </div>

                <!-- Permissions -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-4">
                        انتخاب مجوزها
                    </label>

                @php
                    // تعریف گروه‌بندی مفهومی برای مجوزها بر اساس نوع کاربر و عملکرد
                    $permissionGroups = [
                        // مجوزهای عمومی (مشترک بین همه کاربران)
                        '🌐 دسترسی‌های عمومی' => [
                            'view dashboard','view profile','edit profile',
                        ],
                        
                        // مجوزهای مربوط به کاربران بیمه
                        '🏥 بیمه - مدیریت پالیس‌ها' => [
                            'manage insurance policies','calculate premiums','view claims history',
                        ],
                        '📋 بیمه - پردازش درخواست‌ها' => [
                            'process claims','approve claims','reject claims',
                        ],
                        '💰 بیمه - سهم‌بندی' => [
                            'view insurance shares','manage insurance shares','create insurance shares','edit insurance shares','delete insurance shares',
                        ],
                        '💳 بیمه - پرداخت‌ها' => [
                            'view insurance payments','manage insurance payments','create insurance payments','edit insurance payments','delete insurance payments','view payment details','export payment reports',
                        ],
                        
                        // مجوزهای مربوط به کاربران خیریه
                        '👨‍👩‍👧‍👦 خیریه - مدیریت خانواده' => [
                            'view all families','view own families','create family','edit own family','edit any family','delete own family','delete any family',
                        ],
                        '✅ خیریه - تأیید و رد' => [
                            'change family status','verify family','reject family',
                        ],
                        '👥 خیریه - اعضای خانواده' => [
                            'view family members','add family member','edit family member','remove family member',
                        ],
                        '🏢 خیریه - سازمان‌ها' => [
                            'manage organizations','view organizations',
                        ],
                        
                        // مجوزهای مالی (مشترک)
                        '📊 مالی - گزارش‌ها' => [
                            'view basic reports','view advanced reports','export reports','view financial reports',
                        ],
                        
                        // مجوزهای تنظیمات سیستم
                        '⚙️ سیستم - مدیریت کاربران' => [
                            'manage users','view users','create user','edit user','delete user',
                        ],
                        '🔐 سیستم - نقش‌ها و مجوزها' => [
                            'manage roles','manage permissions',
                        ],
                        '🌍 سیستم - مدیریت مناطق' => [
                            'manage regions',
                        ],
                        '📈 سیستم - آمار و گزارش‌ها' => [
                            'view all statistics','view system logs',
                        ],
                        '🛠️ سیستم - تنظیمات پیشرفته' => [
                            'manage system settings','backup system','restore system',
                        ],
                    ];

                        // برچسب‌های فارسی
                        $labels = \App\Models\CustomRole::getPermissionLabels();

                        // گروه‌بندی فقط برای مجوزهای موجود در $permissions
                        $availablePermissions = collect($permissions)->groupBy(function($perm) use ($permissionGroups) {
                            foreach ($permissionGroups as $groupName => $perms) {
                                if (in_array($perm->name, $perms, true)) {
                                    return $groupName;
                                }
                            }
                            return 'سایر';
                        })->sortKeys();
                    @endphp

                    <div class="space-y-5 max-h-[28rem] overflow-y-auto border rounded-lg p-4">
                        @foreach($availablePermissions as $groupName => $groupPerms)
                            <div class="border border-gray-200 rounded-md">
                                <div class="px-3 py-2 bg-gray-50 border-b text-sm font-semibold text-gray-700">
                                    {{ $groupName }}
                                </div>
                                <div class="p-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($groupPerms as $permission)
                                        <div class="flex items-center">
                                            <input type="checkbox"
                                                   id="permission_{{ $permission->id }}"
                                                   name="permissions[]"
                                                   value="{{ $permission->name }}"
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <label for="permission_{{ $permission->id }}" class="mr-2 text-sm text-gray-700">
                                                {{ $labels[$permission->name] ?? \App\Models\CustomRole::getPermissionLabel($permission->name) ?? 'برچسب فارسی نامشخص' }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end space-x-4 space-x-reverse">
                    <a href="{{ route('admin.access-levels.index') }}"
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        انصراف
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        ایجاد نقش
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
