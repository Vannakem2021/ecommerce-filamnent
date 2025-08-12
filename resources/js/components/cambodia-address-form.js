/**
 * Cambodia Address Form Component for Livewire
 * Handles dynamic province/district/commune selection with postal code auto-fill
 */
class CambodiaAddressForm {
    constructor(options = {}) {
        this.options = {
            provinceSelect: '#city_province',
            districtSelect: '#district_khan',
            communeSelect: '#commune_sangkat',
            postalCodeInput: '#postal_code',
            loadingClass: 'opacity-50',
            ...options
        };

        this.provinces = [];
        this.districts = [];
        this.communes = [];
        this.selectedProvinceId = null;
        this.selectedDistrictId = null;

        this.init();
    }

    async init() {
        this.bindEvents();
        await this.loadProvinces();
    }

    bindEvents() {
        const provinceSelect = document.querySelector(this.options.provinceSelect);
        const districtSelect = document.querySelector(this.options.districtSelect);
        const communeSelect = document.querySelector(this.options.communeSelect);

        if (provinceSelect) {
            provinceSelect.addEventListener('change', (e) => this.onProvinceChange(e));
        }

        if (districtSelect) {
            districtSelect.addEventListener('change', (e) => this.onDistrictChange(e));
        }

        if (communeSelect) {
            communeSelect.addEventListener('change', (e) => this.onCommuneChange(e));
        }
    }

    async loadProvinces() {
        console.log('Loading provinces...');
        try {
            const response = await fetch('/api/addresses/provinces');
            console.log('Response status:', response.status);
            if (response.ok) {
                this.provinces = await response.json();
                console.log('Provinces loaded:', this.provinces.length);
                this.populateProvinceSelect();
            } else {
                console.error('Failed to load provinces. Status:', response.status);
                const errorText = await response.text();
                console.error('Error response:', errorText);

                // Show user-friendly error
                this.showError('Failed to load provinces. Please refresh the page.');
            }
        } catch (error) {
            console.error('Failed to load provinces:', error);
            this.showError('Network error while loading provinces. Please check your connection.');
        }
    }

