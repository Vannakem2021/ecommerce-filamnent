<div class="bg-gray-50 min-h-screen">
    <main class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filter Sidebar -->
            <aside class="lg:w-64 flex-shrink-0">
                <div class="bg-white rounded-xl shadow-md p-6 sticky top-24">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Filters</h2>
                        <button
                            wire:click="resetFilters"
                            class="text-sm text-custom-teal-600 hover:text-custom-teal-700"
                        >
                            Reset All
                        </button>
                    </div>

                    <!-- Categories Filter -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Categories</h3>
                        <div class="space-y-2">
                            @foreach ($categories as $category)
                                <label class="flex items-center cursor-pointer" wire:key="{{ $category->id }}">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selected_categories"
                                        id="{{ $category->slug }}"
                                        value="{{ $category->id }}"
                                        class="w-4 h-4 text-custom-teal-600 rounded focus:ring-custom-teal-500"
                                    >
                                    <span class="ml-2 text-gray-700">{{ $category->name }}</span>
                                    <span class="ml-auto text-sm text-gray-500">({{ $category->products_count ?? 0 }})</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <!-- Brands Filter -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Brands</h3>
                        <div class="space-y-2">
                            @foreach ($brands as $brand)
                                <label class="flex items-center cursor-pointer" wire:key="{{ $brand->id }}">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selected_brands"
                                        value="{{ $brand->id }}"
                                        id="{{ $brand->slug }}"
                                        class="w-4 h-4 text-custom-teal-600 rounded focus:ring-custom-teal-500"
                                    >
                                    <span class="ml-2 text-gray-700">{{ $brand->name }}</span>
                                    <span class="ml-auto text-sm text-gray-500">({{ $brand->products_count ?? 0 }})</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <!-- Quick Filter Options -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Quick Filters</h3>
                        <div class="space-y-2">
                            <label class="flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    id="on_sale"
                                    value="1"
                                    wire:model.live="on_sale"
                                    class="w-4 h-4 text-custom-teal-600 rounded focus:ring-custom-teal-500"
                                >
                                <span class="ml-2 text-gray-700">🏷️ On Sale</span>
                            </label>
                        </div>
                    </div>

                    <!-- Price Range Filter -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Price Range</h3>
                        <div class="space-y-4">
                            <div class="text-center">
                                <span class="font-semibold text-custom-teal-700">{{ Number::currency($price_range, 'USD') }}</span>
                            </div>
                            <div class="relative">
                                <input
                                    type="range"
                                    wire:model.live="price_range"
                                    class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-custom-teal-600"
                                    max="5000"
                                    value="0"
                                    step="10"
                                >
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>{{ Number::currency(10, 'USD') }}</span>
                                    <span>{{ Number::currency(5000, 'USD') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </aside>

            <!-- Products Section -->
            <div class="flex-1">
                <!-- Header with Sort and Results Count -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">All Products</h2>
                            <p class="text-gray-600 mt-1">
                                Showing <span class="font-semibold">{{ $products->count() }}</span> of <span class="font-semibold">{{ $products->total() }}</span> results
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <label class="text-gray-700">Sort by:</label>
                            <select
                                wire:model.live="sort"
                                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-custom-teal-500 focus:border-custom-teal-500"
                            >
                                <option value="latest">Newest First</option>
                                <option value="price">Price: Low to High</option>
                                <option value="price_desc">Price: High to Low</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                    @foreach ($products as $product)
                        <div wire:key="{{ $product->id }}">
                            <x-product-card :product="$product" />
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </main>
</div>
