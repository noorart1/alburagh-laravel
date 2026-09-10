<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LinkDrupalUsedCodes extends Command
{
    protected $signature = 'drupal:link-used-codes';

    protected $description = 'Safely link old Drupal used codes to migrated Laravel subscriptions';

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

        $linked = 0;
        $skipped = 0;
        $alreadyLinked = 0;

        $bar = $this->output->createProgressBar($codes->count());
        $bar->start();

        foreach ($codes as $code) {
            $laravelCode = DB::table('subscription_codes')
                ->where('drupal_nid', $code->nid)
                ->first();

            if (! $laravelCode) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if (
                DB::table('user_subscriptions')
                    ->where('subscription_code_id', $laravelCode->id)
                    ->exists()
            ) {
                $alreadyLinked++;
                $bar->advance();
                continue;
            }

            $sameMomentCodeCount = DB::connection('drupal')
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

            if ($sameMomentCodeCount !== 1) {
                $skipped++;
                $bar->advance();
                continue;
            }

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

            if ($subscriptions->count() !== 1) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $laravelSubscription = DB::table('user_subscriptions')
                ->where('drupal_nid', $subscriptions->first())
                ->first();

            if (! $laravelSubscription) {
                $skipped++;
                $bar->advance();
                continue;
            }

            if ($laravelSubscription->subscription_code_id !== null) {
                $alreadyLinked++;
                $bar->advance();
                continue;
            }

            DB::table('user_subscriptions')
                ->where('id', $laravelSubscription->id)
                ->update([
                    'subscription_code_id' => $laravelCode->id,
                    'updated_at' => now(),
                ]);

            $linked++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Linked safely: {$linked}");
        $this->info("Skipped: {$skipped}");
        $this->info("Already linked: {$alreadyLinked}");

        return Command::SUCCESS;
    }
}