<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * Handle the creation of the product and its variants
     */
    protected function handleRecordCreation(array $data): Model
    {
        // Extract variants data before creating the product
        $variantsData = $data['variants'] ?? [];
        unset($data['variants']);

        // Convert price from dollars to cents
        if (isset($data['price'])) {
            $data['price_cents'] = round($data['price'] * 100);
        }

        // Handle inventory settings for variant vs non-variant products
        if ($data['has_variants'] ?? false) {
            // For variant products, disable product-level inventory tracking
            $data['track_inventory'] = false;
            $data['stock_quantity'] = 0;
        } else {
            // For simple products, ensure inventory tracking is enabled
            $data['track_inventory'] = $data['track_inventory'] ?? true;
            $data['stock_quantity'] = $data['stock_quantity'] ?? 0;
        }

        // Create the product
        $product = Product::create($data);

        // Create variants if they exist and has_variants is true
        if ($product->has_variants && !empty($variantsData)) {
            foreach ($variantsData as $variantData) {
                $this->createVariant($product, $variantData);
            }
        } elseif ($product->has_variants && empty($variantsData)) {
            // Don't create "Standard" variants - require real variants
            // Convert back to simple product if no variants provided
            $product->update(['has_variants' => false]);

            \Filament\Notifications\Notification::make()
                ->title('Product Created as Simple Product')
                ->body('No variants were provided, so the product was created as a simple product. You can enable variants later by editing the product.')
                ->info()
                ->send();
        }

        // Auto-fix any conflicts that might have occurred
        \App\Services\ProductVariantTransitionService::autoFixConflicts($product);

        return $product;
    }

    /**
     * Create a single variant for the product
     */
    protected function createVariant(Product $product, array $variantData): ProductVariant
    {
        // Convert price from dollars to cents if provided
        if (isset($variantData['override_price_dollars'])) {
            $variantData['override_price'] = round($variantData['override_price_dollars'] * 100);
        }

        // Generate SKU based on product SKU and variant options
        if (isset($variantData['color'], $variantData['storage'])) {
            $colorCode = strtoupper(substr($variantData['color'], 0, 3));
            $storageCode = str_replace('GB', '', $variantData['storage']);
            $variantData['sku'] = "{$product->sku}-{$colorCode}-{$storageCode}";

            // Set options JSON
            $variantData['options'] = [
                'Color' => $variantData['color'],
                'Storage' => $variantData['storage']
            ];
        }

        // Remove temporary fields
        unset($variantData['color'], $variantData['storage'], $variantData['override_price_dollars']);

        // Set product_id
        $variantData['product_id'] = $product->id;

        // Set default variant if this is the first one
        if ($product->variants()->count() === 0) {
            $variantData['is_default'] = true;
        }

        return ProductVariant::create($variantData);
    }

    /**
     * Redirect after creation
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
