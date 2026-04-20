@php
    $title = 'Your Admin Account Created';
    $heading = 'Your admin account has been created';
    $statusLabel = 'Credentials';
    $statusTone = 'info';
    $message = 'An admin account has been created for you for the <strong>' . e($tenant->name) . '</strong> barangay tenant.';
    $bodyLines = [
        'Email: ' . e($user->email),
        'Password: ' . e($plainPassword),
        'Login URL: ' . e($loginUrl),
        'Tenant name: ' . e($tenant->name),
        'Barangay: ' . e($tenant->barangay ?: 'Not specified'),
        'Address: ' . e($tenant->address ?: 'Not specified'),
        'Contact Phone: ' . e($tenant->contact_phone ?: 'Not specified'),
    ];
    $ctaText = 'Log In Now';
    $ctaUrl = $loginUrl;
    $footer = 'Please change your password immediately after your first login.';
@endphp
@include('emails.partials.layout')