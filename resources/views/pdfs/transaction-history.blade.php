<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transaction History</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; }
        .user-info { margin-bottom: 20px; }
        .filters { margin-bottom: 20px; background: #f5f5f5; padding: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; }
    </style>
</head>
<body>
<div class="header">
    <h1>Transaction History Report</h1>
    <p>Generated on: {{ $generated_at }}</p>
</div>

<div class="user-info">
    <strong>Account Holder:</strong> {{ $user->name }}<br>
    <strong>Email:</strong> {{ $user->email }}
</div>

@if(!empty($filters))
    <div class="filters">
        <strong>Applied Filters:</strong><br>
        @foreach($filters as $key => $value)
            @if(!empty($value))
                {{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}<br>
            @endif
        @endforeach
    </div>
@endif

<table>
    <thead>
    <tr>
        <th>Date</th>
        <th>Service Type</th>
        <th>Type</th>
        <th>Amount</th>
        <th>Amount Before</th>
        <th>Amount After</th>
        <th>Status</th>
        <th>Reference</th>
    </tr>
    </thead>
    <tbody>
    @forelse($transactions as $transaction)
        <tr>
            <td>{{ $transaction->created_at->format('Y-m-d H:i:s') }}</td>
            <td>{{ $transaction->service_type }}</td>
            <td>{{ $transaction->type }}</td>
            <td>N{{ number_format($transaction->amount, 2) }}</td>
            <td>N{{ number_format($transaction->amount_before ?? 0, 2) }}</td>
            <td>{{ number_format($transaction->amount_after ?? 0, 2) }}</td>
            <td>{{ ucfirst($transaction->status) }}</td>
            <td>{{ $transaction->transaction_reference ?? 'N/A' }}</td>

        </tr>
    @empty
        <tr>
            <td colspan="7" style="text-align: center;">No transactions found</td>
        </tr>
    @endforelse
    </tbody>
</table>

<div class="footer">
    <p>This is an automatically generated report. Total transactions: {{ count($transactions) }}</p>
</div>
</body>
</html>
