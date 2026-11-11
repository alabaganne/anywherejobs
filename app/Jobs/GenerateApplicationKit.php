<?php

namespace App\Jobs;

use App\Ai\ApplicationKits;
use App\Billing\Credits;
use App\Models\ApplicationKit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateApplicationKit implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(public int $kitId) {}

    public function handle(ApplicationKits $kits, Credits $credits): void
    {
        $kit = ApplicationKit::find($this->kitId);
        if (! $kit || $kit->status !== 'queued') { return; }
        try {
            $kit->update([...$kits->write($kit), 'status' => 'ready', 'writing' => null, 'error' => null]);
        } catch (\Throwable $e) {
            Log::warning('Application kit failed.', ['kit_id' => $kit->id, 'error' => Str::limit($e->getMessage(), 300)]);
            $this->fail($kit, $credits);
        }
    }

    public function failed(?\Throwable $e): void
    {
        if ($kit = ApplicationKit::find($this->kitId)) {
            $this->fail($kit, app(Credits::class));
        }
    }

    // A failed draft does not count, and its credit goes back.
    private function fail(ApplicationKit $kit, Credits $credits): void
    {
        if ($kit->status !== 'queued') { return; }
        $locale = $kit->candidate?->user?->preferredLocale();
        $what = __(['cv' => 'your CV', 'letter' => 'your letter'][$kit->writing] ?? 'your CV and letter', [], $locale);
        $kit->update(['status' => $kit->cv || filled($kit->cover_letter) ? 'ready' : 'failed', 'writing' => null, 'error' => __('We could not write :what right now. Please try again in a minute.', ['what' => $what], $locale)]);
        if ($kit->generations > 0) { $kit->decrement('generations'); }
        if ($user = $kit->candidate?->user) { $credits->refund($user, $kit->fresh()); }
    }
}
