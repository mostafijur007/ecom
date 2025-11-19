<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
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
            background-color: #4CAF50;
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
        .item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .item:last-child {
            border-bottom: none;
        }
        .total {
            font-size: 18px;
            font-weight: bold;
            color: #4CAF50;
            text-align: right;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #4CAF50;
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
            background-color: #4CAF50;
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
        }
        th {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Confirmation</h1>
    </div>
    
    <div class="content">
        <h2>Thank you for your order, {{ $customer->name }}!</h2>
        
        <p>Your order has been successfully placed and is being processed. Here are the details:</p>
        
        <div class="order-details">
            <h3>Order Information</h3>
            <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('F d, Y') }}</p>
            <p><strong>Status:</strong> <span style="text-transform: capitalize;">{{ $order->status }}</span></p>
            <p><strong>Payment Method:</strong> <span style="text-transform: capitalize;">{{ str_replace('_', ' ', $order->payment_method) }}</span></p>
        </div>

        <div class="order-details">
            <h3>Shipping Address</h3>
            <p>
                {{ $order->shipping_name }}<br>
                {{ $order->shipping_address }}<br>
                {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
                {{ $order->shipping_country }}<br>
                Phone: {{ $order->shipping_phone }}
            </p>
        </div>

        <div class="order-details">
            <h3>Order Items</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
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
                                <br><small>{{ $item->variant_name }}</small>
                            @endif
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td>${{ number_format($item->unit_price, 2) }}</td>
                        <td>${{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top: 20px;">
                <table>
                    <tr>
                        <td style="text-align: right;"><strong>Subtotal:</strong></td>
                        <td style="text-align: right; width: 100px;">${{ number_format($order->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><strong>Tax:</strong></td>
                        <td style="text-align: right;">${{ number_format($order->tax, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><strong>Shipping:</strong></td>
                        <td style="text-align: right;">${{ number_format($order->shipping_cost, 2) }}</td>
                    </tr>
                    @if($order->discount > 0)
                    <tr>
                        <td style="text-align: right;"><strong>Discount:</strong></td>
                        <td style="text-align: right; color: #4CAF50;">-${{ number_format($order->discount, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="text-align: right; font-size: 18px;"><strong>Total:</strong></td>
                        <td style="text-align: right; font-size: 18px; color: #4CAF50;"><strong>${{ number_format($order->total, 2) }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/orders/{{ $order->id }}" class="button">View Order Details</a>
        </div>

        <p>You will receive another email when your order has been shipped.</p>
        
        <p>If you have any questions, please contact our customer support.</p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        <p>This is an automated email. Please do not reply directly to this message.</p>
    </div>
</body>
</html>
