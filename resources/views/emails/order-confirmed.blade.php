<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
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
            background: linear-gradient(135deg, hsl(20, 25%, 22%) 0%, hsl(20, 25%, 28%) 100%);
            color: hsl(40, 30%, 97%);
            padding: 40px 20px;
            text-align: center;
        }
        .brand-name {
            font-style: italic;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 2px;
            margin-bottom: 15px;
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
        .order-summary {
            background-color: white;
            padding: 25px;
            border-left: 4px solid hsl(35, 45%, 58%);
            margin: 25px 0;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        .order-summary h3 {
            margin-top: 0;
            color: hsl(20, 25%, 22%);
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .order-item {
            border-bottom: 1px solid hsl(30, 15%, 88%);
            padding: 15px 0;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0 10px;
            font-weight: 600;
            font-size: 18px;
            color: hsl(20, 25%, 22%);
            border-top: 2px solid hsl(35, 45%, 58%);
            margin-top: 15px;
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
        table {
            width: 100%;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <div class="brand-name">DuaInsan.Story</div>
            <h1>✓ Order Confirmed!</h1>
            <p>Thank you for your order</p>
        </div>
        
        <div class="content">
            <p>Hi {{ $customer->full_name }},</p>
            
            <p>Your order has been successfully placed! We're excited to create your beautiful wedding invitations.</p>
        
        <div class="order-summary">
            <h3>Order Details</h3>
            <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('F d, Y H:i') }}</p>
            <p><strong>Order Status:</strong> {{ $order->order_status ?? 'Pending Payment' }}</p>
            <p><strong>Payment Status:</strong> {{ $order->payment_status ?? 'pending' }}</p>
        </div>

        @if($invitationDetail)
        <div class="order-summary">
            <h3>Wedding Information</h3>
            <table>
                <tr>
                    <td><strong>Bride:</strong></td>
                    <td>{{ $invitationDetail->bride_full_name }}</td>
                </tr>
                <tr>
                    <td><strong>Groom:</strong></td>
                    <td>{{ $invitationDetail->groom_full_name }}</td>
                </tr>
                <tr>
                    <td><strong>Akad Date:</strong></td>
                    <td>{{ \Carbon\Carbon::parse($invitationDetail->akad_date)->format('F d, Y') }}</td>
                </tr>
                @if($invitationDetail->akad_time)
                <tr>
                    <td><strong>Akad Time:</strong></td>
                    <td>{{ \Carbon\Carbon::parse($invitationDetail->akad_time)->format('H:i') }}</td>
                </tr>
                @endif
                <tr>
                    <td><strong>Reception Date:</strong></td>
                    <td>{{ \Carbon\Carbon::parse($invitationDetail->reception_date)->format('F d, Y') }}</td>
                </tr>
                @if($invitationDetail->reception_time)
                <tr>
                    <td><strong>Reception Time:</strong></td>
                    <td>{{ \Carbon\Carbon::parse($invitationDetail->reception_time)->format('H:i') }}</td>
                </tr>
                @endif
            </table>
        </div>
        @endif
        
        <div class="order-summary">
            <h3>Order Items</h3>
            @foreach($items as $item)
            <div class="order-item">
                <strong>{{ $item->product->name }}</strong>
                @if($item->variant && $item->variant->name)
                    <br><small>Variant: {{ $item->variant->name }}</small>
                @endif
                <table style="margin-top: 10px;">
                    <tr>
                        <td>Quantity: {{ $item->quantity }}</td>
                        <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Subtotal:</strong></td>
                        <td class="text-right"><strong>Rp {{ number_format($item->sub_total, 0, ',', '.') }}</strong></td>
                    </tr>
                </table>
            </div>
            @endforeach
            
            <div class="order-item" style="border-bottom: none;">
                <table>
                    <tr>
                        <td><strong>Shipping Cost:</strong></td>
                        <td class="text-right"><strong>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</strong></td>
                    </tr>
                </table>
            </div>
            
            <div class="total-row">
                <span>Total Amount:</span>
                <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>
        
        <div class="info-box">
            <strong>📦 What happens next?</strong>
            <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                <li>We'll verify your payment</li>
                <li>Our design team will create your invitation proof</li>
                <li>You'll receive an email when your design is ready for review</li>
                <li>After approval, we'll begin production</li>
                <li>Your order will be shipped to your address</li>
            </ul>
        </div>
        
        <center>
            <a href="{{ config('app.frontend_url') }}/orders/{{ $order->id }}" class="button">
                View Order Details
            </a>
        </center>
            
            <p>If you have any questions about your order, please don't hesitate to contact us.</p>
            
            <p style="margin-top: 30px;">Best regards,<br>
            <strong>Dua Insan Story Team</strong></p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Dua Insan Story. All rights reserved.</p>
            <p style="margin-top: 8px;">You're receiving this email because you placed an order on our website.</p>
            <div class="footer-brand">DuaInsan.Story</div>
        </div>
    </div>
</body>
</html>
