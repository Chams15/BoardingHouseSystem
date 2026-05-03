<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Financial Summary - {{ $report['monthLabel'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 24px;
            color: #1f2937;
            background: #f3f4f6;
        }

        .page {
            background: #ffffff;
            padding: 28px;
            border-radius: 12px;
        }

        .header {
            border-bottom: 3px solid #f97316;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #111827;
        }

        .header p {
            margin: 6px 0 0;
            font-size: 11px;
            color: #6b7280;
        }

        .summary-grid {
            display: table;
            width: 100%;
            border-spacing: 8px;
            margin: 16px 0 18px;
        }

        .summary-card {
            display: table-cell;
            width: 16.66%;
            vertical-align: top;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px;
        }

        .summary-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
        }

        .summary-value {
            margin-top: 10px;
            font-size: 16px;
            font-weight: bold;
            color: #111827;
        }

        .section {
            margin-top: 22px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        th, td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f9fafb;
            color: #4b5563;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .muted {
            color: #6b7280;
        }

        .pill-row {
            margin-top: 10px;
        }

        .pill {
            display: inline-block;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            padding: 5px 10px;
            margin: 0 6px 6px 0;
            font-size: 9px;
            color: #374151;
            background: #f9fafb;
        }

        .two-col {
            width: 100%;
            border-spacing: 10px;
        }

        .two-col-cell {
            width: 50%;
            vertical-align: top;
        }

        .subtle-box {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px;
        }

        .footer {
            margin-top: 20px;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1>Monthly Financial Summary</h1>
            <p>{{ $report['monthLabel'] }} • {{ $report['monthStart'] }} to {{ $report['monthEnd'] }}</p>
            <p>Generated {{ now()->format('F d, Y g:i A') }}</p>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-label">Billed</div>
                <div class="summary-value">₱{{ number_format($report['summary']['billed_amount'], 2) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Collected</div>
                <div class="summary-value">₱{{ number_format($report['summary']['collected_amount'], 2) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Outstanding</div>
                <div class="summary-value">₱{{ number_format($report['summary']['outstanding_amount'], 2) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Discounts</div>
                <div class="summary-value">₱{{ number_format($report['summary']['discount_amount'], 2) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Waived</div>
                <div class="summary-value">₱{{ number_format($report['summary']['waived_amount'], 2) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Payments</div>
                <div class="summary-value">{{ $report['summary']['payment_count'] }}</div>
            </div>
        </div>

        <table class="two-col">
            <tr>
                <td class="two-col-cell">
                    <div class="subtle-box">
                        <div class="section-title" style="margin-top:0;">Current Month Status Breakdown</div>
                        <div class="pill-row">
                            @forelse ($report['paymentStatusBreakdown'] as $status => $count)
                                <span class="pill">{{ $status }}: {{ $count }}</span>
                            @empty
                                <span class="muted">No bill statuses available.</span>
                            @endforelse
                        </div>
                    </div>
                </td>
                <td class="two-col-cell">
                    <div class="subtle-box">
                        <div class="section-title" style="margin-top:0;">Monthly Activity Totals</div>
                        <table>
                            <tr><th>Bills</th><td>{{ $report['summary']['bill_count'] }}</td></tr>
                            <tr><th>Settled Payments</th><td>{{ $report['summary']['settled_payment_count'] }}</td></tr>
                            <tr><th>Pending Payments</th><td>{{ $report['summary']['pending_payment_count'] }}</td></tr>
                            <tr><th>Failed Payments</th><td>{{ $report['summary']['failed_payment_count'] }}</td></tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section">
            <div class="section-title">Historical Payment Data</div>
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Billed</th>
                        <th>Collected</th>
                        <th>Waived</th>
                        <th>Payments</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['monthlyHistory'] as $row)
                        <tr>
                            <td>{{ $row['monthLabel'] }}</td>
                            <td>₱{{ number_format($row['billed_amount'], 2) }}</td>
                            <td>₱{{ number_format($row['collected_amount'], 2) }}</td>
                            <td>₱{{ number_format($row['waived_amount'], 2) }}</td>
                            <td>{{ $row['payment_count'] }} total / {{ $row['settled_count'] }} settled</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">All Payments in Month</div>
            <table>
                <thead>
                    <tr>
                        <th>Tenant / Room</th>
                        <th>Bill</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['allPayments'] as $payment)
                        <tr>
                            <td>
                                {{ $payment['bill']['tenant_name'] ?? 'Unknown tenant' }}<br>
                                <span class="muted">{{ $payment['bill']['room_number'] ?? 'No room' }}</span>
                            </td>
                            <td>
                                {{ $payment['bill']['bill_type'] ?? 'Bill' }}
                                <br>
                                <span class="muted">{{ $payment['bill']['billing_period'] ?? '—' }}</span>
                            </td>
                            <td>₱{{ number_format($payment['amount_paid'], 2) }}</td>
                            <td>{{ $payment['provider_status'] ?? 'unknown' }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($payment['paid_at'])->format('M d, Y g:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted">No payment records found for this report period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="footer">
            BoardingHouse admin financial summary report
        </div>
    </div>
</body>
</html>
