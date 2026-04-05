@php
    $title = 'Tenant Signup Approved';
    $heading = 'Tenant signup approved';
    $statusLabel = 'Approved';
    $statusTone = 'success';
    $message = 'Great news. Your request for ' . $signupRequest->tenant_name . ' has been approved.';
    $bodyLines = [
        'Tenant name: ' . $tenant->name,
        'Slug: ' . $tenant->slug,
        'Subdomain: ' . ($tenant->subdomain ?: 'Not set'),
        'Custom domain: ' . ($tenant->custom_domain ?: 'Not set'),
        'Assigned admin: ' . $signupRequest->requested_admin_email,
    ];
    $ctaText = 'Go to Login';
    $ctaUrl = url('/login');
    $footer = 'Your requested admin account is now assigned to this tenant.';
@endphp
@include('emails.partials.plain-text')