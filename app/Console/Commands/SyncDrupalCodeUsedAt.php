<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncDrupalCodeUsedAt extends Command
{
    protected $signature = 'drupal:sync-code-used-at';

    protected $description = 'Sync used_at for old Drupal subscription codes';

    public function handle(): int
    {
        $codes = DB::connection('drupal')
            ->table('node as n')
            ->join('field_data_field_code_status as s', function ($join) {
                $join->on('s.entity_id', '=', 'n.nid')
                    ->where('s.entity_type', '=', 'node')
                    ->where('s.deleted', '=', 0);
            })
            ->where('n.type', 'code')
            ->where('s.field_code_status_value', 'used')
            ->select('n.nid', 'n.changed')
            ->get();

        $updated = 0;
        $missing = 0;

        foreach ($codes as $code) {
            $exists = DB::table('subscription_codes')
                ->where('drupal_nid', $code->nid)
                ->exists();

            if (! $exists) {
                $missing++;
                continue;
            }

            DB::table('subscription_codes')
                ->where('drupal_nid', $code->nid)
                ->update([
                    'used_at' => Carbon::createFromTimestamp($code->changed),
                ]);

            $updated++;
        }

        $this->info("Used dates synced: {$updated}");
        $this->info("Missing Laravel codes: {$missing}");

        return Command::SUCCESS;
    }
}