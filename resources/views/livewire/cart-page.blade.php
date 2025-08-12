<div class="bg-gray-50 min-h-screen">
    <main class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Main Cart Content -->
            <div class="flex-1">
                <!-- Header Section -->
                <div class="bg-white rounded-2xl shadow-md p-8 mb-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">Shopping Cart</h1>
                            <p class="text-gray-600">
                                You have {{ count($cart_items) }} {{ count($cart_items) === 1 ? 'item' : 'items' }} in your cart
                            </p>
                        </div>
                        <a href="{{ route('all-products') }}" wire:navigate class="text-custom-teal-600 hover:text-custom-teal-700 font-medium transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>

                @if(count($cart_items) > 0)
                    <!-- Cart Items -->
                    <div class="space-y-6">
                        @foreach($cart_items as $item)
                            <div class="bg-white rounded-2xl shadow-md p-6">
                                <div class="flex flex-col md:flex-row gap-6">
                                    <div class="flex-shrink-0">
                                        @if($item['image'])
                                            <img src="{{ asset('storage/' . $item['image']) }}" alt="{{ $item['name'] }}" class="w-32 h-32 object-cover rounded-xl">
                                        @else
                                            <div class="w-32 h-32 bg-gray-200 rounded-xl flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400 text-2xl"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex flex-col md:flex-row md:items-center justify-between mb-4">
                                            <div>
                                                @if(isset($item['product_slug']) && $item['product_slug'])
                                                    <a href="{{ route('product-details', $item['product_slug']) }}" wire:navigate class="text-lg font-semibold text-gray-900 hover:text-teal-600 transition-colors mb-1 block">
                                                        {{ $item['name'] }}
                                                    </a>
                                                @else
                                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ $item['name'] }}</h3>
                                                @endif
                                                <p class="text-sm text-gray-600 mb-1">
                                                    Electronics • SKU: {{ 'PRD-' . str_pad($item['product_id'], 3, '0', STR_PAD_LEFT) }}
                                                </p>

                                                <!-- Display Variant Options (Simplified) -->
                                                @if(isset($item['variant_options']) && !empty($item['variant_options']))
                                                    <div class="flex flex-wrap gap-2 mt-2">
                                                        @foreach($item['variant_options'] as $optionName => $optionValue)
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                                {{ $optionName }}: {{ $optionValue }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                            <button
                                                wire:click="removeItem('{{ $item['item_key'] }}')"
                                                class="text-red-600 hover:text-red-700 transition-colors"
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>

                                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                            <div class="flex items-center gap-4">
                                                <div class="flex items-center border border-gray-300 rounded-xl">
                                                    <button
                                                        wire:click="decreaseQuantity('{{ $item['item_key'] }}')"
                                                        class="px-4 py-2 text-gray-600 hover:bg-gray-100 transition-colors rounded-l-xl"
                                                    >
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input
                                                        type="number"
                                                        value="{{ $item['quantity'] }}"
                                                        min="1"
                                                        max="10"
                                                        class="w-16 text-center border-0 focus:ring-0"
                                                        readonly
                                                    />
                                                    <button
                                                        wire:click="increaseQuantity('{{ $item['item_key'] }}')"
                                                        class="px-4 py-2 text-gray-600 hover:bg-gray-100 transition-colors rounded-r-xl"
                                                    >
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                                <span class="text-sm text-green-600 font-medium">In Stock</span>
                                            </div>

                                            <div class="text-right">
                                                <span class="text-2xl font-bold text-custom-teal-700">
                                                    ${{ number_format($item['total_amount'], 2) }}
                                                </span>
                                                @if($item['quantity'] > 1)
                                                    <p class="text-sm text-gray-500 mt-1">
                                                        ${{ number_format($item['unit_amount'], 2) }} each
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>


                @else
                    <!-- Empty Cart State -->
                    <div class="bg-white rounded-2xl shadow-md p-12 text-center">
                        <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-shopping-cart text-gray-400 text-3xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Your cart is empty</h2>
                        <p class="text-gray-600 mb-8">Looks like you haven't added any items to your cart yet.</p>
                        <a href="{{ route('all-products') }}" wire:navigate class="bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-3 px-8 rounded-xl transition-all duration-200 inline-block">
                            Start Shopping
                        </a>
                    </div>
                @endif

                @if(count($cart_items) > 0)
                    <!-- Recommendations -->
                    <div class="bg-white rounded-2xl shadow-md p-8 mt-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6">You Might Also Like</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            @foreach($recommended_products as $product)
                                <div class="group border border-gray-200 rounded-2xl overflow-hidden hover:shadow-md transition-all duration-300">
                                    <a href="{{ route('product-details', $product->slug) }}" wire:navigate class="block">
                                        <div class="relative">
                                            @if($product->images && count($product->images) > 0)
                                                @php
                                                    $images = is_string($product->images) ? json_decode($product->images, true) : $product->images;
                                                    $firstImage = is_array($images) ? ($images[0] ?? null) : null;
                                                @endphp
                                                @if($firstImage)
                                                    <img src="{{ asset('storage/' . $firstImage) }}" alt="{{ $product->name }}" class="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300">
                                                @else
                                                    <div class="w-full h-40 bg-gray-200 flex items-center justify-center">
                                                        <i class="fas fa-image text-gray-400 text-2xl"></i>
                                                    </div>
                                                @endif
                                            @else
                                                <div class="w-full h-40 bg-gray-200 flex items-center justify-center">
                                                    <i class="fas fa-image text-gray-400 text-2xl"></i>
                                                </div>
                                            @endif
                                            @if($product->on_sale)
                                                <span class="absolute top-3 left-3 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                                    Sale
                                                </span>
                                            @endif
                                            @if($product->is_featured)
                                                <span class="absolute top-3 right-3 bg-custom-teal-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                                    Featured
                                                </span>
                                            @endif
                                        </div>
                                    </a>
                                    <div class="p-4">
                                        <a href="{{ route('product-details', $product->slug) }}" wire:navigate>
                                            <h4 class="font-semibold text-gray-900 mb-2 group-hover:text-custom-teal-700 transition-colors">
                                                {{ $product->name }}
                                            </h4>
                                        </a>
                                        <div class="flex items-center mb-2">
                                            <div class="flex text-yellow-400">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i class="fas fa-star text-xs"></i>
                                                @endfor
                                            </div>
                                            <span class="text-xs text-gray-500 ml-2">({{ rand(10, 200) }})</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-lg font-bold text-custom-teal-700">
                                                ${{ number_format($product->price, 2) }}
                                            </span>
                                            <button
                                                wire:click="addToCart({{ $product->id }})"
                                                class="bg-custom-teal-600 hover:bg-custom-teal-700 text-white p-2 rounded-lg transition-colors"
                                            >
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Order Summary Sidebar -->
            @if(count($cart_items) > 0)
                <aside class="lg:w-96 flex-shrink-0">
                    <div class="bg-white rounded-2xl shadow-md p-8 sticky top-24">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Order Summary</h2>

                        <div class="space-y-4 mb-6">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal ({{ count($cart_items) }} {{ count($cart_items) === 1 ? 'item' : 'items' }})</span>
                                <span class="font-medium">${{ number_format($this->getSubtotal(), 2) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Shipping</span>
                                @if($this->getShipping() == 0)
                                    <span class="font-medium text-green-600">FREE</span>
                                @else
                                    <span class="font-medium">${{ number_format($this->getShipping(), 2) }}</span>
                                @endif
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Tax</span>
                                <span class="font-medium">${{ number_format($this->getTax(), 2) }}</span>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-4 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-xl font-bold text-gray-900">Total</span>
                                <span class="text-2xl font-bold text-custom-teal-700">
                                    ${{ number_format($this->getFinalTotal(), 2) }}
                                </span>
                            </div>
                        </div>

                        @auth
                            <a href="{{ route('checkout') }}" wire:navigate onclick="console.log('Checkout button clicked:', '{{ route('checkout') }}'); return true;" class="w-full bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 mb-4 block text-center">
                                Proceed to Checkout
                            </a>

                            <!-- Alternative checkout button without wire:navigate for debugging -->
                            <a href="{{ route('checkout') }}" class="w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200 mb-2 block text-center text-sm">
                                Alternative Checkout (Direct Link)
                            </a>
                        @else
                            <div class="mb-4">
                                <p class="text-sm text-gray-600 mb-3 text-center">Please log in to proceed with checkout</p>
                                <a href="{{ route('login') }}" wire:navigate class="w-full bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 block text-center">
                                    Login to Checkout
                                </a>
                                <p class="text-xs text-gray-500 mt-2 text-center">
                                    Don't have an account? <a href="{{ route('register') }}" wire:navigate class="text-custom-teal-600 hover:text-custom-teal-700">Sign up here</a>
                                </p>
                            </div>
                        @endauth

                        <div class="text-center">
                            <button class="text-custom-teal-600 hover:text-custom-teal-700 font-medium transition-colors">
                                <i class="fas fa-lock mr-2"></i>Secure Checkout
                            </button>
                        </div>

                        <!-- Security Badges -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <p class="text-sm text-gray-600 mb-4 text-center">We accept</p>
                            <div class="flex justify-center space-x-4">
                                <i class="fab fa-cc-visa text-3xl text-gray-400"></i>
                                <i class="fab fa-cc-mastercard text-3xl text-gray-400"></i>
                                <i class="fab fa-cc-amex text-3xl text-gray-400"></i>
                                <i class="fab fa-cc-paypal text-3xl text-gray-400"></i>
                                <i class="fab fa-cc-stripe text-3xl text-gray-400"></i>
                            </div>
                        </div>

                        <!-- Trust Signals -->
                        <div class="mt-6 space-y-3">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-shield-alt text-green-500 mr-2"></i>
                                <span>30-day return policy</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-truck text-green-500 mr-2"></i>
                                <span>Free shipping on orders over ${{ $shipping_threshold }}</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-lock text-green-500 mr-2"></i>
                                <span>Secure payment processing</span>
                            </div>
                        </div>
                    </div>
                </aside>
            @endif
        </div>
    </main>
</div>
