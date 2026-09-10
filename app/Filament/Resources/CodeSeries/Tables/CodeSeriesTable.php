<?php

namespace App\Filament\Resources\CodeSeries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CodeSeriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('prefix')
                    ->label('پیشوند')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('country')
                    ->label('کشور')
                    ->sortable(),

                TextColumn::make('series_start')
                    ->label('شروع سری')
                    ->sortable(),

                TextColumn::make('series_end')
                    ->label('پایان سری')
                    ->sortable(),

                TextColumn::make('codes_count')
                    ->counts('codes')
                    ->label('تعداد کدها')
                    ->sortable(),

                TextColumn::make('drupal_nid')
                    ->label('Drupal NID')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('ویرایش'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}