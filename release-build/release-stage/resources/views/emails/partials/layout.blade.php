@php
    $brandName = config('app.name', 'Barangay Blotter');
    $supportEmail = config('mail.from.address', 'support@example.com');
    $primaryColor = '#2563eb';
    $surfaceColor = '#ffffff';
    $borderColor = '#e5e7eb';
    $mutedColor = '#6b7280';
    $headingColor = '#111827';
    $statusLabel = $statusLabel ?? null;
    $statusTone = $statusTone ?? 'neutral';
    $statusStyles = [
        'success' => ['background' => '#dcfce7', 'color' => '#166534'],
        'warning' => ['background' => '#fef3c7', 'color' => '#92400e'],
        'danger' => ['background' => '#fee2e2', 'color' => '#991b1b'],
        'info' => ['background' => '#dbeafe', 'color' => '#1d4ed8'],
        'neutral' => ['background' => '#e5e7eb', 'color' => '#374151'],
    ];
    $statusStyle = $statusStyles[$statusTone] ?? $statusStyles['neutral'];
@endphp
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ $title ?? $brandName }}</title>
</head>

<body
    style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:{{ $headingColor }};line-height:1.5;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background-color:#f3f4f6;margin:0;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                    style="max-width:600px;width:100%;background-color:{{ $surfaceColor }};border:1px solid {{ $borderColor }};border-radius:16px;overflow:hidden;box-shadow:0 10px 25px rgba(15,23,42,0.08);">
                    <tr>
                        <td
                            style="padding:24px 28px 12px 28px;border-bottom:1px solid {{ $borderColor }};background:linear-gradient(135deg,#ffffff 0%,#f8fafc 100%);">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="font-size:20px;font-weight:700;color:{{ $headingColor }};">
                                        {{ $brandName }}
                                    </td>
                                    @if(!empty($statusLabel))
                                        <td align="right">
                                            <span
                                                style="display:inline-block;padding:6px 12px;border-radius:999px;background-color:{{ $statusStyle['background'] }};color:{{ $statusStyle['color'] }};font-size:12px;font-weight:700;letter-spacing:0.02em;">
                                                {{ strtoupper($statusLabel) }}
                                            </span>
                                        </td>
                                    @endif
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p
                                style="margin:0 0 10px 0;font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:{{ $mutedColor }};font-weight:700;">
                                {{ $eyebrow ?? 'Notification' }}
                            </p>
                            <h1
                                style="margin:0 0 16px 0;font-size:26px;line-height:1.25;color:{{ $headingColor }};font-weight:700;">
                                {{ $heading ?? $title ?? $brandName }}
                            </h1>

                            @if(!empty($message))
                                <p style="margin:0 0 16px 0;font-size:15px;color:{{ $headingColor }};">
                                    {!! $message !!}
                                </p>
                            @endif

                            @if(!empty($bodyLines) && is_array($bodyLines))
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                    style="border-collapse:collapse;margin:16px 0;">
                                    @foreach($bodyLines as $line)
                                        <tr>
                                            <td
                                                style="padding:10px 12px;border:1px solid {{ $borderColor }};border-left:4px solid {{ $primaryColor }};background-color:#f8fafc;font-size:14px;color:{{ $headingColor }};">
                                                {!! $line !!}
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif

                            @isset($slot)
                                {!! $slot !!}
                            @endisset

                            @if(!empty($ctaText) && !empty($ctaUrl))
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                    style="margin:24px 0 12px 0;">
                                    <tr>
                                        <td>
                                            <a href="{{ $ctaUrl }}"
                                                style="display:inline-block;background-color:{{ $primaryColor }};color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:12px 20px;border-radius:10px;">
                                                {{ $ctaText }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if(!empty($secondaryText))
                                <p style="margin:0 0 4px 0;font-size:13px;color:{{ $mutedColor }};">
                                    {!! $secondaryText !!}
                                </p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 28px;border-top:1px solid {{ $borderColor }};background-color:#f9fafb;">
                            <p style="margin:0 0 6px 0;font-size:12px;color:{{ $mutedColor }};">
                                Need help? Contact <a href="mailto:{{ $supportEmail }}"
                                    style="color:{{ $primaryColor }};text-decoration:none;">{{ $supportEmail }}</a>.
                            </p>
                            <p style="margin:0;font-size:12px;color:{{ $mutedColor }};">
                                {{ $footer ?? 'This message was sent automatically. Please do not reply directly to this email.' }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>