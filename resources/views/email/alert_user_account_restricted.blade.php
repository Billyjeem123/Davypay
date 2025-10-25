@extends('email.main')

@section('title', 'Account Restriction Update')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="content">
                <h1 class="greeting">
                    Account {{ $status === 'restricted' ? 'Restricted' : 'Restriction Lifted' }}
                </h1>

                <p class="message">
                    Hello {{ $user->first_name ?? 'there' }},<br><br>
                    Your account associated with <strong>{{ $user->email }}</strong> has been
                    <strong>{{ $status }}</strong> as of {{ now()->format('F j, Y, g:i a') }}.
                </p>

                @if($status === 'restricted')
                    <p class="message">
                        This restriction limits certain actions on your account for security or compliance reasons.
                        Please contact our support team if you believe this was an error or to request a review.
                    </p>
                @else
                    <p class="message">
                        Great news! The restriction on your account has been lifted.
                        You can now access all features of your account as usual.
                    </p>
                @endif

                <p class="message">
                    Thank you for your patience and understanding.<br>
                    — The {{ config('app.name') }} Team
                </p>
            </td>
        </tr>
    </table>
@endsection
