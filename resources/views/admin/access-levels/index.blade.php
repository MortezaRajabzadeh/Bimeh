<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">🔐 مدیریت سطوح دسترسی</h1>
                    <p class="text-gray-600 mt-1">مدیریت نقش‌ها و سطوح دسترسی سلسله‌مراتبی</p>
                </div>
                <div class="flex space-x-2 space-x-reverse">
                    <a href="{{ route('admin.users.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg flex items-center justify-center text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                        </svg>
                        بازگشت به لیست
                    </a>
                    @can('manage roles')
                    <a href="{{ route('admin.access-levels.create') }}" 
                       class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded-lg flex items-center justify-center text-sm">
                        <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        افزودن نقش جدید
                    </a>
                    @endcan
                </div>
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
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">عملیات</th>
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
                            
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex space-x-2 space-x-reverse justify-center">
                                    @can('manage roles')
                                    <a href="{{ route('admin.access-levels.show', $role) }}" class="text-blue-600 hover:text-blue-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('admin.access-levels.edit', $role) }}" class="text-green-600 hover:text-green-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                    </a>
                                    @if($role->children->count() == 0 && $role->users->count() == 0)
                                    <form method="POST" action="{{ route('admin.access-levels.destroy', $role) }}" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('آیا از حذف این نقش اطمینان دارید؟')" class="text-red-600 hover:text-red-900">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
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