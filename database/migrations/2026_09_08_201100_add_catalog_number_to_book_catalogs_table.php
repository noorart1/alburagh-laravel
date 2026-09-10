<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_catalogs', function (Blueprint $table) {
            $table->unsignedInteger('catalog_number')
                ->nullable()
                ->after('id');
        });

        // Existing records: assign 1000, 1001, 1002, ...
        $nextNumber = 1000;

        DB::table('book_catalogs')
            ->orderBy('id')
            ->select('id')
            ->chunkById(200, function ($rows) use (&$nextNumber) {
                foreach ($rows as $row) {
                    DB::table('book_catalogs')
                        ->where('id', $row->id)
                        ->update([
                            'catalog_number' => $nextNumber++,
                        ]);
                }
            });

        // All records must have a number.
        Schema::table('book_catalogs', function (Blueprint $table) {
            $table->unsignedInteger('catalog_number')->nullable(false)->change();
        });

        // Database-level protection against duplicates.
        Schema::table('book_catalogs', function (Blueprint $table) {
            $table->unique(
                'catalog_number',
                'book_catalogs_catalog_number_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('book_catalogs', function (Blueprint $table) {
            $table->dropUnique('book_catalogs_catalog_number_unique');
            $table->dropColumn('catalog_number');
        });
    }
};
