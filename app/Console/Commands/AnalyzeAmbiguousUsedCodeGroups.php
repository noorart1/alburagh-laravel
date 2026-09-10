<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeAmbiguousUsedCodeGroups extends Command
{
    protected $signature = 'drupal:analyze-ambiguous-used-code-groups';

    protected $description = 'Analyze ambiguous used-code groups by timestamp, amount, and user without modifying data';

    public function handle(): int
    {
        $usedCodes = DB::connection('drupal')
            ->table('node as n')
            ->join('field_data_field_code_status as s', function ($join) {
                $join->on('s.entity_id', '=', 'n.nid')
                    ->where('s.entity_type', '=', 'node')
                    ->where('s.deleted', '=', 0);
            })
            ->leftJoin('field_data_field_code_amount as a', function ($join) {
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
            ->get();

        $groups = [];

        foreach ($usedCodes as $code) {
            $laravelCode = DB::table('subscription_codes')
                ->where('drupal_nid', $code->nid)
                ->first();

            if (! $laravelCode) {
                continue;
            }

            $alreadyLinked = DB::table('user_subscriptions')
                ->where('subscription_code_id', $laravelCode->id)
                ->exists();

            if ($alreadyLinked) {
                continue;
            }

            $amount = (int) ($code->amount ?? 0);

            $candidates = DB::connection('drupal')
                ->table('node as n')
                ->join('field_data_field_amount as a', function ($join) {
                    $join->on('a.entity_id', '=', 'n.nid')
                        ->where('a.entity_type', '=', 'node')
                        ->where('a.deleted', '=', 0);
                })
                ->leftJoin('field_data_field_user_reference as u', function ($join) {
                    $join->on('u.entity_id', '=', 'n.nid')
                        ->where('u.entity_type', '=', 'node')
                        ->where('u.deleted', '=', 0);
                })
                ->where('n.type', 'user_subscription')
                ->where('n.created', $code->changed)
                ->where('a.field_amount_value', $amount)
                ->select([
                    'n.nid',
                    'n.uid',
                    'u.field_user_reference_target_id as referenced_uid',
                ])
                ->get();

            if ($candidates->count() <= 1) {
                continue;
            }

            $userIds = $candidates
                ->map(fn ($candidate) =>
                    (int) ($candidate->referenced_uid ?: $candidate->uid)
                )
                ->filter()
                ->unique()
                ->values();

            if ($userIds->count() !== 1) {
                continue;
            }

            $uid = $userIds->first();

            $key = implode('|', [
                $code->changed,
                $amount,
                $uid,
            ]);

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'timestamp' => $code->changed,
                    'amount' => $amount,
                    'uid' => $uid,
                    'codes' => [],
                    'subscriptions' => $candidates->pluck('nid')->unique()->values()->all(),
                ];
            }

            $groups[$key]['codes'][] = $code->nid;
        }

        $equalCountGroups = 0;
        $equalCountCodes = 0;
        $mismatchGroups = 0;

        foreach ($groups as $group) {
            $codeCount = count(array_unique($group['codes']));
            $subscriptionCount = count($group['subscriptions']);

            if ($codeCount === $subscriptionCount) {
                $equalCountGroups++;
                $equalCountCodes += $codeCount;

                $this->line(
                    "EQUAL | Time {$group['timestamp']} | " .
                    "Amount {$group['amount']} | " .
                    "UID {$group['uid']} | " .
                    "Codes {$codeCount} | " .
                    "Subscriptions {$subscriptionCount}"
                );
            } else {
                $mismatchGroups++;
            }
        }

        $this->newLine();

        $this->info('Total ambiguous groups: ' . count($groups));
        $this->info("Equal-count groups: {$equalCountGroups}");
        $this->info("Codes inside equal-count groups: {$equalCountCodes}");
        $this->info("Count-mismatch groups: {$mismatchGroups}");

        return Command::SUCCESS;
    }
}