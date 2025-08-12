@props(['product', 'limit' => 5])

@php
    // Use simplified JSON-based attributes for compact display
    $productAttributes = $product->getProductAttributes();

    // Get default variant options if product has variants
    $variantOptions = [];
    if ($product->has_variants && $product->defaultVariant) {
        $variantOptions = $product->defaultVariant->getVariantOptions();
    }

    // Combine and limit to most important specs
    $allSpecs = array_merge($productAttributes, $variantOptions);

    // Convert to collection and take only the specified limit
    $specifications = collect($allSpecs)->take($limit)->map(function ($value, $key) {
        return (object) [
            'name' => ucfirst(str_replace('_', ' ', $key)),
            'value' => $value,
            'key' => $key
        ];
    });
@endphp

@if($allSpecs->isNotEmpty())
<div class="space-y-2">
    @foreach($allSpecs as $spec)
        @php
            $attribute = $spec->specificationAttribute;
            $formattedValue = $spec->formatted_value;
        @endphp
        
        @if($formattedValue)
        <div class="flex justify-between text-sm">
            <span class="text-gray-600">{{ $attribute->name }}:</span>
            <span class="text-gray-900 font-medium">{{ $formattedValue }}</span>
        </div>
        @endif
    @endforeach
</div>
@endif
