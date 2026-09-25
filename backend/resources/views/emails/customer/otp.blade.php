<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Verification Code</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .header {
            text-align: center;
            padding: 30px 20px;
            background-color: #ffffff;
            border-bottom: 1px solid #e5e7eb;
        }

        .logo {
            max-height: 60px;
            margin-bottom: 15px;
        }

        .site-name {
            font-size: 24px;
            font-weight: bold;
            color: #111827;
            margin: 0;
        }

        .content {
            padding: 40px 30px;
            text-align: center;
            color: #374151;
        }

        .greeting {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #111827;
        }

        .message {
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 30px;
            color: #4b5563;
        }

        .otp-box {
            background-color: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 20px;
            margin: 30px auto;
            max-width: 300px;
        }

        .otp-code {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #2563eb;
            margin: 0;
        }

        .footer {
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #9ca3af;
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }

        .warning {
            font-size: 14px;
            color: #6b7280;
            margin-top: 30px;
        }
    </style>
</head>

<body>
    @php
    $siteName = \App\Models\SiteSetting::get('site_name', 'Unknown Site');
    @endphp

    <div class="container">
        <div class="header">
            <h1 class="site-name">{{ $siteName }}</h1>
        </div>

        <div class="content">
            <div class="greeting">Hello {{ $name }},</div>

            <div class="message">
                You requested a verification code for <strong>{{ $purposeText }}</strong>.<br>
                Please use the following code to continue:
            </div>

            <div class="otp-box">
                <p class="otp-code">{{ $otp }}</p>
            </div>

            <div class="message">
                This code is valid for the next <strong>5 minutes</strong>.
            </div>

            <div class="warning">
                If you did not request this code, please ignore this email or contact support if you have concerns.
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.
        </div>
    </div>
</body>

</html>