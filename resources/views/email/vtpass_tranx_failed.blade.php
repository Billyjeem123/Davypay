{{-- Transaction Failed/Reversed Email Template --}}
@extends('email.main')

@section('title', 'Transaction Update')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="content">

                <!-- Header Message -->
                <h1 class="greeting">
                    Transaction Failed - Amount Reversed
                </h1>

                <p class="message">
                    Hello {{ $data->content->transactions->name ?? 'there' }},<br><br>

                        We regret to inform you that your {{ $data->content->transactions->product_name }} purchase could not be completed. However, your payment has been automatically reversed to your account
                </p>

                    <!-- Failure Message -->
                    <div style=" border-radius: 8px; padding: 20px; margin: 20px 0; border-left: 4px solid #555;">
                        <p style="margin: 0; color: #555; font-weight: 600;">
                            @if(str_contains(strtolower($data->content->transactions->product_name), 'airtime'))
                                 We couldn't process your airtime purchase for {{ $data->content->transactions->phone ?? $data->content->transactions->unique_element }}. Your money has been reversed.
                            @elseif(str_contains(strtolower($data->content->transactions->product_name), 'data'))
                                 We couldn't activate your data bundle on {{ $data->content->transactions->phone ?? $data->content->transactions->unique_element }}. Your money has been reversed.
                            @elseif(str_contains(strtolower($data->content->transactions->product_name), 'electric'))
                                 We couldn't process your electricity payment. Your money has been reversed.
                            @elseif(str_contains(strtolower($data->content->transactions->product_name), 'jamb'))
                                 We couldn't generate your JAMB PIN. Your money has been reversed.
                            @elseif(str_contains(strtolower($data->content->transactions->product_name), 'waec'))
                                 We couldn't generate your WAEC PIN{{ $data->content->transactions->quantity > 1 ? 's' : '' }}. Your money has been reversed.
                            @elseif(str_contains(strtolower($data->content->transactions->product_name), 'neco'))
                                 We couldn't generate your NECO PIN{{ $data->content->transactions->quantity > 1 ? 's' : '' }}. Your money has been reversed.
                            @else
                                 We couldn't complete your {{ strtolower($data->content->transactions->product_name) }} purchase. Your money has been reversed.
                            @endif
                        </p>
                    </div>


            </td>
        </tr>
    </table>
@endsection
