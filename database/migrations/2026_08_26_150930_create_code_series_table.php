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
        Schema::create('code_series', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('drupal_nid')->nullable()->unique();
    
            $table->string('country', 10)->nullable();
            $table->unsignedBigInteger('series_start')->nullable();
            $table->unsignedBigInteger('series_end')->nullable();
            $table->string('prefix')->nullable();
    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('code_series');
    }
};
