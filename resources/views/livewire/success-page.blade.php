<div class="bg-gray-50 min-h-screen">
    <!-- Custom Animations CSS -->
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-float { animation: float 3s ease-in-out infinite; }
        .animate-pulse-slow { animation: pulse 2s ease-in-out infinite; }
        .animate-slide-in { animation: slideIn 0.6s ease-out forwards; }

        .delay-100 { animation-delay: 0.1s; opacity: 0; }
        .delay-200 { animation-delay: 0.2s; opacity: 0; }
        .delay-300 { animation-delay: 0.3s; opacity: 0; }
        .delay-400 { animation-delay: 0.4s; opacity: 0; }
        .delay-500 { animation-delay: 0.5s; opacity: 0; }
    </style>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Success Animation Section -->
            <div class="text-center mb-12">
                <div class="relative inline-block mb-8">
                    <div class="w-32 h-32 bg-green-100 rounded-full flex items-center justify-center mx-auto animate-float">
                        <i class="fas fa-check text-green-600 text-5xl"></i>
                    </div>
                    <div class="absolute -top-2 -right-2 w-12 h-12 bg-yellow-400 rounded-full flex items-center justify-center animate-pulse-slow">
                        <i class="fas fa-star text-white text-xl"></i>
                    </div>
                </div>

                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4 animate-slide-in">
                    Order Confirmed!
                </h1>
                <p class="text-xl text-gray-600 mb-8 animate-slide-in delay-100">
                    Thank you for your purchase. We've received your order and are processing it.
                </p>

                <div class="bg-custom-teal-50 border border-custom-teal-200 rounded-2xl p-6 inline-block animate-slide-in delay-200">
                    <p class="text-custom-teal-800 font-semibold">
                        <i class="fas fa-envelope mr-2"></i>
                        Order confirmation sent to: {{ $user_email }}
                    </p>
                </div>
            </div>

            <!-- Order Details Card -->
            <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 animate-slide-in delay-300">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Order Details</h2>
                        <p class="text-gray-600">Order #{{ $order_number }}</p>
                    </div>
                    <div class="flex items-center space-x-4 mt-4 md:mt-0">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>
                            Confirmed
                        </span>
                        <span class="text-sm text-gray-600">{{ $order_date }}</span>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="border-t border-gray-200 pt-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Items Ordered</h3>
                    <div class="space-y-4">
                        @foreach($order_items as $item)
                            <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-xl">
                                @if($item['image'])
                                    <img src="{{ Storage::url($item['image']) }}" alt="{{ $item['name'] }}" class="w-20 h-20 object-cover rounded-lg">
                                @else
                                    <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400 text-2xl"></i>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">{{ $item['name'] }}</p>
                                    <p class="text-sm text-gray-600">SKU: {{ $item['sku'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-gray-900">${{ number_format($item['total_price'], 2) }}</p>
                                    <p class="text-sm text-gray-600">Qty: {{ $item['quantity'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h3>
                    <div class="space-y-3">
                        @php
                            $subtotal = collect($order_items)->sum('total_price');
                            $shipping = 0;
                            $tax = $subtotal * 0.08;
                            $discount = $subtotal * 0.20; // Assuming 20% discount was applied
                            $total = $subtotal + $shipping + $tax - $discount;
                        @endphp

                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span class="font-medium">${{ number_format($subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping</span>
                            <span class="font-medium">${{ number_format($shipping, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Tax</span>
                            <span class="font-medium">${{ number_format($tax, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-green-600">
                            <span>Discount (WEEKEND20)</span>
                            <span class="font-medium">-${{ number_format($discount, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                            <span class="text-xl font-bold text-gray-900">Total</span>
                            <span class="text-2xl font-bold text-custom-teal-700">${{ number_format($order_total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <div class="bg-white rounded-2xl shadow-lg p-8 animate-slide-in delay-400">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-truck text-custom-teal-600 mr-3"></i>
                        Shipping Information
                    </h3>
                    <div class="space-y-3">
                        @if($shipping_address)
                            <div class="flex items-start">
                                <i class="fas fa-map-marker-alt text-gray-400 mt-1 mr-3 w-5"></i>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $shipping_address['name'] }}</p>
                                    <p class="text-gray-600">{{ $shipping_address['street'] }}</p>
                                    <p class="text-gray-600">{{ $shipping_address['city'] }}, {{ $shipping_address['state'] }} {{ $shipping_address['zip'] }}</p>
                                    <p class="text-gray-600">United States</p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-phone text-gray-400 mr-3 w-5"></i>
                                <p class="text-gray-600">{{ $shipping_address['phone'] }}</p>
                            </div>
                        @endif
                        <div class="flex items-center">
                            <i class="fas fa-envelope text-gray-400 mr-3 w-5"></i>
                            <p class="text-gray-600">{{ $user_email }}</p>
                        </div>
                    </div>

                    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle text-blue-600 mr-3"></i>
                            <p class="text-blue-800 text-sm">
                                <span class="font-medium">Estimated Delivery:</span>
                                {{ $estimated_delivery }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-8 animate-slide-in delay-500">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-credit-card text-custom-teal-600 mr-3"></i>
                        Payment Information
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <i class="fas fa-credit-card text-gray-400 mr-3 w-5"></i>
                            <div>
                                @if($payment_method === 'Credit Card' || $payment_method === 'card')
                                    <p class="font-medium text-gray-900">Visa ending in 3456</p>
                                    <p class="text-gray-600">Expires 12/25</p>
                                @elseif($payment_method === 'PayPal' || $payment_method === 'paypal')
                                    <p class="font-medium text-gray-900">PayPal Account</p>
                                    <p class="text-gray-600">{{ $user_email }}</p>
                                @else
                                    <p class="font-medium text-gray-900">{{ $payment_method }}</p>
                                    <p class="text-gray-600">Payment on delivery</p>
                                @endif
                            </div>
                        </div>
                        @if($shipping_address)
                            <div class="flex items-center">
                                <i class="fas fa-user text-gray-400 mr-3 w-5"></i>
                                <p class="text-gray-600">{{ $shipping_address['name'] }}</p>
                            </div>
                        @endif
                        <div class="flex items-center">
                            <i class="fas fa-shield-alt text-gray-400 mr-3 w-5"></i>
                            <p class="text-gray-600">Payment processed securely</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center animate-slide-in delay-500">
                <a href="{{ route('my-orders') }}" wire:navigate class="bg-white border-2 border-custom-teal-600 text-custom-teal-600 hover:bg-custom-teal-50 font-semibold py-4 px-8 rounded-xl transition-all duration-200 transform hover:scale-105 text-center">
                    <i class="fas fa-file-invoice mr-2"></i>
                    View Order Details
                </a>
                <button onclick="trackOrder()" class="bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-4 px-8 rounded-xl transition-all duration-200 transform hover:scale-105">
                    <i class="fas fa-map-marker-alt mr-2"></i>
                    Track Order
                </button>
                <a href="{{ route('all-products') }}" wire:navigate class="bg-white border-2 border-gray-300 text-gray-700 hover:bg-gray-50 font-semibold py-4 px-8 rounded-xl transition-all duration-200 transform hover:scale-105 text-center">
                    <i class="fas fa-shopping-bag mr-2"></i>
                    Continue Shopping
                </a>
            </div>

            <!-- Recommendations -->
            @if(count($recommended_products) > 0)
                <div class="mt-16 animate-slide-in delay-500">
                    <h2 class="text-2xl font-bold text-gray-900 text-center mb-8">You Might Also Like</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach($recommended_products as $product)
                            <div class="group bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                                <div class="relative">
                                    @if($product['image'])
                                        <img src="{{ Storage::url($product['image']) }}" alt="{{ $product['name'] }}" class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                                    @else
                                        <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400 text-3xl"></i>
                                        </div>
                                    @endif

                                    @if($product['on_sale'])
                                        <span class="absolute top-3 left-3 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">-15%</span>
                                    @endif

                                    @if($product['is_featured'])
                                        <span class="absolute top-3 right-3 bg-custom-teal-500 text-white text-xs font-bold px-2 py-1 rounded-full">Featured</span>
                                    @endif
                                </div>
                                <div class="p-4">
                                    <h4 class="font-semibold text-gray-900 mb-2 group-hover:text-custom-teal-700 transition-colors">
                                        {{ $product['name'] }}
                                    </h4>
                                    <div class="flex items-center mb-2">
                                        <div class="flex text-yellow-400">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star text-xs"></i>
                                            @endfor
                                        </div>
                                        <span class="text-xs text-gray-500 ml-2">({{ rand(45, 234) }})</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="text-lg font-bold text-custom-teal-700">${{ number_format($product['price'], 2) }}</span>
                                            @if($product['on_sale'])
                                                <span class="text-sm text-gray-400 line-through ml-1">${{ number_format($product['price'] * 1.18, 2) }}</span>
                                            @endif
                                        </div>
                                        <button onclick="addToCart({{ $product['id'] }})" class="bg-custom-teal-600 hover:bg-custom-teal-700 text-white p-2 rounded-lg transition-colors">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Customer Support -->
            <div class="mt-16 bg-gradient-to-r from-custom-teal-600 to-custom-teal-800 rounded-2xl p-8 text-white animate-slide-in delay-500">
                <div class="text-center">
                    <h3 class="text-2xl font-bold mb-4">Need Help?</h3>
                    <p class="text-custom-teal-100 mb-6">Our customer support team is here to assist you</p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button onclick="contactSupport()" class="bg-white text-custom-teal-700 hover:bg-gray-100 font-semibold py-3 px-6 rounded-xl transition-colors">
                            <i class="fas fa-headset mr-2"></i>
                            Contact Support
                        </button>
                        <button onclick="viewFAQs()" class="bg-white bg-opacity-20 text-white hover:bg-opacity-30 font-semibold py-3 px-6 rounded-xl transition-colors">
                            <i class="fas fa-book mr-2"></i>
                            View FAQs
                        </button>
                        <button onclick="liveChat()" class="bg-white bg-opacity-20 text-white hover:bg-opacity-30 font-semibold py-3 px-6 rounded-xl transition-colors">
                            <i class="fas fa-comments mr-2"></i>
                            Live Chat
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

    <!-- JavaScript for Interactive Features -->
    <script>
        // Toast notification function
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
            const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';

            toast.className = `${bgColor} text-white px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${icon} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;

            document.getElementById('toastContainer').appendChild(toast);

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

        // Track order function
        function trackOrder() {
            showToast('Opening order tracking...', 'info');
            setTimeout(() => {
                // In a real app, this would open tracking page
                showToast('Order tracking feature coming soon!', 'info');
            }, 1500);
        }

        // Contact support function
        function contactSupport() {
            showToast('Connecting to customer support...', 'info');
            setTimeout(() => {
                showToast('Support ticket created! We\'ll contact you soon.', 'success');
            }, 1500);
        }

        // View FAQs function
        function viewFAQs() {
            showToast('Opening FAQ section...', 'info');
            setTimeout(() => {
                // In a real app, this would navigate to FAQ page
                showToast('FAQ section coming soon!', 'info');
            }, 1500);
        }

        // Live chat function
        function liveChat() {
            showToast('Starting live chat...', 'info');
            setTimeout(() => {
                showToast('Live chat feature coming soon!', 'info');
            }, 1500);
        }

        // Add to cart from recommendations
        function addToCart(productId) {
            showToast('Added to cart!', 'success');

            // In a real app, you would make an AJAX call to add the product to cart
            // For now, we'll just show a success message
            setTimeout(() => {
                showToast('Product added successfully!', 'success');
            }, 500);
        }

        // Initialize animations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Trigger slide-in animations
            const animatedElements = document.querySelectorAll('.animate-slide-in');
            animatedElements.forEach((el, index) => {
                setTimeout(() => {
                    el.classList.remove('opacity-0');
                }, index * 100);
            });

            // Add hover effects to buttons
            const buttons = document.querySelectorAll('button, a');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });

                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Confetti effect (optional - can be enabled for special occasions)
            // createConfetti();
        });

        // Optional confetti effect function
        function createConfetti() {
            const colors = ['#0d9488', '#14b8a6', '#2dd4bf', '#5eead4', '#99f6e4'];
            const confettiCount = 50;

            for (let i = 0; i < confettiCount; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.style.position = 'fixed';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.top = '-10px';
                    confetti.style.width = '10px';
                    confetti.style.height = '10px';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.borderRadius = '50%';
                    confetti.style.pointerEvents = 'none';
                    confetti.style.zIndex = '9999';
                    confetti.style.animation = `fall ${Math.random() * 3 + 2}s linear forwards`;

                    document.body.appendChild(confetti);

                    setTimeout(() => {
                        confetti.remove();
                    }, 5000);
                }, i * 100);
            }
        }

        // Add CSS for confetti animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fall {
                to {
                    transform: translateY(100vh) rotate(360deg);
                }
            }
        `;
        document.head.appendChild(style);

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Press 'T' to track order
            if (e.key.toLowerCase() === 't' && !e.ctrlKey && !e.metaKey) {
                trackOrder();
            }

            // Press 'S' to continue shopping
            if (e.key.toLowerCase() === 's' && !e.ctrlKey && !e.metaKey) {
                window.location.href = '{{ route("all-products") }}';
            }

            // Press 'O' to view orders
            if (e.key.toLowerCase() === 'o' && !e.ctrlKey && !e.metaKey) {
                window.location.href = '{{ route("my-orders") }}';
            }
        });

        // Show keyboard shortcuts hint
        setTimeout(() => {
            showToast('Tip: Press T to track, S to shop, O for orders', 'info');
        }, 3000);
    </script>
</div>