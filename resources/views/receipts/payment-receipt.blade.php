<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt #{{ $payment->payment_id }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            background-color: #f5f5f5;
        }
        .receipt-container {
            background-color: white;
            padding: 40px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #1e40af;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            color: #1e40af;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-weight: bold;
            font-size: 14px;
            color: #1e40af;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .receipt-info {
            font-size: 11px;
            line-height: 1.8;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            width: 40%;
        }
        .info-value {
            color: #333;
            width: 60%;
            text-align: right;
        }
        .tenant-details {
            background-color: #f9f9f9;
            padding: 15px;
            border-left: 4px solid #1e40af;
            margin-bottom: 15px;
            font-size: 12px;
        }
        .bill-details {
            background-color: #f9f9f9;
            padding: 15px;
            border-left: 4px solid #10b981;
            margin-bottom: 15px;
            font-size: 12px;
        }
        .amount-paid-section {
            background-color: #ecfdf5;
            padding: 20px;
            border: 2px solid #10b981;
            text-align: center;
            margin: 25px 0;
        }
        .amount-paid-label {
            font-size: 12px;
            color: #059669;
            margin-bottom: 5px;
        }
        .amount-paid {
            font-size: 32px;
            font-weight: bold;
            color: #10b981;
        }
        .payment-method {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .signature-box {
            width: 45%;
            text-align: center;
        }
        .signature-image {
            max-width: 120px;
            height: auto;
            margin-bottom: 10px;
        }
        .signature-line {
            border-top: 1px solid #333;
            height: 30px;
            font-size: 10px;
            color: #666;
            padding-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #999;
        }
        .status-badge {
            display: inline-block;
            background-color: #10b981;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 10px;
        }
        .reference-number {
            background-color: #f3f4f6;
            padding: 10px;
            border-radius: 4px;
            font-size: 11px;
            margin-top: 10px;
            font-weight: bold;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="header">
            <h1>PAYMENT RECEIPT</h1>
            <p>Receipt #{{ $payment->payment_id }}</p>
            <p>{{ now()->format('F d, Y \a\t g:i A') }}</p>
        </div>

        <!-- Tenant Information -->
        <div class="section">
            <div class="section-title">TENANT INFORMATION</div>
            <div class="tenant-details">
                <div class="info-row">
                    <div class="info-label">Name:</div>
                    <div class="info-value">
                        {{ $payment->bill->leaseContract->tenant->tenant_profile?->full_name ?? $payment->bill->leaseContract->tenant->name ?? 'N/A' }}
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value">{{ $payment->bill->leaseContract->tenant->email }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Room Number:</div>
                    <div class="info-value">{{ $payment->bill->leaseContract->room->room_number }}</div>
                </div>
            </div>
        </div>

        <!-- Bill Information -->
        <div class="section">
            <div class="section-title">BILL INFORMATION</div>
            <div class="bill-details">
                <div class="info-row">
                    <div class="info-label">Bill ID:</div>
                    <div class="info-value">#{{ $payment->bill->bill_id }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Bill Type:</div>
                    <div class="info-value">{{ $payment->bill->bill_type }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Description:</div>
                    <div class="info-value">{{ $payment->bill->description ?? 'N/A' }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Due Date:</div>
                    <div class="info-value">{{ $payment->bill->due_date->format('F d, Y') }}</div>
                </div>
            </div>
        </div>

        <!-- Amount Paid -->
        <div class="amount-paid-section">
            <div class="amount-paid-label">AMOUNT PAID</div>
            <div class="amount-paid">₱{{ number_format($payment->amount_paid, 2) }}</div>
            <div class="payment-method">
                Payment Method: <strong>{{ $payment->payment_method }}</strong>
            </div>
            @if ($payment->reference_no)
                <div class="reference-number">
                    Ref. No.: {{ $payment->reference_no }}
                </div>
            @endif
            <div class="status-badge">✓ PAID</div>
        </div>

        <!-- Payment Details -->
        <div class="section">
            <div class="section-title">PAYMENT DETAILS</div>
            <div class="receipt-info">
                <div class="info-row">
                    <div class="info-label">Payment Date:</div>
                    <div class="info-value">{{ $payment->payment_date->format('F d, Y g:i A') }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Payment ID:</div>
                    <div class="info-value">#{{ $payment->payment_id }}</div>
                </div>
                @if ($payment->provider_metadata && isset($payment->provider_metadata['notes']))
                    <div class="info-row">
                        <div class="info-label">Notes:</div>
                        <div class="info-value">{{ $payment->provider_metadata['notes'] }}</div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                @if ($signatureUrl)
                    <img src="{{ $signatureUrl }}" alt="Property Manager Signature" class="signature-image">
                @endif
                <div class="signature-line">Authorized by<br>Property Manager</div>
            </div>
            <div class="signature-box">
                <div style="height: 50px;"></div>
                <div class="signature-line">Tenant Signature<br>Date</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your payment!</p>
            <p>This is an official receipt generated by the Boarding House Management System.</p>
            <p>For inquiries, please contact the Property Management Office.</p>
        </div>
    </div>
</body>
</html>
