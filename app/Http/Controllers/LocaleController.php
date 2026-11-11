<?php

namespace App\Http\Controllers;

use App\Support\Locale;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate(['locale' => ['required', Rule::in(Locale::SUPPORTED)]]);
        $request->user()->forceFill(['locale' => $data['locale']])->save();

        return back();
    }
}
