@props(['product'])

<div class="w-full">
    <!-- Product Card -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden transform transition-all duration-300 hover:shadow-2xl hover:-translate-y-1 group">
        <!-- Product Image Section -->
        <div class="relative overflow-hidden">
            <a wire:navigate href="{{ route('product-details', $product->slug) }}" class="">
                <img
                    src="{{ $product->images && count($product->images) > 0 ? url('storage', $product->images[0]) : 'https://via.placeholder.com/400x300?text=No+Image' }}"
                    alt="{{ $product->name }}"
                    class="w-full h-64 object-cover transition-transform duration-500 group-hover:scale-110"
                >
            </a>

            <!-- Sale Badge -->
            @if($product->on_sale && $product->compare_price)
                <div class="absolute top-4 left-4">
                    <span class="bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg">
                        {{ round((($product->compare_price - $product->price) / $product->compare_price) * 100) }}% OFF
                    </span>
                </div>
            @endif

            <!-- Featured Badge -->
            @if($product->is_featured)
                <div class="absolute top-4 right-4">
                    <span class="bg-yellow-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg">
                        FEATURED
                    </span>
                </div>
            @endif

            <!-- Variants Badge -->
            @if($product->has_variants && $product->variants && $product->variants->isNotEmpty())
                <div class="absolute bottom-4 left-4">
                    <span class="bg-blue-500 text-white text-xs font-bold px-2 py-1 rounded shadow-lg">
                        {{ $product->variants->count() }} Options
                    </span>
                </div>
            @endif

            <!-- Hover Actions -->
            <div class="absolute bottom-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                <button 
                    wire:click.prevent="quickView({{ $product->id }})"
                    class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-md hover:bg-custom-teal-600 hover:text-white transition-all duration-300 transform hover:scale-110"
                    title="Quick View"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </button>
                <button 
                    wire:click.prevent="toggleWishlist({{ $product->id }})"
                    class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-md hover:bg-red-500 hover:text-white transition-all duration-300 transform hover:scale-110"
                    title="Add to Wishlist"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Product Info Section -->
        <div class="p-6">
            <!-- Category -->
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-2">
                {{ $product->category->name ?? 'Uncategorized' }}
            </p>

            <!-- Product Name -->
            <h3 class="text-lg font-bold text-gray-900 mb-3 overflow-hidden text-ellipsis group-hover:text-custom-teal-700 transition-colors" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                {{ $product->name }}
            </h3>

            <!-- Short Description -->
            @if($product->short_description)
            <div class="mb-3">
                <p class="text-sm text-gray-600 line-clamp-1">
                    {{ $product->short_description }}
                </p>
            </div>
            @endif

            <!-- Rating -->
            <div class="flex items-center mb-4">
                <div class="flex text-yellow-400">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="w-4 h-4 {{ $i <= 4 ? 'fill-current' : 'text-gray-300 fill-current' }}" viewBox="0 0 24 24">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>
                    @endfor
                </div>
                <span class="text-sm text-gray-500 ml-2">({{ rand(10, 200) }} reviews)</span>
            </div>

            <!-- Variant Options Preview (for products with variants) -->
            @if($product->has_variants && $product->variants && $product->variants->isNotEmpty())
                @php
                    $availableColors = collect();
                    try {
                        // Safely get color attributes from variants
                        foreach ($product->variants as $variant) {
                            if ($variant->attributeValues && $variant->attributeValues->isNotEmpty()) {
                                $colorValues = $variant->attributeValues->filter(function($attributeValue) {
                                    return $attributeValue->attribute &&
                                           strtolower($attributeValue->attribute->name) === 'color';
                                });
                                $availableColors = $availableColors->merge($colorValues);
                            }
                        }
                        $availableColors = $availableColors->unique('id')->take(4);
                    } catch (Exception $e) {
                        $availableColors = collect();
                    }
                @endphp

                @if($availableColors->isNotEmpty())
                <div class="mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Colors:</span>
                        <div class="flex gap-1">
                            @foreach($availableColors as $color)
                                <div class="w-4 h-4 rounded-full border border-gray-300"
                                     style="background-color: {{ $color->color_code ?? '#6B7280' }}"
                                     title="{{ $color->value ?? 'Color' }}">
                                </div>
                            @endforeach
                            @php
                                $totalColors = 0;
                                try {
                                    $allColors = collect();
                                    foreach ($product->variants as $variant) {
                                        if ($variant->attributeValues && $variant->attributeValues->isNotEmpty()) {
                                            $colorValues = $variant->attributeValues->filter(function($attributeValue) {
                                                return $attributeValue->attribute &&
                                                       strtolower($attributeValue->attribute->name) === 'color';
                                            });
                                            $allColors = $allColors->merge($colorValues);
                                        }
                                    }
                                    $totalColors = $allColors->unique('id')->count();
                                } catch (Exception $e) {
                                    $totalColors = 0;
                                }
                            @endphp
                            @if($totalColors > 4)
                                <span class="text-xs text-gray-500">+{{ $totalColors - 4 }} more</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            @endif

            <!-- Price Section -->
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-baseline gap-2">
                    @php
                        $displayPrice = $product->price ?? 0;
                        $showPriceRange = false;
                        $minPrice = 0;
                        $maxPrice = 0;

                        if ($product->has_variants && $product->variants && $product->variants->isNotEmpty()) {
                            try {
                                // Use simplified final_price accessor instead of complex price_cents queries
                                $activePrices = $product->variants->where('is_active', true)->pluck('final_price')->filter();
                                if ($activePrices->isNotEmpty()) {
                                    $minPrice = $activePrices->min() / 100;
                                    $maxPrice = $activePrices->max() / 100;
                                    $showPriceRange = $minPrice != $maxPrice;
                                    $displayPrice = $minPrice;
                                }
                            } catch (Exception $e) {
                                $displayPrice = $product->price ?? 0;
                            }
                        }
                    @endphp

                    @if($showPriceRange)
                        <span class="text-2xl font-bold text-custom-teal-700">
                            {{ Number::currency($minPrice, 'INR') }} - {{ Number::currency($maxPrice, 'INR') }}
                        </span>
                    @else
                        <span class="text-2xl font-bold text-custom-teal-700">
                            {{ Number::currency($displayPrice, 'INR') }}
                        </span>
                    @endif

                    @if($product->compare_price && $product->compare_price > ($product->price ?? 0))
                        <span class="text-sm text-gray-400 line-through">
                            {{ Number::currency($product->compare_price, 'INR') }}
                        </span>
                    @endif
                </div>
                @if($product->compare_price && $product->compare_price > ($product->price ?? 0))
                    <span class="text-sm font-semibold text-red-500 bg-red-50 px-2 py-1 rounded">
                        Save {{ Number::currency($product->compare_price - ($product->price ?? 0), 'INR') }}
                    </span>
                @endif
            </div>

            <!-- Add to Cart Button -->
            @if($product->has_variants && $product->variants && $product->variants->isNotEmpty())
                <!-- For products with variants, show "View Options" button -->
                <a href="{{ route('product-details', $product->slug) }}"
                   wire:navigate
                   class="w-full bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-300 flex items-center justify-center gap-2 group-hover:shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <span>View Options</span>
                </a>
            @else
                <!-- For products without variants, show regular Add to Cart -->
                <button
                    wire:click.prevent='addToCart({{ $product->id }})'
                    class="w-full bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-300 flex items-center justify-center gap-2 group-hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                    wire:loading.attr="disabled"
                    wire:target="addToCart({{ $product->id }})"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span wire:loading.remove wire:target='addToCart({{ $product->id }})'>Add to Cart</span>
                    <span wire:loading wire:target='addToCart({{ $product->id }})'>Adding...</span>
                </button>
            @endif

            <!-- Additional Features -->
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
                <div class="flex items-center gap-1 text-xs text-gray-500">
                    <svg class="w-4 h-4 text-custom-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Free Shipping</span>
                </div>
                <div class="flex items-center gap-1 text-xs text-gray-500">
                    <svg class="w-4 h-4 text-custom-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>1 Year Warranty</span>
                </div>
            </div>
        </div>
    </div>
</div>
