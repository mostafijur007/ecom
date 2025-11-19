<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order for Your Products</title>
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
            background-color: #9C27B0;
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
            background-color: #9C27B0;
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
        .highlight {
            background-color: #E1BEE7;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 New Order for Your Products</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $vendorName }},</h2>
        
        <p>Great news! You have received a new order for your products.</p>
        
        <div class="highlight">
            <p style="margin: 0;"><strong>Order Number:</strong> {{ $order->order_number }}</p>
            <p style="margin: 5px 0 0 0;"><strong>Order Date:</strong> {{ $order->created_at->format('F d, Y H:i') }}</p>
        </div>

        <div class="order-details">
            <h3>Your Products in This Order</h3>
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
                    @php
                        $vendorSubtotal = 0;
                    @endphp
                    @foreach($vendorItems as $item)
                    @php
                        $vendorSubtotal += $item->subtotal;
                    @endphp
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

            <div style="margin-top: 15px; text-align: right;">
                <p style="font-size: 18px; color: #9C27B0;"><strong>Your Items Total: ${{ number_format($vendorSubtotal, 2) }}</strong></p>
            </div>
        </div>

        <div class="order-details">
            <h3>Shipping Information</h3>
            <p>
                <strong>Ship to:</strong><br>
                {{ $order->shipping_name }}<br>
                {{ $order->shipping_address }}<br>
                {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
                {{ $order->shipping_country }}<br>
                <br>
                <strong>Phone:</strong> {{ $order->shipping_phone }}<br>
                <strong>Email:</strong> {{ $order->shipping_email }}
            </p>
        </div>

        <div class="order-details">
            <h3>Customer Information</h3>
            <p>
                <strong>Name:</strong> {{ $customer->name }}<br>
                <strong>Email:</strong> {{ $customer->email }}
            </p>
        </div>

        @if($order->notes)
        <div class="order-details">
            <h3>Customer Notes</h3>
            <p>{{ $order->notes }}</p>
        </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/vendor/orders/{{ $order->id }}" class="button">View Full Order Details</a>
        </div>

        <p><strong>Next Steps:</strong></p>
        <ul>
            <li>Review the order details</li>
            <li>Prepare your products for shipment</li>
            <li>Update the order status when ready</li>
            <li>Contact customer if you have any questions</li>
        </ul>

        <p>Thank you for being a valued vendor!</p>
    </div>
    
    <div class="footer">
        <p>This is a vendor notification email.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
