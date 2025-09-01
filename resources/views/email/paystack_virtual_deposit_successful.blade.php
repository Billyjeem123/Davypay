@extends('email.main')

@section('title', 'Transaction Notification')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="content">

                <!-- Header Message -->
                <h1 class="greeting">Transaction Notification</h1>

                <p class="message">
                    Hello {{ $transaction->user->first_name ?? 'there' }},<br><br>

                    Great news!  {{ $sender_name }} Just sent you ₦{{ number_format($transaction->amount, 2) }}. The funds is available in your account.
                </p>

            </td>
        </tr>
    </table>
@endsection
