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

                <!-- Left Sidebar - Filters -->
                <div class="lg:col-span-1">
                    <!-- Categories Filter -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Categories</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            @foreach($categories->take(5) as $category)
                            <label class="flex items-center justify-between cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded">
                                <div class="flex items-center">
                                    <input type="checkbox" class="w-4 h-4 text-teal-600 bg-gray-100 border-gray-300 rounded focus:ring-teal-500 dark:focus:ring-teal-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">{{ $category->name }}</span>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ rand(50, 300) }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Brands Filter -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Brands</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            @foreach($brands->take(5) as $brand)
                            <label class="flex items-center justify-between cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded">
                                <div class="flex items-center">
                                    <input type="checkbox" class="w-4 h-4 text-teal-600 bg-gray-100 border-gray-300 rounded focus:ring-teal-500 dark:focus:ring-teal-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">{{ $brand->name }}</span>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ rand(20, 100) }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Price Range Filter -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Price Range</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-sm text-gray-600 dark:text-gray-400">$ 0</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">$ 10000</span>
                            </div>
                            <input type="range" min="0" max="10000" value="5000" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700 slider">
                            <style>
                                .slider::-webkit-slider-thumb {
                                    appearance: none;
                                    height: 20px;
                                    width: 20px;
                                    border-radius: 50%;
                                    background: #0d9488;
                                    cursor: pointer;
                                }
                                .slider::-moz-range-thumb {
                                    height: 20px;
                                    width: 20px;
                                    border-radius: 50%;
                                    background: #0d9488;
                                    cursor: pointer;
                                    border: none;
                                }
                            </style>
                        </div>
                    </div>
                </div>

                <!-- Center - Carousel -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="swiper hero-swiper h-80">
                            <div class="swiper-wrapper">
                                <!-- Slide 1 -->
                                <div class="swiper-slide relative">
                                    <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&h=400&q=80" alt="Summer Sale" class="w-full h-full object-cover">
                                </div>

                                <!-- Slide 2 -->
                                <div class="swiper-slide relative">
                                    <img src="https://images.unsplash.com/photo-1560472354-b33ff0c44a43?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&h=400&q=80" alt="Electronics" class="w-full h-full object-cover">
                                </div>

                                <!-- Slide 3 -->
                                <div class="swiper-slide relative">
                                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&h=400&q=80" alt="Fashion" class="w-full h-full object-cover">
                                </div>
                            </div>

                            <!-- Navigation buttons -->
                            <div class="swiper-button-next text-white"></div>
                            <div class="swiper-button-prev text-white"></div>

                            <!-- Pagination -->
                            <div class="swiper-pagination"></div>
                        </div>
                    </div>
                </div>

                <!-- Right - Welcome Section -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Welcome Back!</h2>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">Sign in to access your account, track orders, and enjoy exclusive member benefits.</p>

                        @guest
                        <div class="space-y-3">
                            <a wire:navigate href="{{ route('login') }}" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors flex items-center justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                </svg>
                                Sign In
                            </a>
                            <a wire:navigate href="{{ route('register') }}" class="w-full bg-white hover:bg-gray-50 text-blue-600 font-semibold py-3 px-4 rounded-lg border border-blue-600 transition-colors flex items-center justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                Register
                            </a>
                        </div>
                        @else
                        <div class="text-center">
                            <div class="w-16 h-16 bg-teal-100 dark:bg-teal-900 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Hello, {{ auth()->user()->name }}!</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Welcome back to your account</p>
                            <a wire:navigate href="{{ route('profile') }}" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors inline-block">
                                View Profile
                            </a>
                        </div>
                        @endguest
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Brand Section Start --}}
    <section class="py-20">
        <div class="max-w-xl mx-auto">
          <div class="text-center ">
            <div class="relative flex flex-col items-center">
              <h1 class="text-5xl font-bold dark:text-gray-200"> Browse Popular<span class="text-teal-500"> Brands
                </span> </h1>
              <div class="flex w-40 mt-2 mb-6 overflow-hidden rounded">
                <div class="flex-1 h-2 bg-teal-200">
                </div>
                <div class="flex-1 h-2 bg-teal-400">
                </div>
                <div class="flex-1 h-2 bg-teal-600">
                </div>
              </div>
            </div>
            <p class="mb-12 text-base text-center text-gray-500">
              Discover top brands and their latest products in our curated collection.
            </p>
          </div>
        </div>
        <div class="justify-center max-w-6xl px-4 py-4 mx-auto lg:py-0">
          <div class="grid grid-cols-1 gap-6 lg:grid-cols-5 md:grid-cols-3">
            @foreach($brands as $brand)
            <div class="bg-white rounded-lg shadow-md dark:bg-gray-800 hover:shadow-lg transition-shadow duration-300">
              <div class="flex flex-col items-center justify-center p-8">
                @if($brand->image)
                <img src="{{ url('storage', $brand->image) }}" alt="{{ $brand->name }}" class="w-16 h-16 mb-4 object-contain">
                @else
                <div class="w-16 h-16 mb-4 bg-teal-100 dark:bg-teal-900 rounded-lg flex items-center justify-center">
                  <span class="text-teal-600 dark:text-teal-400 font-bold text-lg">{{ substr($brand->name, 0, 2) }}</span>
                </div>
                @endif
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $brand->name }}</h3>
              </div>
            </div>
            @endforeach
          </div>
        </div>
    </section>
    {{-- Brand Section End --}}
</div>
