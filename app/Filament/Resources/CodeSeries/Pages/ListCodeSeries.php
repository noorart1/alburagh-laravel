<?php

namespace App\Filament\Resources\CodeSeries\Pages;

use App\Filament\Resources\CodeSeries\CodeSeriesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCodeSeries extends ListRecords
{
    protected static string $resource = CodeSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
