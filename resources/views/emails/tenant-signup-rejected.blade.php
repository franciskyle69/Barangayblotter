<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Tenant Signup Update</title>
</head>

<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2 style="margin-bottom: 12px;">Tenant signup request update</h2>

    <p>Hi {{ $signupRequest->requested_admin_name }},</p>

    <p>
        Your request for <strong>{{ $signupRequest->tenant_name }}</strong> was not approved at this time.
    </p>

    @if(!empty($signupRequest->review_notes))
        <p><strong>Reviewer note:</strong></p>
        <p>{{ $signupRequest->review_notes }}</p>
    @endif

    <p>
        You may submit a new request after updating your details.
    </p>

    <p style="margin-top: 20px;">Regards,<br>{{ config('app.name') }}</p>
</body>

</html>