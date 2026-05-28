// Public-side JS bundle. Filament/admin pages already get Alpine via
// Livewire; this is for catalog/blog/checkout where we ship plain
// Blade + Alpine without Livewire.
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
