<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
@php($home = rtrim(config('app.url'), '/'))
[{{ __('Browse roles') }}]({{ $home }}/opportunities) · [{{ __('Settings') }}]({{ $home }}/settings) · [{{ __('Contact') }}]({{ $home }}/contact)

{{ config('app.name') }} · {{ __('IT internships and jobs around the world.') }}<br>
© {{ date('Y') }} {{ config('app.name') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
