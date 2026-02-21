<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Receipt</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            color: #1a1a2e;
            background: #ffffff;
            font-size: 13px;
        }

        .page {
            padding: 40px;
            max-width: 700px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #ffffff;
            padding: 30px 35px;
            border-radius: 12px 12px 0 0;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .brand {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .brand span {
            color: #e94560;
        }

        .receipt-label {
            text-align: right;
        }

        .receipt-label .title {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.7;
        }

        .receipt-label .ref {
            font-size: 12px;
            font-family: monospace;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 10px;
            border-radius: 20px;
            margin-top: 6px;
            display: inline-block;
        }

        /* Status Banner */
        .status-banner {
            background: #0f3460;
            padding: 16px 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #22c55e;
            display: inline-block;
        }

        .status-text {
            color: #22c55e;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-date {
            color: rgba(255, 255, 255, 0.6);
            font-size: 12px;
        }

        /* Body */
        .body {
            background: #f8fafc;
            padding: 35px;
        }

        /* Amount Card */
        .amount-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px 30px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .amount-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        .amount-value {
            font-size: 36px;
            font-weight: 700;
            color: #1a1a2e;
        }

        .amount-currency {
            font-size: 20px;
            color: #64748b;
        }

        /* Details Grid */
        .details-section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #94a3b8;
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-key {
            color: #64748b;
        }

        .detail-value {
            font-weight: 600;
            color: #1e293b;
            text-align: right;
        }

        .detail-value.monospace {
            font-family: monospace;
            font-size: 12px;
        }

        /* Footer */
        .footer {
            background: #1a1a2e;
            color: rgba(255, 255, 255, 0.5);
            padding: 20px 35px;
            border-radius: 0 0 12px 12px;
            text-align: center;
            font-size: 11px;
            line-height: 1.6;
        }

        .footer a {
            color: #e94560;
            text-decoration: none;
        }

        /* Type badge */
        .type-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .type-electricity {
            background: #fef9c3;
            color: #854d0e;
        }

        .type-telecoms {
            background: #dbeafe;
            color: #1e40af;
        }

        .type-entertainment {
            background: #fce7f3;
            color: #9d174d;
        }

        .type-default {
            background: #f0fdf4;
            color: #166534;
        }
    </style>
</head>

<body>
    <div class="page">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="brand">Bill<span>Pay</span></div>
                <div class="receipt-label">
                    <div class="title">Payment Receipt</div>
                    <div class="ref">{{ $transaction['reference'] ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <!-- Status Banner -->
        <div class="status-banner">
            <div class="status-badge">
                <span class="status-dot"></span>
                <span class="status-text">Payment Successful</span>
            </div>
            <div class="status-date">{{ now()->format('D, d M Y · H:i') }} WAT</div>
        </div>

        <!-- Body -->
        <div class="body">

            <!-- Amount -->
            <div class="amount-card">
                <div class="amount-label">Total Amount Paid</div>
                <div class="amount-value">
                    <span class="amount-currency">₦</span>{{ number_format($transaction['amount'] ?? 0, 2) }}
                </div>
            </div>

            <!-- Transaction Details -->
            <div class="details-section">
                <div class="section-title">Transaction Details</div>

                <div class="detail-row">
                    <span class="detail-key">Reference</span>
                    <span class="detail-value monospace">{{ $transaction['reference'] ?? '—' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Service Type</span>
                    <span class="detail-value">
                        @php $type = $transaction['type'] ?? 'default'; @endphp
                        <span class="type-badge type-{{ $type }}">{{ ucfirst($type) }}</span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Provider</span>
                    <span class="detail-value">{{ strtoupper($transaction['provider_name'] ?? 'N/A') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Payment Channel</span>
                    <span class="detail-value">{{ ucfirst($transaction['channel'] ?? 'Card') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Status</span>
                    <span class="detail-value"
                        style="color: #22c55e;">{{ ucfirst($transaction['status'] ?? 'Paid') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Date &amp; Time</span>
                    <span
                        class="detail-value">{{ isset($transaction['paid_at']) ? \Carbon\Carbon::parse($transaction['paid_at'])->format('d M Y, H:i') : now()->format('d M Y, H:i') }}</span>
                </div>
            </div>

            <!-- Customer Details -->
            @if (!empty($user))
                <div class="details-section">
                    <div class="section-title">Customer Details</div>
                    <div class="detail-row">
                        <span class="detail-key">Name</span>
                        <span
                            class="detail-value">{{ $user['name'] ?? ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-key">Email</span>
                        <span class="detail-value">{{ $user['email'] ?? '—' }}</span>
                    </div>
                    @if (!empty($user['phone']))
                        <div class="detail-row">
                            <span class="detail-key">Phone</span>
                            <span class="detail-value">{{ $user['phone'] }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Bill Details (from meta) -->
            @if (!empty($transaction['meta']['bill_data']))
                <div class="details-section">
                    <div class="section-title">Bill Information</div>
                    @foreach ($transaction['meta']['bill_data'] as $key => $value)
                        @if (!is_array($value) && !empty($value))
                            <div class="detail-row">
                                <span class="detail-key">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                <span class="detail-value">{{ $value }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

        </div><!-- /body -->

        <!-- Footer -->
        <div class="footer">
            <p>This is an automatically generated receipt. Keep it safe for your records.</p>
            <p style="margin-top:6px;">Questions? Contact <a
                    href="mailto:support@lythubtechnologies.com">support@lythubtechnologies.com</a></p>
            <p style="margin-top:10px; opacity:0.4;">© {{ date('Y') }} BillPay by LythubTech · All rights reserved
            </p>
        </div>
    </div>
</body>

</html>
