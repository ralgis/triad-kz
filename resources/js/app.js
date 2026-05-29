// Public-side JS bundle. Filament/admin pages already get Alpine via
// Livewire; this is for catalog/blog/checkout where we ship plain
// Blade + Alpine without Livewire.
import Alpine from 'alpinejs';
import Swiper from 'swiper';
import { Navigation, Thumbs } from 'swiper/modules';

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

window.Alpine = Alpine;
Alpine.start();
