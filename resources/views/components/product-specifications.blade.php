@props(['product', 'selectedVariant' => null])

@php
    // Get all specifications for the product
    $productSpecs = $product->specificationsWithAttributes()->get();
    
    // Get variant specifications if a variant is selected
    $variantSpecs = collect();
    if ($selectedVariant) {
        $variantSpecs = $selectedVariant->specificationsWithAttributes()->get();
    } elseif ($product->has_variants && $product->defaultVariant) {
        $variantSpecs = $product->defaultVariant->specificationsWithAttributes()->get();
    }
    
    // Combine specifications, with variant specs overriding product specs
    $allSpecs = $productSpecs->keyBy('specification_attribute_id');
    $variantSpecs->each(function ($spec) use ($allSpecs) {
        $allSpecs[$spec->specification_attribute_id] = $spec;
    });
    
    $specifications = $allSpecs->values()->sortBy(function ($spec) {
        return $spec->specificationAttribute->sort_order ?? 999;
    });
@endphp

@if($specifications->isNotEmpty())
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" data-specifications-container data-product-id="{{ $product->id }}">
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Specifications</h3>
    </div>

    <div class="divide-y divide-gray-200" data-specifications-list>
        @foreach($specifications as $spec)
            @php
                $attribute = $spec->specificationAttribute;
                $formattedValue = $spec->formatted_value;
            @endphp
            
            @if($formattedValue)
            <div class="px-6 py-4 flex justify-between items-start">
                <div class="flex-1">
                    <dt class="text-sm font-medium text-gray-900">
                        {{ $attribute->name }}
                        @if($attribute->unit && $attribute->data_type === 'number')
                            <span class="text-gray-500 font-normal">({{ $attribute->unit }})</span>
                        @endif
                    </dt>
                    @if($attribute->description)
                        <dd class="text-xs text-gray-500 mt-1">{{ $attribute->description }}</dd>
                    @endif
                </div>
                
                <div class="flex-shrink-0 ml-4">
                    <dd class="text-sm text-gray-900 font-medium">
                        {{ $formattedValue }}
                    </dd>
                    
                    @if($attribute->scope === 'variant')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                            Variant
                        </span>
                    @endif
                </div>
            </div>
            @endif
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
