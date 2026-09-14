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

    /**
     * Column visibility survives logout/browser restart by storing it on
     * the user record instead of the session (which is wiped on logout).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function loadTableColumnsFromSession(): array
    {
        return auth()->user()->book_catalogs_table_columns
            ?? $this->getDefaultTableColumnState();
    }

    protected function persistTableColumns(): void
    {
        if (! $this->getTable()->persistsColumnsInSession()) {
            return;
        }

        auth()->user()->update([
            'book_catalogs_table_columns' => $this->tableColumns,
        ]);
    }

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
