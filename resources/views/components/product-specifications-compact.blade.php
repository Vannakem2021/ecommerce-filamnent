@props(['product', 'limit' => 5])

@php
    // Get key specifications for compact display
    $productSpecs = $product->specificationsWithAttributes()
        ->limit($limit)
        ->get();

    // Get default variant specs if product has variants
    $variantSpecs = collect();
    if ($product->has_variants && $product->defaultVariant) {
        $variantSpecs = $product->defaultVariant->specificationsWithAttributes()
            ->limit($limit)
            ->get();
    }

    // Combine and prioritize most important specs
    $allSpecs = $productSpecs->concat($variantSpecs)
        ->unique('specification_attribute_id')
        ->sortBy(function ($spec) {
            return $spec->specificationAttribute->sort_order ?? 999;
        })
        ->take($limit);
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
