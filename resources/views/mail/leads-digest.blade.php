<x-mail::message>
# {{ __('Your job leads for today') }}

@foreach ($leads as $lead)
**{!! $lead['company'] !!}**: {!! $lead['title'] !!}<br>
{!! $lead['reason'] !!}

@endforeach
<x-mail::button :url="$url">
{{ __('Open job leads') }}
</x-mail::button>

{{ __('The :app team', ['app' => config('app.name')]) }}

<x-slot:subcopy>
{{ __("You get this email because job lead emails are on (LEADS_DIGEST_EMAIL). If the button doesn't work, open this link:") }} <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
