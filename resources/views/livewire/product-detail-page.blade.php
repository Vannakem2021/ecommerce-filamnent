<div class="w-full max-w-[85rem] py-8 px-4 sm:px-6 lg:px-8 mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-6 lg:p-8">

            <!-- Left Side - Image Gallery -->
            <div class="space-y-4" x-data="{ mainImage: '{{ !empty($currentImages) ? url('storage', $currentImages[0]) : 'https://via.placeholder.com/400x400?text=No+Image' }}' }">

                <!-- Main Image Container with Discount Badge -->
                <div class="relative aspect-square bg-gray-100 rounded-lg overflow-hidden">
                    <!-- Discount Badge -->
                    @if($discountPercentage)
                    <div class="absolute top-4 left-4 z-10">
                        <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                            -{{ $discountPercentage }}% OFF
                        </span>
                    </div>
                    @endif

                    <!-- Main Image -->
                    <img x-bind:src="mainImage"
                         alt="{{ $product->name }}"
                         class="w-full h-full object-cover">
                </div>

                <!-- Thumbnail Images -->
                @if(!empty($currentImages) && count($currentImages) > 1)
                <div class="flex space-x-2 overflow-x-auto pb-2">
                    @foreach($currentImages as $index => $image)
                    <div class="flex-shrink-0">
                        <img src="{{ url('storage', $image) }}"
                             alt="Thumbnail {{ $index + 1 }}"
                             class="w-16 h-16 object-cover rounded-lg cursor-pointer border-2 transition-colors
                                    {{ $loop->first ? 'border-teal-500' : 'border-gray-200 hover:border-teal-300' }}"
                             x-on:click="mainImage='{{ url('storage', $image) }}'"
                             wire:click="setCurrentImage('{{ $image }}')">
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Right Side - Product Information -->
            <div class="space-y-6">

                <!-- Product Title -->
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        {{ $product->name }}
                    </h1>

                    <!-- Rating and Reviews -->
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="flex items-center">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-5 h-5 {{ $i <= floor($averageRating) ? 'text-yellow-400' : 'text-gray-300' }}"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        <span class="text-gray-600 dark:text-gray-400">
                            {{ $averageRating }} out of 5 ({{ $totalReviews }} reviews)
                        </span>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="space-y-2">
                    <div class="flex items-baseline space-x-3">
                        <span class="text-3xl font-bold text-teal-600">
                            ${{ number_format($currentPrice, 2) }}
                        </span>
                        @if($currentComparePrice && $currentComparePrice > $currentPrice)
                        <span class="text-lg text-gray-500 line-through">
                            ${{ number_format($currentComparePrice, 2) }}
                        </span>
                        <span class="text-sm font-medium text-red-500">
                            -{{ $discountPercentage }}% OFF
                        </span>
                        @endif
                    </div>
                </div>

                <!-- Product Description -->
                <div class="prose prose-sm max-w-none text-gray-600 dark:text-gray-400">
                    {!! Str::markdown($product->short_description) !!}
                </div>

                <!-- Variant Selection -->
                @if($product->has_variants && !empty($productAttributes))
                <div class="space-y-4">
                    @foreach($productAttributes as $attribute)
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                            {{ $attribute->name }}
                        </h3>

                        @if(strtolower($attribute->name) === 'color')
                        <!-- Color Selection -->
                        <div class="flex space-x-2">
                            @foreach($attribute->activeValues as $value)
                            <button wire:click="selectAttributeValue({{ $attribute->id }}, {{ $value->id }})"
                                    class="w-8 h-8 rounded-full border-2 transition-all duration-200 relative
                                           {{ isset($selectedAttributes[$attribute->id]) && $selectedAttributes[$attribute->id] == $value->id
                                              ? 'border-teal-500 ring-2 ring-teal-200'
                                              : 'border-gray-300 hover:border-gray-400' }}"
                                    style="background-color: {{ strtolower($value->value) === 'black' ? '#000000' :
                                                                (strtolower($value->value) === 'red' ? '#EF4444' :
                                                                (strtolower($value->value) === 'blue' ? '#3B82F6' : '#6B7280')) }}">
                                @if(isset($selectedAttributes[$attribute->id]) && $selectedAttributes[$attribute->id] == $value->id)
                                <svg class="w-4 h-4 text-white absolute inset-0 m-auto" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                @endif
                            </button>
                            @endforeach
                        </div>
                        @else
                        <!-- Size/Other Attribute Selection -->
                        <div class="flex flex-wrap gap-2">
                            @foreach($attribute->activeValues as $value)
                            <button wire:click="selectAttributeValue({{ $attribute->id }}, {{ $value->id }})"
                                    class="px-4 py-2 border rounded-lg text-sm font-medium transition-colors
                                           {{ isset($selectedAttributes[$attribute->id]) && $selectedAttributes[$attribute->id] == $value->id
                                              ? 'border-teal-500 bg-teal-500 text-white'
                                              : 'border-gray-300 text-gray-700 hover:border-teal-300 dark:text-gray-300 dark:border-gray-600' }}">
                                {{ $value->value }}
                            </button>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif

                <!-- Quantity Selection -->
                <div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Quantity:</h3>
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center border border-gray-300 rounded-lg">
                            <button wire:click="decreaseQty"
                                    class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-50 rounded-l-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                </svg>
                            </button>
                            <input type="number"
                                   wire:model="quantity"
                                   readonly
                                   class="w-16 px-3 py-2 text-center border-0 focus:ring-0 bg-transparent">
                            <button wire:click="increaseQty"
                                    class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-50 rounded-r-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        </div>

                        @if($inStock)
                        <span class="text-sm text-green-600">
                            {{ $stockQuantity }} in stock
                        </span>
                        @else
                        <span class="text-sm text-red-600">
                            Out of stock
                        </span>
                        @endif
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-4">
                    <button wire:click="addToCart"
                            @if(!$inStock) disabled @endif
                            class="flex-1 bg-teal-600 text-white px-6 py-3 rounded-lg font-medium
                                   hover:bg-teal-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed
                                   flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 6M7 13l-1.5-6M17 21a2 2 0 100-4 2 2 0 000 4zM9 21a2 2 0 100-4 2 2 0 000 4z"/>
                        </svg>
                        <span wire:loading.remove>Add to Cart</span>
                        <span wire:loading>Adding...</span>
                    </button>

                    <button wire:click="toggleWishlist"
                            class="px-4 py-3 border border-gray-300 rounded-lg hover:border-teal-300 transition-colors
                                   {{ $isWishlisted ? 'text-red-500 border-red-300' : 'text-gray-600' }}">
                        <svg class="w-5 h-5" fill="{{ $isWishlisted ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
