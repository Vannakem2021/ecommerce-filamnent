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

    protected $listeners = ['refreshProductData'];

    public $product;
    public $title;
    public $quantity = 1;
    public $selectedVariant = null;
    public $selectedOptions = []; // Legacy support
    public $availableOptions = []; // Legacy support
    public $currentImage = null;
    public $isWishlisted = false;

    // Simple Color+Storage variant properties
    public $selectedColor = null;
    public $selectedStorage = null;
    public $availableColors = [];
    public $availableStorage = [];



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

        $this->initializeProductData();
    }

    /**
     * Initialize or refresh product data
     */
    public function initializeProductData()
    {
        // Initialize simplified variant system
        $this->selectedVariant = null;
        $this->selectedOptions = [];
        $this->selectedColor = null;
        $this->selectedStorage = null;

        // Simple Color+Storage variant system
        if ($this->product->has_variants) {
            // Refresh variant data to ensure we have the latest
            $this->product->load('variants');

            // Get available options using simple methods
            $this->availableColors = $this->product->getAvailableColors();
            $this->availableStorage = $this->product->getAvailableStorage();

            // Legacy support
            $this->availableOptions = [
                'Color' => $this->availableColors,
                'Storage' => $this->availableStorage
            ];

            // Auto-select default variant if available
            $defaultVariant = $this->product->variants()->where('is_default', true)->first();
            if ($defaultVariant) {
                $this->selectedVariant = $defaultVariant;
                $this->selectedColor = $defaultVariant->getColor();
                $this->selectedStorage = $defaultVariant->getStorage();

                // Legacy support
                $this->selectedOptions = [
                    'Color' => $this->selectedColor,
                    'Storage' => $this->selectedStorage
                ];
            }
        } else {
            // Clear variant-related data for simple products
            $this->availableColors = [];
            $this->availableStorage = [];
            $this->availableOptions = [];
        }

        // Set initial image (use product images, not variant images)
        $this->currentImage = $this->getCurrentImages()[0] ?? null;
    }

    /**
     * Refresh product data (useful when product is updated externally)
     */
    public function refreshProductData()
    {
        $this->product->refresh();
        $this->initializeProductData();

        // Emit event to update frontend
        $this->dispatch('productDataRefreshed', [
            'hasVariants' => $this->product->has_variants,
            'availableColors' => $this->availableColors,
            'availableStorage' => $this->availableStorage,
            'currentPrice' => $this->getCurrentPrice(),
            'priceRange' => $this->getCurrentPriceRange(),
        ]);
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

        // Emit event for frontend price updates
        $this->dispatch('priceUpdated', [
            'selectedOptions' => $this->selectedOptions,
            'currentPrice' => $this->getCurrentPrice(),
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

        // Find variant that matches selected options with key normalization
        $this->selectedVariant = $this->findVariantByNormalizedOptions($this->selectedOptions);

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
     * Find variant by options with key normalization to handle data inconsistencies
     */
    private function findVariantByNormalizedOptions($selectedOptions)
    {
        $variants = $this->product->variants()->get();

        foreach ($variants as $variant) {
            $variantOptions = $variant->options ?? [];

            // Normalize both sets of options for comparison
            $normalizedVariantOptions = [];
            foreach ($variantOptions as $key => $value) {
                $normalizedVariantOptions[trim($key)] = $value;
            }

            $normalizedSelectedOptions = [];
            foreach ($selectedOptions as $key => $value) {
                $normalizedSelectedOptions[trim($key)] = $value;
            }

            // Check if all selected options match the variant options
            $matches = true;
            foreach ($normalizedSelectedOptions as $key => $value) {
                if (!isset($normalizedVariantOptions[$key]) || $normalizedVariantOptions[$key] !== $value) {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                return $variant;
            }
        }

        return null;
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
        $this->selectedColor = null;
        $this->selectedStorage = null;

        // Reset to default image
        $this->currentImage = $this->getCurrentImages()[0] ?? null;

        // Emit event for frontend updates
        $this->dispatch('optionsCleared');
        $this->dispatch('priceUpdated', [
            'selectedOptions' => [],
            'currentPrice' => $this->getCurrentPrice(),
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



    // Removed calculateDynamicPrice - using simple getCurrentPrice() method instead

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

    // Removed complex getCurrentPrice - using simple version below

    public function getCurrentPriceRange()
    {
        if ($this->selectedVariant) {
            return null; // No range needed when specific variant is selected
        }

        if ($this->product->has_variants) {
            // Refresh the product to ensure we have the latest variant data
            $this->product->refresh();
            $this->product->load('variants');

            $variants = $this->product->variants()->where('is_active', true)->get();

            if ($variants->isNotEmpty()) {
                $prices = $variants->map(function ($variant) {
                    return $variant->getFinalPrice();
                });

                $minPrice = $prices->min();
                $maxPrice = $prices->max();

                if ($minPrice != $maxPrice) {
                    return [
                        'min' => $minPrice,
                        'max' => $maxPrice,
                        'formatted' => '$' . number_format($minPrice, 2) . ' - $' . number_format($maxPrice, 2)
                    ];
                }
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
        // Simple approach: Use selected variant compare price if available
        if ($this->selectedVariant && isset($this->selectedVariant->compare_price_cents)) {
            return $this->selectedVariant->compare_price_cents / 100;
        }

        // Fallback to product compare price
        if (isset($this->product->compare_price_cents) && $this->product->compare_price_cents) {
            return $this->product->compare_price_cents / 100;
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
        return \App\Services\InventoryService::hasStock($this->product);
    }

    public function getStockQuantity()
    {
        if ($this->selectedVariant) {
            return \App\Services\InventoryService::getVariantStock($this->selectedVariant);
        }

        return \App\Services\InventoryService::getTotalStock($this->product);
    }

    public function getStockStatus()
    {
        if ($this->selectedVariant) {
            return \App\Services\InventoryService::getVariantStockStatus($this->selectedVariant);
        }

        return \App\Services\InventoryService::getStockStatus($this->product);
    }

    public function getDisplayStock()
    {
        return \App\Services\InventoryService::getDisplayStock($this->product, $this->selectedVariant);
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

    /**
     * Buy Now functionality - adds to cart and redirects to checkout
     */
    public function buyNow()
    {
        if (!$this->isInStock()) {
            $this->alert('error', 'Product is out of stock!', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);
            return;
        }

        // For products with variants, ensure variant is selected
        if ($this->product->has_variants && !$this->selectedVariant) {
            $this->alert('error', 'Please select product options before buying!', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);
            return;
        }

        // Add to cart first
        if ($this->selectedVariant) {
            // Add variant to cart with simplified options
            $result = CartManagement::addItemToCartWithVariant(
                $this->product->id,
                $this->selectedVariant->id,
                $this->quantity,
                $this->selectedOptions
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

        // Update cart count in navbar
        $total_count = CartManagement::calculateTotalQuantity($result);
        $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

        // Redirect to checkout
        $this->redirect(route('checkout'), navigate: true);
    }



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
        $displayStock = $this->getDisplayStock();

        return view('livewire.product-detail-page', [
            'product' => $this->product,
            'currentImages' => $this->getCurrentImages(),
            'currentPrice' => $this->getCurrentPrice(),
            'currentComparePrice' => $this->getCurrentComparePrice(),
            'discountPercentage' => $this->getDiscountPercentage(),
            'inStock' => $this->isInStock(),
            'stockQuantity' => $this->getStockQuantity(),
            'displayStock' => $displayStock,
            'availableOptions' => $this->availableOptions, // Simplified options
            'selectedOptions' => $this->selectedOptions, // Current selections
        ])->layoutData(['title' => $this->title]);
    }

    // ===========================================
    // SIMPLE COLOR+STORAGE VARIANT METHODS
    // ===========================================

    /**
     * Handle color selection
     */
    public function updatedSelectedColor()
    {
        $this->findVariant();
        $this->emitPriceUpdate();
    }

    /**
     * Select color option (method for wire:click)
     */
    public function selectColor($color)
    {
        $this->selectedColor = $color;
        $this->findVariant();
        $this->emitPriceUpdate();
    }

    /**
     * Handle storage selection
     */
    public function updatedSelectedStorage()
    {
        $this->findVariant();
        $this->emitPriceUpdate();
    }

    /**
     * Select storage option (method for wire:click)
     */
    public function selectStorage($storage)
    {
        $this->selectedStorage = $storage;
        $this->findVariant();
        $this->emitPriceUpdate();
    }

    /**
     * Emit price update event for frontend
     */
    protected function emitPriceUpdate()
    {
        $this->dispatch('priceUpdated', [
            'currentPrice' => $this->getCurrentPrice(),
            'currentPriceFormatted' => $this->getCurrentPriceFormatted(),
            'currentComparePrice' => $this->getCurrentComparePrice(),
            'currentComparePriceFormatted' => $this->getCurrentComparePriceFormatted(),
            'discountPercentage' => $this->getDiscountPercentage(),
            'selectedVariant' => $this->selectedVariant ? [
                'id' => $this->selectedVariant->id,
                'sku' => $this->selectedVariant->sku,
                'stock' => $this->selectedVariant->stock_quantity,
                'price' => $this->selectedVariant->getFinalPrice(),
            ] : null
        ]);
    }

    /**
     * Find variant based on selected color and storage
     */
    private function findVariant()
    {
        if ($this->selectedColor && $this->selectedStorage) {
            $this->selectedVariant = $this->product->findVariant($this->selectedColor, $this->selectedStorage);

            // Update legacy properties for compatibility
            $this->selectedOptions = [
                'Color' => $this->selectedColor,
                'Storage' => $this->selectedStorage
            ];
        } else {
            $this->selectedVariant = null;
            $this->selectedOptions = [];
        }
    }

    /**
     * Get current price based on selected variant or partial selection
     */
    public function getCurrentPrice(): float
    {
        if ($this->selectedVariant) {
            return $this->selectedVariant->getFinalPrice();
        }

        // For products with variants - try to show specific price based on partial selection
        if ($this->product->has_variants) {
            // If storage is selected but no color, find any variant with that storage
            if ($this->selectedStorage && !$this->selectedColor && !empty($this->availableColors)) {
                foreach ($this->availableColors as $color) {
                    $variant = $this->product->findVariant($color, $this->selectedStorage);
                    if ($variant) {
                        return $variant->getFinalPrice();
                    }
                }
            }

            // If color is selected but no storage, find any variant with that color
            if ($this->selectedColor && !$this->selectedStorage && !empty($this->availableStorage)) {
                foreach ($this->availableStorage as $storage) {
                    $variant = $this->product->findVariant($this->selectedColor, $storage);
                    if ($variant) {
                        return $variant->getFinalPrice();
                    }
                }
            }

            $variants = $this->product->variants()->where('is_active', true)->get();

            if ($variants->isNotEmpty()) {
                // Show the minimum price from available variants
                $minPrice = $variants->min(function ($variant) {
                    return $variant->getFinalPrice();
                });
                return $minPrice;
            }

            // Fallback to base price if no active variants
            return ($this->product->price_cents ?? 0) / 100;
        }

        // For simple products, use product price
        return ($this->product->price_cents ?? 0) / 100;
    }

    /**
     * Get current price formatted
     */
    public function getCurrentPriceFormatted(): string
    {
        return '$' . number_format($this->getCurrentPrice(), 2);
    }

    /**
     * Get current price range formatted for display
     */
    public function getCurrentPriceRangeFormatted(): ?string
    {
        if (!$this->product->has_variants) {
            return null;
        }

        $priceRange = $this->getCurrentPriceRange();
        if (!$priceRange) {
            return null;
        }

        if ($priceRange['min'] === $priceRange['max']) {
            return '$' . number_format($priceRange['min'], 2);
        }

        return '$' . number_format($priceRange['min'], 2) . ' - $' . number_format($priceRange['max'], 2);
    }

    /**
     * Get compare price formatted
     */
    public function getCurrentComparePriceFormatted(): ?string
    {
        $comparePrice = $this->getCurrentComparePrice();
        return $comparePrice ? '$' . number_format($comparePrice, 2) : null;
    }
}
