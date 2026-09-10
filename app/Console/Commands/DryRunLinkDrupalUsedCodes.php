<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DryRunLinkDrupalUsedCodes extends Command
{
    protected $signature = 'drupal:dry-run-link-used-codes';

    protected $description = 'Dry run: safely detect one-to-one links between Drupal used codes and Laravel subscriptions';

    public function handle(): int
    {
        $codes = DB::connection('drupal')
            ->table('node as n')
            ->join('field_data_field_code_status as s', function ($join) {
                $join->on('s.entity_id', '=', 'n.nid')
                    ->where('s.entity_type', '=', 'node')
                    ->where('s.deleted', '=', 0);
            })
            ->join('field_data_field_code_amount as a', function ($join) {
                $join->on('a.entity_id', '=', 'n.nid')
                    ->where('a.entity_type', '=', 'node')
                    ->where('a.deleted', '=', 0);
            })
            ->where('n.type', 'code')
            ->where('s.field_code_status_value', 'used')
            ->select([
                'n.nid',
                'n.changed',
                'a.field_code_amount_value as amount',
            ])
            ->orderBy('n.nid')
            ->get();

        $this->info('Drupal used codes: ' . $codes->count());

        $safe = 0;
        $noSubscription = 0;
        $multipleSubscriptions = 0;
        $multipleCodesSameMoment = 0;
        $missingLaravelCode = 0;
        $missingLaravelSubscription = 0;
        $alreadyLinked = 0;

        $bar = $this->output->createProgressBar($codes->count());
        $bar->start();

        foreach ($codes as $code) {

            $laravelCode = DB::table('subscription_codes')
                ->where('drupal_nid', $code->nid)
                ->first();

            if (! $laravelCode) {
                $missingLaravelCode++;
                $bar->advance();
                continue;
            }

            $existingLink = DB::table('user_subscriptions')
                ->where('subscription_code_id', $laravelCode->id)
                ->exists();

            if ($existingLink) {
                $alreadyLinked++;
                $bar->advance();
                continue;
            }

            /*
             * How many USED codes have exactly the same
             * timestamp and duration?
             */
            $codesAtSameMoment = DB::connection('drupal')
                ->table('node as n')
                ->join('field_data_field_code_status as s', function ($join) {
                    $join->on('s.entity_id', '=', 'n.nid')
                        ->where('s.entity_type', '=', 'node')
                        ->where('s.deleted', '=', 0);
                })
                ->join('field_data_field_code_amount as a', function ($join) {
                    $join->on('a.entity_id', '=', 'n.nid')
                        ->where('a.entity_type', '=', 'node')
                        ->where('a.deleted', '=', 0);
                })
                ->where('n.type', 'code')
                ->where('s.field_code_status_value', 'used')
                ->where('n.changed', $code->changed)
                ->where('a.field_code_amount_value', $code->amount)
                ->count();

            if ($codesAtSameMoment !== 1) {
                $multipleCodesSameMoment++;
                $bar->advance();
                continue;
            }

            /*
             * Find subscriptions created at the exact same second
             * with the same duration.
             */
            $subscriptions = DB::connection('drupal')
                ->table('node as n')
                ->join('field_data_field_amount as a', function ($join) {
                    $join->on('a.entity_id', '=', 'n.nid')
                        ->where('a.entity_type', '=', 'node')
                        ->where('a.deleted', '=', 0);
                })
                ->where('n.type', 'user_subscription')
                ->where('n.created', $code->changed)
                ->where('a.field_amount_value', $code->amount)
                ->pluck('n.nid');

            if ($subscriptions->count() === 0) {
                $noSubscription++;
                $bar->advance();
                continue;
            }

            if ($subscriptions->count() !== 1) {
                $multipleSubscriptions++;
                $bar->advance();
                continue;
            }

            $subscriptionDrupalNid = $subscriptions->first();

            $laravelSubscription = DB::table('user_subscriptions')
                ->where('drupal_nid', $subscriptionDrupalNid)
                ->first();

            if (! $laravelSubscription) {
                $missingLaravelSubscription++;
                $bar->advance();
                continue;
            }

            if ($laravelSubscription->subscription_code_id !== null) {
                $alreadyLinked++;
                $bar->advance();
                continue;
            }

            $safe++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Safe one-to-one matches: {$safe}");
        $this->info("No subscription found: {$noSubscription}");
        $this->info("Multiple subscriptions same moment: {$multipleSubscriptions}");
        $this->info("Multiple used codes same moment: {$multipleCodesSameMoment}");
        $this->info("Missing Laravel code: {$missingLaravelCode}");
        $this->info("Missing Laravel subscription: {$missingLaravelSubscription}");
        $this->info("Already linked: {$alreadyLinked}");

        $this->newLine();
        $this->warn('Dry run only. Nothing was changed.');

        return Command::SUCCESS;
    }
}