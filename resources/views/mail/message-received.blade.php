<x-mail::message>
# {!! $sender !!} sent you a message

Hi {!! $recipient !!},

<x-mail::panel>
{!! $excerpt !!}
</x-mail::panel>

<x-mail::button :url="$url">
Read and reply
</x-mail::button>

We only email you about a conversation once in a while, so check {{ config('app.name') }} for newer messages.

The {{ config('app.name') }} team

<x-slot:subcopy>
You get this email because someone messaged you on {{ config('app.name') }}. If the button doesn't work, open this link: <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
