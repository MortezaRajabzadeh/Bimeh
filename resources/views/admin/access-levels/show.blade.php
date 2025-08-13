<x-app-layout>
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-700">
                    {{ $accessLevel->display_name ?: $accessLevel->name }} - جزئیات نقش
                </h2>
                <a href="{{ route('admin.access-levels.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg flex items-center justify-center text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                    </svg>
                    بازگشت به لیست
                </a>
                
                @can('manage roles')
                <div class="flex space-x-2 space-x-reverse">
                    <a href="{{ route('admin.access-levels.edit', $accessLevel) }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        ویرایش نقش
                    </a>
                </div>
                @endcan
            </div>
        </div>

        <div class="p-6">
            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">نام نقش</label>
                        <div class="mt-1 text-lg text-gray-900">{{ $accessLevel->name }}</div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">نام نمایشی</label>
                        <div class="mt-1 text-lg text-gray-900">{{ $accessLevel->display_name ?: '-' }}</div>
                    </div>
                    
                    @if($accessLevel->description)
                    <div>
                        <label class="block text-sm font-medium text-gray-700">توضیحات</label>
                        <div class="mt-1 text-gray-900">{{ $accessLevel->description }}</div>
                    </div>
                    @endif
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">نقش والد</label>
                        <div class="mt-1">
                            @if($accessLevel->parent)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                                    {{ $accessLevel->parent->display_name ?: $accessLevel->parent->name }}
                                </span>
                            @else
                                <span class="text-gray-500">نقش اصلی</span>
                            @endif
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">وراثت مجوزها</label>
                        <div class="mt-1">
                            @if($accessLevel->inherit_permissions)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">
                                    فعال
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-800">
                                    غیرفعال
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">تعداد کاربران</label>
                        <div class="mt-1 text-lg text-gray-900">{{ $accessLevel->users->count() }} کاربر</div>
                    </div>
                </div>
            </div>

            <!-- Children Roles -->
            @if($accessLevel->children->count() > 0)
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">نقش‌های فرزند</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($accessLevel->children as $child)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900">{{ $child->display_name ?: $child->name }}</h4>
                        <p class="text-sm text-gray-600 mt-1">{{ $child->permissions->count() }} مجوز</p>
                        <a href="{{ route('admin.access-levels.show', $child) }}" 
                           class="text-blue-600 hover:text-blue-800 text-sm">مشاهده جزئیات</a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Permissions -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">مجوزهای نقش</h3>
                
                @if($accessLevel->permissions->count() > 0 || ($accessLevel->inherit_permissions && $inheritedPermissions->count() > 0))
                    <div class="space-y-6">
                        <!-- Direct Permissions -->
                        @if($accessLevel->permissions->count() > 0)
                        <div>
                            <h4 class="font-medium text-gray-700 mb-3">مجوزهای مستقیم ({{ $accessLevel->permissions->count() }})</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                @foreach($accessLevel->permissions as $permission)
                                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2">
                                    <span class="text-sm text-blue-800">
                                        {{ \App\Models\CustomRole::getPermissionLabels()[$permission->name] ?? $permission->name }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        <!-- Inherited Permissions -->
                        @if($accessLevel->inherit_permissions && $inheritedPermissions->count() > $accessLevel->permissions->count())
                        <div>
                            <h4 class="font-medium text-gray-700 mb-3">
                                مجوزهای به ارث رسیده ({{ $inheritedPermissions->count() - $accessLevel->permissions->count() }})
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                @foreach($inheritedPermissions as $permission)
                                    @if(!$accessLevel->permissions->contains($permission))
                                    <div class="bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                                        <span class="text-sm text-green-800">
                                            {{ \App\Models\CustomRole::getPermissionLabels()[$permission->name] ?? $permission->name }}
                                        </span>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">بدون مجوز</h3>
                        <p class="mt-1 text-sm text-gray-500">این نقش هیچ مجوزی ندارد.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
</x-app-layout> 