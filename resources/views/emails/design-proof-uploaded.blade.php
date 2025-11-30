<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design Proof Ready for Review</title>
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
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
        }
        .button {
            display: inline-block;
            background-color: #4F46E5;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .details {
            background-color: white;
            padding: 15px;
            border-left: 4px solid #4F46E5;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header">
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
        
        <p>Best regards,<br>
        <strong>Dua Insan Story Team</strong></p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} Dua Insan Story. All rights reserved.</p>
    </div>
</body>
</html>
