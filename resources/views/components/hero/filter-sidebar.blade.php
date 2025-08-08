@props(['categories', 'brands'])

<div>
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
