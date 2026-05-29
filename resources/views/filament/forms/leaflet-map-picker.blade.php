{{--
    Leaflet + OpenStreetMap picker for the Settings → Контакты form.
    Click on map drops/moves a marker, the picked lat/lng push back into
    the form's `data.map_lat` / `data.map_lng` properties via Livewire.

    `wire:ignore` on the wrapper because Leaflet mounts a complex DOM
    that Livewire morphdom would tear up on every re-render.

    Assets are loaded via CDN inside the field so we don't have to wire
    Leaflet into Filament's asset pipeline.

    Styles are INLINE rather than Tailwind utility classes because this
    blade renders inside the Filament admin panel, which uses its own
    Tailwind build that doesn't include our public-site safelist —
    `h-80`/`bg-slate-100`/etc resolve to nothing there and the map
    container collapses to height 0 (Leaflet then never paints tiles).
--}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<div x-data="triadLeafletPicker" wire:ignore>
    <div x-ref="map"
         style="height: 320px; width: 100%; border: 1px solid #cbd5e1; border-radius: 0.5rem; background: #f1f5f9; z-index: 1;"></div>
    <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">
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

            // Alpine auto-runs init() on mount. We use it to GATE the
            // actual Leaflet work behind an async script load — naming
            // the inner method something other than init() because if
            // we called it init() too, Alpine would invoke it BEFORE the
            // script promise resolves and `L` would be undefined.
            async init() {
                if (!window.L) {
                    await new Promise((resolve, reject) => {
                        const s = document.createElement('script');
                        s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        s.onload = resolve;
                        s.onerror = reject;
                        document.head.appendChild(s);
                    });
                }
                this.setupMap();
            },

            setupMap() {
                const lat = parseFloat(this.$wire.get('data.map_lat')) || this.defaultLat;
                const lng = parseFloat(this.$wire.get('data.map_lng')) || this.defaultLng;

                this.map = L.map(this.$refs.map).setView([lat, lng], this.defaultZoom);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19,
                }).addTo(this.map);

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

        register();
        document.addEventListener('alpine:init', register);
    })();
</script>
