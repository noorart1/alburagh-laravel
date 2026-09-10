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
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
    
            $table->unsignedBigInteger('drupal_nid')->nullable()->unique();
    
            $table->enum('type', [
                'book',
                'game',
                'quran',
                'sound_book'
            ]);
    
            $table->string('title');
    
            $table->text('description')->nullable();
    
            $table->foreignId('language_id')
                ->nullable()
                ->constrained('languages')
                ->nullOnDelete();
    
            $table->string('file_path')->nullable();
            $table->string('folder')->nullable();
    
            $table->string('main_image')->nullable();
            $table->string('site_image')->nullable();
    
            $table->integer('sort_order')->default(0);
    
            $table->unsignedBigInteger('reading_count')->default(0);
    
            $table->string('version')->nullable();
            $table->unsignedInteger('content_version')->default(0);
    
            $table->boolean('is_published')->default(true);
    
            $table->timestamps();
    
            $table->index('type');
            $table->index('language_id');
            $table->index('sort_order');
        });
    }
        /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
