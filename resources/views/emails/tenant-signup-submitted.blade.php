<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Tenant Signup Request Received</title>
</head>

<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2 style="margin-bottom: 12px;">Your tenant signup request was received</h2>

    <p>Hi {{ $signupRequest->requested_admin_name }},</p>

    <p>
        We received your request to create a tenant workspace for <strong>{{ $signupRequest->tenant_name }}</strong>.
        Your request is currently <strong>pending review</strong> by the city administrator.
    </p>

    <p><strong>Request details:</strong></p>
    <ul>
        <li>Tenant: {{ $signupRequest->tenant_name }}</li>
        <li>Slug: {{ $signupRequest->slug }}</li>
        <li>Requested admin: {{ $signupRequest->requested_admin_name }} ({{ $signupRequest->requested_admin_email }})
        </li>
    </ul>

    <p>
        We will notify you once your request is approved or rejected.
    </p>

    <p style="margin-top: 20px;">Regards,<br>{{ config('app.name') }}</p>
</body>

</html>