<?php

namespace App\Console\Commands;

use App\Ai\OpportunityClassifier;
use App\Importing\CompanyRegistry;
use Illuminate\Console\Command;

class DiscoverCompanies extends Command
{
    protected $signature = 'companies:discover {--count= : How many new companies to add (default importer.registry.per_day)}';
    protected $description = 'Ask the AI for new companies hiring for IT roles, check each website and careers page, and follow the ones that pass';

    public function handle(CompanyRegistry $registry, OpportunityClassifier $ai): int
    {
        if (! $registry->enabled()) {
            $this->info('AI is off. Set '.$ai->keyName().' to look for new companies.');
            return self::SUCCESS;
        }
        $count = max(1, min(20, (int) ($this->option('count') ?? config('importer.registry.per_day'))));
        try {
            $results = $registry->discover($count);
        } catch (\Throwable $e) {
            $this->error('Company search stopped: '.$e->getMessage());
            return self::FAILURE;
        }
        if ($results) {
            $this->table(['Result', 'Company', 'Note'], $results);
        }
        $added = collect($results)->where(0, 'added')->count();
        $this->info("{$added} new ".str('company')->plural($added).' followed. Their jobs are read on the next import.');
        $this->line(sprintf('Used %d input tokens, %d output tokens and %d web searches.', ...array_values($registry->usage)));
        return self::SUCCESS;
    }
}