    showError(message) {
        // Create a simple error message
        const provinceSelect = document.querySelector(this.options.provinceSelect);
        if (provinceSelect) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'text-red-500 text-sm mt-1';
            errorDiv.textContent = message;

            // Remove existing error messages
            const existingError = provinceSelect.parentNode.querySelector('.text-red-500');
            if (existingError) {
                existingError.remove();
            }

            provinceSelect.parentNode.appendChild(errorDiv);
        }
    }

    populateProvinceSelect() {
        const select = document.querySelector(this.options.provinceSelect);
        if (!select) return;

        // Clear existing options except the first one
        select.innerHTML = '<option value="">Select Province/City</option>';

        this.provinces.forEach(province => {
            const option = document.createElement('option');
            option.value = province.name_kh;
            option.textContent = `${province.name_en} (${province.name_kh})`;
            option.dataset.provinceId = province.id;
            select.appendChild(option);
        });

        // Trigger Livewire to detect the change
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    async onProvinceChange(event) {
        const provinceName = event.target.value;
        const provinceId = event.target.selectedOptions[0]?.dataset?.provinceId;

        this.selectedProvinceId = provinceId ? parseInt(provinceId) : null;

        // Reset dependent selects
        this.resetDistrictSelect();
        this.resetCommuneSelect();
        this.resetPostalCode();

        if (!this.selectedProvinceId) return;

        await this.loadDistricts(this.selectedProvinceId);
    }

    async loadDistricts(provinceId) {
        const districtSelect = document.querySelector(this.options.districtSelect);
        if (!districtSelect) return;

        this.setLoading(districtSelect, true);

        try {
            const response = await fetch(`/api/addresses/districts?province_id=${provinceId}`);
            if (response.ok) {
                this.districts = await response.json();
                this.populateDistrictSelect();
            }
        } catch (error) {
            console.error('Failed to load districts:', error);
        } finally {
            this.setLoading(districtSelect, false);
        }
    }

    populateDistrictSelect() {
        const select = document.querySelector(this.options.districtSelect);
        if (!select) return;

        select.innerHTML = '<option value="">Select District</option>';
        select.disabled = false;

        this.districts.forEach(district => {
            const option = document.createElement('option');
            option.value = district.name_kh;
            option.textContent = `${district.name_en} (${district.name_kh})`;
            option.dataset.districtId = district.id;
            select.appendChild(option);
        });

        // Trigger Livewire to detect the change
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    async onDistrictChange(event) {
        const districtName = event.target.value;
        const districtId = event.target.selectedOptions[0]?.dataset?.districtId;

        this.selectedDistrictId = districtId ? parseInt(districtId) : null;

        // Reset dependent selects
        this.resetCommuneSelect();
        this.resetPostalCode();

        if (!this.selectedDistrictId) return;

        await this.loadCommunes(this.selectedDistrictId);
    }

    async loadCommunes(districtId) {
        const communeSelect = document.querySelector(this.options.communeSelect);
        if (!communeSelect) return;

        this.setLoading(communeSelect, true);

        try {
            const response = await fetch(`/api/addresses/communes?district_id=${districtId}`);
            if (response.ok) {
                this.communes = await response.json();
                this.populateCommuneSelect();
            }
        } catch (error) {
            console.error('Failed to load communes:', error);
        } finally {
            this.setLoading(communeSelect, false);
        }
    }

    populateCommuneSelect() {
        const select = document.querySelector(this.options.communeSelect);
        if (!select) return;

        select.innerHTML = '<option value="">Select Commune</option>';
        select.disabled = false;

        this.communes.forEach(commune => {
            const option = document.createElement('option');
            option.value = commune.name_kh;
            option.textContent = `${commune.name_en} (${commune.name_kh})`;
            option.dataset.postalCode = commune.postal_code;
            select.appendChild(option);
        });

        // Trigger Livewire to detect the change
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    onCommuneChange(event) {
        const communeName = event.target.value;
        const postalCode = event.target.selectedOptions[0]?.dataset?.postalCode;

        if (!communeName) {
            this.resetPostalCode();
            return;
        }

        if (postalCode) {
            this.setPostalCode(postalCode);
        }
    }

    setPostalCode(postalCode) {
        const input = document.querySelector(this.options.postalCodeInput);
        if (input) {
            input.value = postalCode;

            // Trigger Livewire update
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    resetDistrictSelect() {
        const select = document.querySelector(this.options.districtSelect);
        if (select) {
            select.innerHTML = '<option value="">Select District</option>';
            select.disabled = true;
            // Trigger Livewire update
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        this.districts = [];
        this.selectedDistrictId = null;
    }

    resetCommuneSelect() {
        const select = document.querySelector(this.options.communeSelect);
        if (select) {
            select.innerHTML = '<option value="">Select Commune</option>';
            select.disabled = true;
            // Trigger Livewire update
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        this.communes = [];
    }

    resetPostalCode() {
        const input = document.querySelector(this.options.postalCodeInput);
        if (input) {
            input.value = '';
            // Trigger Livewire update
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    setLoading(element, loading) {
        if (loading) {
            element.classList.add(this.options.loadingClass);
            element.disabled = true;
        } else {
            element.classList.remove(this.options.loadingClass);
            element.disabled = false;
        }
    }
}

// Initialize function
function initializeCambodiaAddressForm() {
    console.log('🔍 Checking for address forms...');
    const provinceSelect = document.querySelector('#city_province');
    const districtSelect = document.querySelector('#district_khan');
    const communeSelect = document.querySelector('#commune_sangkat');
    const postalCodeInput = document.querySelector('#postal_code');

    console.log('📍 Elements found:', {
        province: !!provinceSelect,
        district: !!districtSelect,
        commune: !!communeSelect,
        postalCode: !!postalCodeInput
    });

    if (provinceSelect) {
        console.log('🏷️ Province select attributes:', {
            id: provinceSelect.id,
            wireModel: provinceSelect.getAttribute('wire:model'),
            disabled: provinceSelect.disabled,
            optionsCount: provinceSelect.options.length
        });
    }

    if (provinceSelect && !window.cambodiaAddressFormInitialized) {
        console.log('🚀 Initializing Cambodia Address Form...');
        try {
            window.cambodiaAddressForm = new CambodiaAddressForm();
            window.cambodiaAddressFormInitialized = true;
            console.log('✅ Cambodia Address Form initialized successfully');
        } catch (error) {
            console.error('❌ Failed to initialize Cambodia Address Form:', error);
        }
    } else if (provinceSelect && window.cambodiaAddressForm) {
        console.log('🔄 Re-initializing Cambodia Address Form...');
        try {
            window.cambodiaAddressForm = new CambodiaAddressForm();
            console.log('✅ Cambodia Address Form re-initialized successfully');
        } catch (error) {
            console.error('❌ Failed to re-initialize Cambodia Address Form:', error);
        }
    } else if (!provinceSelect) {
        console.log('⚠️ Province select not found - not on checkout page?');
    } else {
        console.log('ℹ️ Cambodia Address Form already initialized');
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing address forms...');
    initializeCambodiaAddressForm();
});

// Re-initialize after Livewire updates (multiple event types for compatibility)
document.addEventListener('livewire:navigated', function() {
    console.log('Livewire navigated, re-initializing address forms...');
    window.cambodiaAddressFormInitialized = false; // Reset flag
    setTimeout(initializeCambodiaAddressForm, 100); // Small delay to ensure DOM is ready
});

// Also listen for Livewire load event (for initial page load with Livewire)
document.addEventListener('livewire:load', function() {
    console.log('Livewire loaded, initializing address forms...');
    initializeCambodiaAddressForm();
});

// Listen for Livewire init event (Livewire v3)
document.addEventListener('livewire:init', function() {
    console.log('Livewire init, initializing address forms...');
    initializeCambodiaAddressForm();
});

// Also try with a longer delay for complex pages
setTimeout(() => {
    console.log('Delayed initialization check...');
    if (!window.cambodiaAddressFormInitialized) {
        initializeCambodiaAddressForm();
    }
}, 2000);

// Export for manual initialization
window.CambodiaAddressForm = CambodiaAddressForm;
