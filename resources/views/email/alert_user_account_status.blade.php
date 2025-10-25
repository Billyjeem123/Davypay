@extends('email.main')

@section('title', 'Account Status Update')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="content">
                <h1 class="greeting">
                    Account {{ ucfirst($status) }}
                </h1>

                <p class="message">
                    Hello {{ $user->first_name ?? 'there' }},<br><br>
                    Your account associated with <strong>{{ $user->email }}</strong> has been
                    <strong>{{ $status }}</strong> as of {{ now()->format('F j, Y, g:i a') }}.
                </p>

                @if($status === 'deactivated')
                    <p class="message">
                        This action was taken due to policy or compliance reasons.
                        Please contact support if you believe this was a mistake.
                    </p>
                @else
                    <p class="message">
                        Great news! Your account has been reactivated.
                        You can now log back in and continue using our services.
                    </p>
                @endif

                <p class="message">
                    Thank you for your understanding and cooperation.<br>
                    — The {{ config('app.name') }} Team
                </p>
            </td>
        </tr>
    </table>
@endsection
