<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>New Incident Reported</title>
</head>

<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2 style="margin-bottom: 12px;">New incident report received</h2>

    <p>
        A {{ $reporterRole === 'resident' ? 'resident' : 'citizen' }} submitted a new incident report in
        <strong>{{ $tenant->name }}</strong>.
    </p>

    <p><strong>Incident details:</strong></p>
    <ul>
        <li>Type: {{ $incident->incident_type }}</li>
        <li>Date: {{ optional($incident->incident_date)->format('M d, Y h:i A') }}</li>
        <li>Location: {{ $incident->location ?: 'Not specified' }}</li>
        <li>Status: {{ ucfirst(str_replace('_', ' ', $incident->status)) }}</li>
        <li>Complainant: {{ $incident->complainant_name }}</li>
        <li>Respondent: {{ $incident->respondent_name }}</li>
        <li>Reported by account: {{ $reporter->name }} ({{ $reporter->email }})</li>
    </ul>

    <p><strong>Description:</strong></p>
    <p>{{ $incident->description }}</p>

    <p>
        Review the report here:
        <a href="{{ url('/incidents/' . $incident->id) }}">{{ url('/incidents/' . $incident->id) }}</a>
    </p>

    <p style="margin-top: 20px;">Regards,<br>{{ config('app.name') }}</p>
</body>

</html>