@extends('email.main')

@section('title', '🚨 Fraud Detection Alert – Immediate Attention Required')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="content">

                <!-- Header Message -->
                <h1 class="greeting" style="color: #dc3545;">🚨 Immediate Action Required</h1>

                <p class="message">
                    Hello {{ $user->first_name  ?? 'there' }},<br><br>

                    A high-risk fraud activity has been detected involving user <strong>{{ $user->name }}</strong>.<br>
                    Our system has automatically taken the following protective action: <strong>{{ $actionText }}</strong>.
                </p>

                <p style="font-weight: bold; color: red;">
                    ⚠️ Please review the fraud details and take further action if necessary.
                </p>

                <h3 style="margin-top: 30px;">What You Should Do:</h3>
                <ul>
                    <li>Log in to your <strong>Admin Dashboard</strong> immediately.</li>
                    <li>Navigate to the <strong>Fraud Management</strong> section.</li>
                    <li>Review the case with Check ID: <strong>{{ $fraudCheckId }}</strong>.</li>
                </ul>

                <p style="margin-top: 30px; color: #333;">
                    ⚠️ <strong>This issue requires your urgent attention to ensure platform integrity and prevent misuse.</strong>
                </p>

            </td>
        </tr>
    </table>
@endsection
