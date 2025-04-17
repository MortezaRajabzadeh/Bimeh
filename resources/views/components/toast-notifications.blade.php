<div class="fixed top-4 right-4 z-50 space-y-2">
    @foreach($toasts as $toast)
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="
                window.addEventListener('toast-start-timer', event => {
                    if (event.detail.id === '{{ $toast['id'] }}') {
                        setTimeout(() => show = false, 3000);
                        setTimeout(() => Livewire.emit('removeToast', '{{ $toast['id'] }}'), 3500);
                    }
                });
            "
            x-transition
            class="flex items-center max-w-sm w-full p-4 rounded shadow text-white
                @if($toast['type'] === 'success') bg-green-500
                @elseif($toast['type'] === 'error') bg-red-500
                @elseif($toast['type'] === 'warning') bg-yellow-500
                @else bg-blue-500 @endif"
        >
            @if($toast['type'] === 'success') ✅
            @elseif($toast['type'] === 'error') ❌
            @elseif($toast['type'] === 'warning') ⚠️
            @else ℹ️
            @endif

            <span class="ml-2">{{ $toast['message'] }}</span>
        </div>
    @endforeach
</div>
