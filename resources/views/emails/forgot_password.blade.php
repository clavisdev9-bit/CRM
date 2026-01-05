{{-- <!DOCTYPE html>
<html>
<body>

<p>Hello {{ $email }},</p>

<p>You requested to reset your password.</p>

<p>Click the link below to reset it:</p>

<p>
    <a href="http://localhost:5173/reset-password?token={{ $token }}&email={{ $email }}">
        Reset Password
    </a>
</p>

<p>If you did not request this, you may safely ignore this email.</p>

</body>
</html> --}}


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Your Password</title>

    <style>
        body {
            background: #f5f6fa;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .email-header {
            background: #4f46e5;
            padding: 25px;
            text-align: center;
            color: white;
        }

        .email-header h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 600;
        }

        .email-body {
            padding: 30px 40px;
            color: #333;
        }

        .email-body p {
            font-size: 16px;
            line-height: 1.6;
        }

        .btn-reset {
            display: inline-block;
            background: #4f46e5;
            padding: 14px 30px;
            border-radius: 6px;
            color: #ffffff !important;
            font-size: 16px;
            margin: 25px 0;
            text-decoration: none;
            font-weight: bold;
        }

        .btn-reset:hover {
            background: #4338ca;
        }

        .footer {
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #888;
        }

        .token-box {
            background: #f0f2ff;
            border-left: 4px solid #4f46e5;
            padding: 12px 16px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <div class="email-wrapper">

        <!-- HEADER -->
        <div class="email-header">
            <h1>Password Reset Request</h1>
        </div>

        <!-- BODY -->
        <div class="email-body">

            <p>Hello {{ $user->fullname }},</p>

            <p>
                We received a request to reset the password for your account.  
                If this request was made by you, please click the button below to create a new password.
            </p>

            <!-- RESET BUTTON -->
            <center>
                <a href="{{ $resetUrl }}" class="btn-reset">
                    Reset Your Password
                </a>
            </center>

            <p>
                Or you can manually use this token (valid for 30 minutes):
            </p>

            <div class="token-box">
                {{ $token }}
            </div>

            <p>
                If you didn’t request a password reset, you can safely ignore this email.  
                Your account will remain secure.
            </p>

        </div>

        <!-- FOOTER -->
        <div class="footer">
            © {{ date('Y') }} CRM APP — All rights reserved.
        </div>

    </div>

</body>

</html>
