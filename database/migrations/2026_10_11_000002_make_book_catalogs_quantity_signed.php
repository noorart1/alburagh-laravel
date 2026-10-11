<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Invoices deduct stock; selling more than is on hand shows as negative instead of failing.
    public function up(): void
    {
        Schema::table('book_catalogs', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('book_catalogs', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(0)->change();
        });
    }
};
