<?php

namespace App\Filament\Resources\ProductAttributeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('value')
                    ->label('Value')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $state, $operation, $set) {
                        if ($operation !== 'create') {
                            return;
                        }
                        $set('slug', \Illuminate\Support\Str::slug($state));
                    }),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->disabled()
                    ->dehydrated(),

                Forms\Components\TextInput::make('color_code')
                    ->label('Color Code')
                    ->placeholder('#FF0000')
                    ->helperText('For color attributes only')
                    ->visible(fn ($livewire) => $livewire->ownerRecord->type === 'color'),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(2),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('value')
            ->columns([
                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\ColorColumn::make('color_code')
                    ->label('Color')
                    ->visible(fn ($livewire) => $livewire->ownerRecord->type === 'color'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sort Order')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_active')
                    ->toggle(),
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
            ])
            ->defaultSort('sort_order');
    }
}
