<x-mail::message>
# {!! __('New application for :role', ['role' => $role]) !!}

{!! __('Hi :name,', ['name' => $company]) !!}

{!! __('**:candidate** applied to **:role** on :app.', ['candidate' => $candidate, 'role' => $role, 'app' => config('app.name')]) !!}

@if ($excerpt)
<x-mail::panel>
{!! $excerpt !!}
</x-mail::panel>
@endif

@if ($attachments)
{{ $attachments === 1 ? __('One file is attached to the application.') : __(':count files are attached to the application.', ['count' => $attachments]) }}

@endif
<x-mail::button :url="$url">
{{ __('Review the application') }}
</x-mail::button>

{{ __('Reply from :app so the candidate always knows where they stand.', ['app' => config('app.name')]) }}

{{ __('The :app team', ['app' => config('app.name')]) }}

<x-slot:subcopy>
{!! __("You get this email because :company posts roles on :app. If the button doesn't work, open this link:", ['company' => $company, 'app' => config('app.name')]) !!} <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
