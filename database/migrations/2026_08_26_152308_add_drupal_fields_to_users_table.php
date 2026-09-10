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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('drupal_uid')->nullable()->unique()->after('id');
    
            $table->string('username', 60)->nullable()->unique()->after('name');
    
            $table->string('legacy_password', 128)->nullable()->after('password');
    
            $table->string('fullname')->nullable()->after('email');
    
            $table->string('country', 2)->nullable()->after('fullname');
    
            $table->boolean('is_active')->default(true)->after('country');
    
            $table->timestamp('drupal_created_at')->nullable();
            $table->timestamp('drupal_last_access_at')->nullable();
            $table->timestamp('drupal_last_login_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'drupal_uid',
                'username',
                'legacy_password',
                'fullname',
                'country',
                'is_active',
                'drupal_created_at',
                'drupal_last_access_at',
                'drupal_last_login_at',
            ]);
        });
    }
};
