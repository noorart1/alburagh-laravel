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
        Schema::create('book_details', function (Blueprint $table) {
            $table->id();
    
            $table->foreignId('content_id')
                ->unique()
                ->constrained('contents')
                ->cascadeOnDelete();
    
            $table->foreignId('age_group_id')
                ->nullable()
                ->constrained('age_groups')
                ->nullOnDelete();
    
            $table->foreignId('author_id')
                ->nullable()
                ->constrained('authors')
                ->nullOnDelete();
    
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();
    
            $table->foreignId('illustrator_id')
                ->nullable()
                ->constrained('illustrators')
                ->nullOnDelete();
    
            $table->foreignId('publisher_id')
                ->nullable()
                ->constrained('publishers')
                ->nullOnDelete();
    
            $table->foreignId('series_id')
                ->nullable()
                ->constrained('series')
                ->nullOnDelete();
    
            $table->string('cover_path')->nullable();
            $table->string('ios_pid')->nullable();
            $table->string('md5')->nullable();
    
            $table->unsignedBigInteger('download_count')->default(0);
    
            $table->decimal('rate', 4, 2)->default(0);
            $table->unsignedBigInteger('rate_count')->default(0);
    
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_details');
    }
};
