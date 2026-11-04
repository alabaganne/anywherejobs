<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('tracked_at')->nullable()->index();
            $table->string('tracked_reason', 300)->nullable();
            $table->json('tracking')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['tracked_at']);
            $table->dropColumn(['tracked_at', 'tracked_reason', 'tracking']);
        });
    }
};
