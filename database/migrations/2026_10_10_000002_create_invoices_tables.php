<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('buyer_name')->index();
            $table->date('date')->index();
            $table->string('currency', 3);
            $table->decimal('total', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_catalog_id')->nullable()->constrained()->nullOnDelete();
            // Snapshot so the invoice stays correct if the book is later edited/deleted.
            $table->string('barcode', 50)->nullable();
            $table->string('title');
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
