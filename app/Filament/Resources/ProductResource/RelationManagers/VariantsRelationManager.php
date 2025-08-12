<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';
    
    protected static ?string $title = 'Color + Storage Variants';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Simple Color + Storage Variant')
                    ->description('Create variants with Color and Storage options - perfect for phones, laptops, etc.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('color')
                                    ->label('Color')
                                    ->options([
                                        'Black' => 'Black',
                                        'White' => 'White',
                                        'Silver' => 'Silver',
                                        'Space Gray' => 'Space Gray',
                                        'Gold' => 'Gold',
                                        'Blue' => 'Blue',
                                        'Red' => 'Red',
                                        'Green' => 'Green',
                                        'Purple' => 'Purple',
                                        'Natural Titanium' => 'Natural Titanium',
                                        'Blue Titanium' => 'Blue Titanium',
                                        'White Titanium' => 'White Titanium',
                                        'Black Titanium' => 'Black Titanium',
                                    ])
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        self::updateVariantData($state, $get('storage'), $set, $get);
                                    }),

                                Forms\Components\Select::make('storage')
                                    ->label('Storage')
                                    ->options([
                                        '64GB' => '64GB',
                                        '128GB' => '128GB',
                                        '256GB' => '256GB',
                                        '512GB' => '512GB',
                                        '1TB' => '1TB',
                                        '2TB' => '2TB',
                                    ])
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        self::updateVariantData($get('color'), $state, $set, $get);
                                    }),
                            ]),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->placeholder('Auto-generated')
                            ->helperText('Will be auto-generated based on color and storage')
                            ->disabled(),

                        Forms\Components\Hidden::make('options'),
                    ]),

                Forms\Components\Section::make('Pricing & Inventory')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('override_price_dollars')
                                    ->label('Price (USD)')
                                    ->helperText('Specific price for this variant')
                                    ->numeric()
                                    ->step(0.01)
                                    ->prefix('$')
                                    ->required()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $set('override_price', $state ? round($state * 100) : null);
                                    })
                                    ->afterStateHydrated(function ($component, $state, $record) {
                                        if ($record && $record->override_price) {
                                            $component->state($record->override_price / 100);
                                        }
                                    })
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('stock_quantity')
                                    ->label('Stock Quantity')
                                    ->numeric()
                                    ->required()
                                    ->default(10)
                                    ->minValue(0),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ]),

                        Forms\Components\Hidden::make('override_price'),
                    ]),
            ]);
    }

    public static function updateVariantData($color, $storage, $set, $get): void
    {
        if ($color && $storage) {
            // Auto-generate SKU
            $colorCode = strtoupper(substr($color, 0, 3));
            $storageCode = str_replace('GB', '', $storage);
            $productSku = 'PROD'; // Will be replaced with actual product SKU
            $sku = "{$productSku}-{$colorCode}-{$storageCode}";
            
            $set('sku', $sku);
            
            // Set options
            $set('options', [
                'Color' => $color,
                'Storage' => $storage
            ]);
        }
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
                    
                Tables\Columns\TextColumn::make('color')
                    ->label('Color')
                    ->getStateUsing(fn ($record) => $record->options['Color'] ?? 'N/A')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Black', 'Black Titanium' => 'gray',
                        'White', 'White Titanium' => 'slate',
                        'Silver' => 'zinc',
                        'Space Gray' => 'stone',
                        'Gold' => 'yellow',
                        'Blue', 'Blue Titanium' => 'blue',
                        'Red' => 'red',
                        'Green' => 'green',
                        'Purple' => 'purple',
                        'Natural Titanium' => 'amber',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('storage')
                    ->label('Storage')
                    ->getStateUsing(fn ($record) => $record->options['Storage'] ?? 'N/A')
                    ->badge()
                    ->color('indigo'),
                    
                Tables\Columns\TextColumn::make('final_price')
                    ->label('Price')
                    ->getStateUsing(fn ($record) => '$' . number_format($record->getFinalPrice(), 2))
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    }),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('color')
                    ->options([
                        'Black' => 'Black',
                        'White' => 'White',
                        'Silver' => 'Silver',
                        'Space Gray' => 'Space Gray',
                        'Gold' => 'Gold',
                        'Blue' => 'Blue',
                        'Red' => 'Red',
                        'Green' => 'Green',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $color): Builder => $query->whereJsonContains('options->Color', $color)
                        );
                    }),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Color+Storage Variant')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Set options from color and storage
                        $data['options'] = [
                            'Color' => $data['color'],
                            'Storage' => $data['storage']
                        ];

                        // Generate SKU if not provided
                        if (empty($data['sku'])) {
                            $colorCode = strtoupper(substr($data['color'], 0, 3));
                            $storageCode = str_replace('GB', '', $data['storage']);
                            $productSku = $this->getOwnerRecord()->sku ?? 'PROD';
                            $data['sku'] = "{$productSku}-{$colorCode}-{$storageCode}";
                        }

                        // Remove temporary fields
                        unset($data['color'], $data['storage']);

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->fillForm(function ($record): array {
                        $data = $record->toArray();

                        // Extract color and storage from options for editing
                        if (isset($data['options'])) {
                            $options = is_string($data['options']) ? json_decode($data['options'], true) : $data['options'];
                            $data['color'] = $options['Color'] ?? '';
                            $data['storage'] = $options['Storage'] ?? '';
                        }

                        return $data;
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        // Set options from color and storage
                        $data['options'] = [
                            'Color' => $data['color'],
                            'Storage' => $data['storage']
                        ];

                        // Update SKU if color or storage changed
                        if (!empty($data['color']) && !empty($data['storage'])) {
                            $colorCode = strtoupper(substr($data['color'], 0, 3));
                            $storageCode = str_replace('GB', '', $data['storage']);
                            $productSku = $this->getOwnerRecord()->sku ?? 'PROD';
                            $data['sku'] = "{$productSku}-{$colorCode}-{$storageCode}";
                        }

                        // Remove temporary fields
                        unset($data['color'], $data['storage']);

                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
