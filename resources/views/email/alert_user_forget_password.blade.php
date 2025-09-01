@extends('email.main')

@section('title', 'Password Reset')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="content">
                <!-- Header Message -->
                <h1 class="greeting">Password Reset Successful</h1>

                <p class="message">
                    Hello {{ $user->first_name ?? 'there' }},<br><br>
                    Your password for the account <strong>{{ $user->email }}</strong> has been reset.
                </p>

                <!-- Credentials -->
                <div style="border-radius: 8px; padding: 15px; margin: 20px 0; border: 1px solid #ddd; background: #f9f9f9;">
                    <p style="margin: 0; font-size: 14px;">
                        <strong>Email:</strong> {{ $user->email }} <br>
                        <strong>Temporary Password:</strong> {{ $token }}
                    </p>
                </div>

                <p class="message">
                    Please log in using the details above and change your password immediately for security.
                </p>
            </td>
        </tr>
    </table>
@endsection
