<x-mail::message>
# {!! __('New roles for ":name"', ['name' => \App\Support\MailText::plain($search->name, 120)]) !!}

{{ __("These open roles match your saved search. Check each employer's requirements before you apply.") }}

@php($arrangements = ['remote' => __('Remote'), 'hybrid' => __('Hybrid'), 'onsite' => __('On-site')])
@foreach ($opportunities as $opportunity)
<div class="role">
<p class="role-meta"><span class="tag">{{ $opportunity->kind === 'job' ? __('Job') : __('Internship') }}</span>@if (isset($arrangements[$opportunity->work_type]))&nbsp; {{ $arrangements[$opportunity->work_type] }}@endif</p>
<h2><a href="{{ $opportunity->public_url }}">{{ $opportunity->title }}</a></h2>
<p class="role-meta">{{ $opportunity->company?->display_name }} · {{ $opportunity->location_label }}</p>
@if ($opportunity->work_type === 'remote' && $opportunity->remote_eligibility === 'unknown')
<p class="role-note">{{ __('Not confirmed that you can work on this remotely from Tunisia.') }}</p>
@endif
<p class="role-note">{{ $opportunity->posted_at ? __('Posted :date', ['date' => $opportunity->posted_at->locale(app()->getLocale())->translatedFormat('j M Y')]) : __('Posting date not given') }}</p>
</div>
@endforeach

<x-mail::button :url="$browseUrl">
{{ __('See all matching roles') }}
</x-mail::button>

{{ __('The :app team', ['app' => config('app.name')]) }}

<x-slot:subcopy>
{{ __('You get :frequency alerts for this saved search.', ['frequency' => __($search->frequency)]) }} [{{ __('Pause these alerts') }}]({{ $unsubscribeUrl }}) {{ __('or') }} [{{ __('manage your saved searches') }}]({{ route('saved-searches.index') }}).
</x-slot:subcopy>
</x-mail::message>
