<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->label('Edit Category'),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Category')
                ->modalDescription('Are you sure you want to delete this category? This action cannot be undone and will affect all associated products.')
                ->modalSubmitActionLabel('Yes, delete it')
                ->icon('heroicon-o-trash'),
        ];
    }
}
