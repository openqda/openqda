<?php

namespace App\Filament\Resources\SelectionResource\Pages;

use App\Filament\Resources\SelectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSelections extends ListRecords
{
    protected static string $resource = SelectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
