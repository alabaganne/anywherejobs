<?php

namespace App\Notifications;

use App\Models\Candidate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PublicProfileReviewed extends Notification
{
    use Queueable;

    public function __construct(public Candidate $candidate)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return $this->candidate->isPubliclyListed()
            ? ['title' => __('Your public profile is live'), 'body' => __('Visitors and companies can now see your profile on :app.', ['app' => config('app.name')]), 'action' => parse_url($this->candidate->publicUrl(), PHP_URL_PATH)]
            : ['title' => __('Your public profile was not approved'), 'body' => __('Your profile stays private. You can update it and ask again from your profile.'), 'action' => route('profile.show', [], false)];
    }
}
