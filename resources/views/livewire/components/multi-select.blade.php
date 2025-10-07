<div
    x-data="{
        open: false,
        search: '',
        focusedIndex: -1
    }"
    @click.away="open = false"
    @keydown.escape.window="open = false"
    class="relative w-full"
>
    <!-- Button (Trigger) -->
    <button
        type="button"
        @click="open = !open"
        @keydown.enter.prevent="open = !open"
        @keydown.space.prevent="open = !open"
        aria-haspopup="listbox"
        :aria-expanded="open"
        aria-label="{{ $placeholder }}"
        class="w-full px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 transition-colors duration-200 bg-white text-right flex justify-between items-center {{ $error ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500' }}"
    >
        <span x-text="$wire.selectedCount > 0 ? $wire.selectedCount + ' مورد انتخاب شده' : '{{ $placeholder }}'"></span>
        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <!-- Dropdown Container -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        role="listbox"
        aria-multiselectable="true"
        tabindex="-1"
        class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-hidden"
    >
        <!-- Search Box (if searchable) -->
        @if($searchable)
            <div class="p-2 border-b border-gray-200">
                <input
                    type="text"
                    x-model="search"
                    x-ref="searchInput"
                    @keydown.escape="open = false"
                    placeholder="جستجو..."
                    class="w-full px-3 py-2 text-sm border-b border-gray-200 focus:outline-none focus:ring-0"
                    x-init="$watch('open', value => { if(value) { setTimeout(() => $refs.searchInput.focus(), 50) } })"
                >
            </div>
        @endif

        <!-- Options List -->
        <div class="py-1 max-h-48 overflow-y-auto">
            @forelse($options as $key => $label)
                <label
                    x-show="search === '' || '{{ $label }}'.toLowerCase().includes(search.toLowerCase())"
                    role="option"
                    :aria-selected="{{ in_array($key, $selected) ? 'true' : 'false' }}"
                    class="flex items-center px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm transition-colors duration-150 {{ in_array($key, $selected) ? 'bg-blue-50' : '' }}"
                    @click="$wire.toggle('{{ $key }}')"
                    @keydown.enter.prevent="$wire.toggle('{{ $key }}')"
                >
                    <input
                        type="checkbox"
                        value="{{ $key }}"
                        @checked(in_array($key, $selected))
                        class="ml-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        tabindex="-1"
                    >
                    <span class="text-gray-900">{{ $label }}</span>
                </label>
            @empty
                <div class="px-3 py-2 text-sm text-gray-500 text-center">
                    موردی یافت نشد
                </div>
            @endforelse
        </div>

        <!-- Footer (Clear All Button) -->
        @if(count($selected) > 0)
            <div class="p-2 border-t border-gray-200">
                <button
                    type="button"
                    wire:click="clearAll"
                    class="w-full px-3 py-1.5 text-sm text-gray-600 hover:text-red-600 rounded hover:bg-red-50 transition-colors duration-150"
                >
                    پاک کردن همه
                </button>
            </div>
        @endif
    </div>

    <!-- Selected Items Display (Below Dropdown) -->
    <div class="flex flex-wrap gap-1.5 mt-2" role="list" aria-label="موارد انتخاب شده">
        @foreach($selected as $selectedKey)
            <div
                role="listitem"
                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-blue-100 text-blue-800 transition-colors duration-150"
            >
                {{ $options[$selectedKey] ?? $selectedKey }}
                <button
                    type="button"
                    wire:click="toggle('{{ $selectedKey }}')"
                    aria-label="حذف {{ $options[$selectedKey] ?? $selectedKey }}"
                    class="mr-1 text-blue-600 hover:text-blue-800 focus:outline-none"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endforeach
    </div>

    <!-- Helper Text -->
    <p class="text-xs text-gray-500 mt-1">
        @if($maxSelections > 0)
            حداکثر {{ $maxSelections }} مورد قابل انتخاب است
        @else
            می‌توانید چندین مورد انتخاب کنید
        @endif
    </p>

    <!-- Error Display -->
    @if($error)
        <p class="mt-1 text-xs text-red-600" role="alert">{{ $error }}</p>
    @endif
</div>