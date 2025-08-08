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
                    <x-hero.filter-sidebar :categories="$categories" :brands="$brands" />
                </div>

                <!-- Center - Carousel -->
                <div class="lg:col-span-2">
                    <x-hero.carousel />
                </div>

                <!-- Right - Welcome Section -->
                <div class="lg:col-span-1">
                    <x-hero.welcome-section />
                </div>
            </div>
        </div>
    </div>

    <!-- Brand Section -->
    <x-brands.section :brands="$brands" />
</div>
