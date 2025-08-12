<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Models\Address;
use App\Services\CartValidationService;
use App\Services\OrderService;
use App\Services\PayWayService;
use Exception;
use Illuminate\Support\Facades\Log;
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
    public $selected_address = 'new';
    public $show_new_address_form = false;
    public $user_addresses = [];

    // Cambodia Address Fields
    public $contact_name = '';
    public $phone_number = '';
    public $house_number = '';
    public $street_number = '';
    public $city_province = '';
    public $district_khan = '';
    public $commune_sangkat = '';
    public $postal_code = '';
    public $additional_info = '';
    public $is_default = false;

    // Shipping Method
    public $shipping_method = 'standard';
    public $shipping_cost = 0;

    // Payment Method
    public $payment_method = 'aba_pay';
    public $available_payment_methods = [];

    // ABA Pay specific fields
    public $customer_firstname = '';
    public $customer_lastname = '';
    public $customer_email = '';
    public $customer_phone = '';

    // Legacy card fields (for backward compatibility)
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

        // Redirect if cart is empty
        if (empty($this->cart_items)) {
            return redirect()->route('cart-products')->with('error', 'Your cart is empty. Please add some items before checkout.');
        }

        $this->calculateTotals();

        // Load available payment methods
        $this->loadPaymentMethods();

        // Pre-fill user data if authenticated
        if (auth()->check()) {
            $user = auth()->user();
            $this->email = $user->email;
            $this->contact_name = $user->name;
            $this->customer_email = $user->email;
            $this->customer_firstname = explode(' ', $user->name)[0] ?? '';
            $this->customer_lastname = implode(' ', array_slice(explode(' ', $user->name), 1)) ?: '';

            // Load user addresses
            $this->loadUserAddresses();
        }
    }

    public function calculateTotals()
    {
        $this->subtotal = CartManagement::calculateGrandTotal($this->cart_items);
        $this->tax_amount = $this->subtotal * $this->tax_rate;
        $this->grand_total = $this->subtotal + $this->shipping_cost + $this->tax_amount - $this->discount_amount;
    }

    public function loadPaymentMethods()
    {
        // Simple payment methods - just ABA Pay and Cash on Delivery
        $this->available_payment_methods = [
            [
                'code' => 'aba_pay',
                'name' => 'ABA Pay',
                'description' => 'Pay securely with ABA Pay',
                'icon' => 'fas fa-credit-card',
                'provider' => 'payway'
            ],
            [
                'code' => 'cod',
                'name' => 'Cash on Delivery',
                'description' => 'Pay when your order is delivered',
                'icon' => 'fas fa-money-bill-wave',
                'provider' => 'manual'
            ]
        ];

        // Set default payment method
        $this->payment_method = 'aba_pay';
    }

    public function loadUserAddresses()
    {
        if (auth()->check()) {
            $this->user_addresses = Address::where('user_id', auth()->id())
                ->where('type', 'shipping')
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        }
    }

    public function toggleNewAddressForm()
    {
        $this->show_new_address_form = !$this->show_new_address_form;
        if ($this->show_new_address_form) {
            $this->selected_address = 'new';
        }
    }

    public function selectAddress($addressId)
    {
        $this->selected_address = $addressId;
        $this->show_new_address_form = false;

        if ($addressId !== 'new') {
            $address = collect($this->user_addresses)->firstWhere('id', $addressId);
            if ($address) {
                $this->contact_name = $address['contact_name'];
                $this->phone_number = $address['phone_number'];
                $this->house_number = $address['house_number'];
                $this->street_number = $address['street_number'];
                $this->city_province = $address['city_province'];
                $this->district_khan = $address['district_khan'];
                $this->commune_sangkat = $address['commune_sangkat'];
                $this->postal_code = $address['postal_code'];
                $this->additional_info = $address['additional_info'];
            }
        }
    }

    public function saveNewAddress()
    {
        $this->validate([
            'contact_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'house_number' => 'nullable|string|max:100',
            'street_number' => 'nullable|string|max:100',
            'city_province' => 'required|string|max:255',
            'district_khan' => 'required|string|max:255',
            'commune_sangkat' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            'additional_info' => 'nullable|string|max:500',
        ]);

        if (auth()->check()) {
            $address = Address::create([
                'user_id' => auth()->id(),
                'type' => 'shipping',
                'contact_name' => $this->contact_name,
                'phone_number' => $this->phone_number,
                'house_number' => $this->house_number,
                'street_number' => $this->street_number,
                'city_province' => $this->city_province,
                'district_khan' => $this->district_khan,
                'commune_sangkat' => $this->commune_sangkat,
                'postal_code' => $this->postal_code,
                'additional_info' => $this->additional_info,
                'is_default' => $this->is_default,
            ]);

            if ($this->is_default) {
                $address->setAsDefault();
            }

            $this->loadUserAddresses();
            $this->selected_address = $address->id;
            $this->show_new_address_form = false;
            $this->alert('success', 'Address saved successfully!');
        }
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
            // Validate cart items first
            $validatedCartItems = CartManagement::getValidatedCartItems();
            if (empty($validatedCartItems)) {
                $this->alert('error', 'Your cart is empty!');
                return redirect()->route('cart-products');
            }

            // Double-check grand total
            if ($this->grand_total <= 0) {
                $this->alert('error', 'Invalid order total!');
                return redirect()->route('cart-products');
            }

            // Check permissions and rate limiting
            if (!CartValidationService::validateCartPermissions('place_order', auth()->id())) {
                $this->alert('error', 'Too many order attempts. Please try again later.');
                return;
            }

            // Validate all required fields
            $this->validate([
                'email' => 'required|email',
                'contact_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:20',
                'city_province' => 'required|string|max:255',
                'district_khan' => 'required|string|max:255',
                'commune_sangkat' => 'required|string|max:255',
                'postal_code' => 'required|string|max:10',
            ]);

            // Validate payment method specific fields
            if ($this->payment_method === 'aba_pay') {
                $this->validate([
                    'customer_email' => 'required|email',
                    'customer_firstname' => 'required|string|max:255',
                    'customer_lastname' => 'required|string|max:255',
                ]);
            } elseif ($this->payment_method === 'card') {
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
                'contact_name' => $this->contact_name,
                'phone_number' => $this->phone_number,
                'house_number' => $this->house_number,
                'street_number' => $this->street_number,
                'city_province' => $this->city_province,
                'district_khan' => $this->district_khan,
                'commune_sangkat' => $this->commune_sangkat,
                'postal_code' => $this->postal_code,
                'additional_info' => $this->additional_info,
            ];

            // Create order using OrderService with validated cart items
            $orderService = new OrderService();
            $order = $orderService->createOrderFromCart($validatedCartItems, $orderData, $shippingAddress);

            // Process payment based on selected method
            $paymentResult = $this->processPayment($order);

            if (!$paymentResult['success']) {
                $this->alert('error', $paymentResult['error'] ?? 'Payment processing failed');
                return;
            }

            // Handle different payment results
            if (isset($paymentResult['redirect_url'])) {
                // For ABA Pay, redirect to payment gateway
                $this->redirect($paymentResult['redirect_url']);
                return;
            }

            // Update cart count in navbar
            $this->dispatch('update-cart-count', total_count: 0)->to(\App\Livewire\Partials\Navbar::class);

            $this->alert('success', 'Order placed successfully!');

            // Dispatch order processing event for UI feedback
            $this->dispatch('order-processing');

            // Redirect to success page with order ID
            $this->redirect(route('success', ['order_id' => $order->id]), navigate: true);

        } catch (Exception $e) {
            // Log the error for debugging
            Log::error('Order creation failed: ' . $e->getMessage(), [
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

    protected function processPayment($order)
    {
        try {
            Log::info('Processing payment', [
                'order_id' => $order->id,
                'payment_method' => $this->payment_method,
                'amount' => $order->grand_total
            ]);

            if ($this->payment_method === 'aba_pay') {
                // Use PayWayService directly
                $payWayService = new PayWayService();

                // Prepare payment data for PayWay
                $paymentData = [
                    'transaction_id' => 'ORD-' . $order->id . '-' . time(),
                    'amount' => $order->grand_total,
                    'currency' => $order->currency ?? 'USD',
                    'firstname' => $this->customer_firstname ?? '',
                    'lastname' => $this->customer_lastname ?? '',
                    'email' => $this->customer_email ?? $this->email,
                    'phone' => $this->customer_phone ?? $this->phone_number,
                    'return_url' => route('payment.aba-pay.return'),
                    'cancel_url' => route('payment.aba-pay.cancel'),
                    'items' => $this->prepareOrderItems($order),
                    'shipping' => 0.00,
                    'type' => 'purchase',
                    'payment_option' => '', // Let user choose
                    'view_type' => 'checkout', // Use 'checkout' for redirect flow
                ];

                // Create ABA Pay transaction record
                $abaTransaction = \App\Models\AbaPayTransaction::create([
                    'order_id' => $order->id,
                    'transaction_id' => $paymentData['transaction_id'],
                    'merchant_id' => config('payway.merchant_id'),
                    'amount' => $order->grand_total,
                    'currency' => $order->currency ?? 'USD',
                    'status' => \App\Models\AbaPayTransaction::STATUS_PENDING,
                    'payment_option' => '',
                    'payment_gate' => 'payway',
                    'request_time' => now()->format('YmdHis'),
                    'hash' => null, // Will be generated by PayWay service
                    'shipping' => 0.00,
                    'type' => 'purchase',
                    'view_type' => 'checkout',
                    'customer_info' => [
                        'firstname' => $this->customer_firstname,
                        'lastname' => $this->customer_lastname,
                        'email' => $this->customer_email,
                        'phone' => $this->customer_phone,
                    ],
                    'urls' => [
                        'return_url' => route('payment.aba-pay.return'),
                        'cancel_url' => route('payment.aba-pay.cancel'),
                    ],
                ]);

                $result = $payWayService->createPayment($paymentData);

                if ($result['success']) {
                    // Update the transaction record with the generated hash if available
                    if (isset($result['payment_data']['hash'])) {
                        $abaTransaction->update(['hash' => $result['payment_data']['hash']]);
                    }

                    // Store payment data for redirect form
                    session([
                        'payway_payment_data' => $paymentData,
                        'payway_order_id' => $order->id
                    ]);

                    return [
                        'success' => true,
                        'redirect_url' => route('payment.aba-pay.redirect')
                    ];
                } else {
                    Log::error('PayWay payment creation failed', [
                        'order_id' => $order->id,
                        'error' => $result['error'] ?? 'Unknown error',
                        'error_code' => $result['error_code'] ?? null,
                        'payment_data' => $paymentData
                    ]);

                    return [
                        'success' => false,
                        'error' => $result['error'] ?? 'Payment processing failed'
                    ];
                }
            } else {
                // Cash on Delivery - just mark as pending
                $order->update(['payment_status' => 'pending']);
                return [
                    'success' => true,
                    'redirect_url' => route('success', ['order_id' => $order->id])
                ];
            }
        } catch (Exception $e) {
            Log::error('Payment processing failed', [
                'order_id' => $order->id,
                'payment_method' => $this->payment_method,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Payment processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Prepare order items for PayWay
     */
    private function prepareOrderItems($order): array
    {
        $items = [];

        foreach ($order->items as $item) {
            $items[] = [
                'name' => $item->product->name,
                'quantity' => $item->quantity,
                'price' => $item->unit_amount
            ];
        }

        return $items;
    }

    public function render()
    {
        return view('livewire.checkout-page');
    }
}
