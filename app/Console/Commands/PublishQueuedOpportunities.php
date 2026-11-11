<?php

namespace App\Console\Commands;

use App\Importing\OpportunityImporter;
use Illuminate\Console\Command;

class PublishQueuedOpportunities extends Command
{
    protected $signature = 'opportunities:publish-queued';
    protected $description = 'Publish the best queued listings, up to the daily limit of new roles';

    public function handle(OpportunityImporter $importer): int
    {
        $this->info(count($importer->publishQueued()).' new opportunities published.');
        return self::SUCCESS;
    }
}
