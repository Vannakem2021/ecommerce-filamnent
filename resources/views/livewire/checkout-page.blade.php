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

                <!-- Shipping Address -->
                <div class="bg-white rounded-2xl shadow-md p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Shipping Address</h2>

                    <!-- Address Form (Always Visible) -->

                            <!-- Contact Information -->
                            <div class="space-y-4 mb-6">
                                <h4 class="text-base font-semibold text-gray-800">Contact Information</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Contact Name <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            wire:model="contact_name"
                                            type="text"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                            placeholder="Enter full name"
                                        />
                                        @error('contact_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Phone Number <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            wire:model="phone_number"
                                            type="tel"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                            placeholder="e.g., +855 12 345 678"
                                        />
                                        @error('phone_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Address Details -->
                            <div class="space-y-4 mb-6">
                                <h4 class="text-base font-semibold text-gray-800">Address Details</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">House Number</label>
                                        <input
                                            wire:model="house_number"
                                            type="text"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                            placeholder="e.g., #123"
                                        />
                                        @error('house_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Street Number</label>
                                        <input
                                            wire:model="street_number"
                                            type="text"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                            placeholder="e.g., Street 240"
                                        />
                                        @error('street_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Cambodia Location Selection -->
                            <div class="space-y-4 mb-6">
                                <h4 class="text-base font-semibold text-gray-800">Location</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            City/Province <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="city_province"
                                            wire:model="city_province"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                        >
                                            <option value="">Select Province/City</option>
                                        </select>
                                        @error('city_province') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            District/Srok/Khan <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="district_khan"
                                            wire:model="district_khan"
                                            disabled
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 disabled:bg-gray-100"
                                        >
                                            <option value="">Select District</option>
                                        </select>
                                        @error('district_khan') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Commune/Khum/Sangkat <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="commune_sangkat"
                                            wire:model="commune_sangkat"
                                            disabled
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500 disabled:bg-gray-100"
                                        >
                                            <option value="">Select Commune</option>
                                        </select>
                                        @error('commune_sangkat') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- Postal Code (Auto-filled) -->
                                <div class="max-w-xs">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Postal Code <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="postal_code"
                                        wire:model="postal_code"
                                        type="text"
                                        readonly
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50"
                                        placeholder="Auto-filled"
                                    />
                                    <p class="text-sm text-gray-500 mt-1">
                                        Postal code will be automatically filled based on your area selection
                                    </p>
                                    @error('postal_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div class="space-y-4 mb-6">
                                <h4 class="text-base font-semibold text-gray-800">Additional Information</h4>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Instructions</label>
                                    <textarea
                                        wire:model="additional_info"
                                        rows="3"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                        placeholder="Any special delivery instructions (optional)"
                                    ></textarea>
                                    @error('additional_info') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                </div>

                <!-- Payment Method -->
                <div class="bg-white rounded-2xl shadow-md p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Payment Method</h2>

                    <div class="space-y-4 mb-6">
                        @forelse($available_payment_methods as $method)
                            <label class="flex items-center p-4 border border-gray-300 rounded-xl cursor-pointer hover:border-custom-teal-500 transition-colors {{ $payment_method === $method['code'] ? 'border-custom-teal-500 bg-custom-teal-50' : '' }}">
                                <input
                                    wire:model="payment_method"
                                    type="radio"
                                    name="payment"
                                    value="{{ $method['code'] }}"
                                    class="w-4 h-4 text-custom-teal-600"
                                />
                                <div class="ml-3 flex items-center">
                                    @if($method['icon'])
                                        <i class="{{ $method['icon'] }} text-gray-600 mr-3"></i>
                                    @else
                                        <i class="fas fa-credit-card text-gray-600 mr-3"></i>
                                    @endif
                                    <div>
                                        <div class="flex items-center">
                                            <p class="font-medium text-gray-900">{{ $method['name'] }}</p>
                                            @if($method['provider'] === 'aba_pay')
                                                <span class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Recommended</span>
                                            @endif
                                        </div>
                                        @if($method['description'])
                                            <p class="text-sm text-gray-600">{{ $method['description'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        @empty
                            <div class="text-center py-8">
                                <i class="fas fa-exclamation-triangle text-4xl text-gray-400 mb-4"></i>
                                <p class="text-gray-600">No payment methods available</p>
                            </div>
                        @endforelse
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

                    <!-- ABA Pay Customer Information -->
                    @if($payment_method === 'aba_pay')
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h3>
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                                        <input
                                            wire:model="customer_firstname"
                                            type="text"
                                            placeholder="John"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                        />
                                        @error('customer_firstname') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                                        <input
                                            wire:model="customer_lastname"
                                            type="text"
                                            placeholder="Doe"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                        />
                                        @error('customer_lastname') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                    <div class="relative">
                                        <input
                                            wire:model="customer_email"
                                            type="email"
                                            placeholder="john@example.com"
                                            class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                        />
                                        <i class="fas fa-envelope absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    </div>
                                    @error('customer_email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number (Optional)</label>
                                    <div class="relative">
                                        <input
                                            wire:model="customer_phone"
                                            type="tel"
                                            placeholder="+855 12 345 678"
                                            class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-xl focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                                        />
                                        <i class="fas fa-phone absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    </div>
                                    @error('customer_phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="mt-6 p-4 bg-blue-50 rounded-xl">
                                <div class="flex items-start">
                                    <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                                    <div>
                                        <h4 class="text-sm font-medium text-blue-900 mb-1">ABA Pay Payment</h4>
                                        <p class="text-sm text-blue-700">
                                            You will be redirected to ABA Pay's secure payment gateway to complete your payment.
                                            Supports ABA PAY, KHQR, cards, Google Pay, and WeChat Pay.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- KHQR Details -->
                    @if($payment_method === 'khqr')
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">KHQR Payment</h3>
                            <div class="bg-gray-50 rounded-xl p-6 text-center">
                                <i class="fas fa-qrcode text-6xl text-gray-400 mb-4"></i>
                                <p class="text-gray-600 mb-2">QR Code will be generated after placing order</p>
                                <p class="text-sm text-gray-500">Scan with your banking app to complete payment</p>
                            </div>
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
                                    @php
                                        $imageUrl = null;
                                        if (!empty($item['image'])) {
                                            // Check if it's already a full URL or needs Storage::url()
                                            if (str_starts_with($item['image'], 'http')) {
                                                $imageUrl = $item['image'];
                                            } else {
                                                $imageUrl = Storage::url($item['image']);
                                            }
                                        }
                                    @endphp

                                    @if($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $item['name'] }}" class="w-16 h-16 object-cover rounded-lg"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center" style="display: none;">
                                            <i class="fas fa-image text-gray-400"></i>
                                        </div>
                                    @else
                                        <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400"></i>
                                        </div>
                                    @endif

                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900">{{ $item['name'] }}</p>
                                        <p class="text-sm text-gray-600">Qty: {{ $item['quantity'] }}</p>
                                        @if(!empty($item['variant_options']))
                                            <p class="text-xs text-gray-500">
                                                @foreach($item['variant_options'] as $key => $value)
                                                    {{ $key }}: {{ $value }}@if(!$loop->last), @endif
                                                @endforeach
                                            </p>
                                        @endif
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
