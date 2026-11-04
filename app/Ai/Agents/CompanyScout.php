<?php

namespace App\Ai\Agents;

use App\Ai\Tools\SuggestCompany;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\WebSearch;

// Searches the web for companies that hire for IT roles and hands each one to suggest_company.
#[MaxSteps(30)]
#[MaxTokens(16000)]
#[Timeout(300)]
class CompanyScout implements Agent, HasProviderOptions, HasTools
{
    use Promptable, SetsEffort;

    public function __construct(private SuggestCompany $suggest) {}

    public function instructions(): string
    {
        return 'You find real companies for '.config('app.name').', an IT job board, to follow. Each one must be hiring for IT roles right now and list its jobs on its own website.';
    }

    public function tools(): iterable
    {
        return [(new WebSearch)->max(15), $this->suggest];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, config('importer.ai.discovery_effort'));
    }
}
