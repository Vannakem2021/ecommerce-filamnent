<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use App\Models\Product;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Shopping Cart - ByteWebster')]

class CartPage extends Component
{
    use LivewireAlert;

    public $cart_items = [];
    public $grand_total;
    public $promo_code = '';
    public $discount_amount = 0;
    public $tax_rate = 0.08; // 8% tax
    public $shipping_threshold = 50; // Free shipping over $50

    public function mount()
    {
        $this->cart_items = CartManagement::getCartItemsFromCookie();
        $this->grand_total = CartManagement::calculateGrandTotal($this->cart_items);
    }

    public function removeItem($item_key)
    {
        $this->cart_items = CartManagement::removeCartItems($item_key);
        $this->grand_total = CartManagement::calculateGrandTotal($this->cart_items);

        $this->dispatch('update-cart-count', total_count: CartManagement::calculateTotalQuantity($this->cart_items))->to(Navbar::class);

        $this->alert('success', 'Item removed from cart!', [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function increaseQuantity($item_key)
    {
        $result = CartManagement::incrementQuantityToCartItem($item_key);

        if (isset($result['error'])) {
            $this->alert('error', $result['error'], [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);
            return;
        }

        $this->cart_items = $result;
        $this->grand_total = CartManagement::calculateGrandTotal($this->cart_items);

        $this->dispatch('update-cart-count', total_count: CartManagement::calculateTotalQuantity($this->cart_items))->to(Navbar::class);

        $this->alert('success', 'Quantity updated!', [
            'position' => 'top-end',
            'timer' => 2000,
            'toast' => true,
        ]);
    }

    public function decreaseQuantity($item_key)
    {
        $this->cart_items = CartManagement::decrementQuantityToCartItem($item_key);
        $this->grand_total = CartManagement::calculateGrandTotal($this->cart_items);

        $this->dispatch('update-cart-count', total_count: CartManagement::calculateTotalQuantity($this->cart_items))->to(Navbar::class);

        $this->alert('success', 'Quantity updated!', [
            'position' => 'top-end',
            'timer' => 2000,
            'toast' => true,
        ]);
    }

    public function applyPromoCode()
    {
        $promo_code = strtoupper(trim($this->promo_code));

        if ($promo_code === 'WEEKEND20') {
            $this->discount_amount = $this->grand_total * 0.20; // 20% discount
            $this->promo_code = '';

            $this->alert('success', 'Promo code applied! 20% discount', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        } else {
            $this->alert('error', 'Invalid promo code', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }

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

        $this->cart_items = $result;
        $this->grand_total = CartManagement::calculateGrandTotal($this->cart_items);
        $total_count = CartManagement::calculateTotalQuantity($this->cart_items);

        $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

        $this->alert('success', 'Product added to cart!', [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    public function getSubtotal()
    {
        return $this->grand_total;
    }

    public function getTax()
    {
        return $this->grand_total * $this->tax_rate;
    }

    public function getShipping()
    {
        return $this->grand_total >= $this->shipping_threshold ? 0 : 9.99;
    }

    public function getFinalTotal()
    {
        return $this->grand_total + $this->getTax() + $this->getShipping() - $this->discount_amount;
    }

    public function getRecommendedProducts()
    {
        return Product::where('is_active', true)
            ->where('is_featured', true)
            ->take(4)
            ->get();
    }

    public function render()
    {
        $recommended_products = $this->getRecommendedProducts();

        return view('livewire.cart-page', [
            'recommended_products' => $recommended_products
        ]);
    }
}
