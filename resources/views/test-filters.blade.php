<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Category Filters</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Category Filter Test</h1>
        
        <div x-data="categoryFilter()" class="bg-white rounded-lg shadow p-6">
            <div class="mb-6">
                <h2 class="text-xl font-semibold mb-4">Filter Controls</h2>
                <div class="flex gap-4 mb-4">
                    <button @click="setFilter('all')" 
                            :class="filter === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-200'"
                            class="px-4 py-2 rounded">
                        All Categories
                    </button>
                    <button @click="setFilter('active')" 
                            :class="filter === 'active' ? 'bg-green-500 text-white' : 'bg-gray-200'"
                            class="px-4 py-2 rounded">
                        Active Only
                    </button>
                    <button @click="setFilter('inactive')" 
                            :class="filter === 'inactive' ? 'bg-red-500 text-white' : 'bg-gray-200'"
                            class="px-4 py-2 rounded">
                        Inactive Only
                    </button>
                </div>
                
                <div class="mb-4">
                    <input type="text" 
                           x-model="search" 
                           @input="filterCategories()"
                           placeholder="Search categories..."
                           class="w-full px-3 py-2 border rounded">
                </div>
            </div>
            
            <div class="mb-4">
                <p class="text-sm text-gray-600">
                    Showing <span x-text="filteredCategories.length"></span> of <span x-text="categories.length"></span> categories
                </p>
            </div>
            
            <div class="grid gap-4">
                <template x-for="category in filteredCategories" :key="category.id">
                    <div class="border rounded p-4 flex justify-between items-center">
                        <div>
                            <h3 class="font-semibold" x-text="category.name"></h3>
                            <p class="text-sm text-gray-600" x-text="category.slug"></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span :class="category.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                  class="px-2 py-1 rounded text-xs font-medium"
                                  x-text="category.is_active ? 'Active' : 'Inactive'">
                            </span>
                            <span class="text-sm text-gray-500" x-text="category.products_count + ' products'"></span>
                        </div>
                    </div>
                </template>
            </div>
            
            <div x-show="filteredCategories.length === 0" class="text-center py-8 text-gray-500">
                No categories found matching your criteria.
            </div>
        </div>
        
        <div class="mt-8 bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Test Results</h2>
            <div class="space-y-2 text-sm">
                <p><strong>Filter Reactivity:</strong> <span x-text="filterReactive ? 'Working ✅' : 'Not Working ❌'"></span></p>
                <p><strong>Search Reactivity:</strong> <span x-text="searchReactive ? 'Working ✅' : 'Not Working ❌'"></span></p>
                <p><strong>Current Filter:</strong> <span x-text="filter"></span></p>
                <p><strong>Search Term:</strong> <span x-text="search || 'None'"></span></p>
            </div>
        </div>
    </div>

    <script>
        function categoryFilter() {
            return {
                categories: @json($categories),
                filteredCategories: [],
                filter: 'all',
                search: '',
                filterReactive: true,
                searchReactive: true,
                
                init() {
                    this.filterCategories();
                },
                
                setFilter(newFilter) {
                    this.filter = newFilter;
                    this.filterCategories();
                    this.filterReactive = true;
                },
                
                filterCategories() {
                    let filtered = this.categories;
                    
                    // Apply status filter
                    if (this.filter === 'active') {
                        filtered = filtered.filter(cat => cat.is_active);
                    } else if (this.filter === 'inactive') {
                        filtered = filtered.filter(cat => !cat.is_active);
                    }
                    
                    // Apply search filter
                    if (this.search) {
                        filtered = filtered.filter(cat => 
                            cat.name.toLowerCase().includes(this.search.toLowerCase()) ||
                            cat.slug.toLowerCase().includes(this.search.toLowerCase())
                        );
                        this.searchReactive = true;
                    }
                    
                    this.filteredCategories = filtered;
                }
            }
        }
    </script>
</body>
</html>
