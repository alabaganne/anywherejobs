<?php

namespace App\Ai\Tools;

use App\Importing\CompanyRegistry;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[Strict]
class SuggestCompany implements Tool
{
    // One row per suggestion: [status, name, message].
    public array $results = [];

    public function __construct(private CompanyRegistry $registry, private int $max) {}

    public function name(): string
    {
        return 'suggest_company';
    }

    public function description(): string
    {
        return 'Add one company to follow. '.config('app.name').' checks its website and careers page, and returns whether it was added or why not.';
    }

    public function handle(Request $request): string
    {
        if ($this->added() >= $this->max) { return "Enough: {$this->max} companies are already added. Stop now."; }
        $result = $this->registry->add($request->all());
        $this->results[] = $result;
        return $this->added() >= $this->max ? $result[2].' That was the last one needed, so stop now.' : $result[2];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'company' => $schema->string()->description('The company name.')->required(),
            'website' => $schema->string()->description('The home page of the company\'s own website, starting with https.')->required(),
            'careers_url' => $schema->string()->description('The page on its own site where it lists its jobs, or empty.')->required(),
            'reason' => $schema->string()->description('A few words on why it fits, such as "Visa sponsor, Berlin" or "Remote, open to Africa".')->required(),
            'recent_role' => $schema->string()->description('One IT job title it posted in the last 14 days.')->required(),
        ];
    }

    private function added(): int
    {
        return collect($this->results)->where(0, 'added')->count();
    }
}
