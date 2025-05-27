<div>
    @if(count($toasts ?? []))
        <div class="fixed top-4 right-4 z-50 space-y-4">
            @foreach($toasts as $toast)
                <div
                    x-data="{ show: true }"
                    x-show="show"
                    x-init="setTimeout(() => show = false, 20000)"
                    x-transition:enter="transform ease-out duration-300 transition"
                    x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                    x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @toast-start-timer.window="if ($event.detail.id === '{{ $toast['id'] }}') setTimeout(() => { $wire.removeToast('{{ $toast['id'] }}') }, 20000)"
                    class="max-w-sm w-full bg-{{ $toast['type'] === 'error' ? 'red' : ($toast['type'] === 'success' ? 'green' : ($toast['type'] === 'warning' ? 'orange' : 'blue')) }}-100 shadow-lg rounded-lg pointer-events-auto border border-{{ $toast['type'] === 'error' ? 'red' : ($toast['type'] === 'success' ? 'green' : ($toast['type'] === 'warning' ? 'orange' : 'blue')) }}-200"
                    role="{{ $toast['type'] === 'error' ? 'alert' : 'status' }}"
                    aria-live="polite">
                    <div class="p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                @if($toast['type'] === 'success')
                                    <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @elseif($toast['type'] === 'error')
                                    <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @elseif($toast['type'] === 'warning')
                                    <svg class="h-6 w-6 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                @else
                                    <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @endif
                            </div>
                            <div class="mr-3 w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 leading-relaxed">
                                    {{ $toast['message'] }}
                                </p>
                            </div>
                            <div class="mr-4 flex-shrink-0 flex">
                                <button
                                    @click="show = false"
                                    class="inline-flex text-gray-400 focus:outline-none focus:text-gray-500 transition ease-in-out duration-150 hover:text-gray-600"
                                    aria-label="بستن">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div> 