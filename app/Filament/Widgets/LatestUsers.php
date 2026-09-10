<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestUsers extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()->latest('id')
            )
            ->heading(
                app()->getLocale() === 'ar'
                    ? 'أحدث المستخدمين'
                    : 'Latest Users'
            )
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label(app()->getLocale() === 'ar' ? 'البريد الإلكتروني' : 'Email')
                    ->formatStateUsing(
                        fn ($state, $record) =>
                            $state
                            ?: $record->username
                            ?: ('User #' . $record->id)
                    )
                    ->searchable(),

                Tables\Columns\TextColumn::make('fullname')
                    ->label(app()->getLocale() === 'ar' ? 'الاسم' : 'Name')
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\TextColumn::make('country')
                    ->label(app()->getLocale() === 'ar' ? 'الدولة' : 'Country')
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(app()->getLocale() === 'ar' ? 'فعّال' : 'Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_admin')
                    ->label(app()->getLocale() === 'ar' ? 'مدير' : 'Admin')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(app()->getLocale() === 'ar' ? 'تاريخ التسجيل' : 'Registered At')
                    ->formatStateUsing(function ($state, $record) {
                        $date = $record->drupal_created_at ?? $record->created_at;

                        return $date
                            ? \Carbon\Carbon::parse($date)->format('Y-m-d H:i')
                            : '—';
                    })
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}
