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
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
    
            $table->string('token')->unique();
    
            $table->unsignedBigInteger('user_id')
                ->nullable();
    
            $table->string('platform', 20)
                ->nullable();
    
            $table->unsignedTinyInteger('type')
                ->default(1);
    
            $table->string('language', 10)
                ->default('ar');
    
            $table->timestamp('registered_at')
                ->nullable();
    
            $table->timestamps();
    
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
