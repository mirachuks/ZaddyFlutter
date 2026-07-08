<html>

<head>
    <meta charset="utf-8" />
</head>

<body>
    <h2>Manual Payment Notification</h2>
    <p>A customer has indicated they made a manual bank transfer.</p>

    <ul>
        <li><strong>Transaction ID:</strong> {{ $transaction->id }}</li>
        <li><strong>User ID:</strong> {{ $transaction->user_id }}</li>
        <li><strong>Amount:</strong> ₦{{ number_format($transaction->balance, 2) }}</li>
        <li><strong>Payment Method:</strong> {{ $transaction->payment_method ?? 'bank_transfer' }}</li>
    </ul>

    @if ($transaction->payment_proof_path)
    <p><strong>Payment proof uploaded:</strong></p>
    <p><a href="{{ Storage::disk('public')->url($transaction->payment_proof_path) }}" target="_blank">View uploaded proof</a></p>
    @else
    <p>No payment proof image was uploaded.</p>
    @endif

    <p>Please review the transaction in the admin panel and approve or reject.</p>
</body>

</html>