<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Shipped</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Jost', Arial, sans-serif;
            line-height: 1.6;
            color: hsl(20, 20%, 25%);
            background-color: hsl(40, 30%, 97%);
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-wrapper {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, hsl(35, 45%, 58%) 0%, hsl(35, 50%, 52%) 100%);
            color: hsl(20, 25%, 22%);
            padding: 40px 20px;
            text-align: center;
        }
        .brand-name {
            font-style: italic;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 2px;
            margin-bottom: 15px;
            color: hsl(40, 30%, 97%);
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.95;
            font-weight: 300;
        }
        .content {
            background-color: hsl(40, 30%, 97%);
            padding: 35px 30px;
        }
        .shipping-info {
            background-color: white;
            padding: 25px;
            border-left: 4px solid hsl(35, 45%, 58%);
            margin: 25px 0;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        .shipping-info h3 {
            margin-top: 0;
            color: hsl(20, 25%, 22%);
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .tracking-box {
            background-color: hsl(40, 40%, 96%);
            padding: 20px;
            border-radius: 6px;
            text-align: center;
            margin: 20px 0;
            border: 2px solid hsl(35, 45%, 58%);
        }
        .tracking-number {
            font-size: 24px;
            font-weight: 600;
            color: hsl(20, 25%, 22%);
            letter-spacing: 1.5px;
        }
        .button {
            display: inline-block;
            background: hsl(20, 25%, 22%);
            color: hsl(40, 30%, 97%);
            padding: 14px 35px;
            text-decoration: none;
            border-radius: 6px;
            margin: 25px 0;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: background 0.3s ease;
        }
        .button:hover {
            background: hsl(20, 25%, 28%);
        }
        .info-box {
            background-color: hsl(40, 40%, 96%);
            border-left: 4px solid hsl(35, 45%, 58%);
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            padding: 25px 20px;
            background-color: hsl(40, 25%, 88%);
            font-size: 12px;
            color: hsl(20, 15%, 55%);
        }
        .footer-brand {
            font-style: italic;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 1.5px;
            color: hsl(20, 25%, 22%);
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <div class="brand-name">DuaInsan.Story</div>
            <h1>📦 Your Order Has Shipped!</h1>
            <p>Order {{ $order->order_number }} is on its way</p>
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
                    <p style="margin: 0 0 10px 0; font-size: 14px; color: hsl(20, 15%, 55%);">Tracking Number:</p>
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
            
            <p style="margin-top: 30px;">Best regards,<br>
            <strong>Dua Insan Story Team</strong></p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Dua Insan Story. All rights reserved.</p>
            <div class="footer-brand">DuaInsan.Story</div>
        </div>
    </div>
</body>
</html>
