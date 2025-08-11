/**
 * JSON Variant Selector Component
 * Handles variant selection for the new JSON-based variant system
 */
class JsonVariantSelector {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            onVariantChange: options.onVariantChange || this.defaultVariantChange.bind(this),
            onPriceUpdate: options.onPriceUpdate || this.defaultPriceUpdate.bind(this),
            onStockUpdate: options.onStockUpdate || this.defaultStockUpdate.bind(this),
            enableCompatibilityCheck: options.enableCompatibilityCheck !== false,
            showPriceOnOptions: options.showPriceOnOptions !== false,
            ...options
        };
        
        this.selectedOptions = {};
        this.availableOptions = {};
        this.variants = [];
        this.currentVariant = null;
        
        this.init();
    }
    
    init() {
        this.loadData();
        this.bindEvents();
        this.updateDisplay();
    }
    
    loadData() {
        // Load data from data attributes or Livewire
        const element = this.element;
        
        try {
            this.availableOptions = JSON.parse(element.dataset.availableOptions || '{}');
            this.variants = JSON.parse(element.dataset.variants || '[]');
            this.selectedOptions = JSON.parse(element.dataset.selectedOptions || '{}');
        } catch (e) {
            console.error('Error parsing variant data:', e);
            this.availableOptions = {};
            this.variants = [];
            this.selectedOptions = {};
        }
    }
    
    bindEvents() {
        // Bind option selection events
        this.element.addEventListener('change', (e) => {
            if (e.target.matches('[data-option-type]')) {
                this.handleOptionSelection(e.target);
            }
        });
        
        // Bind clear options button
        const clearButton = this.element.querySelector('[data-clear-options]');
        if (clearButton) {
            clearButton.addEventListener('click', () => {
                this.clearAllOptions();
            });
        }
        
        // Listen for Livewire events
        this.bindLivewireEvents();
    }
    
    bindLivewireEvents() {
        // Listen for variant updates from Livewire
        document.addEventListener('livewire:load', () => {
            this.bindLivewireListeners();
        });
        
        // For Livewire v3
        document.addEventListener('livewire:navigated', () => {
            this.bindLivewireListeners();
        });
        
        this.bindLivewireListeners();
    }
    
    bindLivewireListeners() {
        if (window.Livewire) {
            // Listen for price updates
            window.Livewire.on('priceUpdated', (data) => {
                this.handlePriceUpdate(data);
            });
            
            // Listen for variant changes
            window.Livewire.on('variantChanged', (data) => {
                this.handleVariantChange(data);
            });
            
            // Listen for options cleared
            window.Livewire.on('optionsCleared', () => {
                this.handleOptionsCleared();
            });
        }
    }
    
    handleOptionSelection(selectElement) {
        const optionType = selectElement.dataset.optionType;
        const optionValue = selectElement.value;
        
        if (!optionValue) {
            delete this.selectedOptions[optionType];
        } else {
            this.selectedOptions[optionType] = optionValue;
        }
        
        // Update compatibility for other options
        if (this.options.enableCompatibilityCheck) {
            this.updateOptionCompatibility();
        }
        
        // Find matching variant
        this.findMatchingVariant();
        
        // Notify Livewire
        this.notifyLivewire(optionType, optionValue);
        
        // Update display
        this.updateDisplay();
    }
    
    findMatchingVariant() {
        this.currentVariant = null;
        
        if (Object.keys(this.selectedOptions).length === 0) {
            return;
        }
        
        // Find variant that matches all selected options
        this.currentVariant = this.variants.find(variant => {
            if (!variant.options) return false;
            
            // Check if variant has all selected options with matching values
            for (const [key, value] of Object.entries(this.selectedOptions)) {
                const variantOption = variant.options[key];
                const variantValue = typeof variantOption === 'object' && variantOption.value !== undefined 
                    ? variantOption.value 
                    : variantOption;
                    
                if (variantValue !== value) {
                    return false;
                }
            }
            
            return true;
        });
        
        // Trigger callback
        this.options.onVariantChange(this.currentVariant, this.selectedOptions);
    }
    
    updateOptionCompatibility() {
        const selects = this.element.querySelectorAll('[data-option-type]');
        
        selects.forEach(select => {
            const optionType = select.dataset.optionType;
            const options = select.querySelectorAll('option[value]:not([value=""])');
            
            options.forEach(option => {
                const optionValue = option.value;
                const isCompatible = this.isOptionCompatible(optionType, optionValue);
                
                option.disabled = !isCompatible;
                option.classList.toggle('incompatible', !isCompatible);
            });
        });
    }
    
    isOptionCompatible(optionType, optionValue) {
        // If this option is already selected, it's compatible
        if (this.selectedOptions[optionType] === optionValue) {
            return true;
        }
        
        // Create test selection
        const testOptions = { ...this.selectedOptions, [optionType]: optionValue };
        
        // Check if any variant exists with these options
        return this.variants.some(variant => {
            if (!variant.options) return false;
            
            for (const [key, value] of Object.entries(testOptions)) {
                const variantOption = variant.options[key];
                const variantValue = typeof variantOption === 'object' && variantOption.value !== undefined 
                    ? variantOption.value 
                    : variantOption;
                    
                if (variantValue !== value) {
                    return false;
                }
            }
            
            return true;
        });
    }
    
    updateDisplay() {
        // Update variant info display
        this.updateVariantInfo();
        
        // Update option displays
        this.updateOptionDisplays();
        
        // Update clear button
        this.updateClearButton();
    }
    
    updateVariantInfo() {
        const infoElement = this.element.querySelector('[data-variant-info]');
        if (!infoElement) return;
        
        if (this.currentVariant) {
            infoElement.innerHTML = `
                <div class="variant-details">
                    <span class="variant-sku">SKU: ${this.currentVariant.sku || 'N/A'}</span>
                    <span class="variant-stock">Stock: ${this.currentVariant.stock_quantity || 0}</span>
                </div>
            `;
            infoElement.classList.remove('hidden');
        } else {
            infoElement.classList.add('hidden');
        }
    }
    
    updateOptionDisplays() {
        if (!this.options.showPriceOnOptions) return;
        
        const selects = this.element.querySelectorAll('[data-option-type]');
        
        selects.forEach(select => {
            const optionType = select.dataset.optionType;
            const options = select.querySelectorAll('option[value]:not([value=""])');
            
            options.forEach(option => {
                const optionValue = option.value;
                const priceInfo = this.getOptionPriceInfo(optionType, optionValue);
                
                if (priceInfo) {
                    const originalText = option.dataset.originalText || option.textContent;
                    option.dataset.originalText = originalText.replace(/ \(\$.*?\)$/, '');
                    option.textContent = `${option.dataset.originalText} (${priceInfo})`;
                }
            });
        });
    }
    
    getOptionPriceInfo(optionType, optionValue) {
        // Find variants with this option
        const variantsWithOption = this.variants.filter(variant => {
            if (!variant.options) return false;
            
            const variantOption = variant.options[optionType];
            const variantValue = typeof variantOption === 'object' && variantOption.value !== undefined 
                ? variantOption.value 
                : variantOption;
                
            return variantValue === optionValue;
        });
        
        if (variantsWithOption.length === 0) return null;
        
        // Get price range for this option
        const prices = variantsWithOption.map(variant => {
            return variant.override_price || variant.price_cents || 0;
        });
        
        const minPrice = Math.min(...prices) / 100;
        const maxPrice = Math.max(...prices) / 100;
        
        if (minPrice === maxPrice) {
            return `$${minPrice.toFixed(2)}`;
        } else {
            return `$${minPrice.toFixed(2)} - $${maxPrice.toFixed(2)}`;
        }
    }
    
    updateClearButton() {
        const clearButton = this.element.querySelector('[data-clear-options]');
        if (!clearButton) return;
        
        const hasSelections = Object.keys(this.selectedOptions).length > 0;
        clearButton.style.display = hasSelections ? 'inline-block' : 'none';
    }
    
    clearAllOptions() {
        this.selectedOptions = {};
        this.currentVariant = null;
        
        // Clear all select elements
        const selects = this.element.querySelectorAll('[data-option-type]');
        selects.forEach(select => {
            select.value = '';
        });
        
        // Update display
        this.updateDisplay();
        
        // Notify Livewire
        if (window.Livewire && this.element.dataset.livewireComponent) {
            window.Livewire.dispatch('clearOptions');
        }
        
        // Trigger callback
        this.options.onVariantChange(null, {});
    }
    
    notifyLivewire(optionType, optionValue) {
        if (window.Livewire && this.element.dataset.livewireComponent) {
            window.Livewire.dispatch('selectOption', {
                optionName: optionType,
                optionValue: optionValue
            });
        }
    }
    
    // Handle events from Livewire
    handlePriceUpdate(data) {
        this.selectedOptions = data.selectedOptions || {};
        
        // Update price display
        this.options.onPriceUpdate(data);
    }
    
    handleVariantChange(data) {
        this.currentVariant = data;
        
        // Update stock display
        this.options.onStockUpdate(data);
        
        // Update variant info
        this.updateVariantInfo();
    }
    
    handleOptionsCleared() {
        this.clearAllOptions();
    }
    
    // Default callbacks
    defaultVariantChange(variant, selectedOptions) {
        console.log('Variant changed:', variant, selectedOptions);
    }
    
    defaultPriceUpdate(data) {
        const priceElement = document.querySelector('[data-current-price]');
        if (priceElement && data.dynamicPrice) {
            priceElement.textContent = `$${data.dynamicPrice.price.toFixed(2)}`;
        }
    }
    
    defaultStockUpdate(data) {
        const stockElement = document.querySelector('[data-stock-info]');
        if (stockElement) {
            const inStock = data.stock > 0;
            stockElement.textContent = inStock ? `${data.stock} in stock` : 'Out of stock';
            stockElement.className = inStock ? 'text-green-600' : 'text-red-600';
        }
    }
    
    // Public API methods
    getSelectedOptions() {
        return { ...this.selectedOptions };
    }
    
    getCurrentVariant() {
        return this.currentVariant;
    }
    
    setSelectedOptions(options) {
        this.selectedOptions = { ...options };
        
        // Update select elements
        const selects = this.element.querySelectorAll('[data-option-type]');
        selects.forEach(select => {
            const optionType = select.dataset.optionType;
            select.value = this.selectedOptions[optionType] || '';
        });
        
        // Find matching variant and update display
        this.findMatchingVariant();
        this.updateDisplay();
    }
    
    updateData(availableOptions, variants) {
        this.availableOptions = availableOptions;
        this.variants = variants;
        this.findMatchingVariant();
        this.updateDisplay();
    }
}

// Auto-initialize variant selectors
document.addEventListener('DOMContentLoaded', function() {
    const selectors = document.querySelectorAll('[data-variant-selector]');
    
    selectors.forEach(element => {
        // Get options from data attributes
        const options = {};
        
        if (element.dataset.enableCompatibilityCheck !== undefined) {
            options.enableCompatibilityCheck = element.dataset.enableCompatibilityCheck === 'true';
        }
        
        if (element.dataset.showPriceOnOptions !== undefined) {
            options.showPriceOnOptions = element.dataset.showPriceOnOptions === 'true';
        }
        
        // Initialize the selector
        new JsonVariantSelector(element, options);
    });
});

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = JsonVariantSelector;
}

// Global access
window.JsonVariantSelector = JsonVariantSelector;
