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
            background-color: #4F46E5;
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
        }
        .order-summary {
            background-color: white;
            padding: 20px;
            border-left: 4px solid #4F46E5;
            margin: 20px 0;
        }
        .order-summary h3 {
            margin-top: 0;
            color: #4F46E5;
        }
        .order-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-weight: bold;
            font-size: 18px;
            color: #4F46E5;
            border-top: 2px solid #4F46E5;
            margin-top: 10px;
        }
        .button {
            display: inline-block;
            background-color: #4F46E5;
            color: white;
            padding: 14px 35px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .info-box {
            background-color: #E0F2FE;
            border-left: 4px solid #0EA5E9;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #777;
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
    <div class="header">
        <h1>✓ Order Confirmed!</h1>
        <p style="margin: 10px 0 0 0;">Thank you for your order</p>
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
        
        <p>Best regards,<br>
        <strong>Dua Insan Story Team</strong></p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} Dua Insan Story. All rights reserved.</p>
        <p>You're receiving this email because you placed an order on our website.</p>
    </div>
</body>
</html>
