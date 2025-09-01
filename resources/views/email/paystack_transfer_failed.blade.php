@extends('email.main')

@section('title', 'Transfer Failed')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="content">

                <!-- Header Message -->
                <h1 class="greeting">Transfer Failed</h1>

                <p class="message">
                    Hello {{ $transaction->user->first_name ?? 'there' }},<br><br>

                    We regret to inform you that your transfer of ₦{{ number_format($data['data']['amount'] / 100, 2) }} to {{ $data['data']['recipient']['details']['account_name'] ?? "_" }} could not be completed.<br><br>

                    The full amount of ₦{{ number_format($data['data']['amount'] / 100, 2) }} has been refunded to your wallet.
                </p>

                <!-- Additional Info -->
                <div style=" border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid #555;">
                    <p style="margin: 0; color: #6c757d; font-size: 14px; text-align: center;">
                        Need help? Contact our support team or check your transaction history in your account dashboard.
                    </p>
                </div>

            </td>
        </tr>
    </table>
@endsection
