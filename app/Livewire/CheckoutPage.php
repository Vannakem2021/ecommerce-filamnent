<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Models\Product;
use App\Services\CartValidationService;
use App\Services\OrderService;
use Exception;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Checkout - ByteWebster')]

class CheckoutPage extends Component
{
    use LivewireAlert;

    // Cart data
    public $cart_items = [];
    public $grand_total = 0;

    // Contact Information
    public $email = '';
    public $phone = '';
    public $newsletter_signup = true;

    // Shipping Address
    public $selected_address = 'existing_1';
    public $show_new_address_form = false;
    public $first_name = '';
    public $last_name = '';
    public $address_phone = '';
    public $street_address = '';
    public $city = '';
    public $postal_code = '';

    // Shipping Method
    public $shipping_method = 'standard';
    public $shipping_cost = 0;

    // Payment Method
    public $payment_method = 'card';
    public $card_number = '';
    public $expiry_date = '';
    public $cvv = '';
    public $cardholder_name = '';
    public $save_card = false;

    // Promo Code
    public $promo_code = '';
    public $discount_amount = 0;
    public $applied_promo = '';

    // Calculations
    public $subtotal = 0;
    public $tax_amount = 0;
    public $tax_rate = 0.08; // 8% tax

    public function mount()
    {
        // Use validated cart items for checkout
        $this->cart_items = CartManagement::getValidatedCartItems();
        $this->calculateTotals();

        // Pre-fill user data if authenticated
        if (auth()->check()) {
            $user = auth()->user();
            $this->email = $user->email;
            $this->first_name = $user->name;
        }
    }

    public function calculateTotals()
    {
        $this->subtotal = CartManagement::calculateGrandTotal($this->cart_items);
        $this->tax_amount = $this->subtotal * $this->tax_rate;
        $this->grand_total = $this->subtotal + $this->shipping_cost + $this->tax_amount - $this->discount_amount;
    }

    public function toggleNewAddressForm()
    {
        $this->show_new_address_form = !$this->show_new_address_form;
    }

    public function saveNewAddress()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address_phone' => 'required|string|max:20',
            'street_address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
        ]);

        // In a real application, you would save this to the database
        $this->alert('success', 'Address saved successfully!');
        $this->show_new_address_form = false;
        $this->selected_address = 'new';
    }

    public function updatedShippingMethod()
    {
        switch ($this->shipping_method) {
            case 'standard':
                $this->shipping_cost = 0;
                break;
            case 'express':
                $this->shipping_cost = 15.99;
                break;
            case 'overnight':
                $this->shipping_cost = 29.99;
                break;
        }
        $this->calculateTotals();
    }

    public function applyPromoCode()
    {
        $code = strtoupper(trim($this->promo_code));

        if ($code === 'WEEKEND20') {
            $this->discount_amount = $this->subtotal * 0.20; // 20% discount
            $this->applied_promo = $code;
            $this->calculateTotals();
            $this->alert('success', 'Promo code applied! 20% discount');
            $this->promo_code = '';
        } else {
            $this->alert('error', 'Invalid promo code');
        }
    }

    public function placeOrder()
    {
        try {
            // Check permissions and rate limiting
            if (!CartValidationService::validateCartPermissions('place_order', auth()->id())) {
                $this->alert('error', 'Too many order attempts. Please try again later.');
                return;
            }

            // Validate all required fields
            $this->validate([
                'email' => 'required|email',
                'phone' => 'required|string|max:20',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'street_address' => 'required|string|max:500',
                'city' => 'required|string|max:255',
                'postal_code' => 'required|string|max:20',
            ]);

            if ($this->payment_method === 'card') {
                $this->validate([
                    'card_number' => 'required|string|min:16',
                    'expiry_date' => 'required|string|min:5',
                    'cvv' => 'required|string|min:3|max:4',
                    'cardholder_name' => 'required|string|max:255',
                ]);
            }

            // Get validated cart items (server-side verification)
            $validatedCartItems = CartManagement::getValidatedCartItems();

            // Check if cart is empty
            if (empty($validatedCartItems)) {
                $this->alert('error', 'Your cart is empty or contains invalid items!');
                return;
            }

            // Final cart validation
            $cartValidation = CartValidationService::validateCart($validatedCartItems);
            if (!$cartValidation['valid']) {
                $this->alert('error', 'Cart validation failed. Please review your items.');
                return;
            }

            // Prepare order data
            $orderData = [
                'payment_method' => $this->payment_method,
                'shipping_method' => $this->shipping_method,
                'shipping_cost' => $this->shipping_cost,
                'tax_rate' => $this->tax_rate,
                'discount_amount' => $this->discount_amount,
                'currency' => 'USD',
                'notes' => null,
            ];

            // Prepare shipping address
            $shippingAddress = [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'phone' => $this->phone,
                'street_address' => $this->street_address,
                'city' => $this->city,
                'state' => null, // Add state field if needed
                'zip_code' => $this->postal_code,
            ];

            // Create order using OrderService with validated cart items
            $orderService = new OrderService();
            $order = $orderService->createOrderFromCart($validatedCartItems, $orderData, $shippingAddress);

            // Update cart count in navbar
            $this->dispatch('update-cart-count', total_count: 0)->to(\App\Livewire\Partials\Navbar::class);

            $this->alert('success', 'Order placed successfully!');

            // Dispatch order processing event for UI feedback
            $this->dispatch('order-processing');

            // Redirect to success page with order ID
            $this->redirect(route('success', ['order_id' => $order->id]), navigate: true);

        } catch (Exception $e) {
            // Log the error for debugging
            \Log::error('Order creation failed: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'cart_items' => $this->cart_items,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->alert('error', 'Failed to place order: ' . $e->getMessage(), [
                'position' => 'top-end',
                'timer' => 5000,
                'toast' => true,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.checkout-page');
    }
}
