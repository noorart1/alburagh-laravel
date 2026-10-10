<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_catalogs', function (Blueprint $table) {
            $table->dropColumn(['cost_usd', 'cost_aed']);
        });
    }

    public function down(): void
    {
        Schema::table('book_catalogs', function (Blueprint $table) {
            $table->decimal('cost_usd', 10, 2)->nullable()->after('quantity');
            $table->decimal('cost_aed', 10, 2)->nullable()->after('cost_usd');
        });
    }
};
