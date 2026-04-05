@php
    $title = 'Tenant Signup Request Received';
    $heading = 'Your tenant signup request was received';
    $message = 'We received your request to create a tenant workspace for ' . $signupRequest->tenant_name . '. Your request is currently pending review by the city administrator.';
    $bodyLines = [
        'Tenant: ' . $signupRequest->tenant_name,
        'Slug: ' . $signupRequest->slug,
        'Requested admin: ' . $signupRequest->requested_admin_name . ' (' . $signupRequest->requested_admin_email . ')',
    ];
    $footer = 'This message was sent automatically after your signup request was submitted.';
@endphp
@include('emails.partials.plain-text')