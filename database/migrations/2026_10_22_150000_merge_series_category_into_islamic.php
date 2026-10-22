<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('book_catalogs')->where('category', 'series')->update(['category' => 'islamic']);
    }

    // one-way: merged rows can't be told apart afterwards
    public function down(): void {}
};
