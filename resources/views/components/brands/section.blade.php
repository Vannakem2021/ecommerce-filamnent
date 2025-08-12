@props(['brands'])

<section class="py-20">
    <div class="max-w-xl mx-auto">
        <div class="text-center">
            <div class="relative flex flex-col items-center">
                <h1 class="text-5xl font-bold dark:text-gray-200">
                    Browse Popular<span class="text-teal-500"> Brands</span>
                </h1>
                <div class="flex w-40 mt-2 mb-6 overflow-hidden rounded">
                    <div class="flex-1 h-2 bg-teal-200"></div>
                    <div class="flex-1 h-2 bg-teal-400"></div>
                    <div class="flex-1 h-2 bg-teal-600"></div>
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
                    <img src="{{ asset('storage/' . $brand->image) }}" alt="{{ $brand->name }}" class="w-16 h-16 mb-4 object-contain">
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
