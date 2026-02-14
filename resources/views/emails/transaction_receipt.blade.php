<html>
<body>
    <h1>Transaction Receipt</h1>
    <p>Hi {{ $user['first_name'] ?? ($user->first_name ?? 'Customer') }},</p>
    <p>Your transaction was processed.</p>
    <ul>
        <li>Order ID: {{ $transaction['orderId'] ?? $transaction['order_id'] ?? 'N/A' }}</li>
        <li>Amount: {{ $transaction['amount'] ?? 'N/A' }}</li>
        <li>Status: {{ $transaction['status'] ?? 'N/A' }}</li>
    </ul>
    <p>Thank you.</p>
</body>
</html>