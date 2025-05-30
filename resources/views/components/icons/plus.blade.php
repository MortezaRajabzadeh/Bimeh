@props(['class' => 'h-5 w-5'])

<svg xmlns="http://www.w3.org/2000/svg" {{ $attributes->merge(['class' => $class]) }} fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path d="M12 4v16M4 12h16" />
</svg> 