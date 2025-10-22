@extends('email.main')

@section('title', 'Referral Reward Credited')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="content">

                <!-- Header Message -->
                <h1 class="greeting"> Referral Reward Alert</h1>

                <p class="message">
                    Hello {{ $notifiable->first_name ?? 'there' }},<br><br>

                    Great news! You've just been rewarded <strong>₦{{ number_format($amount, 2) }}</strong>
                    for referring a new user who completed their first transaction.
                </p>

                <p class="message">
                    The amount has been credited to your wallet and is now available for use.
                </p>

                <p class="message">
                    Thank you for helping our community grow. Keep referring and keep earning!
                </p>


            </td>
        </tr>
    </table>
@endsection
