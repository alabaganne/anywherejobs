<?php

namespace App\Ai\Agents;

use App\Support\LanguageGuess;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Strict;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

// Writes a CV, a cover letter or both for one role from the candidate's own profile and resume.
#[Strict]
#[MaxTokens(16000)]
#[Timeout(180)]
class ApplicationKitWriter implements Agent, HasProviderOptions, HasStructuredOutput
{
    use CvSchema, Promptable, SetsEffort;

    // $part is "cv", "letter" or "both".
    public function __construct(private string $locale, private string $part = 'both') {}

    public function instructions(): string
    {
        $language = LanguageGuess::name($this->locale) ?? 'English';
        $what = ['cv' => 'a CV', 'letter' => 'a cover letter', 'both' => 'a CV and a cover letter'][$this->part];
        $rules = [<<<PROMPT
You prepare job applications for candidates on :site, a job board for IT students and professionals around the world. You get the candidate's profile (and often their resume as a PDF) and one job listing in its original language. You write {$what} for that job, in {$language}.

Rules:
- Use only facts from the profile and resume. Never invent a job, title, date, skill, degree, number, result or link. If something useful is missing, leave it out.
- Use simple, everyday words and short sentences. No buzzwords, no big claims, no clichés.
- Write everything in {$language}, even if the listing or the resume is in another language. Translate job titles, degree names, fields of study, locations and words like Remote or Hybrid. Keep names of companies, schools, tools and products as they are.
PROMPT];
        if ($this->part !== 'letter') {
            $rules[] = <<<'PROMPT'
CV:
- headline: the candidate's role in a few words, aimed at this job, but true to their background.
- summary: two or three sentences on why they fit this job, written without pronouns (no I, he, she or they), like "Full-stack developer who built…".
- skills: up to 15, the ones this job asks for first, only skills the candidate really has.
- experience: their jobs, newest first, with the same companies and dates. Dates as "YYYY-MM" or "YYYY". For a job they still hold, current is true and end is "". For each job, two to four highlights: short lines that put forward what matters for this job.
- education, projects and languages: from the profile or resume. Keep projects that fit the job.
- An ATS must be able to read it: plain text in every field, no tables, no symbols.
PROMPT;
        }
        if ($this->part !== 'cv') {
            $rules[] = <<<'PROMPT'
Cover letter:
- Name the company and the role. Pick the two or three facts that best match the job and say why they matter for this team.
- 150 to 250 words: a greeting, three short paragraphs and a sign-off with the candidate's name.
- Plain text only: no markdown, no subject line, no placeholders in brackets.
PROMPT;
        }

        return str_replace(':site', config('app.name'), implode("\n\n", $rules));
    }

    public function schema(JsonSchema $schema): array
    {
        return array_filter([
            'cv' => $this->part !== 'letter' ? $this->cvSchema($schema) : null,
            'cover_letter' => $this->part !== 'cv' ? $schema->string()->required() : null,
        ]);
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->effort($provider, 'low');
    }
}
