<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        JsonResource::withoutWrapping();

        // Shared here, not in the Inertia middleware, so error pages get them too.
        \Inertia\Inertia::share('brand', fn () => ['name' => config('app.name'), 'logo' => config('site.brand.logo'), 'wordmark' => config('site.brand.wordmark'), 'wordmark_white' => config('site.brand.wordmark_white')]);

        Relation::morphMap([
            'candidate' => \App\Models\Candidate::class,
            'company' => \App\Models\Company::class,
            'opportunity' => \App\Models\Opportunity::class,
        ]);

        \App\Models\Opportunity::observe(\App\Observers\OpportunityIndexingObserver::class);
        \App\Models\Opportunity::observe(\App\Observers\OpportunityTranslationObserver::class);

        \Illuminate\Support\Facades\Event::listen(\Inertia\Ssr\SsrRenderFailed::class, function ($event) {
            \Illuminate\Support\Facades\Log::error('Public server rendering failed.', [
                'component' => $event->component(),
                'path' => parse_url($event->url(), PHP_URL_PATH),
                'type' => $event->type->value,
            ]);
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        VerifyEmail::toMailUsing(fn ($user, string $url) => (new MailMessage)
            ->subject(__('Confirm your email for :app', ['app' => config('app.name')]))
            ->greeting(__('Hi :name,', ['name' => Str::of($user->name)->before(' ')]))
            ->line(__('Thanks for joining :app. Please confirm this is your email address so you can apply to roles, message employers and get alerts.', ['app' => config('app.name')]))
            ->action(__('Confirm my email'), $url)
            ->line(__('The link works for :minutes minutes. If you did not create an account, you can ignore this email.', ['minutes' => config('auth.verification.expire', 60)])));

        ResetPassword::toMailUsing(fn ($user, string $token) => (new MailMessage)
            ->subject(__('Reset your :app password', ['app' => config('app.name')]))
            ->greeting(__('Hi :name,', ['name' => Str::of($user->name)->before(' ')]))
            ->line(__('We got a request to reset the password for your :app account.', ['app' => config('app.name')]))
            ->action(__('Choose a new password'), url(route('password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], false)))
            ->line(__('The link works for :minutes minutes. If you did not ask for this, you can ignore this email and your password stays the same.', ['minutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')])));
    }
}
