<?php

use App\Models\ImportSource;
use Database\Seeders\ImportSourceSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    // The Hacker News "Who is hiring" thread and the job pages its posts link to are read every day.
    public function up(): void
    {
        // A new install gets them from the seeder.
        if (! ImportSource::exists()) { return; }
        $keys = ['hn-who-is-hiring', 'linked-job-pages'];
        (new ImportSourceSeeder())->ensure($keys);
        ImportSource::whereIn('key', $keys)->where('permission_status', 'approved')
            ->update(['enabled' => true, 'next_run_at' => null]);
    }

    public function down(): void {}
};
