<?php

namespace App\Filament\Pages;

use App\Models\Content;
use App\Models\DeviceToken;
use App\Models\SubscriptionCode;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class Reports extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.reports';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('admin.reports');
    }

    public function getTitle(): string
    {
        return __('admin.reports_title');
    }

    public static function getNavigationGroup(): ?string
    {
        return app()->getLocale() === 'ar'
            ? 'إدارة التطبيق'
            : 'App Management';
    }

    public string $period = 'all';

    public array $stats = [];

    public array $latestUsers = [];

    public array $latestSubscriptions = [];

    public array $topBooksByDownloads = [];

    public array $topBooksByReadings = [];

    public function mount(): void
    {
        $this->form->fill([
            'period' => 'all',
        ]);

        $this->loadStats();
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('period')
                ->label(__('admin.time_period'))
                ->options([
                    'all' => __('admin.period_all'),
                    'today' => __('admin.period_today'),
                    '7days' => __('admin.period_7days'),
                    '30days' => __('admin.period_30days'),
                    'year' => __('admin.period_year'),
                ])
                ->default('all')
                ->live()
                ->afterStateUpdated(function ($state) {
                    $this->period = $state ?? 'all';
                    $this->loadStats();
                }),
        ];
    }

    private function getDateFrom(): ?Carbon
    {
        return match ($this->period) {
            'today' => now()->startOfDay(),
            '7days' => now()->subDays(7)->startOfDay(),
            '30days' => now()->subDays(30)->startOfDay(),
            'year' => now()->startOfYear(),
            default => null,
        };
    }

    private function loadStats(): void
    {
        $dateFrom = $this->getDateFrom();

        $usersQuery = User::query();

        $subscriptionsQuery = UserSubscription::query();

        if ($dateFrom) {
            $usersQuery->where(
                'created_at',
                '>=',
                $dateFrom
            );

            $subscriptionsQuery->where(
                'subscription_date',
                '>=',
                $dateFrom
            );
        }

        $this->stats = [

            // کاربران در بازه انتخاب‌شده
            'users_total' => (clone $usersQuery)->count(),

            'users_active' => (clone $usersQuery)
                ->where('is_active', true)
                ->count(),

            'users_admin' => (clone $usersQuery)
                ->where('is_admin', true)
                ->count(),

            // آمار ثابت امروز / ماه
            'users_today' => User::whereDate(
                'created_at',
                today()
            )->count(),

            'users_month' => User::whereYear(
                    'created_at',
                    now()->year
                )
                ->whereMonth(
                    'created_at',
                    now()->month
                )
                ->count(),

            // اشتراک‌ها در بازه انتخاب‌شده
            'subscriptions_total' =>
                (clone $subscriptionsQuery)->count(),

            'subscriptions_month' => UserSubscription::whereYear(
                    'subscription_date',
                    now()->year
                )
                ->whereMonth(
                    'subscription_date',
                    now()->month
                )
                ->count(),

            // کدها
            'codes_total' => SubscriptionCode::count(),

            'codes_printed' => SubscriptionCode::where(
                'status',
                'printed'
            )->count(),

            /*
            |--------------------------------------------------------------------------
            | کدهای استفاده‌شده
            |--------------------------------------------------------------------------
            |
            | اگر فیلتر روی "همه" باشد:
            | کل کدهای تاریخی با status = used شمرده می‌شوند.
            |
            | اگر بازه زمانی انتخاب شده باشد:
            | فقط مصرف‌های واقعی ثبت‌شده در Laravel شمرده می‌شوند.
            |
            */
            'codes_used' => $dateFrom
                ? UserSubscription::query()
                    ->whereNotNull('subscription_code_id')
                    ->where(
                        'subscription_date',
                        '>=',
                        $dateFrom
                    )
                    ->count()
                : SubscriptionCode::where(
                    'status',
                    'used'
                )->count(),

            'codes_generated' => SubscriptionCode::where(
                'status',
                'generated'
            )->count(),

            // محتوا
            'books' => Content::where(
                'type',
                'book'
            )->count(),

            'games' => Content::where(
                'type',
                'game'
            )->count(),

            'sound_books' => Content::where(
                'type',
                'sound_book'
            )->count(),

            'quran' => Content::where(
                'type',
                'quran'
            )->count(),

            'published_contents' => Content::where(
                'is_published',
                true
            )->count(),

            'unpublished_contents' => Content::where(
                'is_published',
                false
            )->count(),

            // مصرف محتوا
            'downloads_total' => DB::table(
                'book_details'
            )->sum('download_count'),

            'readings_total' => Content::sum(
                'reading_count'
            ),

            'read_books_total' => DB::table(
                'user_read_books'
            )->count(),

            // دستگاه‌ها
            'device_tokens_total' =>
                DeviceToken::count(),

            'device_tokens_android' =>
                DeviceToken::where(
                    'platform',
                    'android'
                )->count(),
        ];

        /*
        |--------------------------------------------------------------------------
        | جدیدترین کاربران
        |--------------------------------------------------------------------------
        */

        $latestUsersQuery = User::query()
            ->latest('id');

        if ($dateFrom) {
            $latestUsersQuery->where(
                'created_at',
                '>=',
                $dateFrom
            );
        }

        $this->latestUsers = $latestUsersQuery
            ->limit(10)
            ->get([
                'id',
                'email',
                'username',
                'fullname',
                'country',
                'is_active',
                'created_at',
                'drupal_created_at',
            ])
            ->map(function ($user) {
                return [
                    'email' => $user->email,
                    'username' => $user->username,
                    'fullname' => $user->fullname,
                    'country' => $user->country,
                    'is_active' => $user->is_active,

                    'created_at' =>
                        $user->drupal_created_at
                        ?? $user->created_at,
                ];
            })
            ->all();

        /*
        |--------------------------------------------------------------------------
        | آخرین اشتراک‌ها
        |--------------------------------------------------------------------------
        */

        $latestSubscriptionsQuery =
            UserSubscription::query()
                ->with([
                    'user:id,email,username',
                    'subscriptionCode:id,code',
                ])
                ->latest('subscription_date');

        if ($dateFrom) {
            $latestSubscriptionsQuery->where(
                'subscription_date',
                '>=',
                $dateFrom
            );
        }

        $this->latestSubscriptions =
            $latestSubscriptionsQuery
                ->limit(10)
                ->get()
                ->map(function ($subscription) {
                    return [
                        'email' =>
                            $subscription
                                ->user
                                ?->email,

                        'username' =>
                            $subscription
                                ->user
                                ?->username,

                        'code' =>
                            $subscription
                                ->subscriptionCode
                                ?->code,

                        'amount' =>
                            $subscription->amount,

                        'date' =>
                            $subscription
                                ->subscription_date,
                    ];
                })
                ->all();

        /*
        |--------------------------------------------------------------------------
        | پردانلودترین کتاب‌ها
        |--------------------------------------------------------------------------
        */

        $this->topBooksByDownloads =
            Content::query()
                ->with('bookDetail')
                ->where('type', 'book')
                ->get()
                ->sortByDesc(
                    fn ($content) =>
                        $content
                            ->bookDetail
                            ?->download_count
                        ?? 0
                )
                ->take(10)
                ->map(function ($content) {
                    return [
                        'title' =>
                            $content->title,

                        'downloads' =>
                            $content
                                ->bookDetail
                                ?->download_count
                            ?? 0,
                    ];
                })
                ->values()
                ->all();

        /*
        |--------------------------------------------------------------------------
        | پرمطالعه‌ترین کتاب‌ها
        |--------------------------------------------------------------------------
        */

        $this->topBooksByReadings =
            Content::query()
                ->where('type', 'book')
                ->orderByDesc('reading_count')
                ->limit(10)
                ->get([
                    'title',
                    'reading_count',
                ])
                ->map(function ($content) {
                    return [
                        'title' =>
                            $content->title,

                        'readings' =>
                            $content
                                ->reading_count
                            ?? 0,
                    ];
                })
                ->all();
    }
}