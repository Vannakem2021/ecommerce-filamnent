@props(['product', 'selectedVariant' => null])

@php
    // Use simplified JSON-based attributes instead of complex specifications
    $productAttributes = $product->getProductAttributes();

    // Get variant-specific options if a variant is selected
    $variantOptions = [];
    if ($selectedVariant) {
        $variantOptions = $selectedVariant->getVariantOptions();
    } elseif ($product->has_variants && $product->defaultVariant) {
        $variantOptions = $product->defaultVariant->getVariantOptions();
    }

    // Combine product attributes and variant options for display
    $allSpecs = array_merge($productAttributes, $variantOptions);

    // Convert to collection for easier handling
    $specifications = collect($allSpecs)->map(function ($value, $key) {
        return (object) [
            'name' => ucfirst(str_replace('_', ' ', $key)),
            'value' => $value,
            'key' => $key
        ];
    });
@endphp

@if($specifications->isNotEmpty())
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" data-specifications-container data-product-id="{{ $product->id }}">
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Specifications</h3>
    </div>

    <div class="divide-y divide-gray-200" data-specifications-list>
        @foreach($specifications as $spec)
            <div class="px-6 py-4 flex justify-between items-start">
                <div class="flex-1">
                    <dt class="text-sm font-medium text-gray-900">
                        {{ $spec->name }}
                    </dt>
                </div>

                <div class="flex-shrink-0 ml-4">
                    <dd class="text-sm text-gray-900 font-medium">
                        {{ $spec->value }}
                    </dd>

                    @if(in_array($spec->key, ['color', 'storage', 'ram', 'size']))
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                            Variant
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if($product->has_variants && !$selectedVariant)
    <div class="px-6 py-4 bg-yellow-50 border-t border-yellow-200">
        <p class="text-sm text-yellow-800">
            <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            Some specifications may vary by selected variant. Choose a variant to see specific details.
        </p>
    </div>
    @endif
</div>
@endif
