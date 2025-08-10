<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use App\Models\Product;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ProductDetailPage extends Component
{
    use LivewireAlert;

    public $product;
    public $title;
    public $quantity = 1;
    public $selectedVariant = null;
    public $selectedOptions = []; // Changed from selectedAttributes to selectedOptions (JSON-based)
    public $availableOptions = []; // Cache available options
    public $currentImage = null;
    public $isWishlisted = false;
    public $reviews = [];
    public $averageRating = 4.2;
    public $totalReviews = 128;

    // Dynamic pricing properties
    public $dynamicPrice = null;
    public $dynamicComparePrice = null;
    public $priceModifiers = [];
    public $showDynamicPricing = false;

    public function getReviewsProperty()
    {
        // Mock reviews data - in a real app, this would come from the database
        return [
            [
                'rating' => 5,
                'comment' => 'Excellent product! The quality is outstanding and it exceeded my expectations. Highly recommended for anyone looking for premium quality.',
                'author' => 'John D.',
                'date' => now()->subDays(5)->format('M d, Y')
            ],
            [
                'rating' => 4,
                'comment' => 'Very good product with great features. The design is sleek and modern. Only minor issue is the delivery took a bit longer than expected.',
                'author' => 'Sarah M.',
                'date' => now()->subDays(12)->format('M d, Y')
            ],
            [
                'rating' => 5,
                'comment' => 'Perfect! Exactly what I was looking for. The build quality is impressive and it works flawlessly. Will definitely buy again.',
                'author' => 'Mike R.',
                'date' => now()->subDays(18)->format('M d, Y')
            ],
            [
                'rating' => 4,
                'comment' => 'Good value for money. The product performs well and looks great. Customer service was also very helpful when I had questions.',
                'author' => 'Lisa K.',
                'date' => now()->subDays(25)->format('M d, Y')
            ]
        ];
    }

    public function mount($slug)
    {
        // Handle both slug string and Product model (for testing)
        if ($slug instanceof Product) {
            $this->product = $slug;
            // Ensure variants are loaded for testing
            $this->product->load(['category', 'brand', 'variants']);
        } else {
            $this->product = Product::with([
                'category',
                'brand',
                'variants' // Simplified - no need for complex relationships
            ])->where('slug', $slug)->firstOrFail();
        }

        $this->title = $this->product->name . " - ByteWebster";

        // Initialize simplified variant system
        $this->selectedVariant = null;
        $this->selectedOptions = [];

        // Get available options from simplified system
        if ($this->product->has_variants) {
            $this->availableOptions = $this->product->getAvailableOptions();
            // Simplified: no need for complex price modifiers
            $this->showDynamicPricing = true; // Always show dynamic pricing for variants
        }

        // Set initial image (use product images, not variant images)
        $this->currentImage = $this->getCurrentImages()[0] ?? null;

        // Initialize mock reviews data (in real app, this would come from database)
        $this->initializeReviews();
    }

    // ========================================
    // SIMPLIFIED VARIANT SELECTION METHODS
    // ========================================

    /**
     * Select an option value (simplified approach)
     */
    public function selectOption($optionName, $optionValue)
    {
        $this->selectedOptions[$optionName] = $optionValue;
        $this->findMatchingVariant();
        $this->calculateDynamicPrice();

        // Emit event for frontend price updates
        $this->dispatch('priceUpdated', [
            'selectedOptions' => $this->selectedOptions,
            'dynamicPrice' => $this->dynamicPrice,
            'dynamicComparePrice' => $this->dynamicComparePrice,
            'selectedVariant' => $this->selectedVariant ? $this->selectedVariant->toArray() : null
        ]);
    }

    /**
     * Find matching variant using JSON options (simplified approach)
     */
    public function findMatchingVariant()
    {
        if (empty($this->selectedOptions)) {
            $this->selectedVariant = null;
            return;
        }

        // Find variant that matches selected options
        $this->selectedVariant = $this->product->findVariantByOptions($this->selectedOptions);

        if ($this->selectedVariant) {
            $this->updateCurrentImageForVariant();

            // Emit event for frontend updates
            $this->dispatch('variantChanged', [
                'variantId' => $this->selectedVariant->id,
                'sku' => $this->selectedVariant->sku,
                'stock' => $this->selectedVariant->stock_quantity,
                'options' => $this->selectedVariant->options
            ]);
        }
    }

    /**
     * Get available options with additional metadata for better UX
     * Handles paired attributes correctly (e.g., Storage + RAM combinations)
     */
    public function getAvailableOptionsWithMetadata()
    {
        $optionsWithMeta = [];

        foreach ($this->availableOptions as $optionName => $optionValues) {
            $optionsWithMeta[$optionName] = [];

            foreach ($optionValues as $optionValue) {
                // Find variants that have this option value
                $variantsWithOption = $this->product->variants()
                    ->get()
                    ->filter(function ($variant) use ($optionName, $optionValue) {
                        return isset($variant->options[$optionName]) &&
                               $variant->options[$optionName] === $optionValue;
                    });

                // Check if this option is compatible with current selections
                $isCompatible = $this->isOptionCompatibleWithSelections($optionName, $optionValue);

                // Calculate price range for this option
                $prices = $variantsWithOption->map(function ($variant) {
                    return $variant->final_price_in_dollars;
                });

                $optionsWithMeta[$optionName][$optionValue] = [
                    'value' => $optionValue,
                    'available' => $variantsWithOption->sum('stock_quantity') > 0,
                    'compatible' => $isCompatible,
                    'min_price' => $prices->min(),
                    'max_price' => $prices->max(),
                    'variant_count' => $variantsWithOption->count()
                ];
            }
        }

        return $optionsWithMeta;
    }

    /**
     * Check if an option value is compatible with current selections
     * This prevents invalid combinations like RAM 8GB + Storage 256GB
     */
    public function isOptionCompatibleWithSelections($optionName, $optionValue)
    {
        // If no selections made yet, all options are compatible
        if (empty($this->selectedOptions)) {
            return true;
        }

        // Create a test selection with this new option
        $testOptions = array_merge($this->selectedOptions, [$optionName => $optionValue]);

        // Check if any variant exists with these exact options
        $compatibleVariant = $this->product->variants()
            ->get()
            ->first(function ($variant) use ($testOptions) {
                if (!$variant->options) return false;

                // Check if variant contains all test options
                foreach ($testOptions as $key => $value) {
                    if (!isset($variant->options[$key]) || $variant->options[$key] !== $value) {
                        return false;
                    }
                }
                return true;
            });

        return $compatibleVariant !== null;
    }

    /**
     * Clear all selected options
     */
    public function clearOptions()
    {
        $this->selectedOptions = [];
        $this->selectedVariant = null;
        $this->dynamicPrice = null;
        $this->dynamicComparePrice = null;

        // Reset to default image
        $this->currentImage = $this->getCurrentImages()[0] ?? null;

        // Emit event for frontend updates
        $this->dispatch('optionsCleared');
        $this->dispatch('priceUpdated', [
            'selectedOptions' => [],
            'dynamicPrice' => null,
            'selectedVariant' => null
        ]);
    }

    /**
     * Get missing required options
     */
    public function getMissingOptions()
    {
        if (!$this->product->has_variants) {
            return [];
        }

        $requiredOptions = array_keys($this->availableOptions);
        $selectedOptions = array_keys($this->selectedOptions);

        return array_diff($requiredOptions, $selectedOptions);
    }



    /**
     * Calculate dynamic price based on selected options - SIMPLIFIED
     */
    public function calculateDynamicPrice()
    {
        if (empty($this->selectedOptions)) {
            $this->dynamicPrice = null;
            $this->dynamicComparePrice = null;
            return;
        }

        // Use simplified pricing logic
        $priceData = $this->product->getPriceForVariant(null, $this->selectedOptions);
        $this->dynamicPrice = $priceData;

        // For compare price, use the variant's compare price if available
        if ($priceData['variant'] && $priceData['variant']->compare_price_cents) {
            $this->dynamicComparePrice = [
                'price_cents' => $priceData['variant']->compare_price_cents,
                'price' => $priceData['variant']->compare_price,
                'compare_price' => $priceData['variant']->compare_price // Add this key for compatibility
            ];
        } else {
            $this->dynamicComparePrice = null;
        }
    }

    /**
     * Check if all required options are selected
     */
    public function hasAllRequiredOptions()
    {
        if (!$this->product->has_variants) {
            return true;
        }

        // For simplified system, we require all available option types to be selected
        $requiredOptionCount = count($this->availableOptions);
        return count($this->selectedOptions) >= $requiredOptionCount;
    }

    /**
     * Get available values for a specific option
     */
    public function getAvailableValuesForOption($optionName)
    {
        return $this->availableOptions[$optionName] ?? [];
    }

    public function updateCurrentImageForVariant()
    {
        $newImages = $this->getCurrentImages();

        // If variant has its own image, switch to it (simplified system uses image_url)
        if ($this->selectedVariant && $this->selectedVariant->image_url) {
            $this->currentImage = $this->selectedVariant->image_url;
        }
        // If no variant image, but current image is not in the new image set, switch to first available
        elseif (!empty($newImages) && !in_array($this->currentImage, $newImages)) {
            $this->currentImage = $newImages[0];
        }
        // If no images at all, set to null
        elseif (empty($newImages)) {
            $this->currentImage = null;
        }

        // Dispatch browser event to update the Alpine.js component
        $this->dispatch('variant-images-updated', [
            'images' => $newImages,
            'currentImage' => $this->currentImage ? asset('storage/' . $this->currentImage) : null
        ]);
    }

    public function getCurrentImages()
    {
        // For simplified system, use variant image_url or fall back to product images
        if ($this->selectedVariant && $this->selectedVariant->image_url) {
            return [$this->selectedVariant->image_url];
        }

        return $this->product->images ?? [];
    }

    public function getCurrentPrice()
    {
        // Priority 1: Use dynamic price if available (real-time calculation)
        if ($this->dynamicPrice && !empty($this->selectedOptions)) {
            return $this->dynamicPrice['price'];
        }

        // Priority 2: Use selected variant final price (simplified pricing)
        if ($this->selectedVariant) {
            return $this->selectedVariant->final_price_in_dollars;
        }

        // Priority 3: If no variant selected, return the lowest price for display
        if ($this->product->has_variants) {
            $cheapestVariant = $this->product->getCheapestVariant();
            return $cheapestVariant ? $cheapestVariant->final_price_in_dollars : $this->product->price;
        }

        return $this->product->price;
    }

    public function getCurrentPriceRange()
    {
        if ($this->selectedVariant) {
            return null; // No range needed when specific variant is selected
        }

        if ($this->product->has_variants) {
            $priceRange = $this->product->getPriceRange();
            if ($priceRange && $priceRange['min'] != $priceRange['max']) {
                return $priceRange;
            }
        }

        return null;
    }

    /**
     * Computed property for current price range (Livewire accessor)
     */
    public function getCurrentPriceRangeProperty()
    {
        return $this->getCurrentPriceRange();
    }

    /**
     * Computed property for current price (Livewire accessor)
     */
    public function getCurrentPriceProperty()
    {
        return $this->getCurrentPrice();
    }

    /**
     * Computed property for current compare price (Livewire accessor)
     */
    public function getCurrentComparePriceProperty()
    {
        return $this->getCurrentComparePrice();
    }

    /**
     * Computed property for discount percentage (Livewire accessor)
     */
    public function getDiscountPercentageProperty()
    {
        return $this->getDiscountPercentage();
    }

    public function getCurrentComparePrice()
    {
        // Priority 1: Use dynamic compare price if available
        if ($this->dynamicComparePrice && !empty($this->selectedOptions)) {
            return $this->dynamicComparePrice['compare_price'] ?? $this->dynamicComparePrice['price'] ?? null;
        }

        // Priority 2: Use selected variant compare price
        if ($this->selectedVariant && $this->selectedVariant->compare_price_cents) {
            return $this->selectedVariant->compare_price;
        }

        // Priority 3: Use product compare price
        if ($this->product->compare_price_cents) {
            return $this->product->compare_price;
        }

        return null;
    }

    public function getDiscountPercentage()
    {
        $currentPrice = $this->getCurrentPrice();
        $comparePrice = $this->getCurrentComparePrice();

        if ($comparePrice && $currentPrice && $comparePrice > $currentPrice) {
            return round((($comparePrice - $currentPrice) / $comparePrice) * 100);
        }

        return null;
    }

    public function isInStock()
    {
        if ($this->selectedVariant) {
            return $this->selectedVariant->isInStock(); // Use simplified method
        }

        // If no variant selected but product has variants, check if any variant is in stock
        if ($this->product->has_variants) {
            return $this->product->hasStock(); // Use simplified method
        }

        return $this->product->stock_quantity > 0;
    }

    public function getStockQuantity()
    {
        if ($this->selectedVariant) {
            return $this->selectedVariant->stock_quantity;
        }

        if ($this->product->has_variants) {
            return $this->product->getTotalStock(); // Use simplified method
        }

        return $this->product->stock_quantity;
    }

    public function increaseQty()
    {
        $maxQty = $this->getStockQuantity();
        if ($this->quantity < $maxQty) {
            $this->quantity++;
        }
    }

    public function decreaseQty()
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function setCurrentImage($image)
    {
        $this->currentImage = $image;
    }

    public function toggleWishlist()
    {
        $this->isWishlisted = !$this->isWishlisted;

        $message = $this->isWishlisted ? 'Added to wishlist!' : 'Removed from wishlist!';
        $this->alert('success', $message, [
            'position' => 'top-end',
            'timer' => 2000,
            'toast' => true,
        ]);
    }

    public function initializeReviews()
    {
        // Mock reviews data - in real app, this would come from database
        $this->reviews = [
            ['rating' => 5, 'comment' => 'Excellent quality!'],
            ['rating' => 4, 'comment' => 'Great product, fast delivery'],
            ['rating' => 5, 'comment' => 'Highly recommended'],
        ];
    }

    public function addToCart()
    {
        if (!$this->isInStock()) {
            $this->alert('error', 'Product is out of stock!', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);
            return;
        }

        if ($this->selectedVariant) {
            // Add variant to cart with simplified options
            $result = CartManagement::addItemToCartWithVariant(
                $this->product->id,
                $this->selectedVariant->id,
                $this->quantity,
                $this->selectedOptions // Use simplified options instead of attributes
            );
        } else {
            // Add product to cart
            $result = CartManagement::addItemToCartWithQuantity(
                $this->product->id,
                $this->quantity,
                'product'
            );
        }

        // Check if there was an error (inventory validation failed)
        if (is_array($result) && isset($result['error'])) {
            $this->alert('error', $result['error'], [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);
            return;
        }

        $total_count = CartManagement::calculateTotalQuantity($result);
        $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

        $this->alert('success', 'Product added to cart successfully!', [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    // ========================================
    // LEGACY COMPLEX ATTRIBUTE SYSTEM (DEPRECATED)
    // ========================================
    // This method is no longer used in the simplified variant system
    // Keeping for reference during transition period

    /*
    public function getProductAttributesProperty()
    {
        if (!$this->product->has_variants) {
            return collect();
        }

        // First try to get from variant_attributes field
        $attributes = $this->product->getProductAttributesCollection();

        // If that's empty, get from actual variants (fallback)
        if ($attributes->isEmpty() && $this->product->variants->isNotEmpty()) {
            $attributeIds = $this->product->variants()
                ->with('attributeValues.attribute')
                ->get()
                ->pluck('attributeValues')
                ->flatten()
                ->pluck('attribute.id')
                ->unique()
                ->values();

            if ($attributeIds->isNotEmpty()) {
                $attributes = \App\Models\ProductAttribute::whereIn('id', $attributeIds)
                    ->with('activeValues')
                    ->ordered()
                    ->get();
            }
        }

        return $attributes;
    }
    */

    /**
     * Check if product can be added to cart
     */
    public function canAddToCart()
    {
        return $this->isInStock() && (!$this->product->has_variants || $this->selectedVariant);
    }

    /**
     * Computed property for canAddToCart (Livewire accessor)
     */
    public function getCanAddToCartProperty()
    {
        return $this->canAddToCart();
    }

    /**
     * Debug method to check variant matching
     */
    public function debugVariantMatching()
    {
        $debug = [
            'product_id' => $this->product->id,
            'has_variants' => $this->product->has_variants,
            'selected_options' => $this->selectedOptions,
            'selected_variant_id' => $this->selectedVariant ? $this->selectedVariant->id : null,
            'available_options' => $this->availableOptions,
            'variants_count' => $this->product->variants()->count(),
            'in_stock' => $this->isInStock(),
            'can_add_to_cart' => $this->canAddToCart(),
        ];

        session()->flash('debug', $debug);
    }

    public function render()
    {
        return view('livewire.product-detail-page', [
            'product' => $this->product,
            'currentImages' => $this->getCurrentImages(),
            'currentPrice' => $this->getCurrentPrice(),
            'currentComparePrice' => $this->getCurrentComparePrice(),
            'discountPercentage' => $this->getDiscountPercentage(),
            'inStock' => $this->isInStock(),
            'stockQuantity' => $this->getStockQuantity(),
            'averageRating' => $this->averageRating,
            'totalReviews' => $this->totalReviews,
            'availableOptions' => $this->availableOptions, // Simplified options
            'selectedOptions' => $this->selectedOptions, // Current selections
            'reviews' => $this->reviews,
        ])->layoutData(['title' => $this->title]);
    }
}
