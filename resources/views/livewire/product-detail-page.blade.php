<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-8">
            <a href="{{ route('index') }}" class="hover:text-teal-600 transition-colors">Home</a>
            <span>/</span>
            <a href="{{ route('all-products') }}" class="hover:text-teal-600 transition-colors">Products</a>
            <span>/</span>
            @if($product->category)
            <a href="{{ route('all-products') }}?selected_categories[]={{ $product->category->id }}" class="hover:text-teal-600 transition-colors">{{ $product->category->name }}</a>
            <span>/</span>
            @endif
            <span class="text-gray-900">{{ $product->name }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Product Images -->
            <div class="space-y-4"
                 x-data="{
                    selectedImage: 0,
                    images: [
                        @if(!empty($currentImages))
                            @foreach($currentImages as $image)
                                '{{ asset('storage/' . $image) }}',
                            @endforeach
                        @else
                            'https://via.placeholder.com/600x600?text=No+Image'
                        @endif
                    ]
                 }">
                <!-- Main Image -->
                <div class="aspect-square bg-white rounded-2xl shadow-lg overflow-hidden">
                    <img x-bind:src="images[selectedImage]"
                         alt="{{ $product->name }}"
                         class="w-full h-full object-cover">
                </div>

                <!-- Thumbnail Images -->
                @if(!empty($currentImages) && count($currentImages) > 1)
                <div class="grid grid-cols-4 gap-4">
                    @foreach($currentImages as $index => $image)
                        <button @click="selectedImage = {{ $index }}"
                                x-bind:class="selectedImage === {{ $index }} ? 'border-teal-500 shadow-lg' : 'border-transparent hover:border-gray-300'"
                                class="aspect-square bg-white rounded-lg shadow-md overflow-hidden border-2 transition-all">
                            <img src="{{ asset('storage/' . $image) }}"
                                 alt="{{ $product->name }} view {{ $index + 1 }}"
                                 class="w-full h-full object-cover">
                        </button>
                    @endforeach
                </div>
                @endif
            </div>


          <!-- Product Information -->
          <div class="space-y-8">
            <!-- Header -->
            <div>

              <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $product->name }}</h1>

              <!-- Dynamic Price Display -->
              <div class="mb-4">
                @if($selectedVariant)
                  <!-- Specific variant selected - show exact price -->
                  <div class="flex items-baseline gap-4">
                    <span class="text-3xl font-bold text-teal-600">{{ $this->getCurrentPriceFormatted() }}</span>
                    @if($this->getCurrentComparePrice() && $this->getCurrentComparePrice() > $this->getCurrentPrice())
                      <span class="text-lg text-gray-500 line-through">{{ $this->getCurrentComparePriceFormatted() }}</span>
                      <span class="text-sm font-semibold text-red-500 bg-red-50 px-2 py-1 rounded">{{ $this->getDiscountPercentage() }}% OFF</span>
                    @endif
                  </div>
                  <p class="text-sm text-gray-600 mt-1">
                    Selected: {{ $selectedColor }} {{ $selectedStorage }}
                  </p>
                @elseif($product->has_variants && ($selectedColor || $selectedStorage))
                  <!-- Partial selection - show specific price if possible -->
                  <div class="flex items-baseline gap-4">
                    <span class="text-3xl font-bold text-teal-600">{{ $this->getCurrentPriceFormatted() }}</span>
                    @if($this->getCurrentComparePrice() && $this->getCurrentComparePrice() > $this->getCurrentPrice())
                      <span class="text-lg text-gray-500 line-through">{{ $this->getCurrentComparePriceFormatted() }}</span>
                      <span class="text-sm font-semibold text-red-500 bg-red-50 px-2 py-1 rounded">{{ $this->getDiscountPercentage() }}% OFF</span>
                    @endif
                  </div>
                  <p class="text-sm text-gray-600 mt-1">
                    @if($selectedColor && $selectedStorage)
                      Selected: {{ $selectedColor }} {{ $selectedStorage }}
                    @elseif($selectedColor)
                      Color: {{ $selectedColor }} - Select storage to complete
                    @elseif($selectedStorage)
                      Storage: {{ $selectedStorage }} - Select color to complete
                    @endif
                  </p>
                @elseif($product->has_variants && $this->getCurrentPriceRange())
                  <!-- No variant selected - show price range -->
                  <div class="flex items-baseline gap-4">
                    <span class="text-3xl font-bold text-teal-600">{{ $this->getCurrentPriceRangeFormatted() }}</span>
                    <span class="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-full">Price varies by options</span>
                  </div>
                  <p class="text-sm text-gray-600 mt-1">
                    Select color and storage to see exact price
                  </p>
                @else
                  <!-- Single price product or all variants same price -->
                  <div class="flex items-baseline gap-4">
                    <span class="text-3xl font-bold text-teal-600">{{ $this->getCurrentPriceFormatted() }}</span>
                    @if($this->getCurrentComparePrice() && $this->getCurrentComparePrice() > $this->getCurrentPrice())
                      <span class="text-lg text-gray-500 line-through">{{ $this->getCurrentComparePriceFormatted() }}</span>
                      <span class="text-sm font-semibold text-red-500 bg-red-50 px-2 py-1 rounded">{{ $this->getDiscountPercentage() }}% OFF</span>
                    @endif
                  </div>
                @endif
              </div>
            </div>

            <!-- Simple Color + Storage Variant Selection -->
            @if($product->has_variants)
              <!-- Color Selection -->
              @if(!empty($availableColors))
                <div class="mb-6">
                  <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Color: {{ $selectedColor ?? 'Select Color' }}
                  </h3>
                  <div class="flex gap-3">
                    @foreach($availableColors as $color)
                      @php
                        $isSelected = $selectedColor === $color;
                        $colorCode = match(strtolower($color)) {
                          'natural titanium' => '#8B7355',
                          'blue titanium', 'blue' => '#1E40AF',
                          'white titanium', 'white', 'silver' => '#F8FAFC',
                          'black titanium', 'black', 'space black' => '#1F2937',
                          'gold' => '#F59E0B',
                          'red' => '#EF4444',
                          'green' => '#10B981',
                          default => '#6B7280'
                        };
                        $isLight = in_array(strtolower($color), ['white titanium', 'white', 'silver']);
                      @endphp
                      <button wire:click="selectColor('{{ $color }}')"
                              class="w-12 h-12 rounded-full border-4 transition-all {{ $isSelected ? 'border-teal-500 shadow-lg scale-110' : 'border-gray-300 hover:border-gray-400' }}"
                              style="background-color: {{ $colorCode }}"
                              aria-label="{{ $color }}"
                              title="{{ $color }}">
                        @if($isSelected)
                          <svg class="w-6 h-6 mx-auto"
                               style="color: {{ $isLight ? '#000' : '#fff' }}"
                               fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                          </svg>
                        @endif
                      </button>
                    @endforeach
                  </div>
                </div>
              @endif

              <!-- Storage Selection -->
              @if(!empty($availableStorage))
                <div class="mb-6">
                  <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Storage: {{ $selectedStorage ?? 'Select Storage' }}
                  </h3>
                  <div class="grid grid-cols-2 gap-3">
                    @foreach($availableStorage as $storage)
                      @php
                        $isSelected = $selectedStorage === $storage;
                        // Find variant with this storage - try with current color first, then any color
                        $variant = null;
                        if ($selectedColor) {
                          $variant = $product->findVariant($selectedColor, $storage);
                        } else {
                          // If no color selected, find any variant with this storage to show price
                          foreach($availableColors as $color) {
                            $variant = $product->findVariant($color, $storage);
                            if ($variant) break;
                          }
                        }
                      @endphp
                      <button wire:click="selectStorage('{{ $storage }}')"
                              class="p-4 rounded-lg border-2 text-center transition-all {{ $isSelected ? 'border-teal-500 bg-teal-50 text-teal-700' : 'border-gray-200 hover:border-gray-300 bg-white' }}">
                        <div class="font-semibold">{{ $storage }}</div>
                        @if($variant)
                          <div class="text-sm {{ $isSelected ? 'text-teal-600' : 'text-gray-600' }} font-medium">
                            ${{ number_format($variant->getFinalPrice(), 2) }}
                          </div>
                        @endif
                      </button>
                    @endforeach
                  </div>
                </div>
              @endif
            @endif

            <!-- Quantity -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Quantity</h3>
              <div class="flex items-center gap-4">
                <div class="flex items-center border border-gray-300 rounded-lg">
                  <button wire:click="decreaseQty"
                          class="p-2 hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent"
                          @if($quantity <= 1) disabled @endif>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                    </svg>
                  </button>
                  <span class="px-4 py-2 font-semibold min-w-[3rem] text-center">{{ $quantity }}</span>
                  <button wire:click="increaseQty"
                          class="p-2 hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent"
                          @if($quantity >= $stockQuantity) disabled @endif>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                  </button>
                </div>
                <div class="text-sm text-gray-600">
                  Available: {{ $stockQuantity }} {{ $stockQuantity === 1 ? 'item' : 'items' }}
                </div>
              </div>
            </div>

            <!-- Stock Status Section - More Prominent -->
            <div class="bg-gray-50 rounded-xl p-4 mb-6">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  @if($displayStock['status'] === 'out_of_stock')
                    <div class="flex items-center gap-2">
                      <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                      <span class="text-red-700 font-semibold text-lg">{{ $displayStock['message'] }}</span>
                    </div>
                  @elseif($displayStock['status'] === 'low_stock')
                    <div class="flex items-center gap-2">
                      <div class="w-3 h-3 bg-orange-500 rounded-full animate-pulse"></div>
                      <span class="text-orange-700 font-semibold text-lg">{{ $displayStock['message'] }}</span>
                    </div>
                  @else
                    <div class="flex items-center gap-2">
                      <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                      <span class="text-green-700 font-semibold text-lg">{{ $displayStock['message'] }}</span>
                    </div>
                  @endif
                </div>

                @if($displayStock['status'] !== 'out_of_stock')
                  <div class="text-sm text-gray-600">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Ready to ship
                  </div>
                @endif
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-4">
              <div class="flex gap-4">
                <button wire:click="addToCart"
                        wire:loading.attr="disabled"
                        wire:target="addToCart"
                        @if(!$this->canAddToCart) disabled @endif
                        class="flex-1 bg-teal-600 text-white px-8 py-4 rounded-xl font-semibold hover:bg-teal-700 transition-colors flex items-center justify-center gap-2">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                  </svg>
                  Add to Cart
                </button>
                <button wire:click="toggleWishlist"
                        class="p-4 rounded-xl border-2 transition-all {{ $isWishlisted ? 'border-red-500 bg-red-50 text-red-600' : 'border-gray-300 hover:border-gray-400' }}">
                  <svg class="w-6 h-6 {{ $isWishlisted ? 'fill-current' : '' }}" fill="{{ $isWishlisted ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                  </svg>
                </button>
                <button class="p-4 rounded-xl border-2 border-gray-300 hover:border-gray-400 transition-colors">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                  </svg>
                </button>
              </div>
              <button wire:click="buyNow"
                      wire:loading.attr="disabled"
                      wire:target="buyNow"
                      @if(!$this->canAddToCart) disabled @endif
                      class="w-full bg-gray-900 text-white px-8 py-4 rounded-xl font-semibold hover:bg-gray-800 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                <svg wire:loading wire:target="buyNow" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span wire:loading.remove wire:target="buyNow">Buy Now</span>
                <span wire:loading wire:target="buyNow">Processing...</span>
              </button>
            </div>

            <!-- Features -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-8 border-t border-gray-200">
              <div class="flex items-center gap-3">
                <div class="p-2 bg-teal-100 rounded-lg">
                  <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                  </svg>
                </div>
                <div>
                  <div class="font-semibold text-gray-900">Free Delivery</div>
                  <div class="text-sm text-gray-600">2-day shipping</div>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="p-2 bg-teal-100 rounded-lg">
                  <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                  </svg>
                </div>
                <div>
                  <div class="font-semibold text-gray-900">Warranty</div>
                  <div class="text-sm text-gray-600">1 year coverage</div>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="p-2 bg-teal-100 rounded-lg">
                  <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                  </svg>
                </div>
                <div>
                  <div class="font-semibold text-gray-900">Returns</div>
                  <div class="text-sm text-gray-600">30-day policy</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Product Details -->
        <div class="mt-16 grid grid-cols-1 lg:grid-cols-2 gap-12">
          <!-- Description -->
          <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Product Details</h2>
            <div class="prose prose-teal max-w-none">
              @if($product->description)
                {!! Str::markdown($product->description) !!}
              @else
                <p class="text-gray-700 leading-relaxed mb-4">
                  {{ $product->short_description ?: 'Experience premium quality with this exceptional product. Designed for those who demand the best, featuring superior craftsmanship and attention to detail.' }}
                </p>
                <ul class="space-y-2 text-gray-700">
                  <li>• Premium quality materials and construction</li>
                  <li>• Advanced features for enhanced performance</li>
                  <li>• Sleek and modern design</li>
                  <li>• Easy to use and maintain</li>
                  <li>• Backed by comprehensive warranty</li>
                  <li>• Trusted by customers worldwide</li>
                </ul>
              @endif
            </div>
          </div>

          <!-- Specifications -->
          <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Specifications</h2>
            <div class="space-y-4">
              @php
                $specifications = [
                  ['label' => 'Brand', 'value' => $product->brand->name ?? 'N/A'],
                  ['label' => 'Category', 'value' => $product->category->name ?? 'N/A'],
                  ['label' => 'SKU', 'value' => $product->sku ?? 'N/A'],
                  ['label' => 'Availability', 'value' => $inStock ? 'In Stock' : 'Out of Stock'],
                  ['label' => 'Shipping', 'value' => 'Free delivery on orders over $50'],
                  ['label' => 'Warranty', 'value' => '1 year manufacturer warranty'],
                  ['label' => 'Return Policy', 'value' => '30-day return policy'],
                  ['label' => 'Support', 'value' => '24/7 customer support']
                ];
              @endphp
              @foreach($specifications as $spec)
                <div class="flex justify-between py-3 border-b border-gray-200">
                  <span class="font-medium text-gray-900">{{ $spec['label'] }}</span>
                  <span class="text-gray-600">{{ $spec['value'] }}</span>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>

<script>
  // Initialize image gallery and price updates
  document.addEventListener('DOMContentLoaded', function() {
    // Listen for Livewire events
    Livewire.on('priceUpdated', (data) => {
      console.log('Price updated:', data);
      // The Livewire component will automatically re-render the price section
    });

    Livewire.on('productDataRefreshed', (data) => {
      console.log('Product data refreshed:', data);
      // The Livewire component will automatically re-render
    });

    Livewire.on('variantChanged', (data) => {
      console.log('Variant changed:', data);
      // The Livewire component will automatically re-render
    });
  });

  // Function to refresh product data (can be called externally)
  function refreshProductData() {
    Livewire.dispatch('refreshProductData');
  }
</script>