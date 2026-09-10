<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\UserSubscription;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestUsersAndSubscriptions extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                UserSubscription::query()
                    ->with(['user', 'subscriptionCode'])
                    ->latest('subscription_date')
            )
            ->heading(
                app()->getLocale() === 'ar'
                    ? 'آخر الاشتراكات'
                    : 'Latest Subscriptions'
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->label(app()->getLocale() === 'ar' ? 'البريد الإلكتروني' : 'Email')
                    ->formatStateUsing(
                        fn ($state, $record) =>
                            $state
                            ?: $record->user?->username
                            ?: ('User #' . ($record->user_id ?? '—'))
                    )
                    ->searchable(),

                Tables\Columns\TextColumn::make('subscriptionCode.code')
                    ->label(app()->getLocale() === 'ar' ? 'رمز الاشتراك' : 'Subscription Code')
                    ->formatStateUsing(
                        fn ($state, $record) =>
                            $state
                            ?: $record->subscriptionCode?->serial
                            ?: '—'
                    ),

                Tables\Columns\TextColumn::make('amount')
                    ->label(app()->getLocale() === 'ar' ? 'المدة' : 'Duration')
                    ->suffix(app()->getLocale() === 'ar' ? ' يوم' : ' days'),

                Tables\Columns\TextColumn::make('subscription_date')
                    ->label(app()->getLocale() === 'ar' ? 'تاريخ الاشتراك' : 'Subscription Date')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label(app()->getLocale() === 'ar' ? 'تاريخ الانتهاء' : 'Expires At')
                    ->formatStateUsing(
                        fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('Y-m-d H:i') : '—'
                    ),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }
}
