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
        if ($this->selectedVariant) {
            return $this->selectedVariant->price_cents / 100; // Convert cents to dollars
        }

        // If no variant selected, return the lowest price for display
        if ($this->product->has_variants) {
            $cheapestVariant = $this->product->getCheapestVariant();
            return $cheapestVariant ? $cheapestVariant->price_cents / 100 : $this->product->price_cents / 100;
        }

        return $this->product->price_cents / 100;
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
        if ($this->selectedVariant) {
            return $this->selectedVariant->compare_price;
        }

        return $this->product->compare_price;
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
