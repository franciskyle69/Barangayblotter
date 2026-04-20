@php
    $title = 'New Tenant Signup Request';
    $heading = 'New tenant signup request submitted';
    $statusLabel = 'Pending';
    $statusTone = 'warning';
    $message = 'A new tenant signup request is waiting for review.';
    $bodyLines = [
        'Tenant Name: ' . $signupRequest->tenant_name,
        'Slug: ' . $signupRequest->slug,
        'Subdomain: ' . ($signupRequest->subdomain ?: 'Not set'),
        'Custom Domain: ' . ($signupRequest->custom_domain ?: 'Not set'),
        'Requested Admin: ' . $signupRequest->requested_admin_name . ' (' . $signupRequest->requested_admin_email . ')',
        'Requested Role: Barangay Admin',
        'Requested Plan ID: ' . ($signupRequest->requested_plan_id ?: 'No preference'),
    ];
    $ctaText = 'Review Signup Request';
    $ctaUrl = url('/super/tenant-signup-requests');
    $footer = 'Approve or reject the request from central admin.';
@endphp
@include('emails.partials.plain-text')