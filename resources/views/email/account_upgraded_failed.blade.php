@extends('email.main')

@section('title', 'Account Upgrade Failed')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="content">

                <!-- Header Message -->
                <h1 class="greeting">⚠️ Account Upgrade Unsuccessful</h1>

                <p class="message">
                    Hello {{ $user->first_name ?? 'there' }},<br><br>
                    We attempted to upgrade your account to
                    <strong>{{ ucfirst($tier) }}</strong>, but unfortunately, the process was not successful.
                </p>

                <p class="message">
                    <strong>Reason:</strong> {{ $reason ?? 'An unexpected error occurred' }}.
                </p>

                <p class="message">
                    Please review your details and try again.
                    If you believe this is an error, kindly contact our support team for assistance.
                </p>

            </td>
        </tr>
    </table>
@endsection
