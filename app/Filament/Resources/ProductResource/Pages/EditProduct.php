<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Services\ProductVariantTransitionService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('fix_conflicts')
                ->label('Fix Conflicts')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('warning')
                ->action(function () {
                    $fixed = ProductVariantTransitionService::autoFixConflicts($this->record);

                    if (!empty($fixed)) {
                        \Filament\Notifications\Notification::make()
                            ->title('Conflicts Fixed')
                            ->body('Fixed: ' . implode(', ', $fixed))
                            ->success()
                            ->send();

                        // Refresh the form
                        $this->fillForm();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('No Conflicts Found')
                            ->body('Product pricing and inventory are already properly configured.')
                            ->info()
                            ->send();
                    }
                })
                ->visible(function () {
                    $conflicts = ProductVariantTransitionService::validateAndFixConflicts($this->record);
                    return $conflicts['has_conflicts'];
                }),

            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Handle record update with conflict resolution
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Handle variant mode changes
        $wasVariantProduct = $record->has_variants;
        $willBeVariantProduct = $data['has_variants'] ?? false;

        // Extract variants data before updating the product
        $variantsData = $data['variants'] ?? [];
        unset($data['variants']);

        // Convert price from dollars to cents if provided
        if (isset($data['price'])) {
            $data['price_cents'] = round($data['price'] * 100);
            unset($data['price']); // Remove the dollar field
        }

        // Convert compare price from dollars to cents if provided
        if (isset($data['compare_price'])) {
            $data['compare_price_cents'] = round($data['compare_price'] * 100);
            unset($data['compare_price']); // Remove the dollar field
        }

        // Convert cost price from dollars to cents if provided
        if (isset($data['cost_price'])) {
            $data['cost_price_cents'] = round($data['cost_price'] * 100);
            unset($data['cost_price']); // Remove the dollar field
        }

        // Update the product
        $record->update($data);

        // Handle mode transitions and variant processing
        if (!$wasVariantProduct && $willBeVariantProduct) {
            // Converting to variant product
            if (!empty($variantsData)) {
                // Delete any existing variants
                $record->variants()->delete();

                // Create variants from form data
                foreach ($variantsData as $variantData) {
                    $this->createVariant($record, $variantData);
                }

                // Clear product-level stock since we're using variants
                $record->update(['stock_quantity' => 0, 'track_inventory' => false]);
            } else {
                // No variants provided - revert to simple product
                \Filament\Notifications\Notification::make()
                    ->title('Variants Required')
                    ->body('Please add at least one variant with specific color and storage options.')
                    ->warning()
                    ->send();

                $record->update(['has_variants' => false]);
            }
        } elseif ($wasVariantProduct && !$willBeVariantProduct) {
            // Converting to simple product
            ProductVariantTransitionService::convertToSimpleProduct($record);
        } elseif ($willBeVariantProduct && !empty($variantsData)) {
            // Update existing variants
            $this->updateVariants($record, $variantsData);
        } elseif ($willBeVariantProduct && empty($variantsData)) {
            // No variants provided but trying to enable variants
            $existingVariants = $record->variants()->count();
            if ($existingVariants === 0) {
                \Filament\Notifications\Notification::make()
                    ->title('Variants Required')
                    ->body('Please add at least one variant with specific color and storage options.')
                    ->warning()
                    ->send();

                // Revert to simple product
                $record->update(['has_variants' => false]);
            }
        }

        // Auto-fix any remaining conflicts
        ProductVariantTransitionService::autoFixConflicts($record);

        return $record->fresh();
    }

    /**
     * Create a single variant for the product
     */
    protected function createVariant($product, array $variantData)
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
        unset($variantData['color'], $variantData['storage'], $variantData['override_price_dollars'], $variantData['id']);

        // Set product_id
        $variantData['product_id'] = $product->id;

        return $product->variants()->create($variantData);
    }

    /**
     * Update existing variants
     */
    protected function updateVariants($product, array $variantsData)
    {
        // Delete existing variants and recreate them
        // This is simpler than trying to match and update existing ones
        $product->variants()->delete();

        foreach ($variantsData as $variantData) {
            $this->createVariant($product, $variantData);
        }
    }


}
