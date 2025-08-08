<header class="flex z-50 sticky top-0 flex-wrap md:justify-start md:flex-nowrap w-full bg-white text-sm py-2 md:py-0 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
    <nav class="max-w-[80rem] w-full mx-auto px-4 md:px-6 lg:px-8" aria-label="Global">
      <div class="relative md:flex md:items-center md:justify-between">
        <div class="flex items-center justify-between">
          <a wire:navigate class="flex-none text-xl font-semibold text-gray-800 dark:text-white dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="/" aria-label="Brand">X store</a>
          <div class="md:hidden">
            <button type="button" class="hs-collapse-toggle flex justify-center items-center w-9 h-9 text-sm font-semibold rounded-lg border border-gray-200 text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" data-hs-collapse="#navbar-collapse-with-animation" aria-controls="navbar-collapse-with-animation" aria-label="Toggle navigation">
              <svg class="hs-collapse-open:hidden flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" x2="21" y1="6" y2="6" />
                <line x1="3" x2="21" y1="12" y2="12" />
                <line x1="3" x2="21" y1="18" y2="18" />
              </svg>
              <svg class="hs-collapse-open:block hidden flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
              </svg>
            </button>
          </div>
        </div>

        <div id="navbar-collapse-with-animation" class="hs-collapse hidden overflow-hidden transition-all duration-300 basis-full grow md:block">
          <div class="overflow-hidden overflow-y-auto max-h-[75vh] [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300 dark:[&::-webkit-scrollbar-track]:bg-slate-700 dark:[&::-webkit-scrollbar-thumb]:bg-slate-500">
            <div class="flex flex-col gap-x-0 mt-5 divide-y divide-dashed divide-gray-200 md:flex-row md:items-center md:justify-between md:gap-x-7 md:mt-0 md:ps-7 md:divide-y-0 md:divide-solid dark:divide-gray-700">

              <!-- Left side navigation -->
              <div class="flex flex-col md:flex-row md:items-center md:gap-x-7">
                <a wire:navigate class="font-medium {{ request()->routeIs('all-products') ? 'text-teal-600' : 'text-gray-600' }} hover:text-teal-600 py-3 md:py-4 dark:text-gray-400 dark:hover:text-teal-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-teal-600" href="{{ route('all-products') }}">
                  Products
                </a>

                <a wire:navigate class="font-medium text-gray-600 hover:text-teal-600 py-3 md:py-4 dark:text-gray-400 dark:hover:text-teal-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-teal-600" href="#">
                  New Arrivals
                </a>

                <a wire:navigate class="font-medium text-gray-600 hover:text-teal-600 py-3 md:py-4 dark:text-gray-400 dark:hover:text-teal-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-teal-600" href="#">
                  Best Sellers
                </a>

                <a wire:navigate class="font-medium text-gray-600 hover:text-teal-600 py-3 md:py-4 dark:text-gray-400 dark:hover:text-teal-400 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-teal-600" href="#">
                  Promotions
                </a>
              </div>

              <!-- Center - Search Bar (Expanded) -->
              <div class="flex-1 max-w-2xl mx-8 py-3 md:py-0">
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                    </svg>
                  </div>
                  <input type="search" class="block w-full p-3 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-teal-500 focus:border-teal-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-teal-500 dark:focus:border-teal-500" placeholder="Search products..." />
                </div>
              </div>

              <!-- Right side navigation -->
              <div class="flex items-center gap-x-4">
                <!-- Wishlist/Favorites -->
                <a wire:navigate class="relative flex items-center text-gray-600 hover:text-gray-900 py-3 md:py-4 dark:text-gray-400 dark:hover:text-gray-300" href="#" title="Wishlist">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                  </svg>
                </a>

                <!-- Shopping Cart -->
                <a wire:navigate class="relative flex items-center text-gray-600 hover:text-teal-600 py-3 md:py-4 dark:text-gray-400 dark:hover:text-teal-400" href="{{ route('cart-products') }}">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                  </svg>
                  @if($total_count > 0)
                    <span class="absolute -top-1 -right-1 bg-teal-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">{{ $total_count }}</span>
                  @endif
                </a>

                <!-- User Avatar/Profile -->
                @auth
                  <div class="hs-dropdown [--strategy:static] md:[--strategy:fixed] [--adaptive:none] md:[--trigger:hover] md:py-2">
                    <button type="button" class="flex items-center w-full text-gray-600 hover:text-gray-900 py-3 md:py-4 dark:text-gray-400 dark:hover:text-gray-300">
                      <!-- User Avatar -->
                      <div class="w-8 h-8 rounded-full bg-teal-100 dark:bg-teal-900 flex items-center justify-center mr-2">
                        @if(auth()->user()->avatar)
                          <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full object-cover">
                        @else
                          <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                          </svg>
                        @endif
                      </div>
                      <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m6 9 6 6 6-6" />
                      </svg>
                    </button>
                    <div class="hs-dropdown-menu transition-[opacity,margin] duration-[0.1ms] md:duration-[150ms] hs-dropdown-open:opacity-100 opacity-0 md:w-48 hidden z-10 bg-white md:shadow-md rounded-lg p-2 dark:bg-gray-800 md:dark:border dark:border-gray-700 dark:divide-gray-700 before:absolute top-full md:border before:-top-5 before:start-0 before:w-full before:h-5">

                      <!-- User Info -->
                      <div class="py-2 px-3 border-b border-gray-200 dark:border-gray-700">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</p>
                        @if(auth()->user()->hasAnyRole(['admin', 'product-manager', 'order-manager', 'analytics-viewer']))
                          <span class="inline-flex items-center gap-x-1.5 py-1 px-2 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-800/30 dark:text-amber-500 mt-1">
                            {{ ucwords(str_replace('-', ' ', auth()->user()->getRoleNames()->first())) }}
                          </span>
                        @endif
                      </div>

                      <!-- Admin Dashboard Link -->
                      @if(auth()->user()->hasAnyRole(['admin', 'product-manager', 'order-manager', 'analytics-viewer']))
                        <a href="/admin" target="_blank" class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300">
                          <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="7" height="9" x="3" y="3" rx="1"/>
                            <rect width="7" height="5" x="14" y="3" rx="1"/>
                            <rect width="7" height="9" x="14" y="12" rx="1"/>
                            <rect width="7" height="5" x="3" y="16" rx="1"/>
                          </svg>
                          Admin Dashboard
                        </a>
                      @endif

                      <!-- Profile Link -->
                      <a wire:navigate class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300" href="{{ route('profile') }}">
                        <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                          <circle cx="12" cy="7" r="4"/>
                        </svg>
                        Profile
                      </a>

                      <!-- My Orders Link -->
                      <a wire:navigate class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300" href="{{ route('my-orders') }}">
                        <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                          <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/>
                        </svg>
                        My Orders
                      </a>

                      <!-- Logout Button -->
                      <button wire:click="logout" class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-red-600 hover:bg-red-50 focus:ring-2 focus:ring-red-500 dark:text-red-400 dark:hover:bg-red-900/20 w-full text-left">
                        <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                          <polyline points="16,17 21,12 16,7" />
                          <line x1="21" x2="9" y1="12" y2="12" />
                        </svg>
                        Log out
                      </button>
                    </div>
                  </div>
                @else
                  <!-- Default User Avatar for Non-authenticated Users -->
                  <a wire:navigate href="{{ route('login') }}" class="flex items-center text-gray-600 hover:text-gray-900 py-3 md:py-4 dark:text-gray-400 dark:hover:text-gray-300" title="Sign in to your account">
                    <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                      <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                      </svg>
                    </div>
                  </a>
                @endauth
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>
</header>
