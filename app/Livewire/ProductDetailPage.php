<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use App\Models\Product;
use App\Models\ProductVariant;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ProductDetailPage extends Component
{
    use LivewireAlert;

    public $product;
    public $title;
    public $quantity = 1;
    public $selectedVariant = null;
    public $selectedAttributes = [];
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
        $this->product = Product::with([
            'category',
            'brand',
            'variants.attributeValues.attribute',
            'specificationsWithAttributes.specificationAttribute',
            'specificationsWithAttributes.specificationAttributeOption',
            'variants.specificationsWithAttributes.specificationAttribute',
            'variants.specificationsWithAttributes.specificationAttributeOption'
        ])->where('slug', $slug)->firstOrFail();

        $this->title = $this->product->name . " - ByteWebster";

        // Initialize with no variant selected for better UX
        // Users should progressively select attributes
        $this->selectedVariant = null;
        $this->selectedAttributes = [];

        // Set initial image (use product images, not variant images)
        $this->currentImage = $this->getCurrentImages()[0] ?? null;

        // Initialize mock reviews data (in real app, this would come from database)
        $this->initializeReviews();
    }

    public function initializeSelectedAttributes()
    {
        if ($this->selectedVariant) {
            foreach ($this->selectedVariant->attributeValues as $attributeValue) {
                $this->selectedAttributes[$attributeValue->attribute->id] = $attributeValue->id;
            }
        }
    }

    public function selectAttributeValue($attributeId, $valueId)
    {
        $this->selectedAttributes[$attributeId] = $valueId;
        $this->findMatchingVariant();

        // Dispatch event with availability data for frontend filtering
        $this->dispatch('attributeSelectionChanged', [
            'selectedAttributes' => $this->selectedAttributes,
            'availabilityData' => $this->getAttributeAvailabilityData()
        ]);
    }

    /**
     * Get availability data for all attributes based on current selections
     */
    public function getAttributeAvailabilityData()
    {
        $availabilityData = [];

        foreach ($this->productAttributes as $attribute) {
            $availableValues = $this->product->getAvailableAttributeValues(
                $attribute->id,
                $this->selectedAttributes
            );

            $availabilityData[$attribute->id] = [
                'available_values' => $availableValues->pluck('id')->toArray(),
                'total_values' => $attribute->activeValues->pluck('id')->toArray()
            ];
        }

        return $availabilityData;
    }

    /**
     * Get variant combinations matrix for frontend caching
     */
    public function getVariantCombinationsMatrix()
    {
        return $this->product->getVariantCombinationsMatrix();
    }

    public function findMatchingVariant()
    {
        if (empty($this->selectedAttributes)) {
            $this->selectedVariant = null;
            return;
        }

        // Get total number of required attributes for this product
        $totalRequiredAttributes = $this->productAttributes->where('is_required', true)->count();
        $totalAttributes = $this->productAttributes->count();

        // Use total attributes if no required attributes are specifically marked
        $requiredAttributeCount = $totalRequiredAttributes > 0 ? $totalRequiredAttributes : $totalAttributes;

        // Only try to find a complete match if we have all required attributes selected
        if (count($this->selectedAttributes) >= $requiredAttributeCount) {
            // Use optimized variant matching method
            $variant = $this->product->findVariantByAttributes($this->selectedAttributes);

            if ($variant) {
                $this->selectedVariant = $variant;
                $this->updateCurrentImageForVariant();

                // Dispatch event for any listeners (like specifications update)
                $this->dispatch('variantChanged', ['variantId' => $variant->id]);
            } else {
                // No matching variant found - this combination doesn't exist
                $this->selectedVariant = null;
            }
        } else {
            // Not all required attributes selected yet
            $this->selectedVariant = null;
        }
    }

    public function updateCurrentImageForVariant()
    {
        $newImages = $this->getCurrentImages();

        // If variant has its own images, switch to the first one
        if ($this->selectedVariant && $this->selectedVariant->images && !empty($this->selectedVariant->images)) {
            $this->currentImage = $newImages[0] ?? null;
        }
        // If no variant images, but current image is not in the new image set, switch to first available
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
        if ($this->selectedVariant && $this->selectedVariant->images) {
            return $this->selectedVariant->images;
        }

        return $this->product->images ?? [];
    }

    public function getCurrentPrice()
    {
        if ($this->selectedVariant) {
            return $this->selectedVariant->price;
        }

        // If no variant selected, return the lowest price for display
        if ($this->product->has_variants) {
            return $this->product->lowest_price;
        }

        return $this->product->effective_price;
    }

    public function getCurrentPriceRange()
    {
        if ($this->selectedVariant) {
            return null; // No range needed when specific variant is selected
        }

        if ($this->product->has_variants) {
            $priceRange = $this->product->price_range;
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
            return $this->selectedVariant->stock_status === 'in_stock' && $this->selectedVariant->stock_quantity > 0;
        }

        // If product has variants but no variants exist, or no variant is selected,
        // fall back to product's own stock status
        if ($this->product->has_variants && $this->product->variants->isEmpty()) {
            return $this->product->stock_status === 'in_stock' && $this->product->stock_quantity > 0;
        }

        return $this->product->has_stock;
    }

    public function getStockQuantity()
    {
        if ($this->selectedVariant) {
            return $this->selectedVariant->stock_quantity;
        }

        // If product has variants but no variants exist, fall back to product's own stock
        if ($this->product->has_variants && $this->product->variants->isEmpty()) {
            return $this->product->stock_quantity;
        }

        return $this->product->effective_stock_quantity;
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
            // Add variant to cart with attributes
            $result = CartManagement::addItemToCartWithVariant(
                $this->product->id,
                $this->selectedVariant->id,
                $this->quantity,
                $this->selectedAttributes
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
     * Get product attributes for variant selection (Livewire computed property)
     */
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
            'productAttributes' => $this->productAttributes,
            'reviews' => $this->reviews,
            'variantCombinationsMatrix' => $this->getVariantCombinationsMatrix(),
        ])->layoutData(['title' => $this->title]);
    }
}
