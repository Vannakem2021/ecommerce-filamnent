<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMethodResource\Pages;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Payment Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('Unique identifier for this payment method'),
                        Forms\Components\Select::make('provider')
                            ->required()
                            ->options([
                                'aba_pay' => 'ABA Pay',
                                'manual' => 'Manual (COD, Bank Transfer)',
                            ])
                            ->default('aba_pay'),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Enable/disable this payment method'),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Display order (lower numbers appear first)'),
                        Forms\Components\TextInput::make('icon')
                            ->maxLength(255)
                            ->helperText('CSS class for icon (e.g., fas fa-credit-card)'),
                    ])->columns(3),

                Forms\Components\Section::make('Limits & Currencies')
                    ->schema([
                        Forms\Components\TextInput::make('min_amount')
                            ->numeric()
                            ->step(0.01)
                            ->helperText('Minimum transaction amount'),
                        Forms\Components\TextInput::make('max_amount')
                            ->numeric()
                            ->step(0.01)
                            ->helperText('Maximum transaction amount'),
                        Forms\Components\TagsInput::make('supported_currencies')
                            ->default(['USD', 'KHR'])
                            ->helperText('Supported currencies for this payment method'),
                    ])->columns(3),

                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\KeyValue::make('configuration')
                            ->keyLabel('Setting')
                            ->valueLabel('Value')
                            ->helperText('Provider-specific configuration options')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aba_pay' => 'success',
                        'manual' => 'gray',
                        default => 'primary',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supported_currencies')
                    ->badge()
                    ->separator(','),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->options([
                        'aba_pay' => 'ABA Pay',
                        'manual' => 'Manual',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (PaymentMethod $record): string => $record->is_active ? 'Disable' : 'Enable')
                    ->icon(fn (PaymentMethod $record): string => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (PaymentMethod $record): string => $record->is_active ? 'danger' : 'success')
                    ->action(fn (PaymentMethod $record) => $record->update(['is_active' => !$record->is_active]))
                    ->requiresConfirmation(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
