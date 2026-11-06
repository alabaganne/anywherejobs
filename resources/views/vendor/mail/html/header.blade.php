@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<img src="{{ rtrim(config('app.url'), '/').config('site.brand.wordmark_png') }}" class="logo" width="191" height="40" alt="{{ config('app.name') }}">
</a>
</td>
</tr>
