@php
    $title = 'New Incident Reported';
    $heading = 'New incident report received';
    $statusLabel = ucfirst($incident->status);
    $statusTone = 'info';
    $message = 'A ' . ($reporterRole === 'resident' ? 'resident' : 'citizen') . ' submitted a new incident report in ' . $tenant->name . '.';
    $bodyLines = [
        'Type: ' . $incident->incident_type,
        'Date: ' . optional($incident->incident_date)->format('M d, Y h:i A'),
        'Location: ' . ($incident->location ?: 'Not specified'),
        'Status: ' . ucfirst(str_replace('_', ' ', $incident->status)),
        'Complainant: ' . $incident->complainant_name,
        'Respondent: ' . $incident->respondent_name,
        'Reported by account: ' . $reporter->name . ' (' . $reporter->email . ')',
    ];
    $secondaryText = 'Description: ' . $incident->description;
    $ctaText = 'View Incident';
    $ctaUrl = url('/incidents/' . $incident->id);
    $footer = 'Please review the incident as soon as possible.';
@endphp
@include('emails.partials.plain-text')