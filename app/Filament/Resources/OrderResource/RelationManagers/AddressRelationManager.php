<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AddressRelationManager extends RelationManager
{
    protected static string $relationship = 'address';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('type')
                    ->options([
                        'shipping' => 'Shipping',
                        'billing' => 'Billing',
                    ])
                    ->default('shipping')
                    ->required()
                    ->label('Address Type'),

                TextInput::make('contact_name')
                    ->required()
                    ->maxLength(255)
                    ->label('Contact Name'),

                TextInput::make('phone_number')
                    ->required()
                    ->tel()
                    ->maxLength(20)
                    ->label('Phone Number'),

                TextInput::make('house_number')
                    ->maxLength(100)
                    ->label('House Number'),

                TextInput::make('street_number')
                    ->maxLength(100)
                    ->label('Street Number'),

                TextInput::make('city_province')
                    ->required()
                    ->maxLength(255)
                    ->label('City/Province'),

                TextInput::make('district_khan')
                    ->required()
                    ->maxLength(255)
                    ->label('District/Khan'),

                TextInput::make('commune_sangkat')
                    ->required()
                    ->maxLength(255)
                    ->label('Commune/Sangkat'),

                TextInput::make('postal_code')
                    ->required()
                    ->maxLength(10)
                    ->label('Postal Code'),

                Textarea::make('additional_info')
                    ->maxLength(500)
                    ->label('Additional Information')
                    ->columnSpanFull(),

                Toggle::make('is_default')
                    ->label('Default Address'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('contact_name')
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'shipping' => 'success',
                        'billing' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('contact_name')
                    ->label('Contact Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone_number')
                    ->label('Phone')
                    ->searchable(),

                TextColumn::make('city_province')
                    ->label('City/Province')
                    ->searchable(),

                TextColumn::make('district_khan')
                    ->label('District/Khan')
                    ->searchable(),

                TextColumn::make('postal_code')
                    ->label('Postal Code')
                    ->searchable(),

                TextColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
