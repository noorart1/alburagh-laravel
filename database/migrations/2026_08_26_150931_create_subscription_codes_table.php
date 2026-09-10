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
        Schema::create('subscription_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('drupal_nid')->nullable()->unique();
    
            $table->string('code')->index();
            $table->string('serial')->nullable()->index();
    
            $table->unsignedInteger('amount')->nullable();
    
            $table->string('status')->nullable()->index();
    
            $table->foreignId('code_series_id')
                ->nullable()
                ->constrained('code_series')
                ->nullOnDelete();
    
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_codes');
    }
};
