<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Delivered</title>
    <style>
        body {font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;}
        .header {background-color: #10B981; color: white; padding: 30px 20px; text-align: center; border-radius: 5px 5px 0 0;}
        .header h1 {margin: 0; font-size: 28px;}
        .content {background-color: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 5px 5px;}
        .delivery-info {background-color: white; padding: 20px; border-left: 4px solid #10B981; margin: 20px 0;}
        .delivery-info h3 {margin-top: 0; color: #10B981;}
        .button {display: inline-block; background-color: #10B981; color: white; padding: 14px 35px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold;}
        .info-box {background-color: #DCFCE7; border-left: 4px solid #10B981; padding: 15px; margin: 20px 0;}
        .footer {text-align: center; margin-top: 30px; font-size: 12px; color: #777;}
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 Order Delivered!</h1>
        <p style="margin: 10px 0 0 0;">Your wedding invitations have arrived</p>
    </div>
    
    <div class="content">
        <p>Hi {{ $customer->full_name }},</p>
        
        <p>Wonderful news! Your wedding invitation order has been successfully delivered!</p>
        
        <div class="delivery-info">
            <h3>Order Information</h3>
            <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
            <p><strong>Delivery Date:</strong> {{ now()->format('F d, Y') }}</p>
        </div>
        
        <div class="info-box">
            <strong>📋 Please Check Your Order</strong>
            <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                <li>Inspect the package for any damage</li>
                <li>Verify the quantity and quality of your invitations</li>
                <li>Check that all details are printed correctly</li>
                <li>Contact us immediately if there are any issues</li>
            </ul>
        </div>
        
        <p>We hope you love your wedding invitations! Thank you for choosing Dua Insan Story to be part of your special day.</p>
        
        <p>If everything looks perfect, please consider leaving us a review or recommending us to your friends!</p>
        
        <center>
            <a href="{{ config('app.frontend_url') }}/orders/{{ $order->id }}" class="button">
                View Order Details
            </a>
        </center>
        
        <p><strong>Need to contact us?</strong><br>
        If you have any questions or concerns about your order, please don't hesitate to reach out to us.</p>
        
        <p>Wishing you a beautiful wedding celebration!</p>
        
        <p>Best regards,<br>
        <strong>Dua Insan Story Team</strong></p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} Dua Insan Story. All rights reserved.</p>
    </div>
</body>
</html>
