<?php

namespace App\Console\Commands;

use App\Importing\CompanyRegistry;
use App\Models\ImportSource;
use Illuminate\Console\Command;

class LinkCompanySources extends Command
{
    protected $signature = 'companies:link-sources {--limit=10 : Most sources to check in one run} {--source=* : Only these source keys} {--dry-run : Report without changing anything}';
    protected $description = 'Link each source added before the company registry to its company, and pause the ones the company website does not lead to';

    public function handle(CompanyRegistry $registry): int
    {
        $sources = ImportSource::where('enabled', true)->where('permission_status', 'approved')->where('publication_mode', '!=', 'leads')
            ->when($this->option('source'), fn ($query, $keys) => $query->whereIn('key', $keys))
            ->orderBy('id')->get()->reject(fn ($source) => CompanyRegistry::trusted($source))->take(max(1, (int) $this->option('limit')));
        if ($sources->isEmpty()) {
            $this->info('Every enabled source is linked to a followed company.');
            return self::SUCCESS;
        }
        $rows = $sources->map(fn ($source) => $registry->link($source, (bool) $this->option('dry-run')));
        $this->table(['Result', 'Source', 'Note'], $rows->all());
        $counts = $rows->countBy(0);
        $this->info(sprintf('%d linked, %d paused, %d to try again.', $counts['linked'] ?? 0, $counts['paused'] ?? 0, $counts['skipped'] ?? 0));
        return self::SUCCESS;
    }
}
