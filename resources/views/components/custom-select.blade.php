@props([
    'wire:model' => null,
    'name' => null,
    'id' => null,
    'class' => '',
    'placeholder' => 'انتخاب کنید...',
    'width' => 'w-full',
    'options' => [],
    'selected' => null
])

<div class="relative {{ $width }}">
    <select 
        @if($attributes->get('wire:model')) wire:model="{{ $attributes->get('wire:model') }}" @endif
        @if($name) name="{{ $name }}" @endif
        @if($id) id="{{ $id }}" @endif
        class="appearance-none bg-white border border-gray-300 rounded-lg pr-4 pl-10 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors duration-200 {{ $class }}"
        {{ $attributes->except(['wire:model', 'class']) }}
    >
        @if($placeholder)
            <option value="" disabled @if(!$selected) selected @endif>{{ $placeholder }}</option>
        @endif
        
        @if($options && count($options) > 0)
            @foreach($options as $value => $label)
                <option value="{{ $value }}" @if($selected == $value) selected @endif>
                    {{ $label }}
                </option>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </select>
    
    <!-- آیکون dropdown -->
    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </div>
</div> 