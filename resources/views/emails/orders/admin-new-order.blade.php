<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #FF5722;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .order-details {
            background-color: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #FF5722;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 15px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f0f0f0;
        }
        .alert {
            background-color: #FFF3CD;
            border-left: 4px solid #FFC107;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔔 New Order Received</h1>
    </div>
    
    <div class="content">
        <div class="alert">
            <strong>Action Required:</strong> A new order needs to be processed.
        </div>

        <h2>Order Summary</h2>
        
        <div class="order-details">
            <h3>Order Information</h3>
            <table>
                <tr>
                    <th>Order Number</th>
                    <td>{{ $order->order_number }}</td>
                </tr>
                <tr>
                    <th>Order Date</th>
                    <td>{{ $order->created_at->format('F d, Y H:i:s') }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td style="text-transform: capitalize;">{{ $order->status }}</td>
                </tr>
                <tr>
                    <th>Total Amount</th>
                    <td><strong>${{ number_format($order->total, 2) }}</strong></td>
                </tr>
                <tr>
                    <th>Payment Method</th>
                    <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $order->payment_method) }}</td>
                </tr>
                <tr>
                    <th>Payment Status</th>
                    <td style="text-transform: capitalize;">{{ $order->payment_status }}</td>
                </tr>
            </table>
        </div>

        <div class="order-details">
            <h3>Customer Information</h3>
            <table>
                <tr>
                    <th>Customer Name</th>
                    <td>{{ $customer->name }}</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a></td>
                </tr>
                <tr>
                    <th>Customer ID</th>
                    <td>#{{ $customer->id }}</td>
                </tr>
            </table>
        </div>

        <div class="order-details">
            <h3>Shipping Address</h3>
            <p>
                <strong>{{ $order->shipping_name }}</strong><br>
                {{ $order->shipping_address }}<br>
                {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
                {{ $order->shipping_country }}<br>
                <strong>Phone:</strong> {{ $order->shipping_phone }}<br>
                <strong>Email:</strong> {{ $order->shipping_email }}
            </p>
        </div>

        <div class="order-details">
            <h3>Order Items ({{ $items->count() }} items)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>
                            {{ $item->product_name }}
                            @if($item->variant_name)
                                <br><small style="color: #666;">{{ $item->variant_name }}</small>
                            @endif
                        </td>
                        <td><small>{{ $item->sku }}</small></td>
                        <td>{{ $item->quantity }}</td>
                        <td>${{ number_format($item->unit_price, 2) }}</td>
                        <td>${{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top: 20px; text-align: right;">
                <p><strong>Subtotal:</strong> ${{ number_format($order->subtotal, 2) }}</p>
                <p><strong>Tax:</strong> ${{ number_format($order->tax, 2) }}</p>
                <p><strong>Shipping:</strong> ${{ number_format($order->shipping_cost, 2) }}</p>
                @if($order->discount > 0)
                <p><strong>Discount:</strong> -${{ number_format($order->discount, 2) }}</p>
                @endif
                <p style="font-size: 18px; color: #FF5722;"><strong>Total:</strong> ${{ number_format($order->total, 2) }}</p>
            </div>
        </div>

        @if($order->notes)
        <div class="order-details">
            <h3>Customer Notes</h3>
            <p>{{ $order->notes }}</p>
        </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/admin/orders/{{ $order->id }}" class="button">Process Order</a>
        </div>
    </div>
    
    <div class="footer">
        <p>This is an admin notification email.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
