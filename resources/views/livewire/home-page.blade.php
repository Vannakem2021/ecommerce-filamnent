<div>
    <!-- Hero Section with Sidebar Filter, Carousel, and Welcome -->
    <div class="bg-gray-50 dark:bg-gray-900 py-8">
        <div class="max-w-[95rem] mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Admin Welcome Banner -->
            @auth
              @if(auth()->user()->hasAnyRole(['admin', 'product-manager', 'order-manager', 'analytics-viewer']))
                <div class="bg-teal-50 border border-teal-200 rounded-lg p-4 dark:bg-teal-800/10 dark:border-teal-900 mb-6">
                  <div class="flex">
                    <div class="flex-shrink-0">
                      <svg class="flex-shrink-0 size-4 text-teal-600 mt-0.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="m22 2-5 10-5-5 10-5z"/>
                      </svg>
                    </div>
                    <div class="ms-3">
                      <h3 class="text-teal-800 font-semibold dark:text-white">
                        Welcome back, {{ auth()->user()->name }}!
                      </h3>
                      <p class="text-sm text-teal-700 dark:text-teal-400">
                        You're logged in as <strong>{{ ucwords(str_replace('-', ' ', auth()->user()->getRoleNames()->first())) }}</strong>.
                        <a href="/admin" target="_blank" class="font-medium underline hover:no-underline">Access your admin panel</a> to manage the store.
                      </p>
                    </div>
                  </div>
                </div>
              @endif
            @endauth

            <!-- Main Hero Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

                <!-- Expanded Carousel -->
                <div class="lg:col-span-3">
                    <x-hero.carousel />
                </div>

                <!-- Right - Welcome Section -->
                <div class="lg:col-span-1">
                    <x-hero.welcome-section />
                </div>
            </div>
        </div>
    </div>

    <!-- New Arrivals Section -->
    <section class="py-20 bg-gray-50 dark:bg-gray-900">
        <div class="max-w-xl mx-auto">
            <div class="text-center">
                <div class="relative flex flex-col items-center">
                    <h1 class="text-5xl font-bold dark:text-gray-200">New <span class="text-custom-teal-500">Arrivals</span></h1>
                    <div class="flex w-40 mt-2 mb-6 overflow-hidden rounded">
                        <div class="flex-1 h-2 bg-custom-teal-200"></div>
                        <div class="flex-1 h-2 bg-custom-teal-400"></div>
                        <div class="flex-1 h-2 bg-custom-teal-600"></div>
                    </div>
                </div>
                <p class="mb-12 text-base text-center text-gray-500 dark:text-gray-400">
                    Discover our latest products and stay ahead of the trends.
                </p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($newArrivals as $product)
                    <div wire:key="{{ $product->id }}">
                        <x-product-card :product="$product" />
                    </div>
                @endforeach
            </div>

            <!-- View All Products Button -->
            <div class="text-center mt-12">
                <a wire:navigate href="{{ route('all-products') }}"
                   class="inline-flex items-center px-8 py-3 bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold rounded-lg transition-colors duration-300">
                    <span>View All Products</span>
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Best Sellers Section -->
    <section class="py-20 bg-white dark:bg-gray-800">
        <div class="max-w-xl mx-auto">
            <div class="text-center">
                <div class="relative flex flex-col items-center">
                    <h1 class="text-5xl font-bold dark:text-gray-200">Best <span class="text-custom-teal-500">Sellers</span></h1>
                    <div class="flex w-40 mt-2 mb-6 overflow-hidden rounded">
                        <div class="flex-1 h-2 bg-custom-teal-200"></div>
                        <div class="flex-1 h-2 bg-custom-teal-400"></div>
                        <div class="flex-1 h-2 bg-custom-teal-600"></div>
                    </div>
                </div>
                <p class="mb-12 text-base text-center text-gray-500 dark:text-gray-400">
                    Our most popular products loved by customers worldwide.
                </p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($bestSellers as $product)
                    <div wire:key="bestseller-{{ $product->id }}">
                        <x-product-card :product="$product" />
                    </div>
                @endforeach
            </div>

            <!-- View All Products Button -->
            <div class="text-center mt-12">
                <a wire:navigate href="{{ route('all-products') }}"
                   class="inline-flex items-center px-8 py-3 bg-custom-teal-600 hover:bg-custom-teal-700 text-white font-semibold rounded-lg transition-colors duration-300">
                    <span>Shop All Best Sellers</span>
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
            </div>
        </div>
    </section>
</div>
