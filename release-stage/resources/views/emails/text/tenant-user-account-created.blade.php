@php
    $title = 'Your Tenant Account Created';
    $heading = 'Your tenant account has been created';
    $statusLabel = 'Assigned';
    $statusTone = 'success';
    $message = 'An account has been created for you for the ' . $tenant->name . ' tenant.';
    $bodyLines = [
        'Assigned Role: ' . $roleLabel,
        'Email: ' . $user->email,
        'Password: ' . $plainPassword,
        'Login URL: ' . $loginUrl,
    ];
    $ctaText = 'Log In Now';
    $ctaUrl = $loginUrl;
    $footer = 'Please change your password immediately after your first login.';
@endphp
@include('emails.partials.plain-text')