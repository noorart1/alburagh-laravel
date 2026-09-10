<?php

namespace App\Filament\Resources\BookCatalogs\Pages;

use App\Filament\Exports\BookCatalogExporter;
use App\Filament\Resources\BookCatalogs\BookCatalogResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListBookCatalogs extends ListRecords
{
    protected static string $resource = BookCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(app()->getLocale() === 'ar' ? 'إضافة كتاب' : 'Add Book'),

            ExportAction::make('exportExcel')
                ->label(app()->getLocale() === 'ar' ? 'تصدير Excel' : 'Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->exporter(BookCatalogExporter::class)
                ->formats([
                    ExportFormat::Xlsx,
                ])
                ->enableVisibleTableColumnsByDefault()
                ->columnMappingColumns(3)
                ->modifyQueryUsing(function (Builder $query, array $options): Builder {
                    if (($options['export_scope'] ?? 'all') !== 'range') {
                        return $query;
                    }

                    $from = $options['catalog_number_from'] ?? null;
                    $to = $options['catalog_number_to'] ?? null;

                    if (filled($from)) {
                        $query->where('catalog_number', '>=', (int) $from);
                    }

                    if (filled($to)) {
                        $query->where('catalog_number', '<=', (int) $to);
                    }

                    return $query;
                })
                ->maxRows(10000),
        ];
    }
}
