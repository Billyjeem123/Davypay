@extends('email.main')

@section('title', 'Betting Payment Successful')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="content">
                <!-- Header Message -->
                <h1 class="greeting">Payment Successful</h1>

                <p class="message">
                    Hi  {{ $senderName }},<br><br>

                    Your betting payment of ₦{{ number_format($amount, 2) }} has been successfully processed.<br><br>

                </p>
            </td>
        </tr>
    </table>
@endsection
