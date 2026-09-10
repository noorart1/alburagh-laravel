<?php

namespace App\Filament\Resources\Books\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BooksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('title')
                    ->label(__('admin.title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('language.name')
                    ->label(__('admin.language'))
                    ->sortable(),

                TextColumn::make('bookDetail.author.name')
                    ->label(__('admin.author'))
                    ->searchable(),

                TextColumn::make('bookDetail.category.name')
                    ->label(__('admin.category')),

                TextColumn::make('bookDetail.series.name')
                    ->label(__('admin.series')),

                TextColumn::make('bookDetail.ageGroup.name')
                    ->label(__('admin.age_group')),

                TextColumn::make('bookDetail.rate')
                    ->label(__('admin.rate'))
                    ->sortable(),

                TextColumn::make('reading_count')
                    ->label(__('admin.readings'))
                    ->sortable(),

                IconColumn::make('is_published')
                    ->label(__('admin.is_published'))
                    ->boolean(),
            ])

            ->defaultSort('sort_order')

            ->filters([
                //
            ])

            ->recordActions([
                EditAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}