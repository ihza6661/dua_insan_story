<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmed</title>
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
        .payment-summary {
            background-color: white;
            padding: 25px;
            border-left: 4px solid hsl(35, 45%, 58%);
            margin: 25px 0;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        .payment-summary h3 {
            margin-top: 0;
            color: hsl(20, 25%, 22%);
            font-weight: 500;
            letter-spacing: 0.5px;
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
            background-color: hsl(120, 60%, 95%);
            border-left: 4px solid hsl(35, 45%, 58%);
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .warning-box {
            background-color: hsl(45, 85%, 92%);
            border-left: 4px solid hsl(38, 92%, 50%);
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
            <h1>💳 Payment Confirmed!</h1>
            <p>Your payment has been received</p>
        </div>
        
        <div class="content">
            <p>Hi {{ $customer->full_name }},</p>
            
            <p>Great news! We've received your payment for order <strong>{{ $order->order_number }}</strong>.</p>
        
        <div class="payment-summary">
            <h3>Payment Details</h3>
            <table>
                <tr>
                    <td><strong>Payment Amount:</strong></td>
                    <td class="text-right">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td><strong>Payment Method:</strong></td>
                    <td class="text-right">{{ $payment->payment_gateway ?? 'Midtrans' }}</td>
                </tr>
                <tr>
                    <td><strong>Payment Type:</strong></td>
                    <td class="text-right">{{ $payment->payment_type === 'dp' ? 'Down Payment (50%)' : 'Full Payment' }}</td>
                </tr>
                <tr>
                    <td><strong>Transaction ID:</strong></td>
                    <td class="text-right">{{ $payment->transaction_id }}</td>
                </tr>
                <tr>
                    <td><strong>Payment Date:</strong></td>
                    <td class="text-right">{{ $payment->created_at->format('F d, Y H:i') }}</td>
                </tr>
            </table>
        </div>

        <div class="payment-summary">
            <h3>Order Summary</h3>
            <table>
                <tr>
                    <td><strong>Order Total:</strong></td>
                    <td class="text-right">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td><strong>Amount Paid:</strong></td>
                    <td class="text-right">Rp {{ number_format($order->getAmountPaidAttribute(), 0, ',', '.') }}</td>
                </tr>
                <tr style="border-top: 2px solid #10B981;">
                    <td><strong>Remaining Balance:</strong></td>
                    <td class="text-right"><strong>Rp {{ number_format($order->getRemainingBalanceAttribute(), 0, ',', '.') }}</strong></td>
                </tr>
            </table>
        </div>
        
        @if($order->getRemainingBalanceAttribute() > 0)
        <div class="warning-box">
            <strong>⚠️ Remaining Payment</strong>
            <p style="margin: 10px 0 0 0;">
                You've paid a 50% down payment. The remaining balance of 
                <strong>Rp {{ number_format($order->getRemainingBalanceAttribute(), 0, ',', '.') }}</strong> 
                will need to be paid before we can ship your order.
            </p>
            <p style="margin: 10px 0 0 0;">
                You'll be able to complete the final payment from your order page once your design has been approved and production is complete.
            </p>
        </div>
        @else
        <div class="info-box">
            <strong>✓ Payment Complete!</strong>
            <p style="margin: 10px 0 0 0;">
                Your order is fully paid. We'll begin processing your order and you'll receive updates via email as your order progresses.
            </p>
        </div>
        @endif
        
        <div class="info-box">
            <strong>📦 What's next?</strong>
            <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                @if($order->order_status === 'Pending Payment')
                <li>Your order status will be updated to "Processing"</li>
                @endif
                <li>Our design team will work on your invitation design</li>
                <li>You'll receive an email when your design proof is ready for review</li>
                <li>Once approved, we'll proceed with production</li>
            </ul>
        </div>
        
        <center>
            <a href="{{ config('app.frontend_url') }}/orders/{{ $order->id }}" class="button">
                View Order Status
            </a>
        </center>
            
            <p>Thank you for your payment! If you have any questions, please contact us.</p>
            
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
