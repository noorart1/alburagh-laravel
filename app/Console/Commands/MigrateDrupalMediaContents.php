<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateDrupalMediaContents extends Command
{
    protected $signature = 'drupal:migrate-media-contents';

    protected $description = 'Migrate Drupal games, quran and sound books to Laravel';

    public function handle(): int
    {
        $types = [
            'alburagh_game' => [
                'type' => 'game',
                'prefix' => 'game',
            ],
            'alburagh_quran' => [
                'type' => 'quran',
                'prefix' => 'quran',
            ],
            'alburagh_sound_book' => [
                'type' => 'sound_book',
                'prefix' => 'sound_book',
            ],
        ];

        foreach ($types as $drupalType => $config) {
            $this->migrateType(
                $drupalType,
                $config['type'],
                $config['prefix']
            );
        }

        $this->newLine();
        $this->info('Media contents migration completed.');

        return Command::SUCCESS;
    }

    private function migrateType(
        string $drupalType,
        string $laravelType,
        string $prefix
    ): void {
        $nodes = DB::connection('drupal')
            ->table('node')
            ->where('type', $drupalType)
            ->orderBy('nid')
            ->get();

        $this->newLine();
        $this->info("Migrating {$drupalType}: {$nodes->count()} items");

        $bar = $this->output->createProgressBar($nodes->count());
        $bar->start();

        foreach ($nodes as $node) {

            $languageTid = $this->getFieldValue(
                "field_data_field_{$prefix}_language",
                "field_{$prefix}_language_tid",
                $node->nid
            );

            $languageId = $this->findLaravelId(
                'languages',
                $languageTid
            );

            $description = $this->getFieldValue(
                "field_data_field_{$prefix}_description",
                "field_{$prefix}_description_value",
                $node->nid
            );

            $folder = $this->getFieldValue(
                "field_data_field_{$prefix}_folder",
                "field_{$prefix}_folder_value",
                $node->nid
            );

            $sortOrder = $this->getFieldValue(
                "field_data_field_{$prefix}_order",
                "field_{$prefix}_order_value",
                $node->nid
            );

            $readingCount = $this->getFieldValue(
                "field_data_field_{$prefix}_reading_count",
                "field_{$prefix}_reading_count_value",
                $node->nid
            );

            $version = $this->getFieldValue(
                "field_data_field_{$prefix}_version",
                "field_{$prefix}_version_value",
                $node->nid
            );

            /*
             * content_version در Game و Sound Book
             * نام مشترک دارد، ولی در Quran متفاوت است.
             */
            if ($drupalType === 'alburagh_quran') {
                $contentVersion = $this->getFieldValue(
                    'field_data_field_quran_content_version',
                    'field_quran_content_version_value',
                    $node->nid
                );
            } else {
                $contentVersion = $this->getFieldValue(
                    'field_data_field_content_version',
                    'field_content_version_value',
                    $node->nid
                );
            }

            /*
             * فایل اصلی
             */
            $fileFid = $this->getFieldValue(
                "field_data_field_{$prefix}_file",
                "field_{$prefix}_file_fid",
                $node->nid
            );

            $filePath = $this->getFileUri($fileFid);

            /*
             * تصویر اصلی
             */
            $imageFid = $this->getFieldValue(
                "field_data_field_{$prefix}_image",
                "field_{$prefix}_image_fid",
                $node->nid
            );

            $mainImage = $this->getFileUri($imageFid);

            /*
             * تصویر سایت
             */
            $siteImageFid = $this->getFieldValue(
                "field_data_field_{$prefix}_site_image",
                "field_{$prefix}_site_image_fid",
                $node->nid
            );

            $siteImage = $this->getFileUri($siteImageFid);

            $existing = DB::table('contents')
                ->where('drupal_nid', $node->nid)
                ->first();

            $data = [
                'type' => $laravelType,
                'title' => $node->title,
                'description' => $description,
                'language_id' => $languageId,
                'file_path' => $filePath,
                'folder' => $folder,
                'main_image' => $mainImage,
                'site_image' => $siteImage,
                'sort_order' => (int) ($sortOrder ?? 0),
                'reading_count' => (int) ($readingCount ?? 0),
                'version' => $version,
                'content_version' => (int) ($contentVersion ?? 0),
                'is_published' => (bool) $node->status,
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('contents')
                    ->where('id', $existing->id)
                    ->update($data);

                $contentId = $existing->id;
            } else {
                $data['drupal_nid'] = $node->nid;
                $data['created_at'] = now();

                $contentId = DB::table('contents')
                    ->insertGetId($data);
            }

            /*
             * نام فیلد نمونه در Game مفرد است:
             * field_game_sample
             *
             * در Quran و Sound Book جمع است:
             * field_quran_samples
             * field_sound_book_samples
             */
            if ($drupalType === 'alburagh_game') {
                $samplesTable = 'field_data_field_game_sample';
                $samplesFidColumn = 'field_game_sample_fid';
            } else {
                $samplesTable = "field_data_field_{$prefix}_samples";
                $samplesFidColumn = "field_{$prefix}_samples_fid";
            }
            
            $samples = DB::connection('drupal')
                ->table($samplesTable)
                ->where('entity_type', 'node')
                ->where('entity_id', $node->nid)
                ->where('deleted', 0)
                ->orderBy('delta')
                ->get();
            
            foreach ($samples as $sample) {
            
                $fid = $sample->{$samplesFidColumn} ?? null;
            
                $imagePath = $this->getFileUri($fid);
            
                if (!$imagePath) {
                    continue;
                }
            
                DB::table('content_samples')->insert([
                    'content_id' => $contentId,
                    'image_path' => $imagePath,
                    'sort_order' => (int) $sample->delta,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $count = DB::table('contents')
            ->where('type', $laravelType)
            ->count();

        $this->info("Laravel {$laravelType} count: {$count}");
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

    private function findLaravelId(
        string $table,
        $drupalTid
    ) {
        if (!$drupalTid) {
            return null;
        }

        return DB::table($table)
            ->where('drupal_tid', $drupalTid)
            ->value('id');
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