<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .invoice-header h1 {
            margin: 0;
            font-size: 28px;
            color: #2c3e50;
        }
        .company-info {
            margin-bottom: 30px;
        }
        .company-info h2 {
            margin: 0 0 10px 0;
            font-size: 20px;
            color: #2c3e50;
        }
        .invoice-details {
            width: 100%;
            margin-bottom: 30px;
        }
        .invoice-details table {
            width: 100%;
        }
        .invoice-details td {
            padding: 5px 0;
        }
        .invoice-details .label {
            font-weight: bold;
            width: 150px;
        }
        .billing-shipping {
            width: 100%;
            margin-bottom: 30px;
        }
        .billing-shipping table {
            width: 100%;
        }
        .billing-shipping td {
            width: 50%;
            vertical-align: top;
            padding: 10px;
        }
        .billing-shipping h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #2c3e50;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background-color: #2c3e50;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
            text-align: center;
        }
        .totals-table {
            width: 300px;
            margin-left: auto;
            margin-bottom: 30px;
        }
        .totals-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }
        .totals-table .label {
            font-weight: bold;
            text-align: right;
        }
        .totals-table .value {
            text-align: right;
            width: 120px;
        }
        .totals-table .total-row {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .payment-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .payment-info h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #2c3e50;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-draft {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <h1>INVOICE</h1>
        <p style="margin: 5px 0; font-size: 14px;">#{{ $invoice->invoice_number }}</p>
        <span class="status-badge status-{{ strtolower($invoice->status) }}">
            {{ strtoupper($invoice->status) }}
        </span>
    </div>

    <div class="company-info">
        <h2>E-Commerce Store</h2>
        <p>123 Business Street<br>
        City, State 12345<br>
        Phone: (555) 123-4567<br>
        Email: info@ecom.com<br>
        Website: www.ecom.com</p>
    </div>

    <div class="invoice-details">
        <table>
            <tr>
                <td class="label">Invoice Date:</td>
                <td>{{ $invoice->invoice_date->format('F d, Y') }}</td>
                <td class="label">Due Date:</td>
                <td>{{ $invoice->due_date->format('F d, Y') }}</td>
            </tr>
            <tr>
                <td class="label">Order Number:</td>
                <td>{{ $order->order_number }}</td>
                <td class="label">Payment Method:</td>
                <td>{{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}</td>
            </tr>
            <tr>
                <td class="label">Payment Status:</td>
                <td colspan="3">
                    <span class="status-badge status-{{ strtolower($order->payment_status) }}">
                        {{ strtoupper($order->payment_status) }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div class="billing-shipping">
        <table>
            <tr>
                <td>
                    <h3>Bill To</h3>
                    @if($order->customer)
                    <strong>{{ $order->customer->name }}</strong><br>
                    {{ $order->customer->email }}<br>
                    @if($order->customer->phone)
                    Phone: {{ $order->customer->phone }}<br>
                    @endif
                    @endif
                </td>
                <td>
                    <h3>Ship To</h3>
                    <strong>{{ $order->shipping_name }}</strong><br>
                    @if($order->shipping_email)
                    {{ $order->shipping_email }}<br>
                    @endif
                    @if($order->shipping_phone)
                    Phone: {{ $order->shipping_phone }}<br>
                    @endif
                    {{ $order->shipping_address }}<br>
                    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
                    {{ $order->shipping_country }}
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 40%;">Product</th>
                <th style="width: 15%;" class="text-center">SKU</th>
                <th style="width: 10%;" class="text-right">Unit Price</th>
                <th style="width: 10%;" class="text-center">Quantity</th>
                <th style="width: 20%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->product_name }}</strong>
                    @if($item->variant_name)
                    <br><small>Variant: {{ $item->variant_name }}</small>
                    @endif
                </td>
                <td class="text-center">{{ $item->sku }}</td>
                <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">${{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="label">Subtotal:</td>
            <td class="value">${{ number_format($order->subtotal, 2) }}</td>
        </tr>
        @if($order->discount > 0)
        <tr>
            <td class="label">Discount:</td>
            <td class="value">-${{ number_format($order->discount, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Tax:</td>
            <td class="value">${{ number_format($order->tax, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Shipping:</td>
            <td class="value">${{ number_format($order->shipping_cost, 2) }}</td>
        </tr>
        <tr class="total-row">
            <td class="label">TOTAL:</td>
            <td class="value">${{ number_format($order->total, 2) }}</td>
        </tr>
    </table>

    <div class="payment-info">
        <h3>Payment Information</h3>
        <p><strong>Payment Method:</strong> {{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}</p>
        <p><strong>Payment Status:</strong> {{ strtoupper($order->payment_status) }}</p>
        @if($order->notes)
        <p><strong>Order Notes:</strong> {{ $order->notes }}</p>
        @endif
    </div>

    <div class="footer">
        <p><strong>Thank you for your business!</strong></p>
        <p>This is a computer-generated invoice and does not require a signature.</p>
        <p>For any queries, please contact us at info@ecom.com or call (555) 123-4567</p>
        <p style="margin-top: 10px;">Generated on {{ now()->format('F d, Y \a\t H:i:s') }}</p>
    </div>
</body>
</html>
