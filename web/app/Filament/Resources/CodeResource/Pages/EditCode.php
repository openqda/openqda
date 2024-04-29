<?php

namespace App\Filament\Resources\CodeResource\Pages;

use App\Filament\Resources\CodeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCode extends EditRecord
{
    protected static string $resource = CodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
