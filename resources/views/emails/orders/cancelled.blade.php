<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Cancelled</title>
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
            background-color: #F44336;
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
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 15px 0;
        }
        .refund-info {
            background-color: #FFF3CD;
            border-left: 4px solid #FFC107;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Cancelled</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $customer->name }},</h2>
        
        <p>Your order has been cancelled as requested.</p>
        
        <div class="order-details">
            <h3>Order Information</h3>
            <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('F d, Y') }}</p>
            <p><strong>Cancelled Date:</strong> {{ $order->cancelled_at ? $order->cancelled_at->format('F d, Y H:i') : now()->format('F d, Y H:i') }}</p>
        </div>

        @if($order->payment_status === 'paid')
        <div class="refund-info">
            <h3>💳 Refund Information</h3>
            <p>Since your payment was already processed, a refund of <strong>${{ number_format($order->total, 2) }}</strong> will be issued to your original payment method.</p>
            <p>Please allow 5-10 business days for the refund to appear in your account.</p>
        </div>
        @endif

        <div class="order-details">
            <h3>Cancelled Items</h3>
            <p>The following items were included in your cancelled order:</p>
            <ul>
                @foreach($order->items as $item)
                <li>
                    {{ $item->product_name }}
                    @if($item->variant_name)
                        ({{ $item->variant_name }})
                    @endif
                    - Qty: {{ $item->quantity }}
                </li>
                @endforeach
            </ul>
        </div>

        <div class="order-details">
            <h3>What's Next?</h3>
            <p>The items from your cancelled order have been returned to our inventory. You're welcome to place a new order anytime.</p>
        </div>

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/products" class="button">Continue Shopping</a>
        </div>

        <p>If you have any questions about this cancellation, please don't hesitate to contact our customer support team.</p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        <p>This is an automated email. Please do not reply directly to this message.</p>
    </div>
</body>
</html>
