<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Breadcrumb -->
        <nav class="flex items-center text-sm text-gray-500 mb-8">
            <a href="{{ route('index') }}" class="hover:text-teal-600 transition-colors">Home</a>
            <span class="mx-2">›</span>
            <a href="{{ route('all-products') }}" class="hover:text-teal-600 transition-colors">Products</a>
            <span class="mx-2">›</span>
            @if($product->category)
            <a href="{{ route('all-products') }}?selected_categories[]={{ $product->category->id }}" class="hover:text-teal-600 transition-colors">{{ $product->category->name }}</a>
            <span class="mx-2">›</span>
            @endif
            <span class="text-gray-700">{{ $product->name }}</span>
        </nav>

        <!-- Product Detail Section -->
        <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8 mb-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 md:gap-12">
                <!-- Product Images -->
                <div class="space-y-4"
                     x-data="{
                        mainImage: '{{ !empty($currentImages) ? asset('storage/' . $currentImages[0]) : 'https://via.placeholder.com/600x600?text=No+Image' }}',
                        isLoading: false,
                        changeImage(newSrc) {
                            if (this.mainImage !== newSrc) {
                                this.isLoading = true;
                                this.mainImage = newSrc;
                                setTimeout(() => this.isLoading = false, 300);
                            }
                        }
                     }"
                     @variant-images-updated.window="
                        if ($event.detail[0].currentImage && $event.detail[0].currentImage !== mainImage) {
                            changeImage($event.detail[0].currentImage);
                        }
                     "
                     wire:key="product-images-{{ $selectedVariant?->id ?? 'default' }}">
                    <div class="aspect-square overflow-hidden rounded-xl bg-gray-100 relative">
                        <!-- Discount Badge -->
                        @if($discountPercentage)
                        <div class="absolute top-4 left-4 z-10">
                            <span class="bg-red-500 text-white text-xs font-semibold px-3 py-1 rounded-full">
                                -{{ $discountPercentage }}% OFF
                            </span>
                        </div>
                        @endif

                        <!-- Variant Badge -->
                        @if($selectedVariant)
                        <div class="absolute top-4 right-4 z-10">
                            <span class="bg-blue-500 text-white text-xs font-semibold px-2 py-1 rounded shadow-lg">
                                {{ $selectedVariant->sku }}
                            </span>
                        </div>
                        @endif

                        <!-- Loading Overlay -->
                        <div x-show="isLoading"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-20">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
                        </div>

                        <img x-bind:src="mainImage"
                             alt="{{ $product->name }}"
                             class="w-full h-full object-cover transition-opacity duration-300"
                             x-bind:class="{ 'opacity-75': isLoading, 'opacity-100': !isLoading }"
                             @load="isLoading = false">
                    </div>

                    @if(!empty($currentImages) && count($currentImages) > 1)
                    <div class="flex gap-3 overflow-x-auto pb-2">
                        @foreach($currentImages as $index => $image)
                        <div class="relative flex-shrink-0">
                            <img src="{{ asset('storage/' . $image) }}"
                                 alt="Thumbnail {{ $index + 1 }}"
                                 class="w-20 h-20 object-cover rounded-lg cursor-pointer border-2 transition-all duration-200 hover:scale-105
                                        {{ $currentImage === $image ? 'border-teal-600 shadow-lg' : 'border-transparent hover:border-teal-400' }}"
                                 x-on:click="changeImage('{{ asset('storage/' . $image) }}');
                                            $el.parentElement.parentElement.querySelectorAll('img').forEach(img => {
                                                img.classList.remove('border-teal-600', 'shadow-lg');
                                                img.classList.add('border-transparent');
                                            });
                                            $el.classList.remove('border-transparent');
                                            $el.classList.add('border-teal-600', 'shadow-lg');"
                                 wire:click="setCurrentImage('{{ $image }}')">

                            <!-- Active Indicator -->
                            @if($currentImage === $image)
                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-teal-600 rounded-full flex items-center justify-center">
                                <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    <!-- Image Source Indicator -->
                    @if($selectedVariant && $selectedVariant->images)
                    <div class="text-center mt-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>
                            </svg>
                            Variant Images
                        </span>
                    </div>
                    @endif
                    @endif
                </div>

                <!-- Product Info -->
                <div class="space-y-6">
                    <div>
                        @if($discountPercentage)
                        <span class="inline-block bg-red-500 text-white text-xs font-semibold px-3 py-1 rounded-full mb-4">
                            -{{ $discountPercentage }}% OFF
                        </span>
                        @endif

                        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">
                            {{ $product->name }}
                        </h1>

                        <div class="flex items-center gap-4 mb-4">
                            <div class="flex text-yellow-400">
                                @for($i = 1; $i <= 5; $i++)
                                <svg class="w-5 h-5 fill-current {{ $i <= floor($averageRating) ? 'text-yellow-400' : 'text-gray-300' }}" viewBox="0 0 24 24">
                                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                </svg>
                                @endfor
                            </div>
                            <span class="text-gray-600">{{ $averageRating }} out of 5 ({{ $totalReviews }} reviews)</span>
                        </div>

                        <div class="flex items-baseline gap-4 mb-6">
                            @if($selectedVariant)
                                <!-- Specific variant selected - show exact price -->
                                <span class="text-4xl font-bold text-teal-700">{{ Number::currency($this->currentPrice, 'INR') }}</span>
                                @if($this->currentComparePrice && $this->currentComparePrice > $this->currentPrice)
                                <span class="text-xl text-gray-500 line-through">{{ Number::currency($this->currentComparePrice, 'INR') }}</span>
                                <span class="text-lg font-semibold text-red-500">{{ $this->discountPercentage }}% OFF</span>
                                @endif
                            @elseif($product->has_variants && $this->currentPriceRange)
                                <!-- No variant selected - show price range -->
                                <span class="text-4xl font-bold text-teal-700">
                                    {{ Number::currency($this->currentPriceRange['min'], 'INR') }} - {{ Number::currency($this->currentPriceRange['max'], 'INR') }}
                                </span>
                                <span class="text-sm text-gray-600 bg-gray-100 px-2 py-1 rounded">Price varies by options</span>
                            @else
                                <!-- Single price product or all variants same price -->
                                <span class="text-4xl font-bold text-teal-700">{{ Number::currency($this->currentPrice, 'INR') }}</span>
                                @if($this->currentComparePrice && $this->currentComparePrice > $this->currentPrice)
                                <span class="text-xl text-gray-500 line-through">{{ Number::currency($this->currentComparePrice, 'INR') }}</span>
                                <span class="text-lg font-semibold text-red-500">{{ $this->discountPercentage }}% OFF</span>
                                @endif
                            @endif
                        </div>
                    </div>

                    <p class="text-gray-600 leading-relaxed">
                        {{ $product->short_description ?: 'Experience premium quality with this exceptional product. Designed for those who demand the best, featuring superior craftsmanship and attention to detail.' }}
                    </p>

                    <!-- Product Options -->
                    @if($product->has_variants && !empty($productAttributes))
                    <div class="space-y-8">
                        @foreach($productAttributes as $attribute)
                        <div class="variant-attribute-group">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ $attribute->name }}
                                    @if($attribute->is_required)
                                        <span class="text-red-500 text-sm">*</span>
                                    @endif
                                </h3>
                                @if(isset($selectedAttributes[$attribute->id]))
                                    @php
                                        $selectedValue = $attribute->activeValues->where('id', $selectedAttributes[$attribute->id])->first();
                                    @endphp
                                    @if($selectedValue)
                                        <span class="text-sm text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                            Selected: {{ $selectedValue->value }}
                                        </span>
                                    @endif
                                @endif
                            </div>

                            @if(strtolower($attribute->name) === 'color')
                            <!-- Enhanced Color Selection -->
                            <div class="flex gap-3 flex-wrap">
                                @php
                                    // Get available values for this attribute based on current selections
                                    $availableValues = $product->getAvailableAttributeValues($attribute->id, $selectedAttributes);
                                    $availableValueIds = $availableValues->pluck('id')->toArray();
                                @endphp
                                @foreach($attribute->activeValues as $value)
                                    @php
                                        $isSelected = isset($selectedAttributes[$attribute->id]) && $selectedAttributes[$attribute->id] == $value->id;
                                        $isAvailable = in_array($value->id, $availableValueIds) || $isSelected;
                                    @endphp
                                    <div class="relative group">
                                        <button wire:click="selectAttributeValue({{ $attribute->id }}, {{ $value->id }})"
                                                class="w-12 h-12 rounded-full border-3 relative transition-all duration-200 transform hover:scale-110
                                                       {{ $isSelected ? 'border-teal-600 shadow-lg' : 'border-gray-300 hover:border-teal-400' }}
                                                       {{ !$isAvailable ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                style="background-color: {{ $value->color_code ?: (strtolower($value->value) === 'black' ? '#000000' :
                                                                            (strtolower($value->value) === 'white' ? '#FFFFFF' :
                                                                            (strtolower($value->value) === 'red' ? '#EF4444' :
                                                                            (strtolower($value->value) === 'blue' ? '#3B82F6' :
                                                                            (strtolower($value->value) === 'green' ? '#10B981' : '#6B7280'))))) }}"
                                                {{ !$isAvailable ? 'disabled' : '' }}>
                                            @if($isSelected)
                                                <span class="absolute inset-0 flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </span>
                                            @endif
                                            @if(!$isAvailable)
                                                <span class="absolute inset-0 flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </span>
                                            @endif
                                        </button>
                                        <!-- Tooltip -->
                                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                                            {{ $value->value }}
                                            @if(!$isAvailable)
                                                <br><span class="text-red-300">Out of stock</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @else
                            <!-- Enhanced Size/Other Attribute Selection -->
                            <div class="flex gap-3 flex-wrap">
                                @php
                                    // Get available values for this attribute based on current selections
                                    $availableValues = $product->getAvailableAttributeValues($attribute->id, $selectedAttributes);
                                    $availableValueIds = $availableValues->pluck('id')->toArray();
                                @endphp
                                @foreach($attribute->activeValues as $value)
                                    @php
                                        $isSelected = isset($selectedAttributes[$attribute->id]) && $selectedAttributes[$attribute->id] == $value->id;
                                        $isAvailable = in_array($value->id, $availableValueIds) || $isSelected;
                                    @endphp
                                    <button wire:click="selectAttributeValue({{ $attribute->id }}, {{ $value->id }})"
                                            class="px-4 py-3 border-2 rounded-lg font-medium transition-all duration-200 relative
                                                   {{ $isSelected
                                                      ? 'border-teal-600 bg-teal-50 text-teal-700 shadow-md'
                                                      : 'border-gray-300 hover:border-teal-400 hover:bg-gray-50' }}
                                                   {{ !$isAvailable ? 'opacity-50 cursor-not-allowed bg-gray-100' : '' }}"
                                            {{ !$isAvailable ? 'disabled' : '' }}>
                                        {{ $value->value }}
                                        @if(!$isAvailable)
                                            <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full"></span>
                                        @endif
                                        @if($isSelected)
                                            <span class="absolute -top-1 -right-1 w-3 h-3 bg-teal-500 rounded-full flex items-center justify-center">
                                                <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                            @endif

                            <!-- Attribute Description -->
                            @if($attribute->description)
                                <p class="text-sm text-gray-600 mt-2">{{ $attribute->description }}</p>
                            @endif
                        </div>
                        @endforeach

                        <!-- Variant Selection Status -->
                        @if($selectedVariant)
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-green-800 font-medium">Variant Selected</span>
                                </div>
                                <p class="text-green-700 text-sm mt-1">
                                    SKU: {{ $selectedVariant->sku }} |
                                    Stock: {{ $selectedVariant->stock_quantity }} available
                                </p>
                            </div>
                        @elseif($product->has_variants)
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-yellow-800 font-medium">Please Select Options</span>
                                </div>
                                <p class="text-yellow-700 text-sm mt-1">
                                    Choose your preferred options to see pricing and availability.
                                </p>
                            </div>
                        @endif
                    </div>
                    @endif

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Quantity</h3>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center border-2 border-gray-300 rounded-lg">
                                <button wire:click="decreaseQty"
                                        class="px-4 py-2 text-gray-600 hover:bg-gray-100 transition-colors"
                                        @if($quantity <= 1) disabled @endif>
                                    −
                                </button>
                                <span class="w-16 text-center py-2 text-gray-900 font-medium">
                                    {{ $quantity }}
                                </span>
                                <button wire:click="increaseQty"
                                        class="px-4 py-2 text-gray-600 hover:bg-gray-100 transition-colors"
                                        @if($quantity >= $stockQuantity) disabled @endif>
                                    +
                                </button>
                            </div>
                            @if($inStock)
                            <span class="text-sm text-gray-500">{{ $stockQuantity }} in stock</span>
                            @else
                            <span class="text-sm text-red-500">Out of stock</span>
                            @endif
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-4 pt-4">
                        @php
                            $canAddToCart = $inStock && (!$product->has_variants || $selectedVariant);
                        @endphp
                        <button wire:click="addToCart"
                                wire:loading.attr="disabled"
                                wire:target="addToCart"
                                @if(!$canAddToCart) disabled @endif
                                class="flex-1 font-semibold py-3 px-6 rounded-lg transition-colors duration-300 flex items-center justify-center gap-2
                                       {{ $canAddToCart
                                          ? 'bg-teal-600 hover:bg-teal-700 text-white'
                                          : 'bg-gray-400 text-gray-600 cursor-not-allowed' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            @if(!$inStock)
                                <span>Out of Stock</span>
                            @elseif($product->has_variants && !$selectedVariant)
                                <span>Select Options</span>
                            @else
                                <span wire:loading.remove wire:target="addToCart">Add to Cart</span>
                                <span wire:loading wire:target="addToCart">Adding...</span>
                            @endif
                        </button>
                        <button wire:click="toggleWishlist"
                                class="px-6 py-3 border-2 border-teal-600 text-teal-600 hover:bg-teal-600 hover:text-white font-semibold rounded-lg transition-colors duration-300 flex items-center justify-center gap-2
                                       {{ $isWishlisted ? 'bg-red-500 text-white border-red-500' : '' }}">
                            <svg class="w-5 h-5" fill="{{ $isWishlisted ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                            {{ $isWishlisted ? 'Wishlisted' : 'Wishlist' }}
                        </button>
                    </div>

                    <!-- Product Features -->
                    <div class="grid grid-cols-3 gap-4 pt-6 border-t border-gray-200">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h4 class="font-semibold text-gray-900">Free Shipping</h4>
                            <p class="text-sm text-gray-600">On orders over $50</p>
                        </div>
                        <div class="text-center">
                            <div class="w-12 h-12 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h4 class="font-semibold text-gray-900">30-Day Return</h4>
                            <p class="text-sm text-gray-600">Easy returns policy</p>
                        </div>
                        <div class="text-center">
                            <div class="w-12 h-12 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <h4 class="font-semibold text-gray-900">2-Year Warranty</h4>
                            <p class="text-sm text-gray-600">Full coverage</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Section -->
        <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8" x-data="{ activeTab: 'description' }">
            <div class="flex gap-8 border-b border-gray-200 mb-8">
                <button @click="activeTab = 'description'"
                        :class="activeTab === 'description' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-600 hover:text-teal-600'"
                        class="pb-4 px-2 font-semibold transition-colors">
                    Description
                </button>
                <button @click="activeTab = 'specifications'"
                        :class="activeTab === 'specifications' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-600 hover:text-teal-600'"
                        class="pb-4 px-2 font-semibold transition-colors">
                    Specifications
                </button>
                <button @click="activeTab = 'reviews'"
                        :class="activeTab === 'reviews' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-600 hover:text-teal-600'"
                        class="pb-4 px-2 font-semibold transition-colors">
                    Reviews ({{ $totalReviews }})
                </button>
            </div>

            <!-- Description Tab -->
            <div x-show="activeTab === 'description'" x-transition>
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Product Description</h3>
                @if($product->description)
                <div class="prose prose-gray max-w-none">
                    {!! Str::markdown($product->description) !!}
                </div>
                @else
                <div class="text-gray-600 leading-relaxed space-y-4">
                    <p>{{ $product->short_description ?: 'This premium product delivers exceptional quality and performance.' }}</p>
                    <p>Crafted with attention to detail and designed for those who appreciate excellence, this product combines functionality with style to meet your highest expectations.</p>
                    <p>Whether for personal use or as a gift, this item represents the perfect balance of quality, durability, and aesthetic appeal.</p>
                </div>
                @endif
            </div>

            <!-- Specifications Tab -->
            <div x-show="activeTab === 'specifications'" x-transition style="display: none;">
                <!-- Product Specifications Component -->
                <x-product-specifications :product="$product" :selectedVariant="$selectedVariant" />

                <!-- Basic Product Info -->
                <div class="mt-8 bg-gray-50 rounded-lg p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Product Information</h4>
                    <div class="space-y-3">
                        @if($product->sku)
                        <div class="flex justify-between py-3 border-b border-gray-200">
                            <span class="font-medium text-gray-900">SKU</span>
                            <span class="text-gray-600">{{ $product->sku }}</span>
                        </div>
                        @endif
                        @if($product->brand)
                        <div class="flex justify-between py-3 border-b border-gray-200">
                            <span class="font-medium text-gray-900">Brand</span>
                            <span class="text-gray-600">{{ $product->brand->name }}</span>
                        </div>
                        @endif
                        @if($product->category)
                        <div class="flex justify-between py-3 border-b border-gray-200">
                        <span class="font-medium text-gray-900">Category</span>
                        <span class="text-gray-600">{{ $product->category->name }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between py-3 border-b border-gray-200">
                        <span class="font-medium text-gray-900">Availability</span>
                        <span class="text-gray-600">{{ $inStock ? 'In Stock' : 'Out of Stock' }}</span>
                    </div>
                    @if($product->has_variants)
                    <div class="flex justify-between py-3 border-b border-gray-200">
                        <span class="font-medium text-gray-900">Variants Available</span>
                        <span class="text-gray-600">Yes</span>
                    </div>
                    @endif
                    <div class="flex justify-between py-3">
                        <span class="font-medium text-gray-900">Shipping</span>
                        <span class="text-gray-600">Free shipping on orders over $50</span>
                    </div>
                </div>
            </div>

            <!-- Reviews Tab -->
            <div x-show="activeTab === 'reviews'" x-transition style="display: none;">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Customer Reviews</h3>

                @if(!empty($reviews) && count($reviews) > 0)
                <div class="space-y-6">
                    @foreach($reviews as $review)
                    <div class="border-b border-gray-200 pb-6 last:border-b-0">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="font-semibold text-gray-900">{{ $review['author'] ?? 'Anonymous Customer' }}</h4>
                                <div class="flex text-yellow-400 mt-1">
                                    @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-4 h-4 fill-current {{ $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300' }}" viewBox="0 0 24 24">
                                        <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                    </svg>
                                    @endfor
                                </div>
                            </div>
                            <span class="text-sm text-gray-500">{{ $review['date'] ?? now()->subDays(rand(1, 30))->format('M d, Y') }}</span>
                        </div>
                        <p class="text-gray-600 leading-relaxed">{{ $review['comment'] }}</p>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">No reviews yet</h4>
                    <p class="text-gray-600">Be the first to review this product!</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- Variant Combinations Data -->
@if($product->has_variants && !empty($variantCombinationsMatrix))
<script type="application/json" data-variant-combinations>
{!! json_encode($variantCombinationsMatrix) !!}
</script>
@endif

<script>
    // Handle dynamic specification updates when variants change
    document.addEventListener('livewire:initialized', () => {
        // Listen for variant changes
        Livewire.on('variantChanged', (data) => {
            // Update specifications dynamically
            updateSpecifications(data.variantId);
        });

        // Listen for attribute selection changes
        Livewire.on('attributeSelectionChanged', (data) => {
            // Update frontend availability indicators
            updateAttributeAvailability(data.availabilityData);
        });
    });

    function updateSpecifications(variantId) {
        const productId = {{ $product->id }};
        const specContainer = document.querySelector('[data-specifications-container]');

        if (!specContainer) return;

        // Add loading state
        specContainer.classList.add('opacity-50');

        // Fetch updated specifications
        fetch(`/api/products/${productId}/specifications?variant=${variantId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Update specifications display
            updateSpecificationsDisplay(data.specifications);
        })
        .catch(error => {
            console.error('Error updating specifications:', error);
        })
        .finally(() => {
            // Remove loading state
            specContainer.classList.remove('opacity-50');
        });
    }

    function updateSpecificationsDisplay(specifications) {
        const specsList = document.querySelector('[data-specifications-list]');
        if (!specsList) return;

        // Clear existing specifications
        specsList.innerHTML = '';

        // Add updated specifications
        specifications.forEach(spec => {
            if (spec.formatted_value) {
                const specElement = createSpecificationElement(spec);
                specsList.appendChild(specElement);
            }
        });
    }

    function createSpecificationElement(spec) {
        const div = document.createElement('div');
        div.className = 'px-6 py-4 flex justify-between items-start';

        div.innerHTML = `
            <div class="flex-1">
                <dt class="text-sm font-medium text-gray-900">
                    ${spec.name}
                    ${spec.unit && spec.data_type === 'number' ? `<span class="text-gray-500 font-normal">(${spec.unit})</span>` : ''}
                </dt>
                ${spec.description ? `<dd class="text-xs text-gray-500 mt-1">${spec.description}</dd>` : ''}
            </div>

            <div class="flex-shrink-0 ml-4">
                <dd class="text-sm text-gray-900 font-medium">
                    ${spec.formatted_value}
                </dd>

                ${spec.scope === 'variant' ? `
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                        Variant
                    </span>
                ` : ''}
            </div>
        `;

        return div;
    }

    function updateAttributeAvailability(availabilityData) {
        // Update attribute value buttons based on availability
        Object.entries(availabilityData).forEach(([attributeId, data]) => {
            const attributeContainer = document.querySelector(`[data-attribute-id="${attributeId}"]`);
            if (!attributeContainer) return;

            // Update button states
            const buttons = attributeContainer.querySelectorAll('[data-value-id]');
            buttons.forEach(button => {
                const valueId = parseInt(button.dataset.valueId);
                const isAvailable = data.available_values.includes(valueId);

                if (isAvailable) {
                    button.classList.remove('opacity-50', 'cursor-not-allowed');
                    button.removeAttribute('disabled');
                } else {
                    button.classList.add('opacity-50', 'cursor-not-allowed');
                    button.setAttribute('disabled', 'true');
                }
            });
        });
    }
</script>
@endpush
