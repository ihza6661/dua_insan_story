<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancellation Request Approved</title>
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
        .success-box {
            background-color: hsl(120, 50%, 96%);
            border-left: 4px solid hsl(120, 50%, 50%);
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .info-box {
            background-color: hsl(40, 40%, 96%);
            border-left: 4px solid hsl(35, 45%, 58%);
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
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
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <div class="brand-name">DuaInsan.Story</div>
            <h1>✓ Cancellation Approved</h1>
            <p>Your cancellation request has been approved</p>
        </div>
        
        <div class="content">
            <p>Hi {{ $order->customer->full_name }},</p>
            
            <p>We have reviewed and approved your cancellation request for order <strong>{{ $order->order_number }}</strong>.</p>
        
            <div class="success-box">
                <strong>✓ Request Approved</strong>
                <p style="margin: 10px 0 0 0;">Your cancellation request has been successfully processed. Your order has been cancelled.</p>
            </div>

            <div class="order-summary">
                <h3>Order Information</h3>
                <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
                <p><strong>Order Date:</strong> {{ $order->created_at->format('F d, Y H:i') }}</p>
                <p><strong>Previous Status:</strong> {{ $cancellationRequest->order_status_before }}</p>
                <p><strong>Current Status:</strong> Cancelled</p>
                <p><strong>Total Amount:</strong> Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
            </div>

            <div class="order-summary">
                <h3>Cancellation Details</h3>
                <p><strong>Request ID:</strong> {{ $cancellationRequest->id }}</p>
                <p><strong>Approved Date:</strong> {{ $cancellationRequest->reviewed_at->format('F d, Y H:i') }}</p>
                <p><strong>Approved By:</strong> {{ $cancellationRequest->reviewer->full_name ?? 'Admin' }}</p>
                @if($cancellationRequest->admin_notes)
                <p><strong>Admin Notes:</strong></p>
                <p style="margin-top: 10px; padding: 15px; background-color: hsl(40, 30%, 97%); border-radius: 4px;">
                    {{ $cancellationRequest->admin_notes }}
                </p>
                @endif
            </div>

            @if($cancellationRequest->refund_amount > 0)
            <div class="info-box">
                <strong>💰 Refund Information</strong>
                <p style="margin: 10px 0 0 0;"><strong>Refund Amount:</strong> Rp {{ number_format($cancellationRequest->refund_amount, 0, ',', '.') }}</p>
                <p style="margin: 10px 0 0 0;"><strong>Refund Method:</strong> {{ ucfirst($cancellationRequest->refund_method ?? 'Original Payment Method') }}</p>
                <p style="margin: 10px 0 0 0;">Your refund will be processed within 5-7 business days. You will receive the refund through your original payment method.</p>
            </div>
            @endif

            <center>
                <a href="{{ config('app.frontend_url') }}/orders/{{ $order->id }}" class="button">
                    View Order Details
                </a>
            </center>
            
            <p>We're sorry to see this order cancelled. If you have any questions or concerns, please don't hesitate to contact us. We'd love to serve you again in the future!</p>
            
            <p style="margin-top: 30px;">Best regards,<br>
            <strong>Dua Insan Story Team</strong></p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Dua Insan Story. All rights reserved.</p>
            <p style="margin-top: 8px;">You're receiving this email regarding your order cancellation request.</p>
            <div class="footer-brand">DuaInsan.Story</div>
        </div>
    </div>
</body>
</html>
