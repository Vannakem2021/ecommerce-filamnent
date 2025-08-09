<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpecificationAttributeResource\Pages;
use App\Filament\Resources\SpecificationAttributeResource\RelationManagers;
use App\Models\SpecificationAttribute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class SpecificationAttributeResource extends Resource
{
    protected static ?string $model = SpecificationAttribute::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Specifications';

    protected static ?string $modelLabel = 'Specification Attribute';

    protected static ?string $pluralModelLabel = 'Specification Attributes';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $operation, $state, $set) =>
                                        $operation === 'create' ? $set('code', Str::slug($state, '_')) : null
                                    ),

                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(SpecificationAttribute::class, 'code', ignoreRecord: true)
                                    ->helperText('Unique identifier (auto-generated from name)'),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->rows(3)
                            ->helperText('Description of what this specification represents'),
                    ]),

                Forms\Components\Section::make('Data Configuration')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('data_type')
                                    ->required()
                                    ->options([
                                        'text' => 'Text',
                                        'number' => 'Number',
                                        'boolean' => 'Yes/No',
                                        'enum' => 'Select Option',
                                    ])
                                    ->default('text')
                                    ->helperText('How values should be stored and displayed'),

                                Forms\Components\TextInput::make('unit')
                                    ->maxLength(50)
                                    ->helperText('Unit of measurement (GB, GHz, inch, etc.)')
                                    ->visible(fn (Forms\Get $get) => $get('data_type') === 'number'),

                                Forms\Components\Select::make('scope')
                                    ->required()
                                    ->options([
                                        'product' => 'Product Level (same for all variants)',
                                        'variant' => 'Variant Level (changes per variant)',
                                    ])
                                    ->default('product')
                                    ->helperText('Whether this spec is the same for all variants or varies'),
                            ]),
                    ]),

                Forms\Components\Section::make('Display Options')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Order in which specifications appear'),

                                Forms\Components\Toggle::make('is_active')
                                    ->default(true)
                                    ->helperText('Whether this specification is active'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_filterable')
                                    ->default(true)
                                    ->helperText('Can be used for filtering products'),

                                Forms\Components\Toggle::make('is_required')
                                    ->default(false)
                                    ->helperText('Required for all products'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (SpecificationAttribute $record): string => $record->code),

                Tables\Columns\TextColumn::make('data_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'text' => 'gray',
                        'number' => 'success',
                        'boolean' => 'warning',
                        'enum' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'text' => 'Text',
                        'number' => 'Number',
                        'boolean' => 'Yes/No',
                        'enum' => 'Options',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('unit')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('scope')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'product' => 'success',
                        'variant' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'product' => 'Product',
                        'variant' => 'Variant',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('is_filterable')
                    ->boolean()
                    ->label('Filterable')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_required')
                    ->boolean()
                    ->label('Required')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('data_type')
                    ->options([
                        'text' => 'Text',
                        'number' => 'Number',
                        'boolean' => 'Yes/No',
                        'enum' => 'Options',
                    ]),

                Tables\Filters\SelectFilter::make('scope')
                    ->options([
                        'product' => 'Product Level',
                        'variant' => 'Variant Level',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->default(),

                Tables\Filters\TernaryFilter::make('is_filterable')
                    ->label('Filterable'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpecificationAttributes::route('/'),
            'create' => Pages\CreateSpecificationAttribute::route('/create'),
            'edit' => Pages\EditSpecificationAttribute::route('/{record}/edit'),
        ];
    }
}
