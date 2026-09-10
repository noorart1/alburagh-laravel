<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_catalogs', function (Blueprint $table) {
            $table->id();

            // اطلاعات اصلی
            $table->string('barcode', 50)->nullable()->index();
            $table->string('series_name')->nullable()->index();
            $table->string('title')->index();
            $table->string('author')->nullable()->index();
            $table->string('illustrator')->nullable()->index();
            $table->string('subject')->nullable()->index();

            // اطلاعات چاپ
            $table->unsignedInteger('edition_number')->nullable();
            $table->string('print_place')->nullable();
            $table->unsignedSmallInteger('print_year')->nullable()->index();

            // معرفی و مشخصات
            $table->text('short_description')->nullable();
            $table->unsignedInteger('pages')->nullable();

            // قیمت‌ها
            $table->decimal('price_usd', 10, 2)->nullable();
            $table->decimal('price_aed', 10, 2)->nullable();
            $table->decimal('price_iqd', 12, 0)->nullable();

            // اطلاعات ایداع
            $table->string('deposit_number', 100)->nullable();
            $table->string('wrong_deposit_number', 100)->nullable();
            $table->unsignedSmallInteger('deposit_year')->nullable()->index();

            // مشخصات فیزیکی
            $table->string('size', 100)->nullable();
            $table->decimal('weight', 8, 3)->nullable();

            // گروه سنی
            $table->string('age_group', 100)->nullable()->index();

            // فیلدهای مدیریتی مستقل از اپ
            $table->string('cover_image')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_catalogs');
    }
};
