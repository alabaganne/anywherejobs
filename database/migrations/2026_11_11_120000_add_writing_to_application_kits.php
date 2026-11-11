<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // What a queued kit is writing: the CV, the letter or both.
    public function up(): void
    {
        Schema::table('application_kits', function (Blueprint $table) {
            $table->string('writing', 10)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('application_kits', function (Blueprint $table) {
            $table->dropColumn('writing');
        });
    }
};
