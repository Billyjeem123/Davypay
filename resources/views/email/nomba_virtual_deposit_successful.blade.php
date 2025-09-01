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

                    Great news! {{ $data['data']['customer']['senderName'] ?? 'Someone' }} just sent you
                    ₦{{ number_format($data['data']['transaction']['transactionAmount'] ?? 0, 2) }}.
                    The funds are now available in your wallet.
                </p>

            </td>
        </tr>
    </table>
@endsection
