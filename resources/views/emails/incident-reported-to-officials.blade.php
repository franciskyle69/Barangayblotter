@php
    $title = 'New Incident Reported';
    $heading = 'New incident report received';
    $statusLabel = ucfirst($incident->status);
    $statusTone = 'info';
    $message = 'A ' . e($reporterRole === 'resident' ? 'resident' : 'citizen') . ' submitted a new incident report in <strong>' . e($tenant->name) . '</strong>.';
    $bodyLines = [
        'Type: ' . e($incident->incident_type),
        'Date: ' . e(optional($incident->incident_date)->format('M d, Y h:i A')),
        'Location: ' . e($incident->location ?: 'Not specified'),
        'Status: ' . e(ucfirst(str_replace('_', ' ', $incident->status))),
        'Complainant: ' . e($incident->complainant_name),
        'Respondent: ' . e($incident->respondent_name),
        'Reported by account: ' . e($reporter->name . ' (' . $reporter->email . ')'),
    ];
    $secondaryText = '<strong>Description:</strong> ' . e($incident->description);
    $ctaText = 'View Incident';
    $ctaUrl = url('/incidents/' . $incident->id);
    $footer = 'Please review the incident as soon as possible.';
@endphp
@include('emails.partials.layout')