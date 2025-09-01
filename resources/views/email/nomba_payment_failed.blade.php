@extends('email.main')

@section('title', 'Transaction History Report')

@section('content')
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="content">

                <!-- Header -->
                <h1 class="greeting">Your Transaction History Report</h1>

                <p class="message">
                    Dear {{ $user->name }},
                </p>

                <p class="message">
                    Please find attached your transaction history report generated on {{ $generated_at }}.
                </p>

                <p class="message">
                    <strong>Report Details:</strong><br>
                    Filters Applied: {{ $filter_summary }}
                </p>

                <p class="message">
                    If you have any questions about your transactions, please don't hesitate to contact our support team.
                </p>



            </td>
        </tr>
    </table>
@endsection
