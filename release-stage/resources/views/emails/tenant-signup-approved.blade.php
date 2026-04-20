@php
    $title = 'Tenant Signup Approved';
    $heading = 'Tenant signup approved';
    $statusLabel = 'Approved';
    $statusTone = 'success';
    $message = 'Great news. Your barangay workspace for <strong>' . e($signupRequest->tenant_name) . '</strong> is ready.';
    $bodyLines = [
        'Tenant name: ' . e($tenant->name),
        'Slug: ' . e($tenant->slug),
        'Subdomain: ' . e($tenant->subdomain ?: 'Not set'),
        'Custom domain: ' . e($tenant->custom_domain ?: 'Not set'),
        'Assigned admin: ' . e($signupRequest->requested_admin_email),
    ];
    $ctaText = 'Go to Login';
    $ctaUrl = rtrim($tenant->getUrl(), '/') . '/login';
    $footer = 'Your requested admin account is now assigned to this tenant workspace.';
@endphp
@include('emails.partials.layout')