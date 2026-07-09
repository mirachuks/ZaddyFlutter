<html>

<head>
    <meta charset="utf-8" />
</head>

<body>
    <h2>Payment Approved</h2>
    <p>Your manual payment has been reviewed and approved.</p>

    <ul>
        <li><strong>Transaction ID:</strong> {{ $transaction->id }}</li>
        <li><strong>Amount:</strong> ₦{{ number_format($transaction->balance, 2) }}</li>
        <li><strong>Status:</strong> {{ ucfirst($transaction->status) }}</li>
    </ul>

    @if ($transaction->job_id)
    <p>This payment was applied to job #{{ $transaction->job_id }}.</p>
    @else
    <p>The credited amount has been added to your wallet balance.</p>
    @endif

    <p>Thank you for using Zaddy Express.</p>
</body>

</html>