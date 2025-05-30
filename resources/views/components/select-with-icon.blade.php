<div class="rtl-select-wrapper">
    <select {{ $attributes->merge(['class' => 'rtl-select block w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500']) }}>
        {{ $slot }}
    </select>
</div> 