@php
    $title = 'New Tenant Signup Request';
    $heading = 'New tenant signup request submitted';
    $statusLabel = 'Pending';
    $statusTone = 'warning';
    $message = 'A new tenant signup request is waiting for review.';
    $bodyLines = [
        'Tenant Name: ' . e($signupRequest->tenant_name),
        'Slug: ' . e($signupRequest->slug),
        'Subdomain: ' . e($signupRequest->subdomain ?: 'Not set'),
        'Custom Domain: ' . e($signupRequest->custom_domain ?: 'Not set'),
        'Requested Admin: ' . e($signupRequest->requested_admin_name) . ' (' . e($signupRequest->requested_admin_email) . ')',
        'Requested Role: Barangay Admin',
        'Requested Plan ID: ' . e($signupRequest->requested_plan_id ?: 'No preference'),
    ];
    $ctaText = 'Review Signup Request';
    $ctaUrl = url('/super/tenant-signup-requests');
    $footer = 'Approve or reject the request from central admin.';
@endphp
@include('emails.partials.layout')