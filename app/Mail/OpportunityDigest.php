<?php

namespace App\Mail;

use App\Http\Controllers\SavedSearchController;
use App\Models\SavedSearch;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class OpportunityDigest extends Mailable
{
    public function __construct(public SavedSearch $search, public Collection $opportunities, public string $batchId)
    {
        $this->locale($search->user?->preferredLocale() ?? 'en');
    }

    public function headers(): Headers
    {
        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
        return new Headers(messageId: 'opportunities.'.$this->batchId.'@'.$domain, text: [
            'List-Unsubscribe' => '<'.URL::signedRoute('saved-searches.unsubscribe', ['savedSearch' => $this->search->id]).'>',
        ]);
    }

    public function build()
    {
        return $this->subject(__('New roles for :name on :app', ['name' => $this->search->name, 'app' => config('app.name')]))->markdown('emails.opportunity-digest')->with([
            'unsubscribeUrl' => URL::signedRoute('saved-searches.unsubscribe', ['savedSearch' => $this->search->id]),
            'browseUrl' => SavedSearchController::browseUrl($this->search->filters),
        ]);
    }
}
