@php
    $title = 'Tenant Signup Approved';
    $heading = 'Tenant signup approved';
    $statusLabel = 'Approved';
    $statusTone = 'success';
    $message = 'Great news. Your barangay workspace for ' . $signupRequest->tenant_name . ' is ready.';
    $bodyLines = [
        'Tenant name: ' . $tenant->name,
        'Slug: ' . $tenant->slug,
        'Subdomain: ' . ($tenant->subdomain ?: 'Not set'),
        'Custom domain: ' . ($tenant->custom_domain ?: 'Not set'),
        'Assigned admin: ' . $signupRequest->requested_admin_email,
    ];
    $ctaText = 'Go to Login';
    $ctaUrl = rtrim($tenant->getUrl(), '/') . '/login';
    $footer = 'Your requested admin account is now assigned to this tenant workspace.';
@endphp
@include('emails.partials.plain-text')