// Public-side JS bundle. Filament/admin pages already get Alpine via
// Livewire; this is for catalog/blog/checkout where we ship plain
// Blade + Alpine without Livewire.
import Alpine from 'alpinejs';
import Swiper from 'swiper';
import { Navigation, Thumbs } from 'swiper/modules';
import L from 'leaflet';

// productGallery — wired to the Swiper instance pair on
// catalog/product.blade.php. Defined as an Alpine component so
// the markup stays declarative and there's no global state bleed
// between two product pages opened back-to-back in a SPA shell.
//
// Lightbox state piggybacks on the same component so opening the
// modal can sync the Swiper's active slide (and vice-versa) without
// a separate Alpine root.
Alpine.data('productGallery', (images) => ({
    images,
    main: null,
    thumbs: null,
    lightboxOpen: false,
    lightboxIndex: 0,

    init() {
        if (this.images.length > 1) {
            this.thumbs = new Swiper(this.$refs.thumbs, {
                modules: [Thumbs],
                slidesPerView: 'auto',
                spaceBetween: 8,
                watchSlidesProgress: true,
            });
        }
        this.main = new Swiper(this.$refs.main, {
            modules: [Navigation, Thumbs],
            spaceBetween: 0,
            navigation: this.images.length > 1
                ? { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' }
                : false,
            thumbs: this.thumbs ? { swiper: this.thumbs } : undefined,
        });
    },

    openLightbox(index) {
        this.lightboxIndex = index;
        this.lightboxOpen = true;
        // Lock body scroll while modal is open so wheel events
        // don't jiggle the background page underneath.
        document.documentElement.classList.add('overflow-hidden');
    },

    closeLightbox() {
        this.lightboxOpen = false;
        document.documentElement.classList.remove('overflow-hidden');
        // Keep the Swiper's active slide in sync with whatever the
        // user navigated to in the lightbox — feels coherent when
        // they close and the carousel is on the same image.
        if (this.main) this.main.slideTo(this.lightboxIndex, 0);
    },

    nextLightbox() {
        this.lightboxIndex = (this.lightboxIndex + 1) % this.images.length;
    },

    prevLightbox() {
        this.lightboxIndex = (this.lightboxIndex - 1 + this.images.length) % this.images.length;
    },
}));

// Read-only map on /contacts — marker at office coords with a popup
// showing the address. Tile-stack: OpenStreetMap (no key, no quota).
Alpine.data('contactsMap', ({ lat, lng, label }) => ({
    init() {
        const map = L.map(this.$refs.map, {
            scrollWheelZoom: false,
            zoomControl: true,
        }).setView([lat, lng], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        // Default Leaflet marker icons live in node_modules/leaflet/dist/images
        // and don't bundle through Vite by default. Inline a remote URL so
        // the marker always shows up.
        const icon = L.icon({
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
        });

        const marker = L.marker([lat, lng], { icon }).addTo(map);
        if (label) marker.bindPopup(label).openPopup();
    },
}));

window.Alpine = Alpine;
Alpine.start();
