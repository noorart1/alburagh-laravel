<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeDrupalUsedCodeRevisions extends Command
{
    protected $signature = 'drupal:analyze-used-code-revisions';

    protected $description = 'Analyze Drupal revision history for unresolved used codes';

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

        $unresolved = 0;
        $hasUsedRevision = 0;
        $exactUnique = 0;
        $within5Unique = 0;
        $ambiguous = 0;
        $noMatch = 0;

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

            $unresolved++;

            $amount = (int) ($code->amount ?? 0);

            /*
             * Find every Drupal revision where this code
             * had status = used.
             */
            $usedRevisions = DB::connection('drupal')
                ->table('node_revision as nr')
                ->join('field_revision_field_code_status as s', function ($join) {
                    $join->on('s.entity_id', '=', 'nr.nid')
                        ->on('s.revision_id', '=', 'nr.vid')
                        ->where('s.entity_type', '=', 'node')
                        ->where('s.deleted', '=', 0);
                })
                ->where('nr.nid', $code->nid)
                ->where('s.field_code_status_value', 'used')
                ->select([
                    'nr.vid',
                    'nr.timestamp',
                ])
                ->orderBy('nr.timestamp')
                ->get();

            if ($usedRevisions->isEmpty()) {
                continue;
            }

            $hasUsedRevision++;

            /*
             * Usually the earliest revision where status became
             * "used" is the best historical redemption timestamp.
             */
            $revision = $usedRevisions->first();

            /*
             * First try exact second.
             */
            $exactCandidates = DB::connection('drupal')
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
                ->where('n.created', $revision->timestamp)
                ->where('a.field_amount_value', $amount)
                ->select([
                    'n.nid',
                    'n.uid',
                    'n.created',
                    'u.field_user_reference_target_id as referenced_uid',
                ])
                ->get();

            if ($exactCandidates->count() === 1) {
                $exactUnique++;

                $candidate = $exactCandidates->first();

                $this->line(
                    "EXACT UNIQUE | Code NID {$code->nid} | " .
                    "Laravel {$laravelCode->id} | " .
                    "Amount {$amount} | " .
                    "Revision {$revision->timestamp} | " .
                    "Subscription NID {$candidate->nid}"
                );

                continue;
            }

            /*
             * If exact second is not unique, try ±5 seconds.
             */
            $nearCandidates = DB::connection('drupal')
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
                ->whereBetween('n.created', [
                    $revision->timestamp - 5,
                    $revision->timestamp + 5,
                ])
                ->where('a.field_amount_value', $amount)
                ->select([
                    'n.nid',
                    'n.uid',
                    'n.created',
                    'u.field_user_reference_target_id as referenced_uid',
                ])
                ->get();

            if ($nearCandidates->count() === 1) {
                $within5Unique++;

                $candidate = $nearCandidates->first();

                $this->line(
                    "NEAR UNIQUE | Code NID {$code->nid} | " .
                    "Laravel {$laravelCode->id} | " .
                    "Amount {$amount} | " .
                    "Revision {$revision->timestamp} | " .
                    "Subscription NID {$candidate->nid} | " .
                    "Diff " . abs($candidate->created - $revision->timestamp) . " sec"
                );

                continue;
            }

            if ($nearCandidates->count() > 1) {
                $ambiguous++;
            } else {
                $noMatch++;
            }
        }

        $this->newLine();

        $this->info("Unresolved codes checked: {$unresolved}");
        $this->info("Codes with used revision history: {$hasUsedRevision}");
        $this->info("Exact unique matches: {$exactUnique}");
        $this->info("Unique matches within 5 sec: {$within5Unique}");
        $this->info("Ambiguous revision matches: {$ambiguous}");
        $this->info("No revision-time match: {$noMatch}");

        return Command::SUCCESS;
    }
}