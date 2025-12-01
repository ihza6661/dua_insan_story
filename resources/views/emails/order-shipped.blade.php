<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Shipped</title>
    <style>
        body {font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;}
        .header {background-color: #10B981; color: white; padding: 30px 20px; text-align: center; border-radius: 5px 5px 0 0;}
        .header h1 {margin: 0; font-size: 28px;}
        .content {background-color: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 5px 5px;}
        .shipping-info {background-color: white; padding: 20px; border-left: 4px solid #10B981; margin: 20px 0;}
        .shipping-info h3 {margin-top: 0; color: #10B981;}
        .tracking-box {background-color: #F3F4F6; padding: 15px; border-radius: 5px; text-align: center; margin: 20px 0;}
        .tracking-number {font-size: 24px; font-weight: bold; color: #10B981; letter-spacing: 1px;}
        .button {display: inline-block; background-color: #10B981; color: white; padding: 14px 35px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold;}
        .info-box {background-color: #DCFCE7; border-left: 4px solid #10B981; padding: 15px; margin: 20px 0;}
        .footer {text-align: center; margin-top: 30px; font-size: 12px; color: #777;}
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 Your Order Has Shipped!</h1>
        <p style="margin: 10px 0 0 0;">Order {{ $order->order_number }} is on its way</p>
    </div>
    
    <div class="content">
        <p>Hi {{ $customer->full_name }},</p>
        
        <p>Great news! Your wedding invitation order has been shipped and is on its way to you!</p>
        
        <div class="shipping-info">
            <h3>Shipping Details</h3>
            <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
            @if($courierName)
            <p><strong>Courier:</strong> {{ $courierName }}</p>
            @endif
            @if($trackingNumber)
            <div class="tracking-box">
                <p style="margin: 0 0 10px 0; font-size: 14px;">Tracking Number:</p>
                <div class="tracking-number">{{ $trackingNumber }}</div>
            </div>
            @endif
        </div>
        
        @if($order->shipping_address)
        <div class="shipping-info">
            <h3>Delivery Address</h3>
            <p>
                {{ $order->shipping_address }}<br>
                @if($order->shipping_city)
                {{ $order->shipping_city }}<br>
                @endif
                @if($order->shipping_province)
                {{ $order->shipping_province }}<br>
                @endif
                @if($order->shipping_postal_code)
                {{ $order->shipping_postal_code }}
                @endif
            </p>
        </div>
        @endif
        
        <div class="info-box">
            <strong>📬 Delivery Information</strong>
            <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                <li>Please ensure someone is available to receive the package</li>
                <li>Check the package for any damage upon delivery</li>
                <li>Contact us immediately if there are any issues</li>
                @if($trackingNumber)
                <li>You can track your package using the tracking number above</li>
                @endif
            </ul>
        </div>
        
        <center>
            <a href="{{ config('app.frontend_url') }}/orders/{{ $order->id }}" class="button">
                View Order Details
            </a>
        </center>
        
        <p>Thank you for choosing Dua Insan Story for your special day!</p>
        
        <p>Best regards,<br>
        <strong>Dua Insan Story Team</strong></p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} Dua Insan Story. All rights reserved.</p>
    </div>
</body>
</html>
