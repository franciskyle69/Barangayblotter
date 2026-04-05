@php
    $title = 'Tenant Signup Update';
    $heading = 'Tenant signup request update';
    $statusLabel = 'Rejected';
    $statusTone = 'danger';
    $message = 'Your request for ' . $signupRequest->tenant_name . ' was not approved at this time.';
    $secondaryText = !empty($signupRequest->review_notes) ? 'Reviewer note: ' . $signupRequest->review_notes : null;
    $footer = 'You may submit a new request after updating your details.';
@endphp
@include('emails.partials.plain-text')