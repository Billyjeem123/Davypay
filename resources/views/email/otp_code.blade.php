@extends('email.main')

@section('title', 'Email Verification Code')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="content">

                <!-- Header Message -->
                <h1 class="greeting">Verify Your Email Address</h1>

                <p class="message">
                    Hello {{ $user->name ?? 'there' }},<br><br>

                    Welcome to <strong>{{ config('app.name') }}</strong>! To complete your account setup and ensure the security of your financial data, please verify your email address using the code below.
                </p>

                <!-- Verification Code Box -->
                <div class="highlight-box">
                    <div class="highlight-title">🔐 Your Verification Code</div>
                    <div class="highlight-text">
                        <div style="text-align: center; font-family: 'Courier New', monospace; font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 6px; padding: 20px; background: #ffffff; border: 2px dashed #667eea; border-radius: 8px; margin: 15px 0;">
                            {{ $otp ?? '123456' }}
                        </div>
                        <p style="text-align: center; color: #e53e3e; font-weight: 600; margin: 10px 0 5px 0;">This code expires in <strong>10 minutes</strong></p>
                        <p style="text-align: center; font-size: 12px; color: #718096; margin: 0; font-style: italic;">For your security, never share this code with anyone</p>
                    </div>
                </div>


                <!-- Closing -->
                <p class="message">
                    Thank you for choosing {{ config('app.name') }} for your financial needs.<br><br>

                    Best regards,<br>
                    <strong>The {{ config('app.name') }} Security Team</strong>
                </p>

            </td>
        </tr>
    </table>
@endsection
