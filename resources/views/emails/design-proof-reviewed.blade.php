<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design Proof Status Update</title>
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
            padding: 40px 20px;
            text-align: center;
        }
        .header.approved {
            background: linear-gradient(135deg, hsl(35, 45%, 58%) 0%, hsl(35, 50%, 52%) 100%);
            color: hsl(20, 25%, 22%);
        }
        .header.revision {
            background: linear-gradient(135deg, hsl(38, 92%, 50%) 0%, hsl(38, 92%, 45%) 100%);
            color: hsl(20, 25%, 22%);
        }
        .header.rejected {
            background: linear-gradient(135deg, hsl(0, 70%, 52%) 0%, hsl(0, 70%, 48%) 100%);
            color: white;
        }
        .brand-name {
            font-style: italic;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 2px;
            margin-bottom: 15px;
        }
        .header.approved .brand-name,
        .header.revision .brand-name {
            color: hsl(40, 30%, 97%);
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
        .feedback-box {
            background-color: hsl(45, 85%, 92%);
            border-left: 4px solid hsl(38, 92%, 50%);
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .feedback-box h3 {
            margin-top: 0;
            color: hsl(20, 25%, 22%);
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .success-box {
            background-color: hsl(120, 60%, 95%);
            border-left: 4px solid hsl(35, 45%, 58%);
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .success-box h3 {
            margin-top: 0;
            color: hsl(20, 25%, 22%);
            font-weight: 500;
            letter-spacing: 0.5px;
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
    
    <div class="email-wrapper">
        <div class="header {{ $headerClass }}">
            <div class="brand-name">DuaInsan.Story</div>
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
