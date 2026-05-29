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
Alpine.data('productGallery', (images) => ({
    images,
    main: null,
    thumbs: null,
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
}));

window.Alpine = Alpine;
Alpine.start();
