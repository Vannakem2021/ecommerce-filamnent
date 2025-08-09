/**
 * Variant Matcher Component
 * Handles efficient client-side variant matching and availability checking
 */
class VariantMatcher {
    constructor(variantCombinationsMatrix = []) {
        this.variantMatrix = variantCombinationsMatrix;
        this.cache = new Map();
        this.init();
    }
    
    init() {
        // Pre-process variant matrix for faster lookups
        this.variantLookup = new Map();
        this.attributeValueLookup = new Map();
        
        this.variantMatrix.forEach(variant => {
            // Create lookup key from combination
            const key = this.createCombinationKey(variant.combination);
            this.variantLookup.set(key, variant);
            
            // Index by individual attribute values for availability checking
            Object.entries(variant.combination).forEach(([attrId, valueId]) => {
                if (!this.attributeValueLookup.has(attrId)) {
                    this.attributeValueLookup.set(attrId, new Set());
                }
                if (variant.is_available) {
                    this.attributeValueLookup.get(attrId).add(parseInt(valueId));
                }
            });
        });
    }
    
    /**
     * Create a consistent key from attribute combination
     */
    createCombinationKey(combination) {
        return Object.keys(combination)
            .sort()
            .map(key => `${key}:${combination[key]}`)
            .join('|');
    }
    
    /**
     * Find variant by attribute combination
     */
    findVariant(selectedAttributes) {
        if (!selectedAttributes || Object.keys(selectedAttributes).length === 0) {
            return null;
        }
        
        const key = this.createCombinationKey(selectedAttributes);
        const cacheKey = `variant_${key}`;
        
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }
        
        const variant = this.variantLookup.get(key) || null;
        this.cache.set(cacheKey, variant);
        
        return variant;
    }
    
    /**
     * Get available attribute values based on current selections
     */
    getAvailableValues(attributeId, selectedAttributes = {}) {
        const cacheKey = `available_${attributeId}_${this.createCombinationKey(selectedAttributes)}`;
        
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }
        
        const availableValues = new Set();
        
        // If no selections, return all available values for this attribute
        if (Object.keys(selectedAttributes).length === 0) {
            const values = this.attributeValueLookup.get(attributeId.toString()) || new Set();
            this.cache.set(cacheKey, Array.from(values));
            return Array.from(values);
        }
        
        // Filter variants that match current selections (excluding target attribute)
        const otherSelections = { ...selectedAttributes };
        delete otherSelections[attributeId];
        
        this.variantMatrix.forEach(variant => {
            if (!variant.is_available) return;
            
            // Check if variant matches other selections
            const matchesOthers = Object.entries(otherSelections).every(([attrId, valueId]) => {
                return variant.combination[attrId] == valueId;
            });
            
            if (matchesOthers && variant.combination[attributeId]) {
                availableValues.add(parseInt(variant.combination[attributeId]));
            }
        });
        
        const result = Array.from(availableValues);
        this.cache.set(cacheKey, result);
        return result;
    }
    
    /**
     * Check if a specific combination is available
     */
    isAvailable(selectedAttributes) {
        const variant = this.findVariant(selectedAttributes);
        return variant && variant.is_available;
    }
    
    /**
     * Get price for a specific combination
     */
    getPrice(selectedAttributes) {
        const variant = this.findVariant(selectedAttributes);
        return variant ? variant.price_cents : null;
    }
    
    /**
     * Get stock quantity for a specific combination
     */
    getStock(selectedAttributes) {
        const variant = this.findVariant(selectedAttributes);
        return variant ? variant.stock_quantity : 0;
    }
    
    /**
     * Get all possible combinations for an attribute given current selections
     */
    getPossibleCombinations(attributeId, selectedAttributes = {}) {
        const combinations = [];
        const availableValues = this.getAvailableValues(attributeId, selectedAttributes);
        
        availableValues.forEach(valueId => {
            const testCombination = { ...selectedAttributes, [attributeId]: valueId };
            const variant = this.findVariant(testCombination);
            
            if (variant && variant.is_available) {
                combinations.push({
                    valueId,
                    variant,
                    price: variant.price_cents,
                    stock: variant.stock_quantity
                });
            }
        });
        
        return combinations;
    }
    
    /**
     * Clear cache (useful when variant data changes)
     */
    clearCache() {
        this.cache.clear();
    }
    
    /**
     * Update variant matrix (useful for dynamic updates)
     */
    updateMatrix(newMatrix) {
        this.variantMatrix = newMatrix;
        this.clearCache();
        this.init();
    }
    
    /**
     * Get statistics about variant availability
     */
    getStats() {
        const totalVariants = this.variantMatrix.length;
        const availableVariants = this.variantMatrix.filter(v => v.is_available).length;
        const totalAttributes = new Set();
        
        this.variantMatrix.forEach(variant => {
            Object.keys(variant.combination).forEach(attrId => {
                totalAttributes.add(attrId);
            });
        });
        
        return {
            totalVariants,
            availableVariants,
            outOfStockVariants: totalVariants - availableVariants,
            totalAttributes: totalAttributes.size,
            cacheSize: this.cache.size
        };
    }
}

// Auto-initialize if variant data is available
document.addEventListener('DOMContentLoaded', () => {
    const variantDataElement = document.querySelector('[data-variant-combinations]');
    if (variantDataElement) {
        try {
            const variantData = JSON.parse(variantDataElement.textContent);
            window.variantMatcher = new VariantMatcher(variantData);
        } catch (error) {
            console.error('Failed to initialize variant matcher:', error);
        }
    }
});

// Export for manual initialization
window.VariantMatcher = VariantMatcher;
