<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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

                    Section::make('Variants Configuration (Simplified System)')->schema([
                        Toggle::make('has_variants')
                            ->label('Has Variants')
                            ->helperText('Enable if this product has multiple variants (color, size, etc.). Use the Variants tab below to manage individual variants with JSON options.')
                            ->live(),

                        Forms\Components\Textarea::make('attributes')
                            ->label('Product Attributes (JSON)')
                            ->helperText('Optional: Add product-level attributes as JSON (e.g., {"Brand": "Apple", "Screen Size": "6.1 inch"})')
                            ->rows(3)
                            ->visible(fn ($get) => $get('has_variants'))
                            ->placeholder('{"Brand": "Apple", "Screen Size": "6.1 inch", "Operating System": "iOS"}'),
                    ]),

                    Section::make('SEO Data')->schema([

                        TextInput::make('meta_title')
                            ->maxLength(255),

                        Textarea::make('meta_description')
                            ->autosize(),

                        TextInput::make('meta_keywords')
                            ->maxLength(255),

                    ])
                ])->columnSpan(2),

                Group::make()->schema([

                    Section::make('Pricing')->schema([
                        TextInput::make('price')
                            ->label('Selling Price')
                            ->numeric()
                            ->required()
                            ->prefix('INR')
                            ->step(0.01)
                            ->helperText('Current selling price'),

                        TextInput::make('compare_price')
                            ->label('Compare Price')
                            ->numeric()
                            ->prefix('INR')
                            ->step(0.01)
                            ->helperText('Original/MSRP price for showing discounts'),

                        TextInput::make('cost_price')
                            ->label('Cost Price')
                            ->numeric()
                            ->prefix('INR')
                            ->step(0.01)
                            ->helperText('Cost price for profit calculations'),
                    ]),

                    Section::make('Inventory Management')->schema([
                        TextInput::make('stock_quantity')
                            ->label('Stock Quantity')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Current stock quantity'),

                        Select::make('stock_status')
                            ->label('Stock Status')
                            ->options([
                                'in_stock' => 'In Stock',
                                'out_of_stock' => 'Out of Stock',
                                'back_order' => 'Back Order',
                            ])
                            ->required()
                            ->default('in_stock'),

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

                        Toggle::make('in_stock')
                            ->required()
                            ->default(true),

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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->sortable(),

                Tables\Columns\TextColumn::make('brand.name')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Selling Price')
                    ->money('INR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('compare_price')
                    ->label('Compare Price')
                    ->money('INR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('in_stock')
                    ->label('In Stock')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('on_sale')
                    ->label('On Sale')
                    ->boolean()
                    ->trueIcon('heroicon-o-tag')
                    ->falseIcon('heroicon-o-tag')
                    ->trueColor('info')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name'),

                SelectFilter::make('brand')
                    ->relationship('brand', 'name'),

                Filter::make('is_featured')
                    ->toggle(),

                Filter::make('in_stock')
                    ->toggle(),

                Filter::make('on_sale')
                    ->toggle(),

                Filter::make('is_active')
                    ->toggle(),

                SelectFilter::make('stock_status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'out_of_stock' => 'Out of Stock',
                        'back_order' => 'Back Order',
                    ]),

                Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('stock_quantity <= low_stock_threshold'))
                    ->toggle(),

                Filter::make('track_inventory')
                    ->toggle()
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
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->defaultSort('created_at', 'desc');
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
