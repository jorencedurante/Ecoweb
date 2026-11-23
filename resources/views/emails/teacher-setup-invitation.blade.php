<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Inter', Arial, sans-serif; line-height: 1.6; color: #1e293b; margin: 0; padding: 0; }
        .container { max-width: 520px; margin: 0 auto; padding: 28px; }
        .header { text-align: center; padding-bottom: 18px; border-bottom: 3px solid #0f766e; margin-bottom: 22px; }
        .header h2 { color: #0f766e; margin: 0; font-size: 22px; }
        .subtitle { color: #64748b; font-size: 13px; }
        .button { display: inline-block; padding: 12px 22px; background: #16a34a; color: #ffffff !important; text-decoration: none; border-radius: 10px; font-weight: 700; margin: 18px 0; }
        .link { font-size: 12px; color: #64748b; overflow-wrap: anywhere; }
        .note { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 14px; font-size: 13px; color: #166534; }
        .footer { font-size: 12px; color: #94a3b8; text-align: center; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Welcome to EcoCollect</h2>
            <div class="subtitle">EcoCollect Environmental Achievement Tracking System</div>
        </div>

        <p>Hi {{ $teacherName }},</p>
        <p>An EcoCollect teacher account has been created for you. Use the secure link below to verify your email and create your own password.</p>

        <div class="note">This invitation link is single-use and expires in {{ $expiresInHours }} hours.</div>

        <p style="text-align:center;">
            <a class="button" href="{{ $setupUrl }}">Set Up My Account</a>
        </p>
        <p class="link">If the button does not work, copy this link into your browser:<br>{{ $setupUrl }}</p>

        <p>If you were not expecting this invitation, no further action is required.</p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} EcoCollect. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
