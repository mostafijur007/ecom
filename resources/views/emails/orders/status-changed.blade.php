<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update</title>
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
            background-color: #2196F3;
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
        .status-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: capitalize;
        }
        .status-pending { background-color: #FFC107; color: #000; }
        .status-processing { background-color: #2196F3; color: white; }
        .status-shipped { background-color: #9C27B0; color: white; }
        .status-delivered { background-color: #4CAF50; color: white; }
        .status-cancelled { background-color: #F44336; color: white; }
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
            background-color: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 15px 0;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
            margin: 20px 0;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -21px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #ddd;
        }
        .timeline-item.active:before {
            background-color: #2196F3;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Status Update</h1>
    </div>
    
    <div class="content">
        <h2>Hello {{ $customer->name }},</h2>
        
        <p>Your order status has been updated!</p>
        
        <div class="order-details">
            <h3>Order Information</h3>
            <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('F d, Y') }}</p>
            
            <div style="margin: 20px 0;">
                <p><strong>Status Changed:</strong></p>
                <div>
                    <span class="status-badge status-{{ $oldStatus }}">{{ $oldStatus }}</span>
                    <span style="font-size: 20px; margin: 0 10px;">→</span>
                    <span class="status-badge status-{{ $newStatus }}">{{ $newStatus }}</span>
                </div>
            </div>
        </div>

        @if($newStatus === 'processing')
        <div class="order-details">
            <h3>📦 Your Order is Being Prepared</h3>
            <p>Great news! We've started processing your order. Our team is carefully preparing your items for shipment.</p>
        </div>
        @elseif($newStatus === 'shipped')
        <div class="order-details">
            <h3>🚚 Your Order Has Been Shipped!</h3>
            <p>Your order is on its way! You should receive it soon at the following address:</p>
            <p>
                {{ $order->shipping_address }}<br>
                {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_postal_code }}<br>
                {{ $order->shipping_country }}
            </p>
            @if($order->tracking_number)
            <p><strong>Tracking Number:</strong> {{ $order->tracking_number }}</p>
            @endif
        </div>
        @elseif($newStatus === 'delivered')
        <div class="order-details">
            <h3>✅ Your Order Has Been Delivered!</h3>
            <p>Your order has been successfully delivered. We hope you enjoy your purchase!</p>
            <p>Please let us know if you have any issues with your order.</p>
        </div>
        @endif

        <div class="order-details">
            <h3>Order Timeline</h3>
            <div class="timeline">
                <div class="timeline-item {{ in_array($order->status, ['pending', 'processing', 'shipped', 'delivered']) ? 'active' : '' }}">
                    <strong>Order Placed</strong><br>
                    <small>{{ $order->created_at->format('M d, Y H:i') }}</small>
                </div>
                <div class="timeline-item {{ in_array($order->status, ['processing', 'shipped', 'delivered']) ? 'active' : '' }}">
                    <strong>Processing</strong>
                </div>
                <div class="timeline-item {{ in_array($order->status, ['shipped', 'delivered']) ? 'active' : '' }}">
                    <strong>Shipped</strong>
                    @if($order->shipped_at)
                        <br><small>{{ $order->shipped_at->format('M d, Y H:i') }}</small>
                    @endif
                </div>
                <div class="timeline-item {{ $order->status === 'delivered' ? 'active' : '' }}">
                    <strong>Delivered</strong>
                    @if($order->delivered_at)
                        <br><small>{{ $order->delivered_at->format('M d, Y H:i') }}</small>
                    @endif
                </div>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/orders/{{ $order->id }}" class="button">View Order Details</a>
        </div>

        <p>Thank you for shopping with us!</p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        <p>This is an automated email. Please do not reply directly to this message.</p>
    </div>
</body>
</html>
