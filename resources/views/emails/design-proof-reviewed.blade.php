<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design Proof Status Update</title>
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
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .header.approved { background-color: #10B981; }
        .header.revision { background-color: #F59E0B; }
        .header.rejected { background-color: #EF4444; }
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
        .feedback-box {
            background-color: #FEF3C7;
            padding: 15px;
            border-left: 4px solid #F59E0B;
            margin: 20px 0;
        }
        .success-box {
            background-color: #D1FAE5;
            padding: 15px;
            border-left: 4px solid #10B981;
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
    @php
        $headerClass = match($status) {
            'approved' => 'approved',
            'revision_requested' => 'revision',
            'rejected' => 'rejected',
            default => 'revision'
        };
        
        $headerTitle = match($status) {
            'approved' => 'Design Proof Approved!',
            'revision_requested' => 'Design Revision Requested',
            'rejected' => 'Design Proof Rejected',
            default => 'Design Proof Update'
        };
    @endphp
    
    <div class="header {{ $headerClass }}">
        <h1>{{ $headerTitle }}</h1>
    </div>
    
    <div class="content">
        <p>Hello {{ $order->customer->full_name }},</p>
        
        @if($status === 'approved')
            <div class="success-box">
                <h3>Great News!</h3>
                <p>Your design proof for <strong>{{ $product->name }}</strong> has been approved and we're ready to proceed with production!</p>
            </div>
            
            <div class="details">
                <h3>Order Details:</h3>
                <p><strong>Order #:</strong> {{ $order->order_number }}</p>
                <p><strong>Product:</strong> {{ $product->name }}</p>
                @if($designProof->orderItem->variant)
                    <p><strong>Variant:</strong> {{ $designProof->orderItem->variant->name }}</p>
                @endif
                <p><strong>Version:</strong> {{ $designProof->version }}</p>
            </div>
            
            <p><strong>What's next?</strong></p>
            <ul>
                <li>Your order will now move to production</li>
                <li>We'll keep you updated on the progress</li>
                <li>Expected completion will be communicated shortly</li>
            </ul>
            
        @elseif($status === 'revision_requested')
            <p>You have requested revisions for your design proof for <strong>{{ $product->name }}</strong>.</p>
            
            <div class="details">
                <h3>Order Details:</h3>
                <p><strong>Order #:</strong> {{ $order->order_number }}</p>
                <p><strong>Product:</strong> {{ $product->name }}</p>
                @if($designProof->orderItem->variant)
                    <p><strong>Variant:</strong> {{ $designProof->orderItem->variant->name }}</p>
                @endif
                <p><strong>Version:</strong> {{ $designProof->version }}</p>
            </div>
            
            @if($designProof->customer_feedback)
                <div class="feedback-box">
                    <h3>Your Feedback:</h3>
                    <p>{{ $designProof->customer_feedback }}</p>
                </div>
            @endif
            
            <p><strong>What's next?</strong></p>
            <ul>
                <li>Our design team will review your feedback</li>
                <li>We'll make the requested changes</li>
                <li>A new version will be uploaded for your review</li>
                <li>You'll receive a notification when it's ready</li>
            </ul>
            
        @else
            <p>Your design proof for <strong>{{ $product->name }}</strong> has been rejected.</p>
            
            <div class="details">
                <h3>Order Details:</h3>
                <p><strong>Order #:</strong> {{ $order->order_number }}</p>
                <p><strong>Product:</strong> {{ $product->name }}</p>
                @if($designProof->orderItem->variant)
                    <p><strong>Variant:</strong> {{ $designProof->orderItem->variant->name }}</p>
                @endif
                <p><strong>Version:</strong> {{ $designProof->version }}</p>
            </div>
            
            @if($designProof->customer_feedback)
                <div class="feedback-box">
                    <h3>Your Feedback:</h3>
                    <p>{{ $designProof->customer_feedback }}</p>
                </div>
            @endif
            
            <p>Our team will contact you to discuss the next steps for your order.</p>
        @endif
        
        <center>
            <a href="{{ config('app.frontend_url') }}/orders/{{ $order->id }}/design-proofs/{{ $designProof->id }}" class="button">
                View Order Details
            </a>
        </center>
        
        <p>If you have any questions, please don't hesitate to contact us.</p>
        
        <p>Best regards,<br>
        <strong>Dua Insan Story Team</strong></p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} Dua Insan Story. All rights reserved.</p>
    </div>
</body>
</html>
