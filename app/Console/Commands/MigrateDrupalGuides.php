<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateDrupalGuides extends Command
{
    protected $signature = 'drupal:migrate-guides';

    protected $description = 'Migrate Drupal guides and guide images to Laravel';

    public function handle(): int
    {
        $guides = DB::connection('drupal')
            ->table('node')
            ->where('type', 'guide')
            ->orderBy('nid')
            ->get();

        $this->info("Total Drupal guides: {$guides->count()}");

        $bar = $this->output->createProgressBar($guides->count());
        $bar->start();

        foreach ($guides as $node) {

            $body = $this->getFieldValue(
                'field_data_field_guide_body',
                'field_guide_body_value',
                $node->nid
            );

            $sortOrder = $this->getFieldValue(
                'field_data_field_order',
                'field_order_value',
                $node->nid
            );

            $existing = DB::table('guides')
                ->where('drupal_nid', $node->nid)
                ->first();

            $data = [
                'title' => $node->title,
                'body' => $body,
                'sort_order' => (float) ($sortOrder ?? 0),
                'is_published' => (bool) $node->status,
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('guides')
                    ->where('id', $existing->id)
                    ->update($data);

                $guideId = $existing->id;
            } else {
                $data['drupal_nid'] = $node->nid;
                $data['created_at'] = now();

                $guideId = DB::table('guides')
                    ->insertGetId($data);
            }

            /*
             * حذف تصاویر قبلی برای اجرای مجدد امن
             */
            DB::table('guide_images')
                ->where('guide_id', $guideId)
                ->delete();

            $images = DB::connection('drupal')
                ->table('field_data_field_guide_images')
                ->where('entity_type', 'node')
                ->where('entity_id', $node->nid)
                ->where('deleted', 0)
                ->orderBy('delta')
                ->get();

            foreach ($images as $image) {

                $fid = $image->field_guide_images_fid ?? null;

                $imagePath = $this->getFileUri($fid);

                if (!$imagePath) {
                    continue;
                }

                DB::table('guide_images')->insert([
                    'guide_id' => $guideId,
                    'image_path' => $imagePath,
                    'sort_order' => (int) $image->delta,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $guideCount = DB::table('guides')->count();
        $imageCount = DB::table('guide_images')->count();

        $this->info("Migrated guides: {$guideCount}");
        $this->info("Migrated guide images: {$imageCount}");

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

    private function getFileUri($fid)
    {
        if (!$fid) {
            return null;
        }

        return DB::connection('drupal')
            ->table('file_managed')
            ->where('fid', $fid)
            ->value('uri');
    }
}