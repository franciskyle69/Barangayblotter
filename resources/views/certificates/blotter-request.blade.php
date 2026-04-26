<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 36px 44px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .header { width: 100%; }
        .header-table { width: 100%; border-collapse: collapse; }
        .logo { width: 96px; }
        .logo img { width: 86px; height: 86px; object-fit: contain; }
        .center { text-align: center; }
        .muted { color: #374151; }
        .title { margin-top: 22px; font-size: 18px; font-weight: 700; letter-spacing: 0.5px; }
        .section-label { margin-top: 18px; font-weight: 700; }
        .kv { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .kv td { padding: 2px 0; vertical-align: top; }
        .kv td:first-child { width: 220px; }
        .underline { border-bottom: 1px solid #111827; display: inline-block; min-width: 220px; padding-bottom: 1px; }
        .facts { margin-top: 10px; line-height: 1.55; text-align: justify; }
        .footer { margin-top: 26px; }
        .sig { margin-top: 40px; text-align: right; }
        .sig .name { font-weight: 700; text-transform: uppercase; }
        .sig .role { font-size: 11px; color: #374151; }
        .small { font-size: 10px; color: #4b5563; }
        .hr { border-top: 1px solid #e5e7eb; margin: 12px 0; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo">
                    @php
                        $logoSrc = null;
                        if (!empty($tenant?->logo_url)) {
                            $path = ltrim(parse_url($tenant->logo_url, PHP_URL_PATH) ?? '', '/');
                            $candidate = public_path($path);
                            if ($path !== '' && is_file($candidate)) {
                                $logoSrc = $candidate;
                            }
                        }
                    @endphp
                    @if($logoSrc)
                        <img src="{{ $logoSrc }}" alt="Logo">
                    @endif
                </td>
                <td class="center">
                    <div class="muted">Republic of the Philippines</div>
                    <div class="muted" style="font-weight: 700;">BARANGAY {{ strtoupper($tenant->name ?? '') }}</div>
                    @if(!empty($tenant->address))
                        <div class="muted">{{ $tenant->address }}</div>
                    @endif
                    @if(!empty($tenant->contact_phone))
                        <div class="muted">Contact: {{ $tenant->contact_phone }}</div>
                    @endif
                </td>
                <td style="width: 96px;"></td>
            </tr>
        </table>
    </div>

    <div class="title center">CERTIFICATION</div>

    <div class="section-label">TO WHOM IT MAY CONCERN:</div>

    <div class="facts">
        THIS IS TO CERTIFY that based on the Barangay Blotter records of this Barangay, an incident was recorded as follows:
    </div>

    <table class="kv">
        <tr>
            <td>BLOTTER ENTRY NO</td>
            <td>: <span class="underline">{{ $incident->blotter_number ?? ('#' . $incident->id) }}</span></td>
        </tr>
        <tr>
            <td>NATURE OF CASE / INCIDENT</td>
            <td>: <span class="underline">{{ $incident->incident_type }}</span></td>
        </tr>
        <tr>
            <td>DATE/TIME OF OCCURRENCE</td>
            <td>: <span class="underline">{{ optional($incident->incident_date)->format('F d, Y h:i A') }}</span></td>
        </tr>
        <tr>
            <td>PLACE OF OCCURRENCE</td>
            <td>: <span class="underline">{{ $incident->location ?? '—' }}</span></td>
        </tr>
        <tr>
            <td>COMPLAINANT</td>
            <td>: <span class="underline">{{ $incident->complainant_name }}</span></td>
        </tr>
        <tr>
            <td>RESPONDENT</td>
            <td>: <span class="underline">{{ $incident->respondent_name }}</span></td>
        </tr>
    </table>

    <div class="section-label">FACTS OF THE CASE:</div>
    <div class="facts">
        {{ $incident->description }}
    </div>

    <div class="footer">
        <div class="facts">
            This certification is issued upon request of <strong>{{ $requestedBy?->name ?? 'the requesting party' }}</strong>
            for whatever legal purpose it may serve.
        </div>

        <div class="hr"></div>

        <div class="small">
            Certificate No: {{ $blotterRequest->verification_code ?? '—' }}<br>
            Date Issued: {{ now()->format('F d, Y') }}
        </div>

        <div class="sig">
            <div class="small">For the Barangay:</div>
            <div style="height: 42px;"></div>
            <div class="name">{{ strtoupper($reviewedBy?->name ?? '') }}</div>
            <div class="role">Authorized Signatory</div>
        </div>
    </div>
</body>
</html>

