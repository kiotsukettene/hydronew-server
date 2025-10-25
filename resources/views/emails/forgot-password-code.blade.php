<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Your Email</title>
</head>
<body style="margin: 0; padding: 5px; font-family: 'Arial', sans-serif; background-color: #f4f4f4;">
    <div style="max-width: 500px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; padding: 40px 50px; text-align: center; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
        
        <div style="margin-bottom: 30px;">
            <img src="{{ asset('logo.svg') }}" alt="HydroNew Logo" style="width: 120px; height: auto;">
        </div>

        <div style="margin-bottom: 2px;">
            <img src="{{ asset('email-svg2.svg') }}" alt="Mail Icon" style="width: 100px; height: auto;">
        </div>
 
        <h2 style="color: #2E2E2E; font-size: 26px; margin-bottom: 10px;">Password Reset Code</h2>
        
        <p style="color: #555; font-size: 16px; margin-bottom: 25px;">
            Hello! We received a request to reset the password for your HydroNew account. Your code to reset your password is:
        </p>

        <div style="font-size: 42px; font-weight: bold; letter-spacing: 6px; color: #445104; margin: 20px 0 10px 0;">
            {{ $code }}
        </div>
        <div style="display: flex; justify-content: center;">
            <p style="width: 90%; color: #555; font-size: 15px; line-height: 1.6; margin: 10px 0; text-align: justify;">
                For your security, this code will expire in <strong>15 minutes</strong>.
                If you didn’t request a password reset, please ignore this email and your account will remain secure.
            </p>
        </div>

        <p style="color: #2E2E2E; font-size: 16px; margin-top: 40px; text-align: left;">
            Best regards,<br>
            <strong>HydroNew Team</strong>
        </p>

        <p style="font-size: 12px; color: #999; margin-top: 30px;">
            © {{ date('Y') }} HydroNew. All rights reserved.
        </p>
    </div>
</body>
</html>
