@extends('email.main')

@section('title', $transactionType === 'credit' ? 'Wallet Funded Successfully' : 'Wallet Debited by Admin')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="content">
                <h1 class="greeting">
                    {{ $transactionType === 'credit' ? 'Wallet Funded By Admin' : 'Wallet Debited by Admin' }}
                </h1>

                <p class="message">
                    Hello {{ $user->first_name ?? 'there' }},<br><br>
                    Your wallet has been
                    <strong>{{ $transactionType === 'credit' ? 'credited' : 'debited' }}</strong>
                    by an administrator.
                </p>

                <div style="border-radius: 8px; padding: 15px; margin: 20px 0; border: 1px solid #ddd; background: #f9f9f9;">
                    <p style="margin: 0; font-size: 14px;">
                        <strong>Amount:</strong> ₦{{ number_format($amount, 2) }} <br>
                        <strong>Current Wallet Balance:</strong> ₦{{ number_format($walletBalance, 2) }}
                    </p>
                </div>


                <!-- Admin Note / Description -->
                @if(!empty($description))
                    <div style="border-radius: 8px; padding: 15px; margin: 20px 0; border: 1px solid #ddd; background: #f9f9f9;">
                        <p style="margin: 0; font-size: 14px; color: #8a6d3b;">
                            <strong>Note:</strong><br>
                            {{ $description }}
                        </p>
                    </div>
                @endif



                <p class="message" style="margin-top: 20px;">
                    Best regards,<br>
                    <strong>The Admin Team</strong>
                </p>
            </td>
        </tr>
    </table>
@endsection
