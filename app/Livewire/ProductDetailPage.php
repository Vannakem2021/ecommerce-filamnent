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
    public $selectedAttributes = [];
    public $currentImage = null;
    public $isWishlisted = false;
    public $reviews = [];
    public $averageRating = 4.2;
    public $totalReviews = 128;

    public function mount($slug)
    {
        $this->product = Product::with([
            'category',
            'brand',
            'variants.attributeValues.attribute'
        ])->where('slug', $slug)->firstOrFail();

        $this->title = $this->product->name . " - ByteWebster";

        // Set default variant if product has variants
        if ($this->product->has_variants && $this->product->variants->isNotEmpty()) {
            $this->selectedVariant = $this->product->defaultVariant ?? $this->product->variants->first();
            $this->initializeSelectedAttributes();
        }

        // Set initial image
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
    }

    public function findMatchingVariant()
    {
        if (empty($this->selectedAttributes)) {
            return;
        }

        // Find variant that matches all selected attributes
        $variant = $this->product->variants()
            ->whereHas('attributeValues', function ($query) {
                $query->whereIn('product_attribute_value_id', array_values($this->selectedAttributes));
            }, '=', count($this->selectedAttributes))
            ->first();

        if ($variant) {
            $this->selectedVariant = $variant;
            $this->currentImage = $this->getCurrentImages()[0] ?? null;
        }
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

        return $this->product->effective_price;
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

        return $this->product->has_stock;
    }

    public function getStockQuantity()
    {
        if ($this->selectedVariant) {
            return $this->selectedVariant->stock_quantity;
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

        $productId = $this->selectedVariant ? $this->selectedVariant->id : $this->product->id;
        $total_count = CartManagement::addItemToCartWithQuantity($productId, $this->quantity, $this->selectedVariant ? 'variant' : 'product');

        $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

        $this->alert('success', 'Product added to cart successfully!', [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
        ]);
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
        ])->layoutData(['title' => $this->title]);
    }
}
