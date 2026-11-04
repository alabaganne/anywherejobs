<x-mail::message>
# New application for {!! $role !!}

Hi {!! $company !!},

**{!! $candidate !!}** applied to **{!! $role !!}** on {{ config('app.name') }}.

@if ($excerpt)
<x-mail::panel>
{!! $excerpt !!}
</x-mail::panel>
@endif

@if ($attachments)
{{ $attachments === 1 ? 'One file is attached' : $attachments.' files are attached' }} to the application.

@endif
<x-mail::button :url="$url">
Review the application
</x-mail::button>

Reply from {{ config('app.name') }} so the candidate always knows where they stand.

The {{ config('app.name') }} team

<x-slot:subcopy>
You get this email because {!! $company !!} posts roles on {{ config('app.name') }}. If the button doesn't work, open this link: <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
