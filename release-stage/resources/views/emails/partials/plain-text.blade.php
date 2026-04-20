{{ $title ?? config('app.name', 'Barangay Blotter') }}

{{ $heading ?? $title ?? config('app.name', 'Barangay Blotter') }}

{{ $message ?? '' }}

@if(!empty($bodyLines) && is_array($bodyLines))
    @foreach($bodyLines as $line)
        - {!! strip_tags($line) !!}
    @endforeach
@endif

@if(!empty($ctaText) && !empty($ctaUrl))
    {{ $ctaText }}: {{ $ctaUrl }}
@endif

@if(!empty($secondaryText))
    {{ strip_tags($secondaryText) }}
@endif

Need help? Contact {{ config('mail.from.address', 'support@example.com') }}.