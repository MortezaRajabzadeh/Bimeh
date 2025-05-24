<div class="relative">
    <select {{ $attributes->merge(['class' => 'block appearance-none w-full border rounded px-3 py-2 text-right focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500']) }}>
        {{ $slot }}
    </select>
    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </div>
</div> 