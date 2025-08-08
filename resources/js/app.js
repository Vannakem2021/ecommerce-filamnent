import './bootstrap';
import '../css/app.css';
import 'preline';

// Import Swiper and modules
import { Swiper } from 'swiper';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

document.addEventListener('livewire:navigated', () => {
    // Only reinitialize Preline if it's not already initialized
    if (typeof window.HSStaticMethods !== 'undefined') {
        // Destroy existing instances to prevent duplicates
        if (window.HSStaticMethods.destroy) {
            window.HSStaticMethods.destroy();
        }
        window.HSStaticMethods.autoInit();
    }

    // Initialize Swiper
    initializeSwiper();
});

// Initialize Swiper on page load
document.addEventListener('DOMContentLoaded', () => {
    initializeSwiper();
});

function initializeSwiper() {
    const swiperElement = document.querySelector('.hero-swiper');
    if (swiperElement && !swiperElement.swiper) {
        new Swiper('.hero-swiper', {
            modules: [Navigation, Pagination, Autoplay],
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
    }
}
