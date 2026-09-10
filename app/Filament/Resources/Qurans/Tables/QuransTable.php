<?php

namespace App\Filament\Resources\Qurans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuransTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('title')
                    ->label(__('admin.title'))
                    ->searchable(),

                TextColumn::make('language.name')
                    ->label(__('admin.language')),

                TextColumn::make('folder')
                    ->label(__('admin.folder')),

                TextColumn::make('reading_count')
                    ->label(__('admin.readings')),

                TextColumn::make('version')
                    ->label(__('admin.version')),

                TextColumn::make('content_version')
                    ->label(__('admin.content_version')),

                IconColumn::make('is_published')
                    ->label(__('admin.is_published'))
                    ->boolean(),
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