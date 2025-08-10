import "./bootstrap";
import "../css/app.css";

// Import Alpine.js
import Alpine from "alpinejs";
window.Alpine = Alpine;
Alpine.start();

// Import simplified variant selector
import "./components/simplified-variant-selector.js";

// Import Swiper and modules
import { Swiper } from "swiper";
import { Navigation, Pagination, Autoplay } from "swiper/modules";
import "swiper/css";
import "swiper/css/navigation";
import "swiper/css/pagination";

// Function to check if we're in Filament admin area
function isFilamentAdmin() {
    return (
        window.location.pathname.startsWith("/admin") ||
        document.body.classList.contains("fi-body") ||
        document.querySelector(".fi-main") !== null
    );
}

// Conditionally import and initialize Preline only for frontend
async function initializePreline() {
    if (!isFilamentAdmin()) {
        try {
            await import("preline");

            // Only reinitialize Preline if it's not already initialized
            if (typeof window.HSStaticMethods !== "undefined") {
                // Destroy existing instances to prevent duplicates
                if (window.HSStaticMethods.destroy) {
                    window.HSStaticMethods.destroy();
                }
                window.HSStaticMethods.autoInit();
            }
        } catch (error) {
            console.warn("Failed to load Preline:", error);
        }
    }
}

document.addEventListener("livewire:navigated", () => {
    // Initialize Preline only for frontend
    initializePreline();

    // Initialize Swiper
    initializeSwiper();
});

// Initialize on page load
document.addEventListener("DOMContentLoaded", () => {
    // Initialize Preline only for frontend
    initializePreline();

    // Initialize Swiper
    initializeSwiper();
});

function initializeSwiper() {
    const swiperElement = document.querySelector(".hero-swiper");
    if (swiperElement && !swiperElement.swiper) {
        new Swiper(".hero-swiper", {
            modules: [Navigation, Pagination, Autoplay],
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
        });
    }
}
