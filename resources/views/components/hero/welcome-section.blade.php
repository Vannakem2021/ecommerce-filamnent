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
