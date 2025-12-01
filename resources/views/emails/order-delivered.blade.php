<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Delivered</title>
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
        .delivery-info {
            background-color: white;
            padding: 25px;
            border-left: 4px solid hsl(35, 45%, 58%);
            margin: 25px 0;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        .delivery-info h3 {
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
            <h1>🎉 Order Delivered!</h1>
            <p>Your wedding invitations have arrived</p>
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
