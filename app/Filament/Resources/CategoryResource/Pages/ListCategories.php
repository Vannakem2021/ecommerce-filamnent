<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label('New Category'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->withCount('products');
    }

    public function getTableFiltersForm(): \Filament\Forms\Form
    {
        return parent::getTableFiltersForm()
            ->live(onBlur: true)
            ->debounce(500);
    }

    protected function getTableActions(): array
    {
        return parent::getTableActions();
    }


}
