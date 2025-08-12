<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use App\Models\Product;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Home - ByteWebster')]

class HomePage extends Component
{
    use LivewireAlert;

    // Method for adding the product in the cart
    public function addToCart($product_id)
    {
        $result = CartManagement::addItemToCart($product_id);

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

        $this->alert('success', 'Product added to the cart successfully!', [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    // Method for quick view functionality
    public function quickView($product_id)
    {
        $product = Product::find($product_id);

        if ($product) {
            $this->alert('info', 'Quick view for ' . $product->name, [
                'position' => 'top-end',
                'timer' => 2000,
                'toast' => true,
            ]);
        }
    }

    // Method for wishlist functionality
    public function toggleWishlist($product_id)
    {
        $product = Product::find($product_id);

        if ($product) {
            $this->alert('success', 'Added ' . $product->name . ' to wishlist!', [
                'position' => 'top-end',
                'timer' => 2000,
                'toast' => true,
            ]);
        }
    }

    public function render()
    {
        // Get new arrivals - latest 8 products
        $newArrivals = Product::with([
                'category:id,name,slug',
                'brand:id,name,slug',
                'variants' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('is_default', 'desc');
                },
                'defaultVariant'
            ])
            ->where('is_active', 1)
            ->latest()
            ->take(8)
            ->get();

        // Get best sellers - featured products or fallback to on sale products or random selection
        $bestSellers = Product::with([
                'category:id,name,slug',
                'brand:id,name,slug',
                'variants' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('is_default', 'desc');
                },
                'defaultVariant'
            ])
            ->where('is_active', 1)
            ->where('is_featured', 1)
            ->inRandomOrder()
            ->take(8)
            ->get();

        // If no featured products, try products on sale
        if ($bestSellers->count() < 8) {
            $bestSellers = Product::with([
                    'category:id,name,slug',
                    'brand:id,name,slug',
                    'variants' => function ($query) {
                        $query->where('is_active', true)
                            ->orderBy('is_default', 'desc');
                    },
                    'defaultVariant'
                ])
                ->where('is_active', 1)
                ->where('on_sale', 1)
                ->inRandomOrder()
                ->take(8)
                ->get();
        }

        // If still not enough, get random active products
        if ($bestSellers->count() < 8) {
            $bestSellers = Product::with(['category', 'brand'])
                ->where('is_active', 1)
                ->inRandomOrder()
                ->take(8)
                ->get();
        }

        return view('livewire.home-page', compact('newArrivals', 'bestSellers'));
    }
}
