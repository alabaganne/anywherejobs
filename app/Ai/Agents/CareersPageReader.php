<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

// Lists the job openings on a company's careers page, only from the page's own text and links.
#[Strict]
#[MaxTokens(8000)]
#[Timeout(120)]
class CareersPageReader implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable, SetsEffort;

    public function instructions(): string
    {
        return str_replace(':site', config('app.name'), <<<'PROMPT'
You read a company's careers page for :site, a job board. You get the page text and the links on the page. List every job opening the page shows.

- title: the job title exactly as the page writes it. Never shorten, translate or improve it.
- url: the link to that job, copied exactly from the list of links. Leave the job out when no link in the list leads to it.
- location: the place or work mode the page shows next to that job, copied as written, or an empty string.

Only list real openings. Leave out team pages, blog posts, events, "send us your CV" or open applications, and anything you are not sure about. Never invent a job, a link or a place. When the page shows no openings, answer with an empty list.
PROMPT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'jobs' => $schema->array()->items($schema->object([
                'title' => $schema->string()->required(),
                'url' => $schema->string()->required(),
                'location' => $schema->string()->required(),
            ]))->required(),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, config('importer.ai.effort'));
    }
}
