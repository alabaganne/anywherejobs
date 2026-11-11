<x-mail::message>
# {!! $headline !!}

{!! __('Hi :name,', ['name' => $candidate]) !!}

{!! $body !!}

<x-mail::panel>
**{!! $role !!}**<br>
{!! $company !!} · {{ __('Status') }}: {!! $statusLabel !!}
</x-mail::panel>

<x-mail::button :url="$url">
{{ __('See your application') }}
</x-mail::button>

@if ($tip)
{!! $tip !!}

@endif
{{ __('The :app team', ['app' => config('app.name')]) }}

<x-slot:subcopy>
{{ __("You get this email because you applied to this role on :app. If the button doesn't work, open this link:", ['app' => config('app.name')]) }} <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
