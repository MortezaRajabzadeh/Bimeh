<x-app-layout>
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-semibold text-gray-700">
                        ویرایش سطح دسترسی: 
                        @if($accessLevel->name == 'admin')
                            ادمین
                        @elseif($accessLevel->name == 'charity')
                            خیریه
                        @elseif($accessLevel->name == 'insurance')
                            بیمه
                        @else
                            {{ $accessLevel->name }}
                        @endif
                    </h2>
                    <a href="{{ route('admin.access-levels.index') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg flex items-center justify-center text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                        </svg>
                        بازگشت به لیست
                    </a>
                </div>

                <form action="{{ route('admin.access-levels.update', $accessLevel) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')
                    
                    <!-- نام سطح دسترسی - به صورت نمایشی -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">نام سطح دسترسی</label>
                        <div class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-700">
                            @if($accessLevel->name == 'admin')
                                ادمین
                            @elseif($accessLevel->name == 'charity')
                                خیریه
                            @elseif($accessLevel->name == 'insurance')
                                بیمه
                            @else
                                {{ $accessLevel->name }}
                            @endif
                        </div>
                        <input type="hidden" name="name" value="{{ $accessLevel->name }}">
                    </div>

                    <!-- مجوزها -->
                    <div>
                        <h3 class="text-md font-medium text-gray-700 mb-2">مجوزها</h3>
                        <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($permissions ?? [] as $permission)
                                <div class="flex items-center">
                                    <input type="checkbox" name="permissions[]" id="permission_{{ $permission->id }}" 
                                        value="{{ $permission->id }}" 
                                        {{ in_array($permission->id, old('permissions', $accessLevel->permissions->pluck('id')->toArray())) ? 'checked' : '' }}
                                        class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                    <label for="permission_{{ $permission->id }}" class="mr-2 text-sm text-gray-700">
                                        <x-permission-translation :permission="$permission->name" />
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @error('permissions')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- دکمه ذخیره تغییرات -->
                    <div class="flex justify-end gap-2">
                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            ذخیره تغییرات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout> 