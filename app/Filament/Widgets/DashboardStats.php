<?php

namespace App\Filament\Widgets;

use App\Models\Content;
use App\Models\SubscriptionCode;
use App\Models\User;
use App\Models\UserSubscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $now = now();

        return [
            Stat::make(
                app()->getLocale() === 'ar' ? 'إجمالي المستخدمين' : 'Total Users',
                User::count()
            ),

            Stat::make(
                app()->getLocale() === 'ar' ? 'الاشتراكات الفعّالة' : 'Active Subscriptions',
                UserSubscription::query()
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '>', $now)
                    ->count()
            ),

            Stat::make(
                app()->getLocale() === 'ar' ? 'الاشتراكات المنتهية' : 'Expired Subscriptions',
                UserSubscription::query()
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', $now)
                    ->count()
            ),

            Stat::make(
                app()->getLocale() === 'ar' ? 'الرموز المستخدمة' : 'Used Codes',
                SubscriptionCode::where('status', 'used')->count()
            ),

            Stat::make(
                app()->getLocale() === 'ar' ? 'الكتب' : 'Books',
                Content::where('type', 'book')->count()
            ),

            Stat::make(
                app()->getLocale() === 'ar' ? 'الألعاب' : 'Games',
                Content::where('type', 'game')->count()
            ),

            Stat::make(
                app()->getLocale() === 'ar' ? 'الكتب الصوتية' : 'Sound Books',
                Content::where('type', 'sound_book')->count()
            ),
        ];
    }
}
