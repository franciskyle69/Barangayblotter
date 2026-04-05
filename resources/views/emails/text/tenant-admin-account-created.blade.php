@php
    $title = 'Your Admin Account Created';
    $heading = 'Your admin account has been created';
    $statusLabel = 'Credentials';
    $statusTone = 'info';
    $message = 'An admin account has been created for you for the ' . $tenant->name . ' barangay tenant.';
    $bodyLines = [
        'Email: ' . $user->email,
        'Password: ' . $plainPassword,
        'Login URL: ' . $loginUrl,
        'Tenant name: ' . $tenant->name,
        'Barangay: ' . ($tenant->barangay ?: 'Not specified'),
        'Address: ' . ($tenant->address ?: 'Not specified'),
        'Contact Phone: ' . ($tenant->contact_phone ?: 'Not specified'),
    ];
    $ctaText = 'Log In Now';
    $ctaUrl = $loginUrl;
    $footer = 'Please change your password immediately after your first login.';
@endphp
@include('emails.partials.plain-text')