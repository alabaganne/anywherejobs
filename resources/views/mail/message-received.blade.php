<x-mail::message>
# {!! __(':sender sent you a message', ['sender' => $sender]) !!}

{!! __('Hi :name,', ['name' => $recipient]) !!}

<x-mail::panel>
{!! $excerpt !!}
</x-mail::panel>

<x-mail::button :url="$url">
{{ __('Read and reply') }}
</x-mail::button>

{{ __('We only email you about a conversation once in a while, so check :app for newer messages.', ['app' => config('app.name')]) }}

{{ __('The :app team', ['app' => config('app.name')]) }}

<x-slot:subcopy>
{{ __("You get this email because someone messaged you on :app. If the button doesn't work, open this link:", ['app' => config('app.name')]) }} <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
