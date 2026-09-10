<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateDrupalTaxonomies extends Command
{
    protected $signature = 'drupal:migrate-taxonomies';

    protected $description = 'Migrate Drupal taxonomy terms to Laravel reference tables';

    public function handle(): int
    {
        $map = [
            'age_groups' => 'age_groups',
            'arabic_categories' => 'categories',
            'authors' => 'authors',
            'book_languages' => 'languages',
            'book_series' => 'series',
            'illustrators' => 'illustrators',
            'publishers' => 'publishers',
        ];

        foreach ($map as $vocabularyMachineName => $laravelTable) {

            $vocabulary = DB::connection('drupal')
                ->table('taxonomy_vocabulary')
                ->where('machine_name', $vocabularyMachineName)
                ->first();

            if (!$vocabulary) {
                $this->warn("Vocabulary not found: {$vocabularyMachineName}");
                continue;
            }

            $terms = DB::connection('drupal')
                ->table('taxonomy_term_data')
                ->where('vid', $vocabulary->vid)
                ->orderBy('weight')
                ->orderBy('tid')
                ->get();

            $this->info(
                "{$vocabularyMachineName}: {$terms->count()} terms"
            );

            foreach ($terms as $term) {

                $data = [
                    'drupal_tid' => $term->tid,
                    'name' => $term->name,
                    'updated_at' => now(),
                ];

                if (in_array($laravelTable, [
                    'age_groups',
                    'categories',
                    'series',
                ])) {
                    $data['sort_order'] = $term->weight ?? 0;
                    $data['is_active'] = true;
                }

                if ($laravelTable === 'languages') {
                    $data['is_active'] = true;
                }

                if (in_array($laravelTable, [
                    'age_groups',
                    'categories',
                ])) {
                    $data['slug'] = null;
                }

                $existing = DB::table($laravelTable)
                    ->where('drupal_tid', $term->tid)
                    ->exists();

                if (!$existing) {
                    $data['created_at'] = now();
                }

                DB::table($laravelTable)->updateOrInsert(
                    ['drupal_tid' => $term->tid],
                    $data
                );
            }
        }

        /*
         * انتقال short_name زبان‌ها
         */
        $languageShortNames = DB::connection('drupal')
            ->table('field_data_field_lang_short_name')
            ->where('entity_type', 'taxonomy_term')
            ->where('deleted', 0)
            ->pluck('field_lang_short_name_value', 'entity_id');

        foreach ($languageShortNames as $tid => $shortName) {
            DB::table('languages')
                ->where('drupal_tid', $tid)
                ->update([
                    'short_name' => $shortName,
                    'updated_at' => now(),
                ]);
        }

        $this->newLine();
        $this->info('Drupal taxonomies migrated successfully.');

        return Command::SUCCESS;
    }
}