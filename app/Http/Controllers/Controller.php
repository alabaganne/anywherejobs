<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    // Sign-up and log-in links can carry ?next=/a/page on this site; people go back there afterwards.
    protected function rememberNext(\Illuminate\Http\Request $request): void
    {
        $next = $request->query('next');
        if (is_string($next) && strlen($next) <= 500 && preg_match('~^/(?![/\\\\])[^\\\\\s]*$~', $next)) {
            $request->session()->put('url.intended', $next);
        }
    }
}
