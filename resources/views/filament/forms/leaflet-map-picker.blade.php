{{--
    Leaflet + OpenStreetMap picker for the Settings → Контакты form.
    Click on map drops/moves a marker, the picked lat/lng push back into
    the form's `data.map_lat` / `data.map_lng` properties via Livewire's
    `$wire.set()`.

    `wire:ignore` on the wrapper because Leaflet mounts a complex DOM
    that Livewire morphdom would tear up on every re-render. We read the
    initial coords on mount and use $wire.set() to write back — no
    entanglement needed in either direction.

    Assets are loaded via CDN inside the field so we don't have to wire
    Leaflet into Filament's asset pipeline (it's only used on this one
    settings tab). Loads once per page render.
--}}
<link rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin="">

<div
    x-data="triadLeafletPicker({
        defaultLat: 43.282317,
        defaultLng: 76.900101,
        defaultZoom: 15,
    })"
    x-init="
        await (async () => {
            if (!window.L) {
                await new Promise((resolve, reject) => {
                    const s = document.createElement('script');
                    s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    s.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
                    s.crossOrigin = '';
                    s.onload = resolve;
                    s.onerror = reject;
                    document.head.appendChild(s);
                });
            }
            init();
        })()
    "
    wire:ignore
    class="space-y-2"
>
    <div x-ref="map" class="h-80 w-full rounded border border-slate-300 bg-slate-50"></div>
    <p class="text-xs text-slate-500">
        Клик по карте ставит маркер. Координаты автоматически записываются в поля «Широта» и «Долгота» ниже.
        Маркер можно перетаскивать за иконку.
    </p>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        if (window.Alpine.data && !window.__triadLeafletPickerRegistered) {
            window.__triadLeafletPickerRegistered = true;
            window.Alpine.data('triadLeafletPicker', (opts) => ({
                map: null,
                marker: null,
                opts,

                init() {
                    const lat = parseFloat(this.$wire.get('data.map_lat')) || this.opts.defaultLat;
                    const lng = parseFloat(this.$wire.get('data.map_lng')) || this.opts.defaultLng;

                    this.map = L.map(this.$refs.map).setView([lat, lng], this.opts.defaultZoom);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                        maxZoom: 19,
                    }).addTo(this.map);

                    this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);

                    this.marker.on('dragend', (e) => {
                        const p = e.target.getLatLng();
                        this.commit(p.lat, p.lng);
                    });

                    this.map.on('click', (e) => {
                        this.marker.setLatLng(e.latlng);
                        this.commit(e.latlng.lat, e.latlng.lng);
                    });

                    // Fix Leaflet's auto-sizing race when the field mounts
                    // inside a tab that wasn't visible at first paint.
                    setTimeout(() => this.map.invalidateSize(), 50);
                },

                commit(lat, lng) {
                    this.$wire.set('data.map_lat', lat.toFixed(6), false);
                    this.$wire.set('data.map_lng', lng.toFixed(6), false);
                },
            }));
        }
    });
</script>
