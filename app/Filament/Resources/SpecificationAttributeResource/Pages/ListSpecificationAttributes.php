<?php

namespace App\Filament\Resources\SpecificationAttributeResource\Pages;

use App\Filament\Resources\SpecificationAttributeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSpecificationAttributes extends ListRecords
{
    protected static string $resource = SpecificationAttributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
