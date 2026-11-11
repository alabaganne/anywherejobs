<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

class Locale
{
    public const SUPPORTED = ['en', 'fr'];

    // French under /fr. Public pages follow their link; signed-in pages follow the language saved on the account,
    // and a visitor's other pages (password reset) follow the last sign-in page they used.
    public static function of(Request $request): string
    {
        if ($request->is('fr', 'fr/*')) {
            return 'fr';
        }
        if (PublicSeo::isPublic($request)) {
            return 'en';
        }

        $saved = $request->user()?->locale ?? ($request->hasSession() ? $request->session()->get('locale') : null);

        return $saved === 'fr' ? 'fr' : 'en';
    }

    // "November 05, 2026" in English, "5 novembre 2026" in French.
    public static function date(?\DateTimeInterface $date): ?string
    {
        if (! $date) {
            return null;
        }
        $date = Carbon::instance($date);

        return app()->getLocale() === 'fr' ? $date->locale('fr')->translatedFormat('j F Y') : $date->locale('en')->format('F d, Y');
    }
}
