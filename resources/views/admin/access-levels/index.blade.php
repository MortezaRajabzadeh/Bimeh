<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">🔐 مدیریت سطوح دسترسی</h1>
                    <p class="text-gray-600 mt-1">مدیریت نقش‌ها و سطوح دسترسی سلسله‌مراتبی</p>
                </div>
                
                @can('manage roles')
                <a href="{{ route('admin.access-levels.create') }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    افزودن نقش جدید
                </a>
                @endcan
            </div>

            <!-- Roles Tree -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">درخت سلسله‌ مراتبی نقش‌ها</h3>
                
                @if($roleTree && count($roleTree) > 0)
                    <div class="space-y-2">
                        @foreach($roleTree as $role)
                            @include('admin.access-levels.partials.role-tree-item', ['role' => $role, 'level' => 0])
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">هنوز نقشی تعریف نشده است.</p>
                @endif
            </div>

            <!-- Roles Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">نام نقش</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">نام نمایشی</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">والد</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">تعداد مجوزها</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">وراثت مجوزها</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">عملیات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($roles as $role)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @php
                                        $depth = $role->parent ? ($role->parent->parent ? 2 : 1) : 0;
                                    @endphp
                                    @for($i = 0; $i < $depth; $i++)
                                        <span class="text-gray-400 ml-2">└─</span>
                                    @endfor
                                    <span class="text-sm font-medium text-gray-900">{{ $role->name }}</span>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900">{{ $role->display_name ?: $role->name }}</span>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($role->parent)
                                    <span class="text-sm text-gray-600">{{ $role->parent->display_name ?: $role->parent->name }}</span>
                                @else
                                    <span class="text-sm text-gray-400">-</span>
                                @endif
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900">{{ $role->permissions->count() }}</span>
                                @if($role->inherit_permissions && $role->parent)
                                    <span class="text-xs text-blue-600">(+ {{ $role->getInheritedPermissions()->count() - $role->permissions->count() }} به ارث رسیده)</span>
                                @endif
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($role->inherit_permissions)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        فعال
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        غیرفعال
                                    </span>
                                @endif
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    @can('manage roles')
                                    <a href="{{ route('admin.access-levels.show', $role) }}" 
                                       class="text-blue-600 hover:text-blue-900" title="مشاهده">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    
                                    <a href="{{ route('admin.access-levels.edit', $role) }}" 
                                       class="text-indigo-600 hover:text-indigo-900" title="ویرایش">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    
                                    @if($role->children->count() == 0 && $role->users->count() == 0)
                                    <form action="{{ route('admin.access-levels.destroy', $role) }}" method="POST" class="inline" 
                                          onsubmit="return confirm('آیا از حذف این نقش اطمینان دارید؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="حذف">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                هیچ نقشی یافت نشد
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout> 