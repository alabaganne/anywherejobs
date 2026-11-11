<x-mail::message>
# {{ __('Confirm your new email') }}

{!! __('Hi :name,', ['name' => $name]) !!}

{{ __('Someone asked to change the email of your :app account.', ['app' => config('app.name')]) }}

<x-mail::panel>
{{ __('From') }}: {!! $old !!}<br>
{{ __('To') }}: {!! $new !!}
</x-mail::panel>

{{ __('If this was you, confirm the change. We will then send a link to the new address so you can verify it too.') }}

<x-mail::button :url="$url">
{{ __('Confirm the change') }}
</x-mail::button>

{{ __('The link works for :minutes minutes. Until you confirm, nothing changes and you keep signing in with this address.', ['minutes' => $minutes]) }}

{{ __('If you did not ask for this, ignore this email and change your password.') }}

{{ __('The :app team', ['app' => config('app.name')]) }}

<x-slot:subcopy>
{{ __("If the button doesn't work, open this link:") }} <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
