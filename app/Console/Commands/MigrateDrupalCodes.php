<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateDrupalCodes extends Command
{
    protected $signature = 'drupal:migrate-codes';

    protected $description = 'Migrate Drupal code series and subscription codes to Laravel';

    public function handle(): int
    {
        $this->migrateCodeSeries();
        $this->migrateCodes();

        return Command::SUCCESS;
    }

    private function migrateCodeSeries(): void
    {
        $seriesNodes = DB::connection('drupal')
            ->table('node')
            ->where('type', 'code_series')
            ->orderBy('nid')
            ->get();

        $this->info("Drupal code series: {$seriesNodes->count()}");

        foreach ($seriesNodes as $node) {

            $country = $this->getFieldValue(
                'field_data_field_series_country',
                'field_series_country_iso2',
                $node->nid
            );

            $start = $this->getFieldValue(
                'field_data_field_series_start',
                'field_series_start_value',
                $node->nid
            );

            $end = $this->getFieldValue(
                'field_data_field_series_end',
                'field_series_end_value',
                $node->nid
            );

            $prefix = $this->getFieldValue(
                'field_data_field_series_perfix',
                'field_series_perfix_value',
                $node->nid
            );

            DB::table('code_series')->updateOrInsert(
                [
                    'drupal_nid' => $node->nid,
                ],
                [
                    'country' => $country,
                    'series_start' => $start,
                    'series_end' => $end,
                    'prefix' => $prefix,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->info(
            'Laravel code series: ' .
            DB::table('code_series')->count()
        );
    }

    private function migrateCodes(): void
    {
        $total = DB::connection('drupal')
            ->table('node')
            ->where('type', 'code')
            ->count();

        $this->info("Drupal codes: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::connection('drupal')
            ->table('node')
            ->where('type', 'code')
            ->orderBy('nid')
            ->chunkById(500, function ($nodes) use ($bar) {

                foreach ($nodes as $node) {

                    $code = $this->getFieldValue(
                        'field_data_field_code',
                        'field_code_value',
                        $node->nid
                    );

                    $amount = $this->getFieldValue(
                        'field_data_field_code_amount',
                        'field_code_amount_value',
                        $node->nid
                    );

                    $serial = $this->getFieldValue(
                        'field_data_field_code_serial',
                        'field_code_serial_value',
                        $node->nid
                    );

                    $status = $this->getFieldValue(
                        'field_data_field_code_status',
                        'field_code_status_value',
                        $node->nid
                    );

                    $seriesId = $this->detectSeriesId(
                        $serial,
                        $code
                    );

                    DB::table('subscription_codes')->updateOrInsert(
                        [
                            'drupal_nid' => $node->nid,
                        ],
                        [
                            'code' => $code,
                            'serial' => $serial,
                            'amount' => $amount,
                            'status' => $status,
                            'code_series_id' => $seriesId,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );

                    $bar->advance();
                }
            }, 'nid');

        $bar->finish();
        $this->newLine(2);

        $this->info(
            'Laravel subscription codes: ' .
            DB::table('subscription_codes')->count()
        );
    }

    private function detectSeriesId($serial, $code)
    {
        $series = DB::table('code_series')->get();

        foreach ($series as $item) {

            if (
                $serial !== null &&
                is_numeric($serial) &&
                $item->series_start !== null &&
                $item->series_end !== null &&
                (int) $serial >= (int) $item->series_start &&
                (int) $serial <= (int) $item->series_end
            ) {
                return $item->id;
            }

            if (
                $item->prefix &&
                $code &&
                str_starts_with($code, $item->prefix)
            ) {
                return $item->id;
            }
        }

        return null;
    }

    private function getFieldValue(
        string $table,
        string $column,
        int $entityId
    ) {
        return DB::connection('drupal')
            ->table($table)
            ->where('entity_type', 'node')
            ->where('entity_id', $entityId)
            ->where('deleted', 0)
            ->orderBy('delta')
            ->value($column);
    }
}