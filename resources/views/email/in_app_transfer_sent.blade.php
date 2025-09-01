@extends('email.main')

@section('title', 'Transfer Notification')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="content">
                <!-- Header Message -->
                <h1 class="greeting">Transfer Successful</h1>

                <p class="message">
                    Hello {{ $user->first_name ?? 'there' }},<br><br>

                    Your transfer of ₦{{ number_format($amount, 2) }} to {{ $recipientName }} has been successfully completed.
                </p>
            </td>
        </tr>
    </table>
@endsection
