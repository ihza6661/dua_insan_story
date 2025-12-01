<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update</title>
    <style>
        body {font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;}
        .header {background-color: #4F46E5; color: white; padding: 30px 20px; text-align: center; border-radius: 5px 5px 0 0;}
        .header h1 {margin: 0; font-size: 28px;}
        .content {background-color: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 5px 5px;}
        .status-update {background-color: white; padding: 20px; border-left: 4px solid #4F46E5; margin: 20px 0;}
        .status-update h3 {margin-top: 0; color: #4F46E5;}
        .button {display: inline-block; background-color: #4F46E5; color: white; padding: 14px 35px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold;}
        .info-box {background-color: #E0F2FE; border-left: 4px solid #0EA5E9; padding: 15px; margin: 20px 0;}
        .footer {text-align: center; margin-top: 30px; font-size: 12px; color: #777;}
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 Order Status Update</h1>
        <p style="margin: 10px 0 0 0;">Your order {{ $order->order_number }}</p>
    </div>
    
    <div class="content">
        <p>Hi {{ $customer->full_name }},</p>
        
        <p>Your order status has been updated!</p>
        
        <div class="status-update">
            <h3>Status Change</h3>
            <p><strong>Previous Status:</strong> {{ $oldStatus }}</p>
            <p><strong>New Status:</strong> <span style="color: #10B981; font-weight: bold;">{{ $newStatus }}</span></p>
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
        <div class="info-box" style="background-color: #FEE2E2; border-color: #EF4444;">
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
        
        <p>Best regards,<br>
        <strong>Dua Insan Story Team</strong></p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} Dua Insan Story. All rights reserved.</p>
    </div>
</body>
</html>
