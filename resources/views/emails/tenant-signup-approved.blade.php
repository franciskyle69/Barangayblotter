<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Tenant Signup Approved</title>
</head>

<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2 style="margin-bottom: 12px;">Tenant signup approved</h2>

    <p>Hi {{ $signupRequest->requested_admin_name }},</p>

    <p>
        Great news. Your request for <strong>{{ $signupRequest->tenant_name }}</strong> has been approved.
    </p>

    <p><strong>Tenant details:</strong></p>
    <ul>
        <li>Tenant name: {{ $tenant->name }}</li>
        <li>Slug: {{ $tenant->slug }}</li>
        <li>Subdomain: {{ $tenant->subdomain ?: 'Not set' }}</li>
        <li>Custom domain: {{ $tenant->custom_domain ?: 'Not set' }}</li>
    </ul>

    <p>
        The requested admin account <strong>{{ $signupRequest->requested_admin_email }}</strong> is now assigned to this
        tenant.
    </p>

    <p>
        Login URL: <a href="{{ url('/login') }}">{{ url('/login') }}</a>
    </p>

    <p style="margin-top: 20px;">Regards,<br>{{ config('app.name') }}</p>
</body>

</html>