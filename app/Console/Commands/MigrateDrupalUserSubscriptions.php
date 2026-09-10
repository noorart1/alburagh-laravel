<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MigrateDrupalUserSubscriptions extends Command
{
    protected $signature = 'drupal:migrate-user-subscriptions';

    protected $description = 'Migrate Drupal user subscriptions to Laravel';

    public function handle(): int
    {
        $total = DB::connection('drupal')
            ->table('node')
            ->where('type', 'user_subscription')
            ->count();

        $this->info("Drupal user subscriptions: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::connection('drupal')
            ->table('node')
            ->where('type', 'user_subscription')
            ->orderBy('nid')
            ->chunkById(300, function ($nodes) use ($bar) {

                foreach ($nodes as $node) {

                    $amount = $this->getFieldValue(
                        'field_data_field_amount',
                        'field_amount_value',
                        $node->nid
                    );

                    $date = $this->getFieldValue(
                        'field_data_field_date',
                        'field_date_value',
                        $node->nid
                    );

                    $type = $this->getFieldValue(
                        'field_data_field_type',
                        'field_type_value',
                        $node->nid
                    );

                    $drupalUid = $this->getFieldValue(
                        'field_data_field_user_reference',
                        'field_user_reference_target_id',
                        $node->nid
                    );

                    $laravelUserId = null;

                    if ($drupalUid) {
                        $laravelUserId = DB::table('users')
                            ->where('drupal_uid', $drupalUid)
                            ->value('id');
                    }

                    $subscriptionDate = null;

                    if ($date) {
                        if (is_numeric($date)) {
                            $subscriptionDate = Carbon::createFromTimestamp((int) $date);
                        } else {
                            try {
                                $subscriptionDate = Carbon::parse($date);
                            } catch (\Throwable $e) {
                                $subscriptionDate = null;
                            }
                        }
                    }

                    $existing = DB::table('user_subscriptions')
                        ->where('drupal_nid', $node->nid)
                        ->first();
                    
                    if ($existing) {
                        DB::table('user_subscriptions')
                            ->where('id', $existing->id)
                            ->update([
                                'user_id' => $laravelUserId,
                                'amount' => $amount,
                                'subscription_date' => $subscriptionDate,
                                'type' => $type,
                                'updated_at' => now(),
                            ]);
                    } else {
                        DB::table('user_subscriptions')
                            ->insert([
                                'drupal_nid' => $node->nid,
                                'user_id' => $laravelUserId,
                                'subscription_code_id' => null,
                                'amount' => $amount,
                                'subscription_date' => $subscriptionDate,
                                'type' => $type,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }

                    $bar->advance();
                }
            }, 'nid');

        $bar->finish();
        $this->newLine(2);

        $count = DB::table('user_subscriptions')->count();

        $this->info("Laravel user subscriptions: {$count}");

        $withoutUser = DB::table('user_subscriptions')
            ->whereNull('user_id')
            ->count();

        $this->info("Subscriptions without matched user: {$withoutUser}");

        return Command::SUCCESS;
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