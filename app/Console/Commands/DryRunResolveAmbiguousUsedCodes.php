<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DryRunResolveAmbiguousUsedCodes extends Command
{
    protected $signature = 'drupal:dry-run-resolve-ambiguous-used-codes';

    protected $description = 'Dry run ambiguous Drupal used-code matches using timestamp, amount and NID ordering';

    public function handle(): int
    {
        $groups = DB::connection('drupal')
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
                'n.changed',
                'a.field_code_amount_value as amount',
                DB::raw('COUNT(*) as code_count'),
            ])
            ->groupBy('n.changed', 'a.field_code_amount_value')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('n.changed')
            ->get();

        $this->info('Ambiguous timestamp/amount groups: ' . $groups->count());

        $resolvableGroups = 0;
        $resolvableCodes = 0;
        $countMismatchGroups = 0;
        $missingLaravelRecords = 0;
        $alreadyLinked = 0;

        foreach ($groups as $group) {

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
                ->where('n.changed', $group->changed)
                ->where('a.field_code_amount_value', $group->amount)
                ->orderBy('n.nid')
                ->get([
                    'n.nid',
                    'n.changed',
                ]);

            $subscriptions = DB::connection('drupal')
                ->table('node as n')
                ->join('field_data_field_amount as a', function ($join) {
                    $join->on('a.entity_id', '=', 'n.nid')
                        ->where('a.entity_type', '=', 'node')
                        ->where('a.deleted', '=', 0);
                })
                ->join('field_data_field_user_reference as u', function ($join) {
                    $join->on('u.entity_id', '=', 'n.nid')
                        ->where('u.entity_type', '=', 'node')
                        ->where('u.deleted', '=', 0);
                })
                ->where('n.type', 'user_subscription')
                ->where('n.created', $group->changed)
                ->where('a.field_amount_value', $group->amount)
                ->orderBy('n.nid')
                ->get([
                    'n.nid',
                    'n.created',
                    'u.field_user_reference_target_id as drupal_uid',
                ]);

            if ($codes->count() !== $subscriptions->count()) {
                $countMismatchGroups++;
                continue;
            }

            $groupIsSafe = true;

            foreach ($codes as $index => $code) {
                $subscription = $subscriptions[$index];

                $laravelCode = DB::table('subscription_codes')
                    ->where('drupal_nid', $code->nid)
                    ->first();

                $laravelSubscription = DB::table('user_subscriptions')
                    ->where('drupal_nid', $subscription->nid)
                    ->first();

                if (! $laravelCode || ! $laravelSubscription) {
                    $missingLaravelRecords++;
                    $groupIsSafe = false;
                    break;
                }

                if (
                    $laravelSubscription->subscription_code_id !== null
                    || DB::table('user_subscriptions')
                        ->where('subscription_code_id', $laravelCode->id)
                        ->exists()
                ) {
                    $alreadyLinked++;
                    $groupIsSafe = false;
                    break;
                }
            }

            if ($groupIsSafe) {
                $resolvableGroups++;
                $resolvableCodes += $codes->count();
            }
        }

        $this->newLine();

        $this->info("Resolvable groups by NID order: {$resolvableGroups}");
        $this->info("Resolvable codes by NID order: {$resolvableCodes}");
        $this->info("Groups with count mismatch: {$countMismatchGroups}");
        $this->info("Missing Laravel records: {$missingLaravelRecords}");
        $this->info("Already linked/conflicting: {$alreadyLinked}");

        $this->newLine();
        $this->warn('Dry run only. Nothing was changed.');

        return Command::SUCCESS;
    }
}
