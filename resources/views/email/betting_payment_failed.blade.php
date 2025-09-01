@extends('email.main')

@section('title', 'Betting Payment Failed')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="content">
                <!-- Header Message -->
                <h1 class="greeting">Payment Failed</h1>

                <p class="message">
                    Hi {{ $senderName }},<br><br>
                    Unfortunately, your betting payment of ₦{{ number_format($amount, 2) }}could not be processed.<br><br> . The amount has been reversed and credited back to your wallet.<br><br>
                    Please try again later or contact support if the issue persists.
                </p>
            </td>
        </tr>
    </table>
@endsection
