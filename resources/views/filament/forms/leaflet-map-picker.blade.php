{{--
    Leaflet + OpenStreetMap picker for the Settings → Контакты form.
    Click on map drops/moves a marker, the picked lat/lng push back into
    the form's `data.map_lat` / `data.map_lng` properties via Livewire's
    `$wire.set()`.

    `wire:ignore` on the wrapper because Leaflet mounts a complex DOM
    that Livewire morphdom would tear up on every re-render.

    Assets are loaded via CDN inside the field so we don't have to wire
    Leaflet into Filament's asset pipeline (it's only used on this one
    settings tab).
--}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<div
    x-data="triadLeafletPicker"
    x-init="boot()"
    wire:ignore
    class="space-y-2"
>
    <div x-ref="map" class="h-80 w-full rounded border border-slate-300 bg-slate-100"></div>
    <p class="text-xs text-slate-500">
        Клик по карте ставит маркер. Координаты автоматически записываются в поля «Широта» и «Долгота» ниже.
        Маркер можно перетаскивать за иконку.
    </p>
</div>

<script>
    (function () {
        if (window.__triadLeafletPickerRegistered) return;
        window.__triadLeafletPickerRegistered = true;

        const factory = () => ({
            map: null,
            marker: null,
            defaultLat: 43.282317,
            defaultLng: 76.900101,
            defaultZoom: 15,

            async boot() {
                if (!window.L) {
                    await new Promise((resolve, reject) => {
                        const s = document.createElement('script');
                        s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        s.onload = resolve;
                        s.onerror = reject;
                        document.head.appendChild(s);
                    });
                }
                this.init();
            },

            init() {
                const lat = parseFloat(this.$wire.get('data.map_lat')) || this.defaultLat;
                const lng = parseFloat(this.$wire.get('data.map_lng')) || this.defaultLng;

                this.map = L.map(this.$refs.map).setView([lat, lng], this.defaultZoom);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19,
                }).addTo(this.map);

                // Default Leaflet marker icons need URL injection because
                // we're not bundling them through Vite here.
                const icon = L.icon({
                    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                });

                this.marker = L.marker([lat, lng], { draggable: true, icon }).addTo(this.map);

                this.marker.on('dragend', (e) => {
                    const p = e.target.getLatLng();
                    this.commit(p.lat, p.lng);
                });

                this.map.on('click', (e) => {
                    this.marker.setLatLng(e.latlng);
                    this.commit(e.latlng.lat, e.latlng.lng);
                });

                // Leaflet auto-sizes on mount but Filament tabs sometimes
                // mount the field hidden — re-trigger after layout settles.
                setTimeout(() => this.map.invalidateSize(), 100);
            },

            commit(lat, lng) {
                this.$wire.set('data.map_lat', lat.toFixed(6), false);
                this.$wire.set('data.map_lng', lng.toFixed(6), false);
            },
        });

        const register = () => {
            if (window.Alpine) {
                window.Alpine.data('triadLeafletPicker', factory);
            }
        };

        // Alpine may have already started (Filament boots it eagerly) —
        // register immediately in that case, AND on alpine:init for the
        // race where this script runs before Alpine.
        register();
        document.addEventListener('alpine:init', register);
    })();
</script>
