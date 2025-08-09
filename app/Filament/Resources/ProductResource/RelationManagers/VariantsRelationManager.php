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

                Forms\Components\Section::make('Variant Attributes')
                    ->schema([
                        Forms\Components\Placeholder::make('attribute_info')
                            ->label('')
                            ->content('Select attribute values for this variant. The SKU will be auto-generated based on these selections.')
                            ->visible(fn ($livewire) => $livewire->ownerRecord->has_variants && $livewire->ownerRecord->variant_attributes),
                    ])
                    ->visible(fn ($livewire) => $livewire->ownerRecord->has_variants && $livewire->ownerRecord->variant_attributes),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Selling Price')
                            ->numeric()
                            ->required()
                            ->prefix('INR')
                            ->step(0.01),

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
                        ->label('Convert to JSON Options')
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

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('is_default', 'desc');
    }
}
