@props([
    'product',
    'availableOptions' => [],
    'selectedOptions' => [],
    'selectedVariant' => null,
    'showPriceOnOptions' => true,
    'enableCompatibilityCheck' => true,
    'livewireComponent' => null
])

<div 
    class="space-y-6"
    data-variant-selector
    data-available-options="{{ json_encode($availableOptions) }}"
    data-variants="{{ json_encode($product->variants->toArray()) }}"
    data-selected-options="{{ json_encode($selectedOptions) }}"
    data-livewire-component="{{ $livewireComponent }}"
    data-enable-compatibility-check="{{ $enableCompatibilityCheck ? 'true' : 'false' }}"
    data-show-price-on-options="{{ $showPriceOnOptions ? 'true' : 'false' }}"
>
    <!-- Clear Options Button -->
    @if(!empty($selectedOptions))
    <div class="flex justify-end">
        <button 
            data-clear-options
            class="text-sm text-gray-500 hover:text-teal-600 underline transition-colors"
            type="button"
        >
            Clear all selections
        </button>
    </div>
    @endif

    <!-- Option Selectors -->
    @foreach($availableOptions as $optionName => $optionValues)
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                {{ ucfirst(str_replace('_', ' ', $optionName)) }}
                <span class="text-red-500 text-sm">*</span>
            </h3>
            @if(isset($selectedOptions[$optionName]))
                <span class="text-sm text-teal-600 bg-teal-50 px-3 py-1 rounded-full font-medium">
                    ✓ {{ $selectedOptions[$optionName] }}
                </span>
            @else
                <span class="text-sm text-gray-400 bg-gray-50 px-3 py-1 rounded-full">
                    Not selected
                </span>
            @endif
        </div>

        @if(strtolower($optionName) === 'color' || strtolower($optionName) === 'colour')
            <!-- Color Picker Style -->
            <div class="flex gap-3 flex-wrap">
                @foreach($optionValues as $optionValue)
                    @php
                        $isSelected = isset($selectedOptions[$optionName]) && $selectedOptions[$optionName] === $optionValue;
                        $colorCode = match(strtolower($optionValue)) {
                            'black', 'space gray' => '#000000',
                            'white', 'silver' => '#C0C0C0',
                            'red' => '#DC2626',
                            'blue' => '#2563EB',
                            'green' => '#059669',
                            'yellow' => '#D97706',
                            'purple' => '#7C3AED',
                            'pink' => '#DB2777',
                            'gray', 'grey' => '#6B7280',
                            'orange' => '#EA580C',
                            'indigo' => '#4F46E5',
                            'teal' => '#0D9488',
                            'cyan' => '#0891B2',
                            'emerald' => '#059669',
                            'lime' => '#65A30D',
                            'amber' => '#D97706',
                            'violet' => '#7C3AED',
                            'fuchsia' => '#C026D3',
                            'rose' => '#E11D48',
                            'slate' => '#475569',
                            'zinc' => '#52525B',
                            'neutral' => '#525252',
                            'stone' => '#57534E',
                            default => '#6B7280'
                        };
                    @endphp
                    
                    <label class="relative cursor-pointer group">
                        <input 
                            type="radio" 
                            name="option_{{ $optionName }}" 
                            value="{{ $optionValue }}"
                            data-option-type="{{ $optionName }}"
                            {{ $isSelected ? 'checked' : '' }}
                            class="sr-only peer"
                        >
                        
                        <div class="w-12 h-12 rounded-full border-3 border-gray-200 peer-checked:border-teal-500 peer-checked:ring-4 peer-checked:ring-teal-200 transition-all duration-200 overflow-hidden group-hover:scale-110"
                             style="background-color: {{ $colorCode }}">
                            @if($isSelected)
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        
                        <span class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 text-xs text-gray-600 font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">
                            {{ $optionValue }}
                        </span>
                    </label>
                @endforeach
            </div>

        @elseif(in_array(strtolower($optionName), ['size', 'storage', 'memory', 'ram']))
            <!-- Button Style for Size/Storage/Memory -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                @foreach($optionValues as $optionValue)
                    @php
                        $isSelected = isset($selectedOptions[$optionName]) && $selectedOptions[$optionName] === $optionValue;
                    @endphp
                    
                    <label class="relative cursor-pointer">
                        <input 
                            type="radio" 
                            name="option_{{ $optionName }}" 
                            value="{{ $optionValue }}"
                            data-option-type="{{ $optionName }}"
                            {{ $isSelected ? 'checked' : '' }}
                            class="sr-only peer"
                        >
                        
                        <div class="p-3 text-center border-2 border-gray-200 rounded-xl peer-checked:border-teal-500 peer-checked:bg-teal-50 peer-checked:text-teal-700 hover:border-teal-300 transition-all duration-200 group">
                            <div class="font-semibold text-sm">{{ $optionValue }}</div>
                            
                            @if($showPriceOnOptions)
                                <div class="text-xs text-gray-500 mt-1 option-price" data-option-type="{{ $optionName }}" data-option-value="{{ $optionValue }}">
                                    <!-- Price will be injected by JavaScript -->
                                </div>
                            @endif
                        </div>
                        
                        <!-- Selected checkmark -->
                        @if($isSelected)
                            <div class="absolute -top-2 -right-2 w-6 h-6 bg-teal-500 rounded-full flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        @endif
                    </label>
                @endforeach
            </div>

        @else
            <!-- Dropdown Style for Other Options -->
            <div class="relative">
                <select 
                    name="option_{{ $optionName }}"
                    data-option-type="{{ $optionName }}"
                    class="w-full p-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all duration-200 appearance-none bg-white"
                >
                    <option value="">Select {{ ucfirst(str_replace('_', ' ', $optionName)) }}</option>
                    @foreach($optionValues as $optionValue)
                        <option 
                            value="{{ $optionValue }}" 
                            {{ (isset($selectedOptions[$optionName]) && $selectedOptions[$optionName] === $optionValue) ? 'selected' : '' }}
                        >
                            {{ $optionValue }}
                        </option>
                    @endforeach
                </select>
                
                <!-- Custom dropdown arrow -->
                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>
        @endif
    </div>
    @endforeach

    <!-- Variant Information Display -->
    <div data-variant-info class="hidden bg-blue-50 border border-blue-200 rounded-xl p-4">
        <!-- Content will be populated by JavaScript -->
    </div>

    <!-- Selection Summary -->
    @if(!empty($selectedOptions))
    <div class="bg-gray-50 rounded-xl p-4">
        <h4 class="font-semibold text-gray-800 mb-2">Selected Configuration:</h4>
        <div class="space-y-1">
            @foreach($selectedOptions as $optionName => $optionValue)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $optionName)) }}:</span>
                    <span class="font-medium">{{ $optionValue }}</span>
                </div>
            @endforeach
        </div>
        
        @if($selectedVariant)
            <div class="mt-3 pt-3 border-t border-gray-200">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">SKU:</span>
                    <span class="font-mono text-xs">{{ $selectedVariant->sku }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Stock:</span>
                    <span class="font-medium {{ $selectedVariant->stock_quantity > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $selectedVariant->stock_quantity > 0 ? $selectedVariant->stock_quantity . ' available' : 'Out of stock' }}
                    </span>
                </div>
            </div>
        @endif
    </div>
    @endif

    <!-- Missing Options Alert -->
    @if($product->has_variants)
        @php
            $missingOptions = array_diff(array_keys($availableOptions), array_keys($selectedOptions));
        @endphp
        @if(!empty($missingOptions))
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-blue-800 mb-1">Complete Your Selection</h4>
                        <p class="text-sm text-blue-700">
                            Please select: 
                            @foreach($missingOptions as $index => $missingOption)
                                <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $missingOption)) }}</span>{{ $index < count($missingOptions) - 1 ? ', ' : '' }}
                            @endforeach
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

@push('scripts')
<script src="{{ asset('js/components/json-variant-selector.js') }}"></script>
@endpush
