<?php

namespace App\Filament\Resources\CodeSeries\Pages;

use App\Filament\Resources\CodeSeries\CodeSeriesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCodeSeries extends EditRecord
{
    protected static string $resource = CodeSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
