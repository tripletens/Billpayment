<html>
<body>
    <h1>Meter Check Result</h1>
    <p>Hi {{ $user['first_name'] ?? ($user->first_name ?? 'Customer') }},</p>
    <p>Meter check completed for meter: {{ $meter['meter'] ?? $meter['meterNo'] ?? 'N/A' }}.</p>
    <ul>
        <li>Disco: {{ $meter['disco'] ?? 'N/A' }}</li>
        <li>Customer Name: {{ $meter['customer_name'] ?? 'N/A' }}</li>
    </ul>
    <p>Thank you.</p>
</body>
</html>