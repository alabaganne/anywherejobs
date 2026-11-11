<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// New listings no longer wait for review: every source publishes them through the daily limit (owner, 11 November 2026).
return new class extends Migration
{
    public function up(): void
    {
        DB::table('import_sources')->where('publication_mode', 'review')->update(['publication_mode' => 'automatic']);
    }

    public function down(): void {}
};
