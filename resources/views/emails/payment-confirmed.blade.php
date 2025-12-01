<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmed</title>
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
            background-color: #10B981;
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
        .payment-summary {
            background-color: white;
            padding: 20px;
            border-left: 4px solid #10B981;
            margin: 20px 0;
        }
        .payment-summary h3 {
            margin-top: 0;
            color: #10B981;
        }
        .button {
            display: inline-block;
            background-color: #10B981;
            color: white;
            padding: 14px 35px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .info-box {
            background-color: #DCFCE7;
            border-left: 4px solid #10B981;
            padding: 15px;
            margin: 20px 0;
        }
        .warning-box {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
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
        <h1>💳 Payment Confirmed!</h1>
        <p style="margin: 10px 0 0 0;">Your payment has been received</p>
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
        
        <p>Best regards,<br>
        <strong>Dua Insan Story Team</strong></p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} Dua Insan Story. All rights reserved.</p>
    </div>
</body>
</html>
