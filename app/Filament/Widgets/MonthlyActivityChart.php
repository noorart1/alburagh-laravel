<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class MonthlyActivityChart extends ChartWidget
{
    protected ?string $heading = null;

    protected int | string | array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return app()->getLocale() === 'ar'
            ? 'المستخدمون والاشتراكات خلال آخر 12 شهرًا'
            : 'Users and Subscriptions — Last 12 Months';
    }

    protected function getData(): array
    {
        $labels = [];
        $users = [];
        $subscriptions = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->copy()->subMonths($i)->startOfMonth();
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $labels[] = app()->getLocale() === 'ar'
                ? $this->arabicMonthLabel($month)
                : $month->format('M Y');

            $users[] = User::query()
                ->whereBetween('created_at', [$start, $end])
                ->count();

            $subscriptions[] = UserSubscription::query()
                ->whereBetween('subscription_date', [$start, $end])
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => app()->getLocale() === 'ar' ? 'المستخدمون الجدد' : 'New Users',
                    'data' => $users,
                ],
                [
                    'label' => app()->getLocale() === 'ar' ? 'الاشتراكات' : 'Subscriptions',
                    'data' => $subscriptions,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    private function arabicMonthLabel(Carbon $date): string
    {
        $months = [
            1 => 'يناير',
            2 => 'فبراير',
            3 => 'مارس',
            4 => 'أبريل',
            5 => 'مايو',
            6 => 'يونيو',
            7 => 'يوليو',
            8 => 'أغسطس',
            9 => 'سبتمبر',
            10 => 'أكتوبر',
            11 => 'نوفمبر',
            12 => 'ديسمبر',
        ];

        return $months[$date->month] . ' ' . $date->year;
    }
}
