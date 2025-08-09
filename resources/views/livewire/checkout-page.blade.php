<div class="bg-gray-50 min-h-screen">
    <!-- Checkout Progress -->
    <div class="bg-white border-b border-gray-200">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-center space-x-8 md:space-x-16">
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-custom-teal-600 text-white rounded-full flex items-center justify-center font-semibold">
                        1
                    </div>
                    <span class="font-medium text-custom-teal-600">Cart</span>
                </div>
                <div class="flex-1 h-0.5 bg-gray-300 max-w-24"></div>
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-custom-teal-600 text-white rounded-full flex items-center justify-center font-semibold">
                        2
                    </div>
                    <span class="font-medium text-custom-teal-600">Details</span>
                </div>
                <div class="flex-1 h-0.5 bg-gray-300 max-w-24"></div>
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center font-semibold">
                        3
                    </div>
                    <span class="font-medium text-gray-500">Payment</span>
                </div>
            </div>
        </div>
    </div>

    <main class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Main Content -->
            <div class="flex-1">
                <!-- Contact Information -->
                <div class="bg-white rounded-2xl shadow-md p-8 mb-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Contact Information</h2>
                        @guest
                            <a href="{{ route('login') }}" wire:navigate class="text-custom-teal-600 hover:text-custom-teal-700 font-medium transition-colors">
                                <i class="fas fa-user mr-2"></i>Log in
                            </a>
                        @endguest
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input
                                wire:model="email"
                                type="email"
                                placeholder="john.doe@example.com"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 transition-all duration-200"
                            />
                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                            <input
                                wire:model="phone"
                                type="tel"
                                placeholder="+1 (555) 123-4567"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 transition-all duration-200"
                            />
                            @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="flex items-center">
                            <input
                                wire:model="newsletter_signup"
                                type="checkbox"
                                class="w-4 h-4 text-custom-teal-600 rounded focus:ring-custom-teal-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">Email me with news and offers</span>
                        </label>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="bg-white rounded-2xl shadow-md p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Shipping Address</h2>

                    <div class="space-y-4 mb-6">
                        <label class="flex items-center p-4 border border-gray-300 rounded-xl cursor-pointer hover:border-custom-teal-500 transition-colors">
                            <input
                                wire:model="selected_address"
                                type="radio"
                                name="address"
                                value="existing_1"
                                class="w-4 h-4 text-custom-teal-600"
                            />
                            <span class="ml-3">
                                <p class="font-medium text-gray-900">John Doe</p>
                                <p class="text-sm text-gray-600">123 Main Street, Apt 4B, New York, NY 10001</p>
                            </span>
                        </label>

                        <label class="flex items-center p-4 border border-gray-300 rounded-xl cursor-pointer hover:border-custom-teal-500 transition-colors">
                            <input
                                wire:model="selected_address"
                                type="radio"
                                name="address"
                                value="existing_2"
                                class="w-4 h-4 text-custom-teal-600"
                            />
                            <span class="ml-3">
                                <p class="font-medium text-gray-900">John Doe</p>
                                <p class="text-sm text-gray-600">456 Business Ave, Suite 200, New York, NY 10005</p>
                            </span>
                        </label>
                    </div>

                    <button
                        wire:click="toggleNewAddressForm"
                        class="text-custom-teal-600 hover:text-custom-teal-700 font-medium transition-colors"
                    >
                        <i class="fas fa-plus mr-2"></i>Add New Address
                    </button>

                    <!-- New Address Form -->
                    @if($show_new_address_form)
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">New Address</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                    <input
                                        wire:model="first_name"
                                        type="text"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                    />
                                    @error('first_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                    <input
                                        wire:model="last_name"
                                        type="text"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                    />
                                    @error('last_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                    <input
                                        wire:model="address_phone"
                                        type="tel"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                    />
                                    @error('address_phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Street Address</label>
                                    <input
                                        wire:model="street_address"
                                        type="text"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                    />
                                    @error('street_address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                                    <input
                                        wire:model="city"
                                        type="text"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                    />
                                    @error('city') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                                    <input
                                        wire:model="postal_code"
                                        type="text"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                    />
                                    @error('postal_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            <div class="flex gap-4 mt-6">
                                <button
                                    wire:click="saveNewAddress"
                                    class="bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-2 px-6 rounded-xl transition-colors"
                                >
                                    Save Address
                                </button>
                                <button
                                    wire:click="toggleNewAddressForm"
                                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-6 rounded-xl transition-colors"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Shipping Method -->
                <div class="bg-white rounded-2xl shadow-md p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Shipping Method</h2>

                    <div class="space-y-4">
                        <label class="flex items-center p-4 border border-gray-300 rounded-xl cursor-pointer hover:border-custom-teal-500 transition-colors">
                            <input
                                wire:model="shipping_method"
                                type="radio"
                                name="shipping"
                                value="standard"
                                class="w-4 h-4 text-custom-teal-600"
                            />
                            <div class="ml-3 flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900">Standard Delivery</p>
                                        <p class="text-sm text-gray-600">5-7 business days</p>
                                    </div>
                                    <span class="font-semibold text-custom-teal-700">FREE</span>
                                </div>
                            </div>
                        </label>

                        <label class="flex items-center p-4 border border-gray-300 rounded-xl cursor-pointer hover:border-custom-teal-500 transition-colors">
                            <input
                                wire:model="shipping_method"
                                type="radio"
                                name="shipping"
                                value="express"
                                class="w-4 h-4 text-custom-teal-600"
                            />
                            <div class="ml-3 flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900">Express Delivery</p>
                                        <p class="text-sm text-gray-600">2-3 business days</p>
                                    </div>
                                    <span class="font-semibold text-gray-900">${{ number_format(15.99, 2) }}</span>
                                </div>
                            </div>
                        </label>

                        <label class="flex items-center p-4 border border-gray-300 rounded-xl cursor-pointer hover:border-custom-teal-500 transition-colors">
                            <input
                                wire:model="shipping_method"
                                type="radio"
                                name="shipping"
                                value="overnight"
                                class="w-4 h-4 text-custom-teal-600"
                            />
                            <div class="ml-3 flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900">Overnight Delivery</p>
                                        <p class="text-sm text-gray-600">Next business day</p>
                                    </div>
                                    <span class="font-semibold text-gray-900">${{ number_format(29.99, 2) }}</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="bg-white rounded-2xl shadow-md p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Payment Method</h2>

                    <div class="space-y-4 mb-6">
                        <label class="flex items-center p-4 border border-gray-300 rounded-xl cursor-pointer hover:border-custom-teal-500 transition-colors">
                            <input
                                wire:model="payment_method"
                                type="radio"
                                name="payment"
                                value="card"
                                class="w-4 h-4 text-custom-teal-600"
                            />
                            <div class="ml-3 flex items-center">
                                <i class="fas fa-credit-card text-gray-600 mr-3"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Credit/Debit Card</p>
                                    <p class="text-sm text-gray-600">Pay with Visa, Mastercard, or Amex</p>
                                </div>
                            </div>
                        </label>

                        <label class="flex items-center p-4 border border-gray-300 rounded-xl cursor-pointer hover:border-custom-teal-500 transition-colors">
                            <input
                                wire:model="payment_method"
                                type="radio"
                                name="payment"
                                value="paypal"
                                class="w-4 h-4 text-custom-teal-600"
                            />
                            <div class="ml-3 flex items-center">
                                <i class="fab fa-paypal text-blue-600 mr-3"></i>
                                <div>
                                    <p class="font-medium text-gray-900">PayPal</p>
                                    <p class="text-sm text-gray-600">Pay with your PayPal account</p>
                                </div>
                            </div>
                        </label>

                        <label class="flex items-center p-4 border border-gray-300 rounded-xl cursor-pointer hover:border-custom-teal-500 transition-colors">
                            <input
                                wire:model="payment_method"
                                type="radio"
                                name="payment"
                                value="cod"
                                class="w-4 h-4 text-custom-teal-600"
                            />
                            <div class="ml-3 flex items-center">
                                <i class="fas fa-money-bill-wave text-green-600 mr-3"></i>
                                <div>
                                    <p class="font-medium text-gray-900">Cash on Delivery</p>
                                    <p class="text-sm text-gray-600">Pay when you receive your order</p>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Card Details Form -->
                    @if($payment_method === 'card')
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Card Details</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Card Number</label>
                                    <div class="relative">
                                        <input
                                            wire:model="card_number"
                                            type="text"
                                            placeholder="1234 5678 9012 3456"
                                            class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                        />
                                        <i class="fas fa-credit-card absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    </div>
                                    @error('card_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                                        <input
                                            wire:model="expiry_date"
                                            type="text"
                                            placeholder="MM/YY"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                        />
                                        @error('expiry_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">CVV</label>
                                        <input
                                            wire:model="cvv"
                                            type="text"
                                            placeholder="123"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                        />
                                        @error('cvv') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Cardholder Name</label>
                                    <input
                                        wire:model="cardholder_name"
                                        type="text"
                                        placeholder="John Doe"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                    />
                                    @error('cardholder_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <label class="flex items-center mt-6">
                                <input
                                    wire:model="save_card"
                                    type="checkbox"
                                    class="w-4 h-4 text-custom-teal-600 rounded focus:ring-custom-teal-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">Save this card for future purchases</span>
                            </label>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Order Summary Sidebar -->
            <aside class="lg:w-96 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-md p-8 sticky top-24">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Order Summary</h2>

                    <!-- Order Items -->
                    @if(count($cart_items) > 0)
                        <div class="space-y-4 mb-6">
                            @foreach($cart_items as $item)
                                <div class="flex items-center space-x-4">
                                    @if($item['image'])
                                        <img src="{{ Storage::url($item['image']) }}" alt="{{ $item['name'] }}" class="w-16 h-16 object-cover rounded-lg">
                                    @else
                                        <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400"></i>
                                        </div>
                                    @endif
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900">{{ $item['name'] }}</p>
                                        <p class="text-sm text-gray-600">Qty: {{ $item['quantity'] }}</p>
                                    </div>
                                    <span class="font-semibold text-gray-900">${{ number_format($item['total_amount'], 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Price Breakdown -->
                    <div class="space-y-3 mb-6 pt-6 border-t border-gray-200">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span class="font-medium">${{ number_format($subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping</span>
                            @if($shipping_cost == 0)
                                <span class="font-medium text-green-600">FREE</span>
                            @else
                                <span class="font-medium">${{ number_format($shipping_cost, 2) }}</span>
                            @endif
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Tax</span>
                            <span class="font-medium">${{ number_format($tax_amount, 2) }}</span>
                        </div>
                        @if($discount_amount > 0)
                            <div class="flex justify-between text-green-600">
                                <span>Discount ({{ $applied_promo }})</span>
                                <span class="font-medium">-${{ number_format($discount_amount, 2) }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Total -->
                    <div class="border-t border-gray-200 pt-4 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-xl font-bold text-gray-900">Total</span>
                            <span class="text-2xl font-bold text-custom-teal-700">${{ number_format($grand_total, 2) }}</span>
                        </div>
                    </div>

                    <!-- Promo Code -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Promo Code</label>
                        <div class="flex gap-2">
                            <input
                                wire:model="promo_code"
                                type="text"
                                placeholder="Enter code"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                            />
                            <button
                                wire:click="applyPromoCode"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg transition-colors"
                            >
                                Apply
                            </button>
                        </div>
                    </div>

                    <!-- Place Order Button -->
                    <button
                        wire:click="placeOrder"
                        class="w-full bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 mb-4"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                    >
                        <span wire:loading.remove>Place Order</span>
                        <span wire:loading>
                            <i class="fas fa-spinner fa-spin mr-2"></i>Processing...
                        </span>
                    </button>

                    <!-- Security Info -->
                    <div class="text-center">
                        <button class="text-custom-teal-600 hover:text-custom-teal-700 font-medium transition-colors">
                            <i class="fas fa-lock mr-2"></i>Secure Checkout
                        </button>
                    </div>

                    <!-- Trust Badges -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="flex justify-center space-x-6">
                            <div class="text-center">
                                <i class="fas fa-shield-alt text-green-500 text-2xl mb-2"></i>
                                <p class="text-xs text-gray-600">Secure Payment</p>
                            </div>
                            <div class="text-center">
                                <i class="fas fa-truck text-green-500 text-2xl mb-2"></i>
                                <p class="text-xs text-gray-600">Free Shipping</p>
                            </div>
                            <div class="text-center">
                                <i class="fas fa-undo text-green-500 text-2xl mb-2"></i>
                                <p class="text-xs text-gray-600">Easy Returns</p>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Order Confirmation Modal -->
    <div
        id="orderModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 hidden transition-opacity duration-300"
    >
        <div
            id="modalContent"
            class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 transform transition-all duration-300 scale-95 opacity-0"
        >
            <div class="text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-green-600 text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Order Confirmed!</h3>
                <p class="text-gray-600 mb-6">Thank you for your order. We'll send you a confirmation email shortly.</p>
                <p class="text-lg font-semibold text-custom-teal-700 mb-6">Order #TS-{{ date('Y') }}-{{ str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT) }}</p>
                <button
                    onclick="closeOrderModal()"
                    class="bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-3 px-8 rounded-xl transition-colors"
                >
                    Continue Shopping
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript for Enhanced Interactions -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Format card number input
            const cardNumberInput = document.querySelector('input[wire\\:model="card_number"]');
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
                    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                    if (formattedValue !== e.target.value) {
                        e.target.value = formattedValue;
                    }
                });
            }

            // Format expiry date input
            const expiryInput = document.querySelector('input[wire\\:model="expiry_date"]');
            if (expiryInput) {
                expiryInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length >= 2) {
                        value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                    e.target.value = value;
                });
            }

            // Format phone number inputs
            const phoneInputs = document.querySelectorAll('input[type="tel"]');
            phoneInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        if (value.length <= 3) {
                            value = `(${value}`;
                        } else if (value.length <= 6) {
                            value = `(${value.slice(0, 3)}) ${value.slice(3)}`;
                        } else {
                            value = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6, 10)}`;
                        }
                    }
                    e.target.value = value;
                });
            });

            // CVV input restriction
            const cvvInput = document.querySelector('input[wire\\:model="cvv"]');
            if (cvvInput) {
                cvvInput.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/\D/g, '').substring(0, 4);
                });
            }

            // Smooth scroll to form sections when validation errors occur
            const errorElements = document.querySelectorAll('.text-red-500');
            if (errorElements.length > 0) {
                errorElements[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // Enhanced form validation feedback
            const inputs = document.querySelectorAll('input[required], input[wire\\:model]');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() === '' && this.hasAttribute('required')) {
                        this.classList.add('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
                        this.classList.remove('border-gray-300', 'focus:border-custom-teal-500', 'focus:ring-custom-teal-500');
                    } else {
                        this.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
                        this.classList.add('border-gray-300', 'focus:border-custom-teal-500', 'focus:ring-custom-teal-500');
                    }
                });
            });

            // Progress indicator animation
            const progressSteps = document.querySelectorAll('.w-10.h-10');
            progressSteps.forEach((step, index) => {
                if (index < 2) { // Current step is "Details" (index 1)
                    step.classList.add('animate-pulse');
                    setTimeout(() => {
                        step.classList.remove('animate-pulse');
                    }, 2000);
                }
            });
        });

        // Order confirmation modal functions
        function showOrderModal() {
            const modal = document.getElementById('orderModal');
            const modalContent = document.getElementById('modalContent');

            if (modal && modalContent) {
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modalContent.classList.remove('scale-95', 'opacity-0');
                    modalContent.classList.add('scale-100', 'opacity-100');
                }, 100);
            }
        }

        function closeOrderModal() {
            const modal = document.getElementById('orderModal');
            const modalContent = document.getElementById('modalContent');

            if (modal && modalContent) {
                modalContent.classList.remove('scale-100', 'opacity-100');
                modalContent.classList.add('scale-95', 'opacity-0');

                setTimeout(() => {
                    modal.classList.add('hidden');
                    // Redirect to products page
                    window.location.href = '{{ route("all-products") }}';
                }, 300);
            }
        }

        // Make functions globally available
        window.showOrderModal = showOrderModal;
        window.closeOrderModal = closeOrderModal;

        // Livewire event listeners
        document.addEventListener('livewire:initialized', () => {
            // Listen for order processing events
            Livewire.on('order-processing', () => {
                // Show loading state and then modal
                setTimeout(() => {
                    showOrderModal();
                }, 2000);
            });

            // Listen for successful promo code application
            Livewire.on('promo-applied', () => {
                const promoInput = document.querySelector('input[wire\\:model="promo_code"]');
                if (promoInput) {
                    promoInput.classList.add('border-green-300', 'bg-green-50');
                    setTimeout(() => {
                        promoInput.classList.remove('border-green-300', 'bg-green-50');
                        promoInput.value = '';
                    }, 2000);
                }
            });
        });

        // Toast notification system
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
            const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';

            toast.className = `${bgColor} text-white px-6 py-4 rounded-lg shadow-md transform transition-all duration-300 translate-x-full fixed bottom-4 right-4 z-50`;
            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${icon} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(toast);

            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);

            // Remove after 3 seconds
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + Enter to place order
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                const placeOrderBtn = document.querySelector('button[wire\\:click="placeOrder"]');
                if (placeOrderBtn && !placeOrderBtn.disabled) {
                    placeOrderBtn.click();
                }
            }
        });
    </script>
</div>
