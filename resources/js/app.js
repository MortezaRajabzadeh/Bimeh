import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

// Import Alpine.js plugins
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';

// Initialize Alpine.js before registering plugins
window.Alpine = Alpine;

// Register Alpine.js plugins
Alpine.plugin(focus);
Alpine.plugin(persist);

// Start Livewire
Livewire.start();
