<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateDrupalBooks extends Command
{
    protected $signature = 'drupal:migrate-books';

    protected $description = 'Migrate Alburagh books from Drupal to Laravel';

    public function handle(): int
    {
        $this->info('Starting Drupal books migration...');

        $books = DB::connection('drupal')
            ->table('node')
            ->where('type', 'alburagh_book')
            ->orderBy('nid')
            ->get();

        $this->info("Total Drupal books: {$books->count()}");

        $bar = $this->output->createProgressBar($books->count());
        $bar->start();

        foreach ($books as $node) {

            /*
             * Taxonomy references
             */
            $ageGroupTid = $this->getTaxonomyTid(
                'field_data_field_book_age_group',
                'field_book_age_group_tid',
                $node->nid
            );

            $authorTid = $this->getTaxonomyTid(
                'field_data_field_book_author',
                'field_book_author_tid',
                $node->nid
            );

            $categoryTid = $this->getTaxonomyTid(
                'field_data_field_book_category',
                'field_book_category_tid',
                $node->nid
            );

            $illustratorTid = $this->getTaxonomyTid(
                'field_data_field_book_illustrator',
                'field_book_illustrator_tid',
                $node->nid
            );

            $languageTid = $this->getTaxonomyTid(
                'field_data_field_book_language',
                'field_book_language_tid',
                $node->nid
            );

            $publisherTid = $this->getTaxonomyTid(
                'field_data_field_book_publisher',
                'field_book_publisher_tid',
                $node->nid
            );

            $seriesTid = $this->getTaxonomyTid(
                'field_data_field_book_series',
                'field_book_series_tid',
                $node->nid
            );

            /*
             * تبدیل Drupal TID به Laravel ID
             */
            $ageGroupId = $this->findLaravelId('age_groups', $ageGroupTid);
            $authorId = $this->findLaravelId('authors', $authorTid);
            $categoryId = $this->findLaravelId('categories', $categoryTid);
            $illustratorId = $this->findLaravelId('illustrators', $illustratorTid);
            $languageId = $this->findLaravelId('languages', $languageTid);
            $publisherId = $this->findLaravelId('publishers', $publisherTid);
            $seriesId = $this->findLaravelId('series', $seriesTid);

            /*
             * فایل‌ها
             */
            $coverFid = $this->getFieldValue(
                'field_data_field_book_cover',
                'field_book_cover_fid',
                $node->nid
            );

            $bookFileFid = $this->getFieldValue(
                'field_data_field_book_file',
                'field_book_file_fid',
                $node->nid
            );

            $coverPath = $this->getFileUri($coverFid);
            $bookFilePath = $this->getFileUri($bookFileFid);

            /*
             * فیلدهای متنی و عددی
             */
            $description = $this->getFieldValue(
                'field_data_field_book_description',
                'field_book_description_value',
                $node->nid
            );

            $folder = $this->getFieldValue(
                'field_data_field_book_folder',
                'field_book_folder_value',
                $node->nid
            );

            $iosPid = $this->getFieldValue(
                'field_data_field_book_ios_pid',
                'field_book_ios_pid_value',
                $node->nid
            );

            $md5 = $this->getFieldValue(
                'field_data_field_book_md5',
                'field_book_md5_value',
                $node->nid
            );

            $sortOrder = $this->getFieldValue(
                'field_data_field_book_order',
                'field_book_order_value',
                $node->nid
            );

            $rate = $this->getFieldValue(
                'field_data_field_book_rate',
                'field_book_rate_value',
                $node->nid
            );

            $rateCount = $this->getFieldValue(
                'field_data_field_book_rate_count',
                'field_book_rate_count_value',
                $node->nid
            );

            $readings = $this->getFieldValue(
                'field_data_field_book_readings',
                'field_book_readings_value',
                $node->nid
            );

            $downloadCount = $this->getFieldValue(
                'field_data_field_book_download_count',
                'field_book_download_count_value',
                $node->nid
            );

            $version = $this->getFieldValue(
                'field_data_field_book_version',
                'field_book_version_value',
                $node->nid
            );

            $contentVersion = $this->getFieldValue(
                'field_data_field_content_version',
                'field_content_version_value',
                $node->nid
            );

            /*
             * ایجاد یا بروزرسانی رکورد Contents
             */
            $existingContent = DB::table('contents')
                ->where('drupal_nid', $node->nid)
                ->first();

            $contentData = [
                'type' => 'book',
                'title' => $node->title,
                'description' => $description,
                'language_id' => $languageId,
                'file_path' => $bookFilePath,
                'folder' => $folder,
                'main_image' => $coverPath,
                'site_image' => null,
                'sort_order' => (int) ($sortOrder ?? 0),
                'reading_count' => (int) ($readings ?? 0),
                'version' => $version,
                'content_version' => (int) ($contentVersion ?? 0),
                'is_published' => (bool) $node->status,
                'updated_at' => now(),
            ];

            if ($existingContent) {

                DB::table('contents')
                    ->where('id', $existingContent->id)
                    ->update($contentData);

                $contentId = $existingContent->id;

            } else {

                $contentData['drupal_nid'] = $node->nid;
                $contentData['created_at'] = now();

                $contentId = DB::table('contents')
                    ->insertGetId($contentData);
            }

            /*
             * اطلاعات اختصاصی کتاب
             */
            DB::table('book_details')->updateOrInsert(
                [
                    'content_id' => $contentId,
                ],
                [
                    'age_group_id' => $ageGroupId,
                    'author_id' => $authorId,
                    'category_id' => $categoryId,
                    'illustrator_id' => $illustratorId,
                    'publisher_id' => $publisherId,
                    'series_id' => $seriesId,

                    'cover_path' => $coverPath,
                    'ios_pid' => $iosPid,
                    'md5' => $md5,

                    'download_count' => (int) ($downloadCount ?? 0),
                    'rate' => (float) ($rate ?? 0),
                    'rate_count' => (int) ($rateCount ?? 0),

                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            /*
             * تصاویر نمونه کتاب
             */
            $samples = DB::connection('drupal')
                ->table('field_data_field_book_samples')
                ->where('entity_type', 'node')
                ->where('entity_id', $node->nid)
                ->where('deleted', 0)
                ->orderBy('delta')
                ->get();

            /*
             * چون Migration قابل اجرای مجدد است،
             * نمونه‌های قبلی همین کتاب حذف و دوباره ثبت می‌شوند.
             */
            DB::table('content_samples')
                ->where('content_id', $contentId)
                ->delete();

            foreach ($samples as $sample) {

                $fid = $sample->field_book_samples_fid ?? null;
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
        $this->newLine(2);

        $count = DB::table('contents')
            ->where('type', 'book')
            ->count();

        $samplesCount = DB::table('content_samples')
            ->whereIn(
                'content_id',
                DB::table('contents')
                    ->select('id')
                    ->where('type', 'book')
            )
            ->count();

        $this->info("Migrated Laravel books: {$count}");
        $this->info("Migrated book samples: {$samplesCount}");

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

    private function getTaxonomyTid(
        string $table,
        string $column,
        int $entityId
    ) {
        return $this->getFieldValue(
            $table,
            $column,
            $entityId
        );
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