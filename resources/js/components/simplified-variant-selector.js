/**
 * Simplified Variant Selector Component
 * Handles enhanced UX for the JSON-based variant system
 */
class SimplifiedVariantSelector {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeAnimations();
    }

    bindEvents() {
        // Listen for Livewire events
        document.addEventListener('livewire:initialized', () => {
            // Listen for variant changes
            Livewire.on('variantChanged', (data) => {
                this.handleVariantChange(data[0]);
            });

            // Listen for price updates
            Livewire.on('priceUpdated', (data) => {
                this.handlePriceUpdate(data[0]);
            });

            // Listen for options cleared
            Livewire.on('optionsCleared', () => {
                this.handleOptionsCleared();
            });
        });

        // Add smooth transitions to option buttons
        this.addButtonInteractions();
    }

    handleVariantChange(data) {
        // Animate the variant selection status
        const statusElement = document.querySelector('.variant-selection-status');
        if (statusElement) {
            statusElement.classList.add('animate-pulse');
            setTimeout(() => {
                statusElement.classList.remove('animate-pulse');
            }, 1000);
        }

        // Show success notification
        this.showNotification(`Variant selected: ${data.sku}`, 'success');

        // Update URL with selected options (optional)
        this.updateURL(data);
    }

    handlePriceUpdate(data) {
        // Animate price changes
        const priceElement = document.getElementById('current-price');
        if (priceElement) {
            priceElement.classList.add('animate-bounce');
            setTimeout(() => {
                priceElement.classList.remove('animate-bounce');
            }, 1000);
        }

        // Highlight price changes
        this.highlightPriceChange();
    }

    handleOptionsCleared() {
        // Reset all option buttons
        const optionButtons = document.querySelectorAll('.variant-option-group button');
        optionButtons.forEach(button => {
            button.classList.remove('border-teal-600', 'bg-teal-50', 'text-teal-700');
            button.classList.add('border-gray-300', 'bg-white', 'text-gray-700');
        });

        this.showNotification('Options cleared', 'info');
    }

    addButtonInteractions() {
        // Add hover effects and click animations
        document.addEventListener('click', (e) => {
            if (e.target.closest('.variant-option-group button')) {
                const button = e.target.closest('button');
                
                // Add click animation
                button.classList.add('animate-pulse');
                setTimeout(() => {
                    button.classList.remove('animate-pulse');
                }, 300);
            }
        });
    }

    highlightPriceChange() {
        const priceDisplay = document.getElementById('price-display');
        if (priceDisplay) {
            priceDisplay.classList.add('ring-2', 'ring-teal-200', 'ring-opacity-50');
            setTimeout(() => {
                priceDisplay.classList.remove('ring-2', 'ring-teal-200', 'ring-opacity-50');
            }, 2000);
        }
    }

    showNotification(message, type = 'info') {
        // Create a simple notification
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
        
        // Set colors based on type
        switch (type) {
            case 'success':
                notification.className += ' bg-green-500 text-white';
                break;
            case 'error':
                notification.className += ' bg-red-500 text-white';
                break;
            default:
                notification.className += ' bg-blue-500 text-white';
        }
        
        notification.textContent = message;
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Animate out and remove
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    updateURL(data) {
        // Optional: Update URL with selected variant for sharing/bookmarking
        if (data.options && Object.keys(data.options).length > 0) {
            const params = new URLSearchParams();
            Object.entries(data.options).forEach(([key, value]) => {
                params.set(key.toLowerCase(), value.toLowerCase());
            });
            
            const newURL = `${window.location.pathname}?${params.toString()}`;
            window.history.replaceState({}, '', newURL);
        }
    }

    initializeAnimations() {
        // Add CSS animations if not already present
        if (!document.getElementById('variant-selector-styles')) {
            const style = document.createElement('style');
            style.id = 'variant-selector-styles';
            style.textContent = `
                .variant-option-group button {
                    transition: all 0.2s ease-in-out;
                }
                
                .variant-option-group button:hover:not(:disabled) {
                    transform: translateY(-1px);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                }
                
                .variant-option-group button:active {
                    transform: translateY(0);
                }
                
                .variant-option-group button:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                }
                
                @keyframes price-highlight {
                    0% { background-color: transparent; }
                    50% { background-color: rgba(20, 184, 166, 0.1); }
                    100% { background-color: transparent; }
                }
                
                .price-highlight {
                    animation: price-highlight 1s ease-in-out;
                }
            `;
            document.head.appendChild(style);
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new SimplifiedVariantSelector();
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SimplifiedVariantSelector;
}
