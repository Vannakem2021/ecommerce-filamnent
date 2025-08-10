<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Variant Information')
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Leave empty to auto-generate based on product and attributes')
                            ->placeholder('Auto-generated'),

                        Forms\Components\TextInput::make('name')
                            ->label('Variant Name')
                            ->maxLength(255)
                            ->helperText('Optional custom name for this variant'),
                    ])->columns(2),

                Forms\Components\Section::make('Variant Options (Simplified System)')
                    ->schema([
                        Forms\Components\KeyValue::make('options')
                            ->label('Variant Options')
                            ->helperText('Define the variant options as key-value pairs (e.g., Color: Black, Storage: 256GB, RAM: 8GB)')
                            ->keyLabel('Attribute')
                            ->valueLabel('Value')
                            ->addActionLabel('Add Option')
                            ->reorderable(false)
                            ->columnSpanFull()
                            ->required(),

                        Forms\Components\TextInput::make('override_price_dollars')
                            ->label('Override Price (USD)')
                            ->helperText('Leave empty to use product base price. Set a specific price for this variant combination.')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('$')
                            ->afterStateUpdated(function ($state, $set) {
                                // Convert dollars to cents for storage
                                $set('override_price', $state ? round($state * 100) : null);
                            })
                            ->afterStateHydrated(function ($component, $state, $record) {
                                // Convert cents to dollars for display
                                if ($record && $record->override_price) {
                                    $component->state($record->override_price / 100);
                                }
                            })
                            ->dehydrated(false), // Don't save this field directly

                        Forms\Components\Hidden::make('override_price'), // The actual field that gets saved

                        Forms\Components\TextInput::make('image_url')
                            ->label('Variant Image URL')
                            ->helperText('Optional: Specific image for this variant')
                            ->url()
                            ->placeholder('https://example.com/image.jpg'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Legacy Attributes (Old System)')
                    ->schema([
                        Forms\Components\Placeholder::make('attribute_info')
                            ->label('')
                            ->content('⚠️ This is the old complex system. Use "Variant Options" above for the simplified approach.')
                            ->visible(fn ($livewire) => $livewire->ownerRecord->has_variants && $livewire->ownerRecord->variant_attributes),
                    ])
                    ->visible(fn ($livewire) => $livewire->ownerRecord->has_variants && $livewire->ownerRecord->variant_attributes)
                    ->collapsed(),

                Forms\Components\Section::make('Pricing (Simplified System)')
                    ->schema([
                        Forms\Components\Placeholder::make('pricing_info')
                            ->label('Pricing Logic')
                            ->content('✅ This variant uses simplified pricing: final_price = override_price ?? product.base_price')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('price')
                            ->label('Legacy Price (Deprecated)')
                            ->helperText('⚠️ This field is deprecated. Use Override Price above for variant-specific pricing.')
                            ->numeric()
                            ->prefix('INR')
                            ->step(0.01)
                            ->disabled(),

                        Forms\Components\TextInput::make('compare_price')
                            ->label('Compare Price')
                            ->numeric()
                            ->prefix('INR')
                            ->step(0.01),

                        Forms\Components\TextInput::make('cost_price')
                            ->label('Cost Price')
                            ->numeric()
                            ->prefix('INR')
                            ->step(0.01),
                    ])->columns(3),

                Forms\Components\Section::make('Inventory')
                    ->schema([
                        Forms\Components\TextInput::make('stock_quantity')
                            ->label('Stock Quantity')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\Select::make('stock_status')
                            ->label('Stock Status')
                            ->options([
                                'in_stock' => 'In Stock',
                                'out_of_stock' => 'Out of Stock',
                                'back_order' => 'Back Order',
                            ])
                            ->required()
                            ->default('in_stock'),

                        Forms\Components\TextInput::make('low_stock_threshold')
                            ->label('Low Stock Threshold')
                            ->numeric()
                            ->required()
                            ->default(5)
                            ->minValue(0),

                        Forms\Components\Toggle::make('track_inventory')
                            ->label('Track Inventory')
                            ->default(true),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Default Variant')
                            ->helperText('The default variant shown for this product'),
                    ])->columns(3),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\TextInput::make('weight')
                            ->label('Weight (kg)')
                            ->numeric()
                            ->step(0.01),

                        Forms\Components\TextInput::make('barcode')
                            ->label('Barcode')
                            ->maxLength(255),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Variant Name')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('options')
                    ->label('Options (JSON)')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return '—';
                        }
                        return collect($state)->map(function ($value, $key) {
                            return "{$key}: {$value}";
                        })->join(', ');
                    })
                    ->limit(40)
                    ->tooltip(function ($record) {
                        return $record->options ? json_encode($record->options, JSON_PRETTY_PRINT) : null;
                    }),

                Tables\Columns\TextColumn::make('override_price')
                    ->label('Override Price')
                    ->formatStateUsing(function ($state) {
                        return $state ? '$' . number_format($state / 100, 2) : '—';
                    })
                    ->tooltip('Custom price for this variant. Empty = uses base price'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->is_low_stock ? 'warning' : 'success'),

                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'out_of_stock' => 'danger',
                        'back_order' => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in_stock' => 'In Stock',
                        'out_of_stock' => 'Out of Stock',
                        'back_order' => 'Back Order',
                    }),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stock_status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'out_of_stock' => 'Out of Stock',
                        'back_order' => 'Back Order',
                    ]),
                Tables\Filters\Filter::make('is_active')
                    ->toggle(),
                Tables\Filters\Filter::make('is_default')
                    ->toggle(),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('stock_quantity <= low_stock_threshold'))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('generate_variants')
                    ->label('Generate All Variants')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->visible(fn ($livewire) => $livewire->ownerRecord->has_variants && $livewire->ownerRecord->variant_attributes)
                    ->action(function ($livewire) {
                        $product = $livewire->ownerRecord;
                        $generated = $product->generateVariants();

                        if ($generated) {
                            \Filament\Notifications\Notification::make()
                                ->title('Variants Generated')
                                ->body('All possible variants have been generated based on selected attributes.')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Generation Failed')
                                ->body('Could not generate variants. Please check product configuration.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalDescription('This will generate all possible combinations of the selected attributes as variants.'),

                Tables\Actions\CreateAction::make()
                    ->visible(fn ($livewire) => $livewire->ownerRecord->has_variants)
                    ->after(function ($record) {
                        // Regenerate SKU after variant is created (in case attributes were set)
                        $record->regenerateSku();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('regenerate_sku')
                    ->label('Regenerate SKU')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function ($record) {
                        $oldSku = $record->sku;
                        $record->regenerateSku();

                        \Filament\Notifications\Notification::make()
                            ->title('SKU Regenerated')
                            ->body("SKU updated from '{$oldSku}' to '{$record->sku}'")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalDescription('This will regenerate the SKU based on the current product and attribute values.'),

                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        // Regenerate SKU after editing (in case attributes changed)
                        $record->regenerateSku();
                        // Convert attributes to JSON options for simplified system
                        $record->convertAttributesToOptions();
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('regenerate_skus')
                        ->label('Regenerate SKUs')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $oldSku = $record->sku;
                                $record->regenerateSku();
                                if ($record->sku !== $oldSku) {
                                    $count++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('SKUs Regenerated')
                                ->body("Updated {$count} variant SKUs based on current attributes.")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalDescription('This will regenerate SKUs for all selected variants based on their current attributes.'),

                    Tables\Actions\BulkAction::make('convert_to_json_options')
                        ->label('🔄 Convert to JSON Options')
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->color('success')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $oldOptions = $record->options;
                                $record->convertAttributesToOptions();
                                $record->refresh();
                                if ($record->options !== $oldOptions) {
                                    $count++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Options Converted')
                                ->body("Converted {$count} variants to JSON options system.")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalDescription('This will convert attribute values to JSON options for the simplified variant system.'),

                    Tables\Actions\BulkAction::make('set_paired_pricing')
                        ->label('💰 Set Paired Pricing')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('warning')
                        ->form([
                            Forms\Components\Placeholder::make('info')
                                ->content('Set override prices for common Storage + RAM combinations:'),
                            Forms\Components\TextInput::make('entry_price')
                                ->label('Entry Tier (8GB + 64GB)')
                                ->numeric()
                                ->prefix('$')
                                ->placeholder('Leave empty for base price'),
                            Forms\Components\TextInput::make('mid_price')
                                ->label('Mid Tier (12GB + 256GB)')
                                ->numeric()
                                ->prefix('$')
                                ->default(1300),
                            Forms\Components\TextInput::make('high_price')
                                ->label('High Tier (16GB + 512GB)')
                                ->numeric()
                                ->prefix('$')
                                ->default(1500),
                            Forms\Components\TextInput::make('premium_price')
                                ->label('Premium Tier (16GB + 1TB)')
                                ->numeric()
                                ->prefix('$')
                                ->default(1700),
                        ])
                        ->action(function ($records, $data) {
                            $updated = 0;
                            foreach ($records as $record) {
                                $options = $record->options ?? [];
                                $ram = $options['RAM'] ?? '';
                                $storage = $options['Storage'] ?? '';

                                $overridePrice = null;

                                // Match tier combinations
                                if ($ram === '8GB' && $storage === '64GB' && $data['entry_price']) {
                                    $overridePrice = $data['entry_price'] * 100;
                                } elseif ($ram === '12GB' && $storage === '256GB' && $data['mid_price']) {
                                    $overridePrice = $data['mid_price'] * 100;
                                } elseif ($ram === '16GB' && $storage === '512GB' && $data['high_price']) {
                                    $overridePrice = $data['high_price'] * 100;
                                } elseif ($ram === '16GB' && $storage === '1TB' && $data['premium_price']) {
                                    $overridePrice = $data['premium_price'] * 100;
                                }

                                if ($overridePrice !== null) {
                                    $record->override_price = $overridePrice;
                                    $record->save();
                                    $updated++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Pricing Updated')
                                ->body("Updated pricing for {$updated} variants with paired combinations.")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalDescription('This will set override prices for valid Storage + RAM combinations.'),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('is_default', 'desc');
    }
}
