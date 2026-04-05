@php
    $title = 'Tenant Signup Request Received';
    $heading = 'Your tenant signup request was received';
    $message = 'We received your request to create a tenant workspace for <strong>' . e($signupRequest->tenant_name) . '</strong>. Your request is currently <strong>pending review</strong> by the city administrator.';
    $bodyLines = [
        'Tenant: ' . e($signupRequest->tenant_name),
        'Slug: ' . e($signupRequest->slug),
        'Requested admin: ' . e($signupRequest->requested_admin_name) . ' (' . e($signupRequest->requested_admin_email) . ')',
    ];
    $footer = 'This message was sent automatically after your signup request was submitted.';
@endphp
@include('emails.partials.layout')