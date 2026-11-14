<?php
namespace App\Support;
use Illuminate\Support\Facades\Cache;
class ScheduledTasks {
    public const COMMANDS = [
        'opportunities:import' => 'opportunities:import --due --limit=',
        'opportunities:reconcile' => 'opportunities:reconcile',
        'opportunities:publish-queued' => 'opportunities:publish-queued',
        'opportunities:digests' => 'opportunities:digests',
        'opportunities:indexing' => 'opportunities:indexing --limit=100',
        'companies:logos' => 'companies:logos',
        'companies:research' => 'companies:research',
        'leads:daily' => 'leads:daily',
        'companies:link-sources' => 'companies:link-sources',
        'companies:discover' => 'companies:discover',
        'seo:indexnow' => 'seo:indexnow',
        'opportunities:translate' => 'opportunities:translate --limit=200',
        'credits:monthly' => 'credits:monthly',
    ];
    public static function enabled(string $name): bool { return (bool) Cache::get('schedule.enabled.'.$name, false); }
    public static function listing(): array { return collect(self::COMMANDS)->map(fn ($command, $name) => ['name' => $name, 'enabled' => self::enabled($name)])->values()->all(); }
}
