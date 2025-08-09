/**
 * Product Specifications Component
 * Handles dynamic specification updates when product variants are selected
 */
class ProductSpecifications {
    constructor(options = {}) {
        this.productId = options.productId;
        this.specificationContainer = options.specificationContainer || '#product-specifications';
        this.variantSelector = options.variantSelector || '[data-variant-selector]';
        this.loadingClass = options.loadingClass || 'opacity-50';
        
        this.init();
    }
    
    init() {
        this.bindEvents();
    }
    
    bindEvents() {
        // Listen for variant selection changes
        document.addEventListener('change', (e) => {
            if (e.target.matches(this.variantSelector)) {
                this.handleVariantChange();
            }
        });
        
        // Listen for custom variant change events
        document.addEventListener('variantChanged', (e) => {
            this.updateSpecifications(e.detail.variantId);
        });
    }
    
    handleVariantChange() {
        const selectedVariant = this.getSelectedVariant();
        if (selectedVariant) {
            this.updateSpecifications(selectedVariant.id);
        }
    }
    
    getSelectedVariant() {
        const selectors = document.querySelectorAll(this.variantSelector);
        const selectedValues = {};
        
        selectors.forEach(selector => {
            const attributeId = selector.dataset.attributeId;
            const value = selector.value;
            if (attributeId && value) {
                selectedValues[attributeId] = value;
            }
        });
        
        // Find matching variant based on selected attribute values
        // This would need to be implemented based on your variant data structure
        return this.findVariantByAttributes(selectedValues);
    }
    
    findVariantByAttributes(selectedValues) {
        // This method should find the variant that matches the selected attribute values
        // Implementation depends on how variant data is available on the frontend
        // For now, return null - this would need to be customized
        return null;
    }
    
    async updateSpecifications(variantId) {
        const container = document.querySelector(this.specificationContainer);
        if (!container) return;
        
        // Show loading state
        container.classList.add(this.loadingClass);
        
        try {
            const response = await fetch(`/api/products/${this.productId}/specifications?variant=${variantId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to fetch specifications');
            }
            
            const data = await response.json();
            this.renderSpecifications(data.specifications);
            
        } catch (error) {
            console.error('Error updating specifications:', error);
        } finally {
            // Remove loading state
            container.classList.remove(this.loadingClass);
        }
    }
    
    renderSpecifications(specifications) {
        const container = document.querySelector(this.specificationContainer);
        if (!container) return;
        
        // Find the specifications list container
        const specsList = container.querySelector('[data-specifications-list]');
        if (!specsList) return;
        
        // Clear existing specifications
        specsList.innerHTML = '';
        
        // Render new specifications
        specifications.forEach(spec => {
            const specElement = this.createSpecificationElement(spec);
            specsList.appendChild(specElement);
        });
    }
    
    createSpecificationElement(spec) {
        const div = document.createElement('div');
        div.className = 'px-6 py-4 flex justify-between items-start';
        
        div.innerHTML = `
            <div class="flex-1">
                <dt class="text-sm font-medium text-gray-900">
                    ${spec.name}
                    ${spec.unit && spec.data_type === 'number' ? `<span class="text-gray-500 font-normal">(${spec.unit})</span>` : ''}
                </dt>
                ${spec.description ? `<dd class="text-xs text-gray-500 mt-1">${spec.description}</dd>` : ''}
            </div>
            
            <div class="flex-shrink-0 ml-4">
                <dd class="text-sm text-gray-900 font-medium">
                    ${spec.formatted_value}
                </dd>
                
                ${spec.scope === 'variant' ? `
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                        Variant
                    </span>
                ` : ''}
            </div>
        `;
        
        return div;
    }
}

// Auto-initialize if product ID is available
document.addEventListener('DOMContentLoaded', () => {
    const productElement = document.querySelector('[data-product-id]');
    if (productElement) {
        const productId = productElement.dataset.productId;
        new ProductSpecifications({ productId });
    }
});

// Export for manual initialization
window.ProductSpecifications = ProductSpecifications;
