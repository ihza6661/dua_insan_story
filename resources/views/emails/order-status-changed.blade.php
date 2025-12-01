<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update</title>
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
        .status-update {
            background-color: white;
            padding: 25px;
            border-left: 4px solid hsl(35, 45%, 58%);
            margin: 25px 0;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        .status-update h3 {
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
            background-color: hsl(40, 40%, 96%);
            border-left: 4px solid hsl(35, 45%, 58%);
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .warning-box {
            background-color: hsl(0, 70%, 95%);
            border-left: 4px solid hsl(0, 70%, 52%);
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
            <h1>📦 Order Status Update</h1>
            <p>Your order {{ $order->order_number }}</p>
        </div>
        
        <div class="content">
            <p>Hi {{ $customer->full_name }},</p>
            
            <p>Your order status has been updated!</p>
            
            <div class="status-update">
                <h3>Status Change</h3>
                <p><strong>Previous Status:</strong> {{ $oldStatus }}</p>
                <p><strong>New Status:</strong> <span style="color: hsl(35, 45%, 58%); font-weight: 600;">{{ $newStatus }}</span></p>
                <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
            </div>
            
            @if($newStatus === 'Processing')
            <div class="info-box">
                <strong>🎨 What's happening now?</strong>
                <p style="margin: 10px 0 0 0;">
                    Your order is being processed. Our design team will start working on your wedding invitation design shortly.
                </p>
            </div>
            @elseif($newStatus === 'Design Approval')
            <div class="info-box">
                <strong>👀 Action Required!</strong>
                <p style="margin: 10px 0 0 0;">
                    Your design proof is ready for your review. Please check your email for the design proof notification or visit your order page to approve or request changes.
                </p>
            </div>
            @elseif($newStatus === 'In Production')
            <div class="info-box">
                <strong>🏭 In Production</strong>
                <p style="margin: 10px 0 0 0;">
                    Your design has been approved and we're now producing your wedding invitations. We'll notify you once your order has been shipped.
                </p>
            </div>
            @elseif($newStatus === 'Cancelled')
            <div class="warning-box">
                <strong>❌ Order Cancelled</strong>
                <p style="margin: 10px 0 0 0;">
                    Your order has been cancelled. If you have any questions or if this was done in error, please contact us immediately.
                </p>
            </div>
            @else
            <div class="info-box">
                <strong>📬 Updates</strong>
                <p style="margin: 10px 0 0 0;">
                    We'll keep you updated as your order progresses. You can always check the current status on your order page.
                </p>
            </div>
            @endif
            
            <center>
                <a href="{{ config('app.frontend_url') }}/orders/{{ $order->id }}" class="button">
                    View Order Details
                </a>
            </center>
            
            <p>If you have any questions, feel free to contact us.</p>
            
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
