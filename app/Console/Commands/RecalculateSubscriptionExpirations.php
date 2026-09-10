<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateSubscriptionExpirations extends Command
{
    protected $signature = 'subscriptions:recalculate-expirations';

    protected $description = 'Recalculate subscription expiration dates using the original Drupal cumulative logic';

    public function handle(): int
    {
        $userIds = UserSubscription::query()
            ->whereNotNull('user_id')
            ->distinct()
            ->orderBy('user_id')
            ->pluck('user_id');

        $this->info('Users with subscriptions: ' . $userIds->count());

        $updated = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($userIds->count());
        $bar->start();

        DB::transaction(function () use (
            $userIds,
            &$updated,
            &$skipped,
            $bar
        ) {
            foreach ($userIds as $userId) {

                $subscriptions = UserSubscription::query()
                    ->where('user_id', $userId)
                    ->whereNotNull('subscription_date')
                    ->orderBy('subscription_date')
                    ->orderBy('id')
                    ->get();

                $expire = null;

                foreach ($subscriptions as $subscription) {

                    $start = $subscription->subscription_date?->copy();

                    if (! $start) {
                        $skipped++;
                        continue;
                    }

                    $days = (int) ($subscription->amount ?? 0);

                    if ($expire === null) {
                        $expire = $start->copy()->addDays($days);
                    } elseif ($start->lessThan($expire)) {
                        $expire = $expire->copy()->addDays($days);
                    } else {
                        $expire = $start->copy()->addDays($days);
                    }

                    DB::table('user_subscriptions')
                        ->where('id', $subscription->id)
                        ->update([
                            'expires_at' => $expire,
                            'updated_at' => now(),
                        ]);

                    $updated++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Subscriptions updated: {$updated}");
        $this->info("Subscriptions skipped: {$skipped}");

        return Command::SUCCESS;
    }
}