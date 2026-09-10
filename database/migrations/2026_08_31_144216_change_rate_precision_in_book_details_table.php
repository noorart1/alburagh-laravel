<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('book_details', function (Blueprint $table) {
            $table->decimal('rate', 10, 5)
                ->default(0)
                ->change();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('book_details', function (Blueprint $table) {
            $table->decimal('rate', 4, 2)
                ->default(0)
                ->change();
        });
    }
};
