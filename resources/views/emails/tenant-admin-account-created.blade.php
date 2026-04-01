<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Your Admin Account Created</title>
</head>

<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2 style="margin-bottom: 12px;">Your Admin Account Has Been Created</h2>

    <p>Hi {{ $user->name }},</p>

    <p>
        An admin account has been created for you for the <strong>{{ $tenant->name }}</strong> barangay tenant.
    </p>

    <p><strong>Login Credentials:</strong></p>
    <ul style="background-color: #f3f4f6; padding: 12px 20px; border-radius: 4px; border-left: 4px solid #3b82f6;">
        <li><strong>Email:</strong> {{ $user->email }}</li>
        <li><strong>Password:</strong> <code style="background-color: #e5e7eb; padding: 2px 6px; border-radius: 3px;">{{ $plainPassword }}</code></li>
        <li><strong>Login URL:</strong> <a href="{{ $loginUrl }}">{{ $loginUrl }}</a></li>
    </ul>

    <p style="margin-top: 16px; color: #dc2626;">
        <strong>⚠️ Important:</strong> Please change your password immediately after your first login for security purposes.
    </p>

    <p><strong>Tenant Information:</strong></p>
    <ul>
        <li>Tenant Name: {{ $tenant->name }}</li>
        <li>Barangay: {{ $tenant->barangay ?: 'Not specified' }}</li>
        <li>Address: {{ $tenant->address ?: 'Not specified' }}</li>
        <li>Contact Phone: {{ $tenant->contact_phone ?: 'Not specified' }}</li>
    </ul>

    <p style="margin-top: 20px;">
        If you did not expect this email or have any questions, please contact the system administrator.
    </p>

    <p style="margin-top: 20px;">Regards,<br>{{ config('app.name') }} System</p>
</body>

</html>
