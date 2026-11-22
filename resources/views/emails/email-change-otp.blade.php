<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Inter', Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 480px; margin: 0 auto; padding: 24px; }
        .header { text-align: center; padding: 20px 0; }
        .code { font-size: 32px; font-weight: 800; letter-spacing: 8px; text-align: center; padding: 20px; background: #f0fdf4; border-radius: 8px; color: #16a34a; margin: 20px 0; }
        .footer { font-size: 12px; color: #999; text-align: center; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Email Change Verification</h2>
        </div>
        <p>Hi {{ $userName }},</p>
        <p>A request was made to change the email address on this account. Use this verification code sent to the current registered email address to authorize the change:</p>
        <div class="code">{{ $otp }}</div>
        <p>This code will expire in 10 minutes.</p>
        <p>If you did not request or authorize this email change, do not share this code and contact an administrator.</p>
        <div class="footer">
            <p>&copy; {{ date('Y') }} EcoCollect. All rights reserved.</p>
        </div>
    </div>
</body>
</html>