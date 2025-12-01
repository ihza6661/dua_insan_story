<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design Proof Ready for Review</title>
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
        .content {
            background-color: hsl(40, 30%, 97%);
            padding: 35px 30px;
        }
        .details {
            background-color: white;
            padding: 25px;
            border-left: 4px solid hsl(35, 45%, 58%);
            margin: 25px 0;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        .details h3 {
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
            <h1>Your Design Proof is Ready!</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $order->customer->full_name }},</p>
            
            <p>Great news! Your design proof for <strong>{{ $product->name }}</strong> is now ready for your review.</p>
            
            <div class="details">
                <h3>Order Details:</h3>
                <p><strong>Order #:</strong> {{ $order->order_number }}</p>
                <p><strong>Product:</strong> {{ $product->name }}</p>
                @if($designProof->orderItem->variant)
                    <p><strong>Variant:</strong> {{ $designProof->orderItem->variant->name }}</p>
                @endif
                <p><strong>Version:</strong> {{ $designProof->version }}</p>
                <p><strong>File Name:</strong> {{ $designProof->file_name }}</p>
            </div>
            
            <p>Please review the design proof carefully and let us know if you approve it or need any revisions.</p>
            
            <center>
                <a href="{{ config('app.frontend_url') }}/orders/{{ $order->id }}/design-proofs/{{ $designProof->id }}" class="button">
                    View Design Proof
                </a>
            </center>
            
            <p><strong>What's next?</strong></p>
            <ul>
                <li>Review the design proof thoroughly</li>
                <li>Approve if everything looks good</li>
                <li>Request revisions if changes are needed</li>
                <li>We'll proceed with production once approved</li>
            </ul>
            
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
