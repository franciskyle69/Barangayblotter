@php
    $title = 'Your Tenant Account Created';
    $heading = 'Your tenant account has been created';
    $statusLabel = 'Assigned';
    $statusTone = 'success';
    $message = 'An account has been created for you for the <strong>' . e($tenant->name) . '</strong> tenant.';
    $bodyLines = [
        'Assigned Role: ' . e($roleLabel),
        'Email: ' . e($user->email),
        'Password: ' . e($plainPassword),
        'Login URL: ' . e($loginUrl),
    ];
    $ctaText = 'Log In Now';
    $ctaUrl = $loginUrl;
    $footer = 'Please change your password immediately after your first login.';
@endphp
@include('emails.partials.layout')