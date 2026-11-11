<x-mail::message>
# {{ __('Your :app account is ready', ['app' => config('app.name')]) }}

{!! __('Hi :name,', ['name' => $name]) !!}

{{ __('An account was created for you on :app, where students and professionals find internships and jobs in Tunisia and worldwide.', ['app' => config('app.name')]) }}

{{ __('Choose a password to sign in. This also confirms your email address.') }}

<x-mail::button :url="$url">
{{ __('Choose my password') }}
</x-mail::button>

{{ __('The link works for :minutes minutes. After that, use "Forgot password" on the sign-in page to get a new one.', ['minutes' => $minutes]) }}

{{ __('The :app team', ['app' => config('app.name')]) }}

<x-slot:subcopy>
{{ __("If you did not expect this email, you can ignore it. If the button doesn't work, open this link:") }} <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
