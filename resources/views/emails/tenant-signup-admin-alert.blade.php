<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>New Tenant Signup Request</title>
</head>

<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2 style="margin-bottom: 12px;">New tenant signup request submitted</h2>

    <p>A new tenant signup request is pending review.</p>

    <p><strong>Request details:</strong></p>
    <ul>
        <li>Tenant Name: {{ $signupRequest->tenant_name }}</li>
        <li>Slug: {{ $signupRequest->slug }}</li>
        <li>Subdomain: {{ $signupRequest->subdomain ?: 'Not set' }}</li>
        <li>Custom Domain: {{ $signupRequest->custom_domain ?: 'Not set' }}</li>
        <li>Requested Admin: {{ $signupRequest->requested_admin_name }} ({{ $signupRequest->requested_admin_email }})
        </li>
        <li>Requested Role: {{ $signupRequest->requested_admin_role ?: 'purok_secretary' }}</li>
        <li>Requested Plan ID: {{ $signupRequest->requested_plan_id ?: 'No preference' }}</li>
    </ul>

    <p>
        Review this request in central admin:
        <a href="{{ url('/super/tenant-signup-requests') }}">{{ url('/super/tenant-signup-requests') }}</a>
    </p>

    <p style="margin-top: 20px;">Regards,<br>{{ config('app.name') }}</p>
</body>

</html>