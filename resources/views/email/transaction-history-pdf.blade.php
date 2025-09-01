<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
<h2>Your Transaction History Report</h2>

<p>Dear {{ $user->name }},</p>

<p>Please find attached your transaction history report generated on {{ $generated_at }}.</p>

<p><strong>Report Details:</strong><br>
    Filters Applied: {{ $filter_summary }}</p>

<p>If you have any questions about your transactions, please don't hesitate to contact our support team.</p>

<p>Best regards,<br>
    Your Finance Team</p>
</body>
</html>
