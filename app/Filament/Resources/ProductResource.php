<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Support\Collection;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()->can('products.view');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('products.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('products.edit');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('products.delete');
    }



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('Product Information')->schema([

                        TextInput::make('name')
                            ->label('Product Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $state, $operation, $set) {
                                if ($operation !== 'create') {
                                    return;
                                }
                                $set('slug', Str::slug($state));
                            }),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated()
                            ->unique(Product::class, 'slug', ignoreRecord: true),

                        MarkdownEditor::make('description')
                            ->columnSpanFull()
                            ->label('Long Description')
                            ->fileAttachmentsDirectory('products'),

                        MarkdownEditor::make('short_description')
                            ->columnSpanFull()
                            ->label('Short Description')
                            ->fileAttachmentsDirectory('products'),

                        TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText('Leave empty to auto-generate from brand and product name')
                            ->placeholder('Auto-generated if empty'),

                    ])->columns(2),

                    Section::make('Images')->schema([
                        FileUpload::make('images')
                            ->multiple()
                            ->disk('public')
                            ->directory('products')
                            ->maxFiles(5)
                            ->reorderable()
                    ]),

                    Section::make('Product Variants')
                        ->description('Configure product variants for different color and storage combinations')
                        ->icon('heroicon-o-squares-2x2')
                        ->schema([
                            Toggle::make('has_variants')
                                ->label('Enable Color + Storage Variants')
                                ->helperText('Turn on to create multiple variants with different colors and storage options')
                                ->live()
                                ->inline(false)
                            ->afterStateUpdated(function ($state, $set, ?Model $record) {
                                if ($record && $record->exists) {
                                    // Handle transition for existing products
                                    if ($state && !$record->has_variants) {
                                        // Converting to variant product - create default variant
                                        \App\Services\ProductVariantTransitionService::convertToVariantProduct($record);

                                        // Set default variant data in form
                                        $defaultVariant = $record->variants()->where('is_default', true)->first();
                                        if ($defaultVariant) {
                                            $set('variants', [[
                                                'color' => $defaultVariant->getColor(),
                                                'storage' => $defaultVariant->getStorage(),
                                                'override_price_dollars' => $defaultVariant->getFinalPrice(),
                                                'stock_quantity' => $defaultVariant->stock_quantity,
                                                'is_active' => $defaultVariant->is_active,
                                                'sku' => $defaultVariant->sku,
                                                'options' => $defaultVariant->options,
                                                'override_price' => $defaultVariant->override_price,
                                            ]]);
                                        }
                                    } elseif (!$state && $record->has_variants) {
                                        // Converting to simple product - consolidate variants
                                        \App\Services\ProductVariantTransitionService::convertToSimpleProduct($record);
                                        $set('variants', []);
                                    }
                                }
                            }),

                            Forms\Components\Placeholder::make('variant_help')
                                ->label('💡 Variant System Guide')
                                ->content(new \Illuminate\Support\HtmlString('
                                    <div class="space-y-2 text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="text-green-600">✅</span>
                                            <span>Perfect for phones, laptops, tablets with multiple options</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-green-600">✅</span>
                                            <span>Each variant has unique Color + Storage combination</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-green-600">✅</span>
                                            <span>Individual pricing and stock management per variant</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-red-600">⚠️</span>
                                            <span><strong>Required:</strong> You must add at least one variant with specific color and storage</span>
                                        </div>
                                        <div class="mt-3 p-3 bg-blue-50 rounded-lg">
                                            <strong>Examples:</strong> iPhone (Black 128GB, Blue 256GB), MacBook (Silver 512GB, Space Gray 1TB)
                                        </div>
                                    </div>
                                '))
                                ->visible(fn ($get) => $get('has_variants')),

                            Forms\Components\Placeholder::make('transition_warning')
                                ->label('⚠️ Important: Mode Transition')
                                ->content(function (?Model $record) {
                                    if (!$record || !$record->exists) {
                                        return new \Illuminate\Support\HtmlString('
                                            <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm">
                                                <strong>New Product:</strong> Choose whether this product will have variants before saving.
                                            </div>
                                        ');
                                    }

                                    $conflicts = \App\Services\ProductVariantTransitionService::validateAndFixConflicts($record);

                                    if ($conflicts['has_conflicts']) {
                                        $issuesList = implode('<br>• ', $conflicts['issues']);
                                        return new \Illuminate\Support\HtmlString('
                                            <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm">
                                                <strong>⚠️ Conflicts Detected:</strong><br>
                                                • ' . $issuesList . '<br><br>
                                                <strong>Auto-fix available:</strong> Save the form to automatically resolve these conflicts.
                                            </div>
                                        ');
                                    }

                                    return new \Illuminate\Support\HtmlString('
                                        <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm">
                                            <strong>✅ No Conflicts:</strong> Product pricing and inventory are properly configured.
                                        </div>
                                    ');
                                })
                                ->visible(fn (?Model $record) => $record && $record->exists),
                        ])
                        ->collapsed(false),

                    // Dynamic Variant Creation Section
                    Section::make('Create Product Variants')
                        ->description('Add specific color and storage combinations with individual pricing and inventory')
                        ->icon('heroicon-o-swatch')
                        ->schema([
                            Repeater::make('variants')
                                ->label('Product Variants')
                                ->helperText('Add multiple color and storage combinations for this product')
                                ->afterStateHydrated(function (Repeater $component, ?Model $record) {
                                    if ($record && $record->exists && $record->has_variants) {
                                        // Load existing variants and convert them to form data
                                        $variants = $record->variants()->get();
                                        $variantData = [];

                                        foreach ($variants as $variant) {
                                            $options = is_string($variant->options) ? json_decode($variant->options, true) : $variant->options;
                                            $variantData[] = [
                                                'id' => $variant->id,
                                                'color' => $options['Color'] ?? '',
                                                'storage' => $options['Storage'] ?? '',
                                                'override_price_dollars' => $variant->override_price ? $variant->override_price / 100 : null,
                                                'stock_quantity' => $variant->stock_quantity,
                                                'is_active' => $variant->is_active,
                                                'sku' => $variant->sku,
                                                'options' => $variant->options,
                                                'override_price' => $variant->override_price,
                                            ];
                                        }

                                        $component->state($variantData);
                                    }
                                })
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
                                                    'Starlight' => 'Starlight',
                                                    'Midnight' => 'Midnight',
                                                    'Gold' => 'Gold',
                                                    'Blue' => 'Blue',
                                                    'Red' => 'Red',
                                                    'Green' => 'Green',
                                                    'Purple' => 'Purple',
                                                    'Pink' => 'Pink',
                                                    'Yellow' => 'Yellow',
                                                    'Phantom Black' => 'Phantom Black',
                                                    'Cream' => 'Cream',
                                                    'Lavender' => 'Lavender',
                                                    'Mint' => 'Mint',
                                                    'Platinum Silver' => 'Platinum Silver',
                                                    'Graphite' => 'Graphite',
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
                                                })
                                                ->dehydrated(false), // Don't save this field directly

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
                                                })
                                                ->dehydrated(false), // Don't save this field directly
                                        ]),

                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('override_price_dollars')
                                                ->label('Price ($)')
                                                ->helperText('Specific price for this variant')
                                                ->numeric()
                                                ->step(0.01)
                                                ->prefix('$')
                                                ->required()
                                                ->afterStateUpdated(function ($state, $set) {
                                                    $set('override_price', $state ? round($state * 100) : null);
                                                })
                                                ->dehydrated(false),

                                            Forms\Components\TextInput::make('stock_quantity')
                                                ->label('Stock')
                                                ->numeric()
                                                ->required()
                                                ->default(10)
                                                ->minValue(0),

                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Active')
                                                ->default(true),
                                        ]),

                                    Forms\Components\TextInput::make('sku')
                                        ->label('SKU')
                                        ->placeholder('Auto-generated')
                                        ->helperText('Will be auto-generated based on color and storage')
                                        ->disabled()
                                        ->dehydrated(),

                                    Forms\Components\Hidden::make('options'),
                                    Forms\Components\Hidden::make('override_price'),
                                ])
                                ->addActionLabel('Add Variant')
                                ->reorderable(false)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string =>
                                    isset($state['color'], $state['storage'])
                                        ? "{$state['color']} - {$state['storage']}"
                                        : 'New Variant'
                                )
                                ->defaultItems(0)
                                ->minItems(0)
                        ])
                        ->visible(fn ($get) => $get('has_variants'))
                        ->collapsed(false),

                    Section::make('SEO & Meta Data')
                        ->description('Optimize your product for search engines')
                        ->icon('heroicon-o-magnifying-glass')
                        ->collapsed(true)
                        ->schema([

                        TextInput::make('meta_title')
                            ->maxLength(255),

                        Textarea::make('meta_description')
                            ->autosize(),

                        TextInput::make('meta_keywords')
                            ->maxLength(255),

                    ])
                ])->columnSpan(2),

                Group::make()->schema([

                    Section::make('Pricing & Cost Management')
                        ->description('Set pricing information in US Dollars')
                        ->icon('heroicon-o-currency-dollar')
                        ->schema([
                        TextInput::make('price')
                            ->label('Base Price (USD)')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->step(0.01)
                            ->default(0)
                            ->helperText(fn (Get $get): string =>
                                $get('has_variants')
                                    ? 'Base price for variants (variants can override this price). Set to 0 if all variants have override prices.'
                                    : 'Current selling price in US Dollars'
                            ),

                        TextInput::make('compare_price')
                            ->label('Compare Price (USD)')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->helperText('Original/MSRP price in US Dollars for showing discounts'),

                        TextInput::make('cost_price')
                            ->label('Cost Price (USD)')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->helperText('Cost price in US Dollars for profit calculations'),
                    ]),

                    Section::make('Inventory Management')->schema([
                        TextInput::make('stock_quantity')
                            ->label('Stock Quantity')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0)
                            ->rules(['integer', 'min:0'])
                            ->helperText('Current stock quantity (only used for products without variants)')
                            ->hidden(fn (Get $get): bool => $get('has_variants')),

                        // Stock status is now calculated automatically - no manual editing
                        Placeholder::make('stock_status_display')
                            ->label('Stock Status')
                            ->content(function (?Model $record): string {
                                if (!$record) {
                                    return 'Not calculated yet';
                                }

                                $status = \App\Services\InventoryService::getStockStatus($record);
                                return ucfirst(str_replace('_', ' ', $status));
                            })
                            ->hidden(fn (Get $get): bool => $get('has_variants'))
                            ->helperText('Stock status is calculated automatically based on stock quantity'),

                        TextInput::make('low_stock_threshold')
                            ->label('Low Stock Threshold')
                            ->numeric()
                            ->required()
                            ->default(5)
                            ->minValue(0)
                            ->helperText('Alert when stock falls below this number'),

                        Toggle::make('track_inventory')
                            ->label('Track Inventory')
                            ->default(true)
                            ->helperText('Enable inventory tracking for this product'),

                        // Display calculated stock info for products with variants
                        Placeholder::make('calculated_stock_info')
                            ->label('Stock Information')
                            ->content(function (?Model $record): string {
                                if (!$record || !$record->has_variants) {
                                    return '';
                                }

                                $totalStock = \App\Services\InventoryService::getTotalStock($record);
                                $status = \App\Services\InventoryService::getStockStatus($record);

                                return "Total Stock: {$totalStock} | Status: " . ucfirst(str_replace('_', ' ', $status)) .
                                       " | Managed by variants";
                            })
                            ->visible(fn (Get $get): bool => $get('has_variants')),
                    ]),

                    Section::make('Associations')->schema([

                        Select::make('category_id')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship('category', 'name'),

                        Select::make('brand_id')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship('brand', 'name'),
                    ]),

                    Section::make('Status')->schema([

                        Toggle::make('is_active')
                            ->required()
                            ->default(true),

                        Toggle::make('is_featured')
                            ->required(),

                        Toggle::make('on_sale')
                            ->required(),

                    ])

                ])->columnSpan(1)
            ])->columns(3);
    }

    /**
     * Update variant data when color or storage changes
     */
    public static function updateVariantData($color, $storage, $set, $get): void
    {
        if ($color && $storage) {
            // Auto-generate SKU
            $colorCode = strtoupper(substr($color, 0, 3));
            $storageCode = str_replace('GB', '', $storage);

            // Try to get product SKU from form, fallback to placeholder
            $productSku = $get('../../sku') ?: $get('sku') ?: 'PROD';
            $sku = "{$productSku}-{$colorCode}-{$storageCode}";

            $set('sku', $sku);

            // Set options
            $set('options', [
                'Color' => $color,
                'Storage' => $storage
            ]);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('Image')
                    ->circular()
                    ->stacked()
                    ->limit(1)
                    ->limitedRemainingText()
                    ->getStateUsing(fn ($record) => $record->images ? (is_array($record->images) ? $record->images[0] ?? null : $record->images) : null)
                    ->defaultImageUrl(url('/images/placeholder-product.png'))
                    ->size(40),

                Tables\Columns\TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->sku)
                    ->wrap(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Brand')
                    ->sortable()
                    ->badge()
                    ->color('indigo'),

                Tables\Columns\TextColumn::make('variants_count')
                    ->label('Variants')
                    ->getStateUsing(function ($record) {
                        if (!$record->has_variants) {
                            return 'Simple Product';
                        }
                        $count = $record->variants()->count();
                        return $count . ' variant' . ($count !== 1 ? 's' : '');
                    })
                    ->badge()
                    ->color(fn ($record) => $record->has_variants ? 'success' : 'gray')
                    ->icon(fn ($record) => $record->has_variants ? 'heroicon-o-squares-2x2' : 'heroicon-o-cube'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable()
                    ->description(fn ($record) => $record->has_variants ? 'Base price' : 'Selling price')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('compare_price')
                    ->label('Compare Price')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('stock_info')
                    ->label('Stock')
                    ->sortable(false)
                    ->getStateUsing(function ($record) {
                        $totalStock = \App\Services\InventoryService::getTotalStock($record);
                        $status = \App\Services\InventoryService::getStockStatus($record);

                        return [
                            'quantity' => $totalStock,
                            'status' => $status,
                            'display' => $totalStock . ' units'
                        ];
                    })
                    ->formatStateUsing(fn ($state) => $state['display'] ?? '0 units')
                    ->badge()
                    ->color(function ($record) {
                        $status = \App\Services\InventoryService::getStockStatus($record);
                        return match ($status) {
                            'in_stock' => 'success',
                            'low_stock' => 'warning',
                            'out_of_stock' => 'danger',
                            default => 'gray'
                        };
                    })
                    ->icon(function ($record) {
                        $status = \App\Services\InventoryService::getStockStatus($record);
                        return match ($status) {
                            'in_stock' => 'heroicon-o-check-circle',
                            'low_stock' => 'heroicon-o-exclamation-triangle',
                            'out_of_stock' => 'heroicon-o-x-circle',
                            default => 'heroicon-o-question-mark-circle'
                        };
                    })
                    ->tooltip(function ($record) {
                        $status = \App\Services\InventoryService::getStockStatus($record);
                        return match ($status) {
                            'in_stock' => 'In Stock',
                            'low_stock' => 'Low Stock - Consider restocking',
                            'out_of_stock' => 'Out of Stock',
                            default => 'Unknown Status'
                        };
                    }),

                Tables\Columns\TextColumn::make('calculated_status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        return \App\Services\InventoryService::getStockStatus($record);
                    })
                    ->color(function ($record) {
                        $status = \App\Services\InventoryService::getStockStatus($record);
                        return match ($status) {
                            'in_stock' => 'success',
                            'low_stock' => 'warning',
                            'out_of_stock' => 'danger',
                            default => 'gray'
                        };
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                        default => ucfirst(str_replace('_', ' ', $state))
                    }),

                Tables\Columns\TextColumn::make('product_status')
                    ->label('Product Status')
                    ->getStateUsing(function ($record) {
                        $statuses = [];

                        if (!$record->is_active) {
                            $statuses[] = 'Inactive';
                        } else {
                            $statuses[] = 'Active';
                        }

                        if ($record->is_featured) {
                            $statuses[] = 'Featured';
                        }

                        if ($record->on_sale) {
                            $statuses[] = 'On Sale';
                        }

                        return $statuses;
                    })
                    ->formatStateUsing(function ($state) {
                        return collect($state)->join(' • ');
                    })
                    ->badge()
                    ->color(function ($record) {
                        if (!$record->is_active) return 'danger';
                        if ($record->is_featured) return 'warning';
                        if ($record->on_sale) return 'info';
                        return 'success';
                    })
                    ->icon(function ($record) {
                        if (!$record->is_active) return 'heroicon-o-x-circle';
                        if ($record->is_featured) return 'heroicon-o-star';
                        if ($record->on_sale) return 'heroicon-o-tag';
                        return 'heroicon-o-check-circle';
                    }),

                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable()
                    ->copyMessage('SKU copied!')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('brand')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('price_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price_from')
                                    ->label('Min Price ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('0'),
                                Forms\Components\TextInput::make('price_to')
                                    ->label('Max Price ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('1000'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['price_from'],
                                fn (Builder $query, $price): Builder => $query->where('price_cents', '>=', $price * 100),
                            )
                            ->when(
                                $data['price_to'],
                                fn (Builder $query, $price): Builder => $query->where('price_cents', '<=', $price * 100),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['price_from'] ?? null) {
                            $indicators['price_from'] = 'Min: ₹' . number_format($data['price_from']);
                        }
                        if ($data['price_to'] ?? null) {
                            $indicators['price_to'] = 'Max: ₹' . number_format($data['price_to']);
                        }
                        return $indicators;
                    }),

                SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'out_of_stock' => 'Out of Stock',
                        'back_order' => 'Back Order',
                    ])
                    ->default('in_stock'),

                Filter::make('is_active')
                    ->label('Active Only')
                    ->default()
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
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
                        ->modalDescription('This will regenerate the SKU based on the current brand and product name. All variant SKUs will also be updated.'),

                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()->requiresConfirmation(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each->update(['is_active' => true]);

                            \Filament\Notifications\Notification::make()
                                ->title('Products Activated')
                                ->body($records->count() . ' products have been activated.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (Collection $records) {
                            $records->each->update(['is_active' => false]);

                            \Filament\Notifications\Notification::make()
                                ->title('Products Deactivated')
                                ->body($records->count() . ' products have been deactivated.')
                                ->warning()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('feature')
                        ->label('Mark as Featured')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each->update(['is_featured' => true]);

                            \Filament\Notifications\Notification::make()
                                ->title('Products Featured')
                                ->body($records->count() . ' products have been marked as featured.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
