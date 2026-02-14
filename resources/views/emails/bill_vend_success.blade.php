<html>
<body>
    <h1>Bill Payment Successful</h1>
    <p>Hi {{ $user['first_name'] ?? ($user->first_name ?? 'Customer') }},</p>
    <p>Your bill payment was successful.</p>
    <ul>
        <li>Order ID: {{ $vend['orderId'] ?? $vend['order_id'] ?? 'N/A' }}</li>
        <li>Amount: {{ $vend['amount'] ?? 'N/A' }}</li>
        <li>Product: {{ $vend['product_name'] ?? 'N/A' }}</li>
    </ul>
    <p>Thank you.</p>
</body>
</html>