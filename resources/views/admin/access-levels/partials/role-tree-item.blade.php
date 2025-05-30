@php
    $paddingStyle = 'padding-right: ' . ($level * 20) . 'px;';
@endphp

<div class="flex items-center py-2 px-3 rounded-lg hover:bg-gray-100" style="{{ $paddingStyle }}">
    <div class="flex items-center flex-1">
        @if($level > 0)
            <span class="text-gray-400 mr-2">└─</span>
        @endif
        
        <div class="flex items-center">
            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            
            <div>
                <div class="text-sm font-medium text-gray-900">
                    {{ $role->display_name ?: $role->name }}
                </div>
                <div class="text-xs text-gray-500">
                    {{ is_array($role->permissions) ? count($role->permissions) : $role->permissions->count() }} مجوز
                    @if($role->inherit_permissions && $role->parent)
                        @php
                            $inherited = $role->getInheritedPermissions();
                            $ownCount = is_array($role->permissions) ? count($role->permissions) : $role->permissions->count();
                            $totalCount = $inherited ? $inherited->count() : 0;
                        @endphp
                        (+ {{ $totalCount - $ownCount }} به ارث رسیده)
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex items-center space-x-2 space-x-reverse">
        @if($role->inherit_permissions)
            <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full">و راثت فعال</span>
        @endif
        
        @can('manage roles')
        <a href="{{ route('admin.access-levels.show', $role) }}" 
           class="text-blue-600 hover:text-blue-800 text-xs">مشاهده</a>
        @endcan
    </div>
</div>

@if(isset($role->children) && ((is_array($role->children) && count($role->children) > 0) || (!is_array($role->children) && $role->children && $role->children->count() > 0)))
    @foreach((is_array($role->children) ? $role->children : $role->children) as $child)
        @include('admin.access-levels.partials.role-tree-item', ['role' => $child, 'level' => $level + 1])
    @endforeach
@endif 